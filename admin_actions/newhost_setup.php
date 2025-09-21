<?php
$addClasses[] = 'api';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
require_once($dir['vendor'] . '/autoload.php');

use phpseclib3\Net\SSH2;


#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# HANDLE THE FORM POSTING ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {


    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $api_key = $_POST['api_key'];

    $auth_response = $api->authenticate_api_key($api_key);

    if ($auth_response['success']) {
        function reconnect($host, $username, $password)
        {
            $ssh = new SSH2($host);
            if (!$ssh->login($username, $password)) {
                echo "<div class='mt-4 alert alert-danger'>Login Failed</div>";
                return false;
            }
            // Increase timeout if necessary
            $ssh->setTimeout(120); // Set timeout to 120 seconds
            return $ssh;
        }

        $ssh = reconnect($host, $username, $password);
        if ($ssh) {

            // Turn off output buffering
            ini_set('output_buffering', 'Off');

            // Turn off zlib output compression
            ini_set('zlib.output_compression', 'Off');
            ob_start();
            ob_implicit_flush(true);
            ob_end_flush();

            echo '<section></section><div class="container">';
            flush(); // Send output to the browser
            flush(); // Send output to the browser

            $doactions = $_REQUEST['serveraction'];

            $listofcommands = [];

            foreach ($doactions as $action) {
                ///==========================================================================
                if ($action == 'deploy_www') {
                    $listofcommands[] = 'ls -ltr';
                    $listofcommands[] = 'date';
                    $listofcommands[] = 'cd';
                    $listofcommands[] = './deploy_www.sh';
                }
                ///==========================================================================
                if ($action == 'install_webserver') {
                    // INSTALL WEB NODE
                    $listofcommands[] = '[ -f ~/install_webserver.sh ] && rm ~/install_webserver.sh';
                    $listofcommands[] = '[ -f ~/install_state ] && rm ~/install_state';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_webserver.sh';
                    $listofcommands[] = 'chmod 700 install_webserver.sh';
                    // Execute this install script three times
                    for ($i = 0; $i < 3; $i++) {
                        $listofcommands[] = './install_webserver.sh';
                        $listofcommands[] = function () use ($host, $username, $password) {
                            sleep(120); // Wait for 2 minutes for the server to reboot
                            return reconnect($host, $username, $password);
                        };
                    }
                    // INSTALL MYSQL DB
                    $listofcommands[] = '[ -f ~/install_mysqldb.sh ] && rm ~/install_mysqldb.sh';
                    $listofcommands[] = '[ -f ~/install_state_mysql ] && rm ~/install_state_mysql';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_mysqldb.sh';
                    $listofcommands[] = 'chmod 700 install_mysqldb.sh';
                    $listofcommands[] = './install_mysqldb.sh';
                    // INSTALL HAPROXY
                    $listofcommands[] = '[ -f ~/install_haproxynode.sh ] && rm ~/install_haproxynode.sh';
                    $listofcommands[] = '[ -f ~/haproxy_add_state ] && rm ~/haproxy_add_state';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_haproxynode.sh';
                    $listofcommands[] = 'chmod 700 install_haproxynode.sh';
                    $listofcommands[] = './install_haproxynode.sh';
                    // Add to Metabase
                    $listofcommands[] = '[ -f ~/install_addtometabase_web.sh ] && rm ~/install_addtometabase_web.sh';
                    $listofcommands[] = '[ -f ~/metabase_add_state_web ] && rm ~/metabase_add_state_web';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_addtometabase_web.sh';
                    $listofcommands[] = 'chmod 700 install_addtometabase_web.sh';
                    $listofcommands[] = './install_addtometabase_web.sh';
                    // Add node to Uptime Kuma monitoring
                    $listofcommands[] = '[ -f ~/install_uptime_monitors_web.sh ] && rm ~/install_uptime_monitors_web.sh';
                    $listofcommands[] = '[ -f ~/uptime_kuma_add_state ] && rm ~/uptime_kuma_add_state';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_uptime_monitors_web.sh';
                    $listofcommands[] = 'chmod 700 install_uptime_monitors_web.sh';
                    $listofcommands[] = './install_uptime_monitors_web.sh';
                }
                ///==========================================================================
                if ($action == 'install_mysqldb') {
                    // INSTALL MYSQL DB
                    $listofcommands[] = '[ -f ~/install_mysqldb.sh ] && rm ~/install_mysqldb.sh';
                    $listofcommands[] = '[ -f ~/install_state_mysql ] && rm ~/install_state_mysql';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_mysqldb.sh';
                    $listofcommands[] = 'chmod 700 install_mysqldb.sh';
                    $listofcommands[] = './install_mysqldb.sh';
                    // Additional steps or messages can be added here if needed
                }
                ///==========================================================================
                if ($action == 'install_mailserver') {
                    // INSTALL MAILSERVER
                    $listofcommands[] = '[ -f ~/install_mailserver.sh ] && rm ~/install_mailserver.sh';
                    $listofcommands[] = '[ -f ~/install_state_mail ] && rm ~/install_state_mail';
                    $listofcommands[] = 'wget https://dev.birthday.gold/admin_actions/install_mailserver.sh';
                    $listofcommands[] = 'chmod 700 install_mailserver.sh';
                    $listofcommands[] = './install_mailserver.sh';
                    // Additional steps or messages can be added here if needed
                }
                ///==========================================================================
                if ($action == 'install_emailqueue_docker') {
                    // INSTALL EMAIL QUEUE
                    $listofcommands[] = 'apt-get update';
                    $listofcommands[] = 'apt-get -y install make';
                    $listofcommands[] = '[ -f ~/bg-emailqueue-docker/makefile ] && cd ~ && rm -rf ~/bg-emailqueue-docker';

                    $listofcommands[] = 'docker ps -q | xargs -r docker stop | xargs -r docker rm';

                    $listofcommands[] = 'git clone https://github.com/ddgconsultant/bg-emailqueue-docker';
                    $listofcommands[] = 'sleep 30'; // Sleep for 80 seconds to ensure the git clone command completes

                    $listofcommands[] = 'echo "apikey=\'' . $api_key . '\'" > $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DJLTPL-RKLNL-TNTSM-LTLNTR&apikey=' . $api_key . '&');   // birthday_gold_admin 
                    $listofcommands[] = 'echo "adminpass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DTOWO8-UOONO-NTWNR-TTRT8T&apikey=' . $api_key . '&');   // postmasterpass 
                    $listofcommands[] = 'echo "postmasterpass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCKHC-KJGBK-9DBGB-JBBJ97&apikey=' . $api_key . '&');   // FTP - webinstall 
                    $listofcommands[] = 'echo "ftppass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $listofcommands[] = 'make -C ~/bg-emailqueue-docker config';
                    $listofcommands[] = 'sleep 80'; // Sleep for 80 seconds to ensure the git clone command completes
                    $listofcommands[] = 'make -C ~/bg-emailqueue-docker up';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCK9C-97J99-FKDKJ-9HHDFF&apikey=' . $api_key . '&');   // bgdbreplicator1                   
                    $listofcommands[] = 'echo "ALTER USER \'bgdbreplicator1\'@\'%\' IDENTIFIED BY \'' . $pass . '\';"';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DVN3RN-OTMX3-Q7OSO-OQSNOS&apikey=' . $api_key . '&');   // birthday_gold_admin                   
                    $listofcommands[] = 'echo "ALTER USER \'birthday_gold_admin\'@\'%\' IDENTIFIED BY \'' . $pass . '\';"';

                    // Additional steps or messages can be added here if needed
                }

                ///==========================================================================
                if ($action == 'ftp_production_config_to_webservers') {
                 $listofcommands[] = 'ftp -inv dev.birthday.gold <<EOF user richard Hvm!7644; get "/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc" "/var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc";bye;EOF;';
                }

                ///==========================================================================
                if ($action == 'remove_emailqueue_mysqlinstance') {
                $listofcommands[] = "systemctl stop mysql3316";
                $listofcommands[] = "systemctl disable mysql3316";
                $listofcommands[] = "rm /etc/systemd/system/mysql3316.service";
                $listofcommands[] = "systemctl daemon-reload";
                $listofcommands[] = "rm -rf /var/lib/mysql3316";
                $listofcommands[] = "rm -rf /var/log/mysql3316";
                $listofcommands[] = "rm -rf /etc/mysql/mysql3316";
                $listofcommands[] = "systemctl status mysql";
                $listofcommands[] = "systemctl status mysql3316";
                }

                ///==========================================================================
                if ($action == 'install_emailqueue_mysqlinstance') {
                $listofcommands[] = "apt update";
                $listofcommands[] = "apt install mysql-server";
                
               # $listofcommands[] = "systemctl status mysql";
                
        #        $listofcommands[] = "mkdir -p /var/lib/mysql3316";
        #        $listofcommands[] = "mkdir -p /var/log/mysql3316";
               $listofcommands[] = "mkdir -p /etc/mysql/mysql3316";

// Set appropriate permissions for the data and log directories
#$listofcommands[] = "chown mysql:mysql /var/lib/mysql3316";
#$listofcommands[] = "chmod 700 /var/lib/mysql3316";
#$listofcommands[] = "chown mysql:mysql /var/log/mysql3316";
#$listofcommands[] = "chmod 750 /var/log/mysql3316";

                $listofcommands[] = "cp -r /etc/mysql/* /etc/mysql/mysql3316/.";
                 
               # $listofcommands[] = "nano /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^port.*/port = 3316/' /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^datadir.*/datadir = \\/var\\/lib\\/mysql_additionalinstances\\/mysql3316/' /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^socket.*/socket = \\/var\\/run\\/mysqld\\/mysqld3316.sock/' /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^log_error.*/log_error = \\/var\\/log\\/mysql3316\\/error.log/' /etc/mysql/mysql3316/mysqld.cnf";
                $listofcommands[] = "sed -i 's/^pid_file.*/pid_file = \\/var\\/run\\/mysqld\\/mysqld3316.pid/' /etc/mysql/mysql3316/mysqld.cnf";
                
                $listofcommands[] = "mysqld --initialize --datadir=/var/lib/mysql3316 --user=mysql";
                
                $listofcommands[] = "cp /lib/systemd/system/mysql.service /etc/systemd/system/mysql3316.service";
                
           #     $listofcommands[] = "nano /etc/systemd/system/mysql3316.service";
                $listofcommands[] = "sed -i 's/^ExecStart=.*/ExecStart=\\/usr\\/sbin\\/mysqld --defaults-file=\\/etc\\/mysql\\/mysql3316\\/mysqld.cnf/' /etc/systemd/system/mysql3316.service";
                
                $listofcommands[] = "systemctl daemon-reload";
                $listofcommands[] = "systemctl start mysql3316";
                $listofcommands[] = "systemctl enable mysql3316";
                
                $listofcommands[] = "systemctl status mysql3316";
                
             #   $listofcommands[] = "mysql_secure_installation --socket=/var/run/mysqld/mysqld3316.sock";
                
              #  $listofcommands[] = "mysql -u root -p --socket=/var/run/mysqld/mysqld3316.sock";
                
                }

                ///==========================================================================
                if ($action == 'install_emailqueue') {
                    // INSTALL EMAIL QUEUE
                    $listofcommands[] = 'apt-get update';
                    $listofcommands[] = 'apt-get -y install make';
                    $listofcommands[] = '[ -f ~/bg-emailqueue ] && cd ~ && rm -rf ~/bg-emailqueue';

                    $listofcommands[] = 'git clone https://github.com/ddgconsultant/bg-emailqueue';
                    $listofcommands[] = 'sleep 30'; // Sleep for 80 seconds to ensure the git clone command completes

                    $listofcommands[] = 'echo "apikey=\'' . $api_key . '\'" > $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DJLTPL-RKLNL-TNTSM-LTLNTR&apikey=' . $api_key . '&');   // birthday_gold_admin 
                    $listofcommands[] = 'echo "adminpass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DTOWO8-UOONO-NTWNR-TTRT8T&apikey=' . $api_key . '&');   // postmasterpass 
                    $listofcommands[] = 'echo "postmasterpass=\'' . $pass . '\'" >> $HOME/.passwordfile';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCKHC-KJGBK-9DBGB-JBBJ97&apikey=' . $api_key . '&');   // FTP - webinstall 
                    $listofcommands[] = 'echo "ftppass=\'' . $pass . '\'" >> $HOME/.passwordfile';




                    $listofcommands[] = 'make -C ~/bg-emailqueue-docker config';
                    $listofcommands[] = 'sleep 80'; // Sleep for 80 seconds to ensure the git clone command completes
                    $listofcommands[] = 'make -C ~/bg-emailqueue-docker up';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DZCK9C-97J99-FKDKJ-9HHDFF&apikey=' . $api_key . '&');   // bgdbreplicator1                   
                    $listofcommands[] = 'echo "ALTER USER \'bgdbreplicator1\'@\'%\' IDENTIFIED BY \'' . $pass . '\';"';

                    $pass = file_get_contents('https://dev.birthday.gold/admin/accessmanager/accessmanager_get?id=DVN3RN-OTMX3-Q7OSO-OQSNOS&apikey=' . $api_key . '&');   // birthday_gold_admin                   
                    $listofcommands[] = 'echo "ALTER USER \'birthday_gold_admin\'@\'%\' IDENTIFIED BY \'' . $pass . '\';"';

                    // Additional steps or messages can be added here if needed
                }


                #-------------------------------------------------------------------------------
                // Stream output to the browser

                foreach ($listofcommands as $command) {
                    if (is_callable($command)) {
                        $ssh = $command();
                        if (!$ssh) {
                            break;
                        }
                    } else {

                        $output = $ssh->exec($command);
                        if (strpos($command, 'password') !== false) $displaycommand = '{{suppressed}}';
                        else $displaycommand = $command;
                        echo '<hr><div class="mt-4"><i>' . date('r') . '</i><h5>Command Output: <span class="text-primary">' . $displaycommand . '</span></h5><pre>' . $output . '</pre></div>';
                        flush(); // Send output to the browser
                        sleep(1); // Delay to ensure command completion
                        flush(); // Send output to the browser

                    }
                }
                #-------------------------------------------------------------------------------

            }
            echo '</div>';
        }
    }
}







