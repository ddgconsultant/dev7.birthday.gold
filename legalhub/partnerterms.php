<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Partner Terms and Conditions - Birthday Gold';
$pagedata['metakeywords'] = 'Birthday Gold Partner Terms, Business Partner Agreement, Partner Terms and Conditions';
$pagedata['metadescriptions'] = 'Read the terms and conditions for becoming a Birthday Gold business partner. Learn about our partnership agreement and requirements.';

// Additional styles
$additionalstyles = '
<style>
.partner-terms-content h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #212529;
}

.partner-terms-content ul {
    margin-bottom: 1.5rem;
}

.partner-terms-content li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.display-1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .display-1 {
        font-size: 2.5rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Partner Terms Start -->
<div class="container main-content py-6">
    <div class="container">
        <div class="row">
            <div class="col">
                <h1 class="display-1">Partner Terms and Conditions</h1>
                
                <?PHP
                $type = '';

                if (isset($_REQUEST['full'])) $type = 'full';
                switch ($type) {

                    case 'full':
                        $header_content= ' <h5 class="mt-5">We encourage you to read our full Partner Terms and Conditions and understand the terms that may bind you as a Birthday Gold business partner.</h5>';

                        $query = "SELECT id, content, DATE_FORMAT(publish_dt, '%M %e, %Y') AS effective_date  FROM bg_content WHERE name= 'partner_terms_full' and `status`='active' order by create_dt desc limit 1";
                        $include = $_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/content-partnertermsfull.php';
                        break;

                    default:
                        $header_content= '
<h5 class="mt-5">As a Birthday Gold partner, it is important to understand our partnership agreement and the mutual expectations for our business relationship.</h5>

<p>At birthday.gold, we want our business partners to be fully informed about our partnership terms without overwhelming you with legal jargon.</p>
<p>If you need more detailed information or have specific questions, you can always read our <a href="/legalhub/partnerterms?full" class="btn btn-primary btn-sm py-0 my-0">FULL PARTNER TERMS AND CONDITIONS</a> or contact our legal department. 
The condensed version below covers the key points, but the full terms and conditions contain all legal requirements and supersede this summary.</p>
';
                        $query = "SELECT id, content, DATE_FORMAT(publish_dt, '%M %e, %Y') AS effective_date FROM bg_content WHERE name= 'partner_terms' and `status`='active' order by create_dt desc limit 1";
                        $include = $_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/content-partnerterms.php';
                }

                $stmt = $database->prepare($query);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $content = '<!-- '.$result['id']. ' -->'.$result['content'];
                    $effective_date = $result['effective_date'];
                    echo '<p class="mb-4">Effective Date: '.$effective_date.'</p>';
                    echo $header_content;
                    echo $content;
                } else {
                    // If no content in database, show default partner terms
                    echo '<p class="mb-4">Effective Date: ' . date('F j, Y') . '</p>';
                    echo $header_content;
                    ?>
                    
                    <!-- Default Partner Terms Content -->
                    <div class="partner-terms-content mt-5">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> These partner terms are currently under review by our legal department. The final version will be available soon.
                        </div>
                        
                        <h2>1. Partnership Overview</h2>
                        <p>By becoming a Birthday Gold partner ("Partner"), you agree to offer birthday rewards, deals, or experiences ("Offers") to Birthday Gold members ("Members") through our platform.</p>
                        
                        <h2>2. Partner Requirements</h2>
                        <ul>
                            <li>Maintain accurate business information on the platform</li>
                            <li>Honor all published birthday offers to verified Members</li>
                            <li>Provide quality products/services consistent with your brand</li>
                            <li>Respond to member inquiries and issues promptly</li>
                            <li>Comply with all applicable laws and regulations</li>
                        </ul>
                        
                        <h2>3. Birthday Offer Guidelines</h2>
                        <ul>
                            <li>Offers must be clearly defined and easy to understand</li>
                            <li>Any restrictions or limitations must be clearly stated</li>
                            <li>Offers must be available during the member's entire birthday month</li>
                            <li>Partners cannot require additional purchases unless clearly stated</li>
                            <li>Offers must provide genuine value to Members</li>
                        </ul>
                        
                        <h2>4. Verification and Redemption</h2>
                        <ul>
                            <li>Partners must verify member eligibility through the Birthday Gold platform</li>
                            <li>Accept valid Birthday Gold membership verification</li>
                            <li>Track redemptions through our partner dashboard</li>
                            <li>Report any issues or fraudulent activity immediately</li>
                        </ul>
                        
                        <h2>5. Marketing and Promotion</h2>
                        <ul>
                            <li>Birthday Gold may promote Partner offers across our platform</li>
                            <li>Partners grant us license to use business name, logos, and offer details</li>
                            <li>Partners may promote their Birthday Gold partnership</li>
                            <li>All marketing materials must be accurate and not misleading</li>
                        </ul>
                        
                        <h2>6. Fees and Payment</h2>
                        <ul>
                            <li>Partnership fees (if applicable) will be clearly communicated</li>
                            <li>Partners are responsible for their own operational costs</li>
                            <li>Birthday Gold does not take commission on member transactions</li>
                        </ul>
                        
                        <h2>7. Data Protection and Privacy</h2>
                        <ul>
                            <li>Partners must protect member data and use it only for offer fulfillment</li>
                            <li>Comply with all privacy laws and regulations</li>
                            <li>Not share or sell member information to third parties</li>
                            <li>Delete member data when no longer needed</li>
                        </ul>
                        
                        <h2>8. Term and Termination</h2>
                        <ul>
                            <li>Partnership continues until terminated by either party</li>
                            <li>Either party may terminate with 30 days written notice</li>
                            <li>Birthday Gold may terminate immediately for breach of terms</li>
                            <li>Outstanding offers must be honored through termination period</li>
                        </ul>
                        
                        <h2>9. Liability and Indemnification</h2>
                        <ul>
                            <li>Each party is responsible for their own actions</li>
                            <li>Partners indemnify Birthday Gold against claims arising from their offers</li>
                            <li>Birthday Gold provides platform "as is" without warranties</li>
                        </ul>
                        
                        <h2>10. General Provisions</h2>
                        <ul>
                            <li>These terms are governed by applicable state law</li>
                            <li>Changes to terms require written agreement</li>
                            <li>Partners are independent contractors, not employees or agents</li>
                            <li>Disputes will be resolved through good faith negotiation</li>
                        </ul>
                        
                        <div class="alert alert-info mt-5">
                            <h5><i class="bi bi-info-circle me-2"></i>Questions?</h5>
                            <p class="mb-0">If you have any questions about these partner terms, please contact our partner support team at <a href="mailto:partners@birthday.gold">partners@birthday.gold</a> or call us at 1-800-BIRTHDAY.</p>
                        </div>
                    </div>
                    
                    <?php
                }

                if (isset($_REQUEST['partner'])) {
                    echo '                <a class="btn btn-primary py-3 px-5 no-print" href="/business/partner">Back to Partner Application</a>';
                } else {
                    echo '                <a class="btn btn-primary py-3 px-5 no-print" href="'. htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/business/partner').'">Go Back</a>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
<!-- Partner Terms End -->

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>