<?php
/**
 * Extend unsubscribe_emails for real suppression enforcement.
 *
 * Background: the table was created ad-hoc (no schema file in core/dbschema/)
 * and unsubscribe.php writes to it, but nothing ever read from it. This
 * migration additively brings it up to the shape required by
 * Mail::isEmailSuppressed(). Safe to re-run.
 *
 * Usage: hit /admin_actions/add_email_suppression_columns.php as admin, or
 *        run via CLI with DOCUMENT_ROOT set.
 */

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
}

// Prime $_SERVER with web-style values when invoked from CLI so that
// site-controller.php's session_tracking() insert doesn't blow up on
// overlong SCRIPT_NAME or missing HTTP_HOST / REMOTE_ADDR. Also bypass
// the startup session_tracking() call via $nosessiontracking to avoid
// writing a spurious init row for a maintenance script.
if (php_sapi_name() === 'cli') {
    $_SERVER['SCRIPT_NAME']  = '/admin_actions/add_email_suppression_columns.php';
    $_SERVER['REQUEST_URI']  = $_SERVER['REQUEST_URI']  ?? $_SERVER['SCRIPT_NAME'];
    $_SERVER['HTTP_HOST']    = $_SERVER['HTTP_HOST']    ?? 'dev7.birthday.gold';
    $_SERVER['SERVER_PORT']  = $_SERVER['SERVER_PORT']  ?? '443';
    $_SERVER['QUERY_STRING'] = $_SERVER['QUERY_STRING'] ?? '';
    $_SERVER['REMOTE_ADDR']  = $_SERVER['REMOTE_ADDR']  ?? '127.0.0.1';
    $_SERVER['SERVER_ADDR']  = $_SERVER['SERVER_ADDR']  ?? '127.0.0.1';
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $nosessiontracking = true;
}

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin gate — same pattern as sibling migrations. Skip the check when
// running from CLI so devops can invoke it directly.
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli && (!isset($account) || !$account->isadmin())) {
    die("Access denied. Admin privileges required.\n");
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== unsubscribe_emails suppression migration ===\n\n";

// Create the table if it doesn't exist at all. In prod it does — this is
// purely so dev databases can run the same script.
try {
    $database->query("CREATE TABLE IF NOT EXISTS unsubscribe_emails (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        create_dt DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table unsubscribe_emails present.\n";
} catch (Exception $e) {
    die("ERROR creating base table: " . $e->getMessage() . "\n");
}

// Snapshot existing columns once so the loop below is one query, not N.
$existing = [];
try {
    $stmt = $database->query("SHOW COLUMNS FROM unsubscribe_emails");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing[$row['Field']] = $row;
    }
} catch (Exception $e) {
    die("ERROR reading schema: " . $e->getMessage() . "\n");
}

// Additive schema. Strings only so we avoid DEFAULT-mismatch drift across
// MySQL versions. Each row is (column_name => ALTER fragment).
$columns = [
    'user_id'   => "ADD COLUMN user_id BIGINT NULL COMMENT 'Audit link to bg_users when known'",
    'source'    => "ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT 'user|abuse|bounce|admin|manual'",
    'scope'     => "ADD COLUMN scope VARCHAR(20) NOT NULL DEFAULT 'marketing_only' COMMENT 'all|marketing_only'",
    'reason'    => "ADD COLUMN reason VARCHAR(255) NULL COMMENT 'Free-text note — e.g. SES complaint date'",
    'status'    => "ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active|pending|revoked'",
    'modify_dt' => "ADD COLUMN modify_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
];

foreach ($columns as $name => $fragment) {
    if (isset($existing[$name])) {
        echo "  [skip]  column $name already exists\n";
        continue;
    }
    try {
        $database->query("ALTER TABLE unsubscribe_emails $fragment");
        echo "  [add]   $name\n";
    } catch (Exception $e) {
        echo "  [error] $name: " . $e->getMessage() . "\n";
    }
}

// Backfill the default source/scope/status on any legacy rows that were
// inserted before these columns existed (the ALTER gave them the defaults
// on add, but belt-and-braces for dev DBs that had older rows).
try {
    $database->query("UPDATE unsubscribe_emails
                         SET source = IFNULL(NULLIF(source, ''), 'user'),
                             scope  = IFNULL(NULLIF(scope,  ''), 'marketing_only'),
                             status = IFNULL(NULLIF(status, ''), 'active')
                       WHERE source IS NULL OR source = ''
                          OR scope  IS NULL OR scope  = ''
                          OR status IS NULL OR status = ''");
    echo "  [ok]    backfilled defaults on any pre-existing rows\n";
} catch (Exception $e) {
    echo "  [warn]  backfill failed: " . $e->getMessage() . "\n";
}

// Normalize existing emails to lowercase so the UNIQUE index can be added
// without collisions. We also strip whitespace.
try {
    $database->query("UPDATE unsubscribe_emails SET email = LOWER(TRIM(email)) WHERE email != LOWER(TRIM(email))");
    echo "  [ok]    normalized existing emails to lowercase\n";
} catch (Exception $e) {
    echo "  [warn]  lowercase normalization failed: " . $e->getMessage() . "\n";
}

// Dedupe on (lowercased) email, keeping the newest row per address, before
// the UNIQUE index goes on. Losing an older row is fine here — the newest
// suppression record is authoritative.
try {
    $dupes = $database->getrows("SELECT email, COUNT(*) c, MAX(id) keep_id
                                   FROM unsubscribe_emails
                                  GROUP BY email HAVING c > 1");
    foreach ($dupes as $d) {
        $database->query("DELETE FROM unsubscribe_emails WHERE email = :email AND id <> :keep_id",
            ['email' => $d['email'], 'keep_id' => $d['keep_id']]);
        echo "  [ok]    deduped " . (int)($d['c'] - 1) . " older rows for " . $d['email'] . "\n";
    }
} catch (Exception $e) {
    echo "  [warn]  dedupe failed: " . $e->getMessage() . "\n";
}

// Add a UNIQUE index on email. If one already exists under any name, skip.
try {
    $has_unique = false;
    $idx = $database->query("SHOW INDEX FROM unsubscribe_emails");
    while ($row = $idx->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Column_name'] === 'email' && (int)$row['Non_unique'] === 0) {
            $has_unique = true;
            break;
        }
    }
    if ($has_unique) {
        echo "  [skip]  UNIQUE index on email already present\n";
    } else {
        $database->query("ALTER TABLE unsubscribe_emails ADD UNIQUE KEY uq_unsubscribe_email (email)");
        echo "  [add]   UNIQUE KEY uq_unsubscribe_email (email)\n";
    }
} catch (Exception $e) {
    echo "  [error] unique index: " . $e->getMessage() . "\n";
}

// Helpful secondary index for the status+source lookup in the suppress_email
// CLI's list views. Non-critical, skip on failure.
try {
    $has_status_idx = false;
    $idx = $database->query("SHOW INDEX FROM unsubscribe_emails WHERE Key_name = 'idx_unsub_status_source'");
    if ($idx->fetch(PDO::FETCH_ASSOC)) $has_status_idx = true;
    if (!$has_status_idx) {
        $database->query("ALTER TABLE unsubscribe_emails ADD KEY idx_unsub_status_source (status, source)");
        echo "  [add]   KEY idx_unsub_status_source (status, source)\n";
    } else {
        echo "  [skip]  idx_unsub_status_source already present\n";
    }
} catch (Exception $e) {
    echo "  [warn]  status index: " . $e->getMessage() . "\n";
}

echo "\nDone. Current schema:\n";
try {
    $stmt = $database->query("SHOW CREATE TABLE unsubscribe_emails");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $row['Create Table'] . "\n";
} catch (Exception $e) {
    echo "Could not dump schema: " . $e->getMessage() . "\n";
}
