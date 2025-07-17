<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Phone numbers configuration
$bg_phonenumbers = [
    'tollfree'         => '1-877-BDGOLD-2',
    'tollfree_numbers'  => '1-877-234-6532',
    'text'             => '223-200-GOLD',
    'text_numbers'      => '223-200-4653'
];

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
                        $header_content= ' <h5 class="mt-5">These are the complete Partner Terms and Conditions that govern your participation as a Birthday Gold business partner.</h5>';

                        $query = "SELECT id, content, DATE_FORMAT(publish_dt, '%M %e, %Y') AS effective_date  FROM bg_content WHERE name= 'partner_terms_full' and `status`='active' order by create_dt desc limit 1";
                        
                        // Check if content exists in database
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
                            // Show full partner terms if not in database
                            echo '<p class="mb-4">Effective Date: ' . date('F j, Y') . '</p>';
                            echo $header_content;
                            ?>
                            
                            <!-- Full Partner Terms Content -->
                            <div class="partner-terms-content mt-5">
                                <p>These Partner Terms and Conditions govern your participation in partnering with birthday.gold and you or the entity you represent. These terms take effect once you are approved as a partner with birthday.gold. You represent to us that you are lawfully able to enter into a contract (are not a minor) and that you represent to us that you also have legal authority to bind yourself or the legal entity you represent.</p>
                                
                                <h2>1. Partnership Overview</h2>
                                <p>By becoming a Birthday Gold partner ("Partner"), you agree to offer birthday rewards, deals, or experiences ("Offers") to Birthday Gold members ("Members") through the birthday.gold platform and services ("Company").</p>
                                
                                <h2>2. Partner Requirements</h2>
                                <p><strong>Partner shall:</strong></p>
                                <ul>
                                    <li>Maintain accurate business information on the platform</li>
                                    <li>Honor all published birthday offers to verified Members</li>
                                    <li>Provide quality products/services consistent with your brand</li>
                                    <li>Respond to member inquiries and issues promptly</li>
                                    <li>Comply with all applicable laws and regulations</li>
                                    <li>Be responsible for all activities that occur under your Partnership account</li>
                                    <li>Comply with all applicable Government procurement laws, rules, regulations, and contract provisions, including any that pertain to discounts and rebates, or that pertain to ethics and integrity (e.g., prohibitions against gratuities, bribery, corruption, kickbacks, conflicts of interest, false statements or claims, etc.)</li>
                                    <li>Ensure use of Benefits does not create a conflict of interest (or the appearance of a conflict of interest) for you or birthday.gold or give rise to any liability for birthday.gold</li>
                                </ul>
                                
                                <p><strong>Partner shall not:</strong></p>
                                <ul>
                                    <li>Make false or misleading claims about Company</li>
                                    <li>Engage in any harmful, false or deceptive acts or practices</li>
                                    <li>Engage in spamming or other unethical marketing practices</li>
                                    <li>Modify or alter Company-provided marketing materials without approval</li>
                                    <li>Pay bribes to anyone, for any reason. You will not violate or knowingly permit your employees or representatives to violate any applicable anti-corruption laws</li>
                                    <li>Be an affiliate marketing entity, engage in affiliate marketing or multilevel marketing activities</li>
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
                                    <li>We may list your name, website, and other general contact details on the birthday.gold site</li>
                                    <li>Birthday.gold may use mechanisms that rate, or allow customers to rate, your products or services, and may make these ratings publicly available</li>
                                </ul>
                                
                                <h2>6. Fees and Payment</h2>
                                <ul>
                                    <li>Partnership fees (if applicable) will be clearly communicated</li>
                                    <li>Partners are responsible for their own operational costs</li>
                                    <li>Birthday Gold does not take commission on member transactions</li>
                                    <li>Neither you nor birthday.gold intend for these Terms to create any revenue sharing, commission fees, or similar arrangement</li>
                                </ul>
                                
                                <h2>7. Data Protection and Privacy</h2>
                                <ul>
                                    <li>Partners must protect member data and use it only for offer fulfillment</li>
                                    <li>Comply with all privacy laws and regulations</li>
                                    <li>Not share or sell member information to third parties</li>
                                    <li>Delete member data when no longer needed</li>
                                    <li>Handle any Third Party Data in accordance with applicable data protection laws and only for the purpose for which it is provided</li>
                                    <li>Delete any Third Party Data provided by birthday.gold upon request</li>
                                </ul>
                                
                                <h2>8. Confidentiality</h2>
                                <ul>
                                    <li>Partner agrees to maintain the confidentiality of all non-public information shared by Company</li>
                                    <li>Confidential obligations continue for 3 years after termination of this Agreement</li>
                                </ul>
                                
                                <h2>9. Intellectual Property</h2>
                                <ul>
                                    <li>If you provide trademark, service mark, trade name, proprietary logo or insignia, URL, domain, or other content ("Your Materials"), you grant us and our affiliates a worldwide, royalty-free, non-exclusive, non-sublicensable, and non-transferrable license to use, reproduce, display, distribute, and translate all or part of Your Materials in connection with the Program</li>
                                    <li>You confirm that you have all necessary rights to grant birthday.gold and its affiliates the rights described</li>
                                    <li>Birthday.gold may make reasonable, minor changes to Your Materials, such as resizing or reformatting</li>
                                    <li>Between the parties, you retain all rights, title, and interest in and to Your Materials</li>
                                </ul>
                                
                                <h2>10. Term and Termination</h2>
                                <ul>
                                    <li>This Agreement begins upon Company's acceptance of Partner's application and continues until terminated</li>
                                    <li>Either party may terminate with 30 days written notice</li>
                                    <li>Birthday Gold may terminate immediately for breach of terms</li>
                                    <li>Outstanding offers must be honored through termination period</li>
                                    <li>Upon termination: (a) you remain responsible for all Program fees incurred, (b) you will immediately return, cease use of, and remove all Materials in your possession, (c) you will immediately cease to identify yourself as a Program participant</li>
                                </ul>
                                
                                <h2>11. Liability and Indemnification</h2>
                                <ul>
                                    <li>Each party is responsible for their own actions</li>
                                    <li>Partners indemnify Birthday Gold against claims arising from their offers</li>
                                    <li>Birthday Gold provides platform "as is" without warranties</li>
                                </ul>
                                
                                <h2>12. Limitation of Liability</h2>
                                <p>WE AND OUR AFFILIATES WILL NOT HAVE LIABILITY TO YOU FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR EXEMPLARY DAMAGES, OR FOR ANY LOSS OF REVENUE, PROFITS, OR GOODWILL, EVEN IF A PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH LIABILITY. IN ANY CASE, THE AGGREGATE LIABILITY OF BIRTHDAY.GOLD AND OUR AFFILIATES IN CONNECTION WITH THESE TERMS AND THE PROGRAM WILL BE LIMITED TO A REFUND OF THE FEES PAID BY YOU TO US OR OUR AFFILIATES DURING THE 12 MONTHS BEFORE THE LIABILITY AROSE. THE LIMITATIONS IN THIS SECTION APPLY ONLY TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW AND DO NOT APPLY TO LOSSES CAUSED BY OUR GROSS NEGLIGENCE OR CRIMINAL MISCONDUCT.</p>
                                
                                <h2>13. Modifications</h2>
                                <ul>
                                    <li>We may change or discontinue all or any part of the Program at any time</li>
                                    <li>We may modify these Terms by posting a revised version on the birthday.gold Site or by otherwise notifying you</li>
                                    <li>If we modify these Terms in a way that is materially adverse to you, we will give you at least 30 days' prior notice</li>
                                    <li>By continuing to participate after modifications, you agree to be bound by the modified terms</li>
                                    <li>It is your responsibility to check the birthday.gold Site regularly for modifications</li>
                                </ul>
                                
                                <h2>14. General Provisions</h2>
                                <ul>
                                    <li>These terms are governed by applicable state law</li>
                                    <li>Changes to terms require written agreement</li>
                                    <li>Partners are independent contractors, not employees or agents</li>
                                    <li>The use of the term "birthday.gold Partner" or "partner of birthday.gold" refers solely to membership in the Program</li>
                                    <li>Neither party has the authority to bind the other</li>
                                    <li>These Terms are non-exclusive and do not preclude either party from entering into similar agreements with third parties</li>
                                    <li>Nothing in these Terms is a revenue guarantee to either party</li>
                                    <li>Disputes will be resolved through good faith negotiation</li>
                                    <li>You will not assign or transfer these Terms without our prior written consent</li>
                                    <li>We may assign these Terms without your consent in connection with a merger, acquisition, or corporate reorganization</li>
                                </ul>
                                
                                <div class="text-center my-4">
                                    <a href="/legalhub/partnerterms" class="btn btn-secondary">View Summary Version</a>
                                </div>
                                
                                <div class="alert alert-info mt-5">
                                    <h5><i class="bi bi-info-circle me-2"></i>Questions?</h5>
                                    <p class="mb-0">If you have any questions about these partner terms, please contact our partner support team at <a href="mailto:partners@birthday.gold">partners@birthday.gold</a> or call us at <?php echo $bg_phonenumbers['tollfree']; ?> (<?php echo $bg_phonenumbers['tollfree_numbers']; ?>).</p>
                                </div>
                            </div>
                            
                            <?php
                        }
                        break;

                    default:
                        $header_content= '