#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');

try {
    // Query to fetch data
    $query = "SELECT name, url FROM bg_system_availability where `status`='A' order by name";
    $stmt = $database->prepare($query);
    $stmt->execute();
    $systems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle the error
    echo "Error: " . $e->getMessage();
}



echo '
    <div class="container mt-5">
        <h2>SSH Command Executor</h2>
        <form method="post" action="/admin_actions/newhost_setup">            
' . $display->inputcsrf_token() . '
<div class="mb-3">
    <label for="host" class="form-label">Host</label>
    <select multiple class="form-select" name="host" id="host">
   ';

// Append options dynamically
foreach ($systems as $system) {
    $name = htmlspecialchars($system['name']);
    $url = htmlspecialchars($system['url']);
    echo '<option value="'.$url.'">'.$name.'</option>';
}

echo '
    </select>
</div>
';
/*
            <div class="mb-3">
                <label for="host" class="form-label">Remote Server Host</label>
                <input type="text" class="form-control" id="host" name="host" value="march03.bday.gold" required>
            </div>
*/
echo '
            <div class="mb-3">
                <label for="username" class="form-label">OS Username</label>
                <input type="text" class="form-control" id="username" name="username" value="root" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">OS User Password</label>
                <input type="password" class="form-control" id="password" name="password" value="" required>
            </div>
            <div class="mb-3">
                <label for="api_key" class="form-label">API Key</label>
                <input type="text" class="form-control" id="api_key" name="api_key" >
            </div>
';
$actions = [
    'deploy_www' => 'Deploy WWW',
    'install_webserver' => 'Install Webserver',
    'install_mysqldb' => 'Install MySQL DB',
    'install_mailserver' => 'Install Mailserver',
    'install_emailqueue' => 'Install Email Queue Docker',
    'remove_emailqueue_mysqlinstance' => 'Remove Email Queue',
    'install_emailqueue_mysqlinstance' => 'Install Email Queue',
];

foreach ($actions as $act => $label) {
    echo '<div class="form-check">';
    echo '<input class="form-check-input" type="checkbox" name="serveraction[]" value="' . $act . '" id="serveraction_' . $act . '" />';
    echo '<label class="form-check-label" for="serveraction_' . $act . '"> ' . $label . ' </label>';
    echo '</div>';
}


echo '     <button type="submit" class="btn btn-primary">Execute Command</button>
        </form>

    
    </div>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/5.0.0-beta3/js/bootstrap.bundle.min.js"></script>';


#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.inc');
#include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footerjs.inc');


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();