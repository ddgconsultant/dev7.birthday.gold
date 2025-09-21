<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-5 main-content">
    <h1>🔐 SSL Certificate Management</h1>
    
    <div class="row">
        <div class="col-md-8">
            <h2>Certificate Update Process</h2>
            
            <div class="alert alert-info">
                <strong>Current Certificate:</strong> Valid until Sep 30, 2026<br>
                <strong>Source Location:</strong> <code>/mnt/w/BIRTHDAY_SERVER/_CERTS_/birthday.gold/2025_cert/</code><br>
                <strong>Distribution Location:</strong> <code>.claude/ssl/</code>
            </div>
            
            <h3>Method 1: Central Deployment (Recommended)</h3>
            <p>Deploy certificates to all servers from dev7:</p>
            <pre><code>php /var/www/BIRTHDAY_SERVER/dev7.birthday.gold/admin_actions/deploy-ssl-certificates.php</code></pre>
            
            <h3>Method 2: Individual Server Self-Heal</h3>
            <p>Each server can update its own certificates by running:</p>
            <pre><code>curl -s https://dev7.birthday.gold/admin_actions/self-heal-ssl.sh | bash</code></pre>
            
            <h3>Method 3: Manual Server Updates</h3>
            
            <h4>Web Servers (Apache)</h4>
            <pre><code>cd /var/web_certs/BIRTHDAY_SERVER/birthday.gold/
wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/STAR_birthday_gold.crt
wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/server.key
wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/SectigoRSADomainValidationSecureServerCA.crt
wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/USERTrustRSAAAACA.crt
sudo chown -R www-data:www-data /var/web_certs/BIRTHDAY_SERVER
sudo chmod 440 /var/web_certs/BIRTHDAY_SERVER/birthday.gold/*
sudo chmod 400 /var/web_certs/BIRTHDAY_SERVER/birthday.gold/server.key
sudo systemctl reload apache2</code></pre>
            
            <h4>HAProxy Load Balancer</h4>
            <pre><code>cd /var/web_certs/BIRTHDAY_SERVER/birthday.gold/
wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/STAR_birthday_gold.crt
wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/server.key
wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/SectigoRSADomainValidationSecureServerCA.crt
wget --no-check-certificate -N https://dev7.birthday.gold/.claude/ssl/USERTrustRSAAAACA.crt
cat STAR_birthday_gold.crt server.key SectigoRSADomainValidationSecureServerCA.crt USERTrustRSAAAACA.crt > combined.pem
cat STAR_birthday_gold.crt server.key SectigoRSADomainValidationSecureServerCA.crt USERTrustRSAAAACA.crt > STAR_birthday_gold_combined.pem
chmod 440 combined.pem STAR_birthday_gold_combined.pem
chown root:root combined.pem STAR_birthday_gold_combined.pem
systemctl restart haproxy</code></pre>
            
            <h3>Automated Deployment Options</h3>
            
            <h4>Option A: Cron Job Self-Healing</h4>
            <p>Add this to each server's crontab to check for certificate updates daily:</p>
            <pre><code># Check for SSL certificate updates daily at 2 AM
0 2 * * * curl -s https://dev7.birthday.gold/admin_actions/self-heal-ssl.sh | bash >> /var/log/ssl-self-heal.log 2>&1</code></pre>
            
            <h4>Option B: Deployment Hook</h4>
            <p>Add certificate deployment to the main deployment script by calling:</p>
            <pre><code>php /var/www/BIRTHDAY_SERVER/dev7.birthday.gold/admin_actions/deploy-ssl-certificates.php</code></pre>
            
            <h3>Server Inventory</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>Web Servers (Apache)</h5>
                    <ul>
                        <li>july02.birthday.gold</li>
                        <li>july04.birthday.gold</li>
                        <li>august.birthday.gold</li>
                        <li><em>Add new servers to deploy script</em></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Load Balancers (HAProxy)</h5>
                    <ul>
                        <li>april21.bday.gold</li>
                    </ul>
                </div>
            </div>
            
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>🚀 Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="/admin_actions/deploy-ssl-certificates" class="btn btn-primary btn-sm mb-2 d-block">Deploy to All Servers</a>
                    <a href="/.claude/ssl/" class="btn btn-info btn-sm mb-2 d-block">View Certificate Files</a>
                    <button class="btn btn-warning btn-sm mb-2 d-block" onclick="checkCertExpiry()">Check Certificate Status</button>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>📋 Certificate Files</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li>📄 STAR_birthday_gold.crt</li>
                        <li>🔑 server.key</li>
                        <li>📄 SectigoRSADomain...CA.crt</li>
                        <li>📄 USERTrustRSAAAACA.crt</li>
                        <li>⛓️ STAR_birthday_gold_chained.crt</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkCertExpiry() {
    // This could make AJAX calls to check certificate status on each server
    alert('Certificate expires: Sep 30, 2026');
}
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>