<h5 class="mt-5">As a Birthday Gold partner, it is important to understand our partnership agreement and the mutual expectations for our business relationship.</h5>

<p>At birthday.gold, we want our business partners to be fully informed about our partnership terms without overwhelming you with legal jargon.</p>
<p>If you need more detailed information or have specific questions, you can always read our <a href="/legalhub/partnerterms?full" class="btn btn-primary btn-sm py-0 my-0">FULL PARTNER TERMS AND CONDITIONS</a> or contact our legal department. 
The condensed version below covers the key points, but the full terms and conditions contain all legal requirements and supersede this summary.</p>
';
                        $query = "SELECT id, content, DATE_FORMAT(publish_dt, '%M %e, %Y') AS effective_date FROM bg_content WHERE name= 'partner_terms' and `status`='active' order by create_dt desc limit 1";
                        
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
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Summary Version:</strong> This is a simplified summary of our Partner Terms for your convenience. The <a href="/legalhub/partnerterms?full">full Partner Terms and Conditions</a> contain all legal requirements and are the binding agreement between you and Birthday Gold.
                        </div>
                        
                        <h2>Quick Overview</h2>
                        <p>As a Birthday Gold partner, you'll join our platform to offer special birthday rewards to our members. Here's what you need to know:</p>
                        
                        <h2>Your Responsibilities</h2>
                        <ul>
                            <li><strong>Honor Your Offers:</strong> All birthday offers must be honored for verified members during their birthday month</li>
                            <li><strong>Stay Legal:</strong> Comply with all laws, including anti-corruption and data protection regulations</li>
                            <li><strong>Be Honest:</strong> No false claims, spam, or deceptive practices</li>
                            <li><strong>Protect Data:</strong> Keep member information secure and use it only for fulfilling offers</li>
                        </ul>
                        
                        <h2>What We Provide</h2>
                        <ul>
                            <li>Platform access to reach Birthday Gold members</li>
                            <li>Marketing support and promotion of your offers</li>
                            <li>Verification system for member eligibility</li>
                            <li>Partner dashboard for tracking redemptions</li>
                        </ul>
                        
                        <h2>Key Terms</h2>
                        <ul>
                            <li><strong>Independent Contractor:</strong> You're a partner, not an employee</li>
                            <li><strong>Non-Exclusive:</strong> Both parties can work with others</li>
                            <li><strong>Termination:</strong> Either party can end the partnership with 30 days notice</li>
                            <li><strong>Liability Limited:</strong> Our liability is capped at fees paid in the last 12 months</li>
                        </ul>
                        
                        <h2>Important Restrictions</h2>
                        <ul>
                            <li>No affiliate marketing or MLM activities</li>
                            <li>No modifying our marketing materials without approval</li>
                            <li>No bribery or unethical business practices</li>
                            <li>Cannot assign or transfer partnership without our consent</li>
                        </ul>
                        
                        <h2>Privacy & Confidentiality</h2>
                        <ul>
                            <li>Keep all non-public information confidential (3 years after termination)</li>
                            <li>Delete member data when no longer needed</li>
                            <li>We may use your business info and logos for marketing</li>
                        </ul>
                        
                        <div class="alert alert-warning mt-4">
                            <h5><i class="bi bi-exclamation-circle me-2"></i>Remember</h5>
                            <p class="mb-0">This summary is provided for quick reference only. By participating in the Birthday Gold Partner Program, you agree to be bound by the <a href="/legalhub/partnerterms?full" class="alert-link"><strong>full Partner Terms and Conditions</strong></a>, which contain all the detailed legal obligations and supersede this summary.</p>
                        </div>
                        
                        <div class="text-center my-4">
                            <a href="/legalhub/partnerterms?full" class="btn btn-primary btn-lg">View Full Partner Terms</a>
                        </div>
                        
                        <div class="alert alert-info mt-5">
                            <h5><i class="bi bi-info-circle me-2"></i>Questions?</h5>
                            <p class="mb-0">If you have any questions about these partner terms, please contact our partner support team at <a href="mailto:partners@birthday.gold">partners@birthday.gold</a> or call us at <?php echo $bg_phonenumbers['tollfree']; ?> (<?php echo $bg_phonenumbers['tollfree_numbers']; ?>).</p>
                        </div>
                    </div>
                    
                    
                            <?php
                        }
                        break;
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