<?php
/**
 * SSL Certificate Deployment Script
 * Pushes new SSL certificates to all production servers
 */

// Server configurations
$webservers = [
    'july02.birthday.gold',
    'july04.birthday.gold', 
    'august.birthday.gold',
    // Add other web servers
];

$haproxy_servers = [
    'april21.bday.gold'
];

$cert_source = '/var/www/BIRTHDAY_SERVER/dev7.birthday.gold/.claude/ssl/';
$web_cert_dest = '/var/web_certs/BIRTHDAY_SERVER/birthday.gold/';

echo "🔐 Starting SSL Certificate Deployment...\n\n";

// Deploy to web servers
foreach ($webservers as $server) {
    echo "📡 Deploying to web server: $server\n";
    
    $commands = [
        "cd $web_cert_dest",
        "wget --no-check-certificate -O STAR_birthday_gold.crt https://dev7.birthday.gold/.claude/ssl/STAR_birthday_gold.crt",
        "wget --no-check-certificate -O server.key https://dev7.birthday.gold/.claude/ssl/server.key", 
        "wget --no-check-certificate -O SectigoRSADomainValidationSecureServerCA.crt https://dev7.birthday.gold/.claude/ssl/SectigoRSADomainValidationSecureServerCA.crt",
        "wget --no-check-certificate -O USERTrustRSAAAACA.crt https://dev7.birthday.gold/.claude/ssl/USERTrustRSAAAACA.crt",
        "wget --no-check-certificate -O STAR_birthday_gold_chained.crt https://dev7.birthday.gold/.claude/ssl/STAR_birthday_gold_chained.crt",
        "chown -R www-data:www-data /var/web_certs/BIRTHDAY_SERVER",
        "chmod 440 $web_cert_dest*",
        "chmod 400 {$web_cert_dest}server.key",
        "systemctl restart apache2"
    ];
    
    foreach ($commands as $cmd) {
        $full_command = "ssh root@$server '$cmd'";
        echo "  Running: $cmd\n";
        $result = shell_exec($full_command . ' 2>&1');
        if ($result) echo "  Output: $result";
    }
    echo "✅ Completed: $server\n\n";
}

// Deploy to HAProxy servers  
foreach ($haproxy_servers as $server) {
    echo "⚖️  Deploying to HAProxy server: $server\n";
    
    $commands = [
        "cd $web_cert_dest",
        "wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/STAR_birthday_gold.crt",
        "wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/server.key",
        "wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/SectigoRSADomainValidationSecureServerCA.crt", 
        "wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/USERTrustRSAAAACA.crt",
        "cat STAR_birthday_gold.crt server.key SectigoRSADomainValidationSecureServerCA.crt USERTrustRSAAAACA.crt > combined.pem",
        "cat STAR_birthday_gold.crt server.key SectigoRSADomainValidationSecureServerCA.crt USERTrustRSAAAACA.crt > STAR_birthday_gold_combined.pem", 
        "chmod 440 combined.pem STAR_birthday_gold_combined.pem",
        "chown root:root combined.pem STAR_birthday_gold_combined.pem",
        "systemctl restart haproxy"
    ];
    
    $full_command = "ssh root@$server '" . implode(' && ', $commands) . "'";
    echo "  Running certificate update and HAProxy restart...\n";
    $result = shell_exec($full_command . ' 2>&1');
    if ($result) echo "  Output: $result";
    echo "✅ Completed: $server\n\n";
}

echo "🎉 SSL Certificate Deployment Complete!\n";
echo "📋 Summary:\n";
echo "   • Web servers updated: " . count($webservers) . "\n";
echo "   • HAProxy servers updated: " . count($haproxy_servers) . "\n";
echo "   • Certificate valid until: Sep 30, 2026\n";
?>