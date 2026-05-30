<?php
/**
 * Email suppression ops tool.
 *
 * Reads/writes unsubscribe_emails. Use this to mark an address as
 * suppressed (abuse complaint, hard bounce, manual request) so that
 * Mail::sendmail() will short-circuit any further messages to it.
 *
 * Works either as an admin-gated web page (hit it as an admin user) or
 * as a CLI script for ops automation.
 *
 * Web:
 *   /admin_actions/suppress_email.php                          → list
 *   /admin_actions/suppress_email.php?action=add               → form to add
 *   POST ?action=add email=... source=abuse scope=all reason=  → add/update
 *   POST ?action=remove email=...                              → revoke
 *   /admin_actions/suppress_email.php?action=export            → CSV
 *
 * CLI (must set DOCUMENT_ROOT):
 *   php suppress_email.php list [--source=abuse]
 *   php suppress_email.php add --email=foo@bar.com --source=abuse --scope=all --reason="SES complaint 2026-04-06"
 *   php suppress_email.php remove --email=foo@bar.com
 *   php suppress_email.php export > suppressions.csv
 */

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
}

// Prime $_SERVER with web-style values when invoked from CLI so that
// site-controller.php's session_tracking() insert doesn't blow up on
// overlong SCRIPT_NAME or missing HTTP_HOST / REMOTE_ADDR. Skip the
// startup session_tracking() row for ops scripts.
if (php_sapi_name() === 'cli') {
    $_SERVER['SCRIPT_NAME']  = '/admin_actions/suppress_email.php';
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

$is_cli = (php_sapi_name() === 'cli');

// ---------------------------------------------------------------------------
// Parse inputs from either CLI argv or HTTP request
// ---------------------------------------------------------------------------
$action = null;
$args = [
    'email'  => null,
    'source' => null,
    'scope'  => null,
    'reason' => null,
];

if ($is_cli) {
    $action = $argv[1] ?? 'list';
    for ($i = 2; $i < count($argv); $i++) {
        if (preg_match('/^--([a-z_]+)=(.*)$/', $argv[$i], $m)) {
            $args[$m[1]] = $m[2];
        }
    }
} else {
    // Admin gate for web access — never expose this to unauthenticated users.
    if (!isset($account) || !$account->isadmin()) {
        http_response_code(403);
        die("Access denied. Admin privileges required.\n");
    }
    $action = $_REQUEST['action'] ?? 'list';
    foreach ($args as $k => $_) {
        if (isset($_REQUEST[$k])) $args[$k] = $_REQUEST[$k];
    }
}

// ---------------------------------------------------------------------------
// Validation helpers
// ---------------------------------------------------------------------------
$VALID_SOURCES = ['user', 'abuse', 'bounce', 'admin', 'manual'];
$VALID_SCOPES  = ['all', 'marketing_only'];

function normalize_email($e) {
    return strtolower(trim((string)$e));
}

function die_with($msg, $is_cli, $code = 1) {
    if ($is_cli) {
        fwrite(STDERR, $msg . "\n");
        exit($code);
    }
    http_response_code(400);
    echo htmlspecialchars($msg) . "\n";
    exit;
}

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------
switch ($action) {

    // -----------------------------------------------------------------------
    case 'add':
    case 'upsert':
        $email  = normalize_email($args['email']);
        $source = $args['source'] ?: 'admin';
        $scope  = $args['scope']  ?: ($source === 'user' ? 'marketing_only' : 'all');
        $reason = $args['reason'] ?: null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die_with("Invalid --email", $is_cli);
        }
        if (!in_array($source, $VALID_SOURCES, true)) {
            die_with("Invalid --source (must be one of: " . implode(',', $VALID_SOURCES) . ")", $is_cli);
        }
        if (!in_array($scope, $VALID_SCOPES, true)) {
            die_with("Invalid --scope (must be one of: " . implode(',', $VALID_SCOPES) . ")", $is_cli);
        }

        // Look up user_id for audit link (nullable — unknown addresses are fine).
        $user_row = $database->getrow(
            "SELECT user_id FROM bg_users WHERE LOWER(email) = :email LIMIT 1",
            ['email' => $email]
        );
        $user_id = $user_row ? $user_row['user_id'] : null;

        $sql = "INSERT INTO unsubscribe_emails (email, user_id, source, scope, reason, status, unsubscribed_at)
                VALUES (:email, :user_id, :source, :scope, :reason, 'active', NOW())
                ON DUPLICATE KEY UPDATE
                    user_id  = VALUES(user_id),
                    source   = VALUES(source),
                    scope    = VALUES(scope),
                    reason   = VALUES(reason),
                    status   = 'active',
                    modify_dt = NOW()";
        $database->query($sql, [
            'email'   => $email,
            'user_id' => $user_id,
            'source'  => $source,
            'scope'   => $scope,
            'reason'  => $reason,
        ]);

        session_tracking('suppression_admin', [
            'verb'    => 'add',
            'email'   => $email,
            'user_id' => $user_id,
            'source'  => $source,
            'scope'   => $scope,
            'reason'  => $reason,
        ]);

        $msg = "Suppressed $email (source=$source, scope=$scope"
             . ($user_id ? ", user_id=$user_id" : ", no matching user")
             . ")";
        if ($is_cli) echo $msg . "\n"; else echo "<pre>" . htmlspecialchars($msg) . "</pre>";
        break;

    // -----------------------------------------------------------------------
    case 'remove':
    case 'revoke':
        $email = normalize_email($args['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die_with("Invalid --email", $is_cli);
        }

        // Soft revoke — keep the row for audit history. isEmailSuppressed()
        // filters on status='active' so a revoked row becomes inert.
        $stmt = $database->query(
            "UPDATE unsubscribe_emails SET status = 'revoked', modify_dt = NOW()
              WHERE email = :email AND status = 'active'",
            ['email' => $email]
        );
        $affected = $stmt ? $stmt->rowCount() : 0;

        session_tracking('suppression_admin', [
            'verb'  => 'remove',
            'email' => $email,
            'rows'  => $affected,
        ]);

        $msg = $affected > 0
            ? "Revoked suppression for $email"
            : "No active suppression found for $email";
        if ($is_cli) echo $msg . "\n"; else echo "<pre>" . htmlspecialchars($msg) . "</pre>";
        break;

    // -----------------------------------------------------------------------
    case 'list':
        $where_parts = ["status = 'active'"];
        $params = [];
        if (!empty($args['source'])) {
            $where_parts[] = "source = :source";
            $params['source'] = $args['source'];
        }
        $where = implode(' AND ', $where_parts);

        $rows = $database->getrows(
            "SELECT id, email, user_id, source, scope, reason, status, unsubscribed_at, modify_dt
               FROM unsubscribe_emails
              WHERE $where
              ORDER BY unsubscribed_at DESC
              LIMIT 500",
            $params
        );

        if ($is_cli) {
            printf("%-6s %-40s %-10s %-8s %-16s %-20s %s\n",
                   'ID', 'EMAIL', 'SOURCE', 'SCOPE', 'USER_ID', 'CREATED', 'REASON');
            foreach ($rows as $r) {
                printf("%-6s %-40s %-10s %-8s %-16s %-20s %s\n",
                       $r['id'], $r['email'], $r['source'], $r['scope'],
                       $r['user_id'] ?? '-', $r['unsubscribed_at'], $r['reason'] ?? '');
            }
            echo count($rows) . " row(s)\n";
        } else {
            echo "<pre>";
            printf("%-6s %-40s %-10s %-15s %-20s %s\n",
                   'ID', 'EMAIL', 'SOURCE', 'SCOPE', 'CREATED', 'REASON');
            foreach ($rows as $r) {
                printf("%-6s %-40s %-10s %-15s %-20s %s\n",
                       $r['id'],
                       htmlspecialchars($r['email']),
                       htmlspecialchars($r['source']),
                       htmlspecialchars($r['scope']),
                       $r['unsubscribed_at'],
                       htmlspecialchars($r['reason'] ?? ''));
            }
            echo "\n" . count($rows) . " active suppression row(s)</pre>";
        }
        break;

    // -----------------------------------------------------------------------
    case 'export':
        $rows = $database->getrows(
            "SELECT id, email, user_id, source, scope, reason, status, unsubscribed_at, modify_dt
               FROM unsubscribe_emails
              ORDER BY unsubscribed_at DESC"
        );

        if (!$is_cli) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="suppressions_' . date('Ymd_His') . '.csv"');
        }

        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','email','user_id','source','scope','reason','status','unsubscribed_at','modify_dt']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['email'], $r['user_id'], $r['source'], $r['scope'],
                $r['reason'], $r['status'], $r['unsubscribed_at'], $r['modify_dt'],
            ]);
        }
        fclose($out);
        break;

    // -----------------------------------------------------------------------
    default:
        die_with("Unknown action: $action. Use one of: add, remove, list, export", $is_cli);
}
