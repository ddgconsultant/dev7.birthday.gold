<?php

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="container mt-5 main-content">';



$search='"';
$replace='';
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $subdomain = str_replace($search, $replace, escapeshellarg($_POST["subdomain"]));
    $domain = str_replace($search, $replace, escapeshellarg($_POST["domain"]));
    $transferCerts = isset($_POST["transferCerts"]) ? true : false;
    
    $sslBasePath = "/var/web_certs/BIRTHDAY_SERVER/" . str_replace(".gold", "", $domain) . "/";

    $commands = [
        // Build Directory for vHost
        "mkdir -p /var/www/BIRTHDAY_SERVER/{$subdomain}.{$domain}",
        "chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/{$subdomain}.{$domain}",
        "echo \"<?php echo 'Hello Birthday Gold World - '. date('r'); ?>\" > /var/www/BIRTHDAY_SERVER/{$subdomain}.{$domain}/index.php",
        "echo \"subdomain={$subdomain}\" >  /var/www/BIRTHDAY_SERVER/{$subdomain}.{$domain}/subdomain.id",
        "chmod 444  /var/www/BIRTHDAY_SERVER/{$subdomain}.{$domain}/subdomain.id",
        "sudo chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/{$subdomain}.{$domain}",
     
        
    ];

    // Add FTP commands if the checkbox is checked
    if ($transferCerts) {
        $ftpCommands = [
            "##########################################################",
            "##  TRANSFER CERTS AND ENV CONFIG FILE",
            "",
            "ftp -inv dev.{$domain} <<EOF",
            "user richard {{PASSWORD}}",
            "get /BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc /var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc",
            // Adjust paths based on the domain
            "get /BIRTHDAY_SERVER/_CERTS_/{$domain}/xfer/AAACertificateServices.crt {$sslBasePath}AAACertificateServices.crt",
            "get /BIRTHDAY_SERVER/_CERTS_/{$domain}/xfer/SectigoRSADomainValidationSecureServerCA.crt {$sslBasePath}SectigoRSADomainValidationSecureServerCA.crt",
            "get /BIRTHDAY_SERVER/_CERTS_/{$domain}/xfer/server.key {$sslBasePath}server.key",
            "get /BIRTHDAY_SERVER/_CERTS_/{$domain}/xfer/STAR_{$domain}.crt {$sslBasePath}STAR_{$domain}.crt",
            "get /BIRTHDAY_SERVER/_CERTS_/{$domain}/xfer/USERTrustRSAAAACA.crt {$sslBasePath}USERTrustRSAAAACA.crt",
            "bye",
            "EOF",
            "sudo chown -R www-data:www-data /var/www/BIRTHDAY_SERVER/ENV_CONFIGS",
            "sudo chown -R www-data:www-data /var/web_certs",
            "chmod 440 -R /var/www/BIRTHDAY_SERVER/ENV_CONFIGS/config-main-production.inc",
            "chmod 440 -R /var/web_certs/BIRTHDAY_SERVER",
            "chmod 400 /var/web_certs/BIRTHDAY_SERVER"
        ];

        $commands = array_merge($commands, $ftpCommands);
    }

    $enablevhost = [
        // Virtual Host configuration
        "",
        "##########################################################",
        "##  Enable SSL and the Virtual Host",
        "a2enmod ssl",
        "a2enmod rewrite",
        "",
        "systemctl reload apache2",
        "",
        
    ];
    $commands = array_merge($commands, $enablevhost);
echo '<div><h3>Commands to Configure Subdomain</h3>';
    echo "<pre style='padding:20px'>";
    foreach ($commands as $command) {
        echo htmlspecialchars($command) . "\n";
    }
    echo "</pre>";
    echo '</div>';
}

?>






<div>
    <h2>Configure Subdomain</h2>
    <form method="post" class="mt-3">
        <div class="mb-3">
            <label for="subdomain" class="form-label">Subdomain</label>
            <input type="text" class="form-control" id="subdomain" name="subdomain" required>
        </div>
        <div class="mb-3">
            <label for="domain" class="form-label">Domain</label>
            <select id="domain" name="domain" class="form-select">
                <option value="bd.gold">bd.gold</option>
                <option value="bday.gold">bday.gold</option>
                <option value="birthday.gold" selected>birthday.gold</option>
            </select>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="transferCerts" name="transferCerts">
            <label class="form-check-label" for="transferCerts">Transfer SSL Cert</label>
        </div>
        <button type="submit" class="btn btn-primary">Generate Commands</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Your PHP code for handling the form submission and generating commands
        // Display the commands as before
    }
    ?>
</div>


</div>
<?PHP

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
