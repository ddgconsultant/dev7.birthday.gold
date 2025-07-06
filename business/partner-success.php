<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Application Submitted - Birthday Gold Partner';
$pagedata['metakeywords'] = 'Birthday Gold Partner Application Success';
$pagedata['metadescriptions'] = 'Thank you for applying to become a Birthday Gold partner. We will review your application shortly.';

// Header flush for better spacing
$header_flush = true;

// Additional styles
$additionalstyles = '
<style>
.success-container {
    max-width: 800px;
    margin: 4rem auto;
    text-align: center;
    padding: 0 15px;
}

.success-icon {
    font-size: 5rem;
    color: #198754;
    margin-bottom: 2rem;
}

.success-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #212529;
}

.success-message {
    font-size: 1.2rem;
    color: #6c757d;
    margin-bottom: 3rem;
    line-height: 1.6;
}

.next-steps {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 3rem;
    text-align: left;
}

.next-steps h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #212529;
}

.next-steps ol {
    margin: 0;
    padding-left: 1.5rem;
}

.next-steps li {
    margin-bottom: 0.5rem;
    color: #6c757d;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-cta {
    padding: 0.75rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary-cta {
    background: #198754;
    color: white;
}

.btn-primary-cta:hover {
    background: #157347;
    transform: translateY(-2px);
    color: white;
}

.btn-secondary-cta {
    background: white;
    color: #198754;
    border: 2px solid #198754;
}

.btn-secondary-cta:hover {
    background: #198754;
    color: white;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .success-title {
        font-size: 2rem;
    }
    
    .success-message {
        font-size: 1.1rem;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-cta {
        width: 100%;
        max-width: 300px;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="success-container">
    <i class="bi bi-check-circle-fill success-icon"></i>
    
    <h1 class="success-title">Application Submitted Successfully!</h1>
    
    <p class="success-message">
        Thank you for your interest in becoming a Birthday Gold partner. 
        We've received your application and will review it within 1-2 business days.
    </p>
    
    <div class="next-steps">
        <h3>What Happens Next?</h3>
        <ol>
            <li>Our partnership team will review your application and proposed birthday offer</li>
            <li>We may contact you if we need any additional information</li>
            <li>Once approved, we'll send you onboarding materials and help you get started</li>
            <li>Your business will be live on Birthday Gold and ready to receive birthday customers!</li>
        </ol>
    </div>
    
    <p class="text-muted mb-4">
        <i class="bi bi-envelope"></i> A confirmation email has been sent to the email address you provided.
    </p>
    
    <div class="cta-buttons">
        <a href="/" class="btn-cta btn-primary-cta">Return to Home</a>
        <a href="/about" class="btn-cta btn-secondary-cta">Learn More About Us</a>
    </div>
</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>