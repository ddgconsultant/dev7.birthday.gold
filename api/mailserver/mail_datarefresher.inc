<?php echo <<<'eod'
#!/usr/bin/php
<?php

### ===============================================================================================================
switch ($recipient) {
    // =======================================================
    case 'refresh_users':
    case 'populate_users':
        switch ($recipient) {
            case 'refresh_users':
                echo 'start refreshing users - ' . date('r') . "\r\n";
                $counter['inserted'] = $counter['updated'] = $counter['skipped'] = $counter['total'] = 0;
                $records = file_get_contents('https://api.birthday.gold/mailserver/populate_maildata.php?type=refresh_users');
                break;
            case 'populate_users':
                echo 'start populating users - ' . date('r') . "\r\n";
                $counter['inserted'] = $counter['updated'] = $counter['skipped'] = $counter['total'] = 0;
                $records = file_get_contents('https://api.birthday.gold/mailserver/populate_maildata.php?type=populate_users');
                break;
        }
        print_r($records);
        echo "\r\n";
        $records = json_decode($records, true);
        if ($records) {
            echo 'processing records users' . "\r\n";
            foreach ($records as $record) {
                $counter['total']++;
                // Check if user_id exists in bg_mail_users
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bg_mail_users WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $record['user_id']]);
                $exists = $stmt->fetchColumn();

                if ($exists) {
                    $counter['updated']++;
                    // Update existing record
                    $stmt = $pdo->prepare("UPDATE bg_mail_users SET feature_email = :feature_email, status = :status, create_dt = :create_dt WHERE user_id = :user_id");
                } else {
                    $counter['inserted']++;
                    // Insert new record
                    $stmt = $pdo->prepare("INSERT INTO bg_mail_users (feature_email, status, create_dt, user_id) VALUES (:feature_email, :status, :create_dt, :user_id)");
                }

                $stmt->execute([
                    ':feature_email' => $record['feature_email'],
                    ':status' => $record['status'] ?? 'active', // Default to 'active' if status is not set
                    ':create_dt' => $record['create_dt'] ?? date('Y-m-d H:i:s'), // Default to current timestamp if create_dt is not set
                    ':user_id' => $record['user_id'],
                ]);
            }
        }
        echo 'end process users: ' . print_r($counter, 1) . "\r\n";
        return;
        break;
    
    // =======================================================
    case 'refresh_companies':
    case 'populate_companies':
        switch ($recipient) {
            case 'refresh_companies':
                echo 'Start refreshing companies - ' . date('r') . "\r\n";
                $counter['inserted'] = $counter['updated'] = $counter['skipped'] = $counter['total'] = 0;
                $records = file_get_contents('https://api.birthday.gold/mailserver/populate_maildata.php?type=refresh_companies');
                break;
            case 'populate_companies':
                echo 'start populating companies - ' . date('r') . "\r\n";
                $counter['inserted'] = $counter['updated'] = $counter['skipped'] = $counter['total'] = 0;
                $records = file_get_contents('https://api.birthday.gold/mailserver/populate_maildata.php?type=populate_companies');
                break;
        }
        print_r($records);
        echo "\r\n";
        $records = json_decode($records, true);
        if ($records) {
            echo 'Processing records companies' . "\r\n";
            foreach ($records as $record) {
                $counter['total']++;
                // Check if company_id exists in bg_mail_companies
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bg_mail_companies WHERE company_id = :company_id and email_domain = :email_domain");
                $stmt->execute([':company_id' => $record['company_id'], ':email_domain' => $record['email_domain']]);
                $exists = $stmt->fetchColumn();

                if ($exists) {
                    if ($record['status'] == 'inactive') {
                        $counter['updated']++;
                        // Update existing record
                        $stmt = $pdo->prepare("UPDATE bg_mail_companies SET email_domain = :email_domain, status = :status, create_dt = :create_dt WHERE company_id = :company_id");
                    } else {
                        $counter['skipped']++;
                        $stmt = false;
                    }
                } else {
                    $counter['inserted']++;
                    // Insert new record
                    $stmt = $pdo->prepare("INSERT INTO bg_mail_companies (email_domain, status, create_dt, company_id) VALUES (:email_domain, :status, :create_dt, :company_id)");
                }
                if ($stmt) {
                    $stmt->execute([
                        ':email_domain' => $record['email_domain'],
                        ':status' => $record['status'] ?? 'active', // Default to 'active' if status is not set
                        ':create_dt' => $record['create_dt'] ?? date('Y-m-d H:i:s'), // Default to current timestamp if create_dt is not set
                        ':company_id' => $record['company_id'],
                    ]);
                }
            }
        }
        echo 'End process companies: ' . print_r($counter, 1) . "\r\n";

        // --------------------------------------------
        // clearing zero-id messages
        $stmt = $pdo->prepare("UPDATE messages JOIN bg_mail_companies ON SUBSTRING_INDEX(messages.sender, '@', -1) = bg_mail_companies.email_domain
        SET messages.company_id = bg_mail_companies.company_id WHERE messages.company_id in (0,99)");
        $stmt->execute();
        $numRows = $stmt->rowCount();
        echo 'Updated domain - zero-id/99 messages: ' . $numRows . "\r\n";
        $stmt = $pdo->prepare("UPDATE messages JOIN bg_mail_companies ON messages.sender = bg_mail_companies.email_domain
        SET messages.company_id = bg_mail_companies.company_id WHERE messages.company_id in (0,99)");
        $stmt->execute();
        $numRows = $stmt->rowCount();
        echo 'Updated sender - zero-id/99 messages: ' . $numRows . "\r\n";
        echo '----- end ' . date('r') . "\r\n";

        return;
        break;
            }
eod;