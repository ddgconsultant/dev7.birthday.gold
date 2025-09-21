<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = 'Sales Training - Birthday.Gold';

$slides = [
    1 => [
        'title' => 'Welcome to Birthday.Gold Sales Training',
        'content' => 'Empowering Your Success in Sales and Recruitment<br>Company Logo<br>Date: May 25, 2024',
        'notes' => 'Welcome, everyone, to the Birthday.Gold Sales Training session. Today, we are excited to empower you with the tools and knowledge you need to succeed in sales and recruitment for Birthday.Gold. Let’s get started on this journey to bring joy and exclusive birthday perks to our users.'
    ],
    2 => [
        'title' => 'Introduction',
        'content' => 'Brief Introduction to Birthday.Gold:<br>- Founded to provide exclusive birthday perks and celebrations.<br>- Partnered with over '.$website['numberofbiz'].'+ '.$website['biznames'].'.<br>Mission Statement:<br>- Bringing joy and value to our users on their special day.<br>Overview of the Presentation:<br>- Topics to be covered: Our Plans, Target Audience, Sales Pitches, Recruiting Tactics, Signup Process, Commission Structure, Policies, Tools and Resources, Success Stories, Overcoming Challenges, Q&A, Conclusion.',
        'notes' => 'Birthday.Gold was founded to create unique and valuable birthday experiences by partnering with over '.$website['numberofbiz'].'+ '.$website['biznames'].'. Our mission is to bring joy and value to our users on their special day. Today, we will cover various topics including our plans, target audience, effective sales pitches, recruiting tactics, and much more. Let’s dive in.'
    ],
    3 => [
        'title' => 'Our Plans',
        'content' => 'Description of Available Plans:<br>- Free Plan: Basic access to birthday offers.<br>- Premium Plan: Enhanced offers and experiences.<br>- VIP Plan: Exclusive access to top-tier perks.<br>Benefits of Each Plan:<br>- Tailored offers, greater discounts, VIP experiences.',
        'notes' => 'Birthday.Gold offers three distinct plans to cater to different user needs: Free, Premium, and VIP. Each plan provides tailored offers, with increasing levels of discounts and exclusive experiences as you move up the tiers. This ensures that all users can find value in our services.'
    ],
    4 => [
        'title' => 'Our Plans (Detailed)',
        'content' => 'Free Plan:<br>- Limited offers.<br>- Basic support.<br>Premium Plan:<br>- More offers.<br>- Priority support.<br>- Birthday gifts.<br>VIP Plan:<br>- Best offers.<br>- VIP events.<br>- Personalized gifts.',
        'notes' => 'Let’s break down each plan in more detail. The Free Plan offers basic access to birthday perks and limited support. The Premium Plan provides more offers, priority support, and birthday gifts. The VIP Plan delivers the best offers, access to exclusive VIP events, and personalized gifts for a truly memorable experience.'
    ],
    5 => [
        'title' => 'Target Audience',
        'content' => 'Demographics:<br>- Age Groups: Teens, young adults, middle-aged adults.<br>- Interests: Shopping, dining, entertainment.<br>- Geographical Locations: Urban and suburban areas.<br>Psychographics:<br>- Motivations: Celebrating birthdays uniquely, getting value.<br>- Preferences: Personalized offers, convenience.',
        'notes' => 'Our target audience includes teens, young adults, and middle-aged adults, who are interested in shopping, dining, and entertainment. They are primarily located in urban and suburban areas. Psychographically, they are motivated by unique birthday celebrations and getting good value, with a preference for personalized and convenient offers.'
    ],
    6 => [
        'title' => 'Understanding Our Users',
        'content' => 'Common User Profiles:<br>- Young Professionals: Busy, tech-savvy, value experiences.<br>- Parents: Looking for family-friendly offers.<br>- Seniors: Enjoy leisure activities and dining.<br>Pain Points and Needs:<br>- Finding unique birthday celebrations, getting good deals.<br>How Birthday.Gold Addresses These Needs:<br>- Personalized offers, wide range of partners, easy signup.',
        'notes' => 'Understanding our users is crucial. We cater to young professionals who are busy and tech-savvy, parents looking for family-friendly offers, and seniors who enjoy leisure activities and dining. Their pain points include finding unique birthday celebrations and getting good deals, which Birthday.Gold addresses through personalized offers, a wide range of partners, and an easy signup process.'
    ],
    7 => [
        'title' => 'Sales Pitches',
        'content' => 'Effective Sales Language:<br>- Highlighting Benefits: “Experience your best birthday yet!”<br>- Addressing Objections: “No upfront cost, cancel anytime.”<br>Key Phrases to Use:<br>- “Exclusive access.”<br>- “Personalized offers.”<br>- “VIP experiences.”',
        'notes' => 'Effective sales language is key to success. Highlight the benefits of our plans with phrases like "Experience your best birthday yet!" Address common objections by emphasizing the lack of upfront cost and the ease of cancellation. Use key phrases such as "Exclusive access," "Personalized offers," and "VIP experiences" to capture the user’s interest.'
    ],
    8 => [
        'title' => 'Sales Pitches (Examples)',
        'content' => 'Example 1: Pitching to a Young Professional:<br>- Focus on convenience, tech integration, and exclusive deals.<br>Example 2: Pitching to a Parent:<br>- Emphasize family-friendly offers, ease of use, value for kids.',
        'notes' => 'Let’s look at some examples. When pitching to a young professional, focus on convenience, tech integration, and exclusive deals. For parents, emphasize family-friendly offers, ease of use, and value for kids. Tailoring your pitch to the audience’s specific needs and interests will make your message more compelling.'
    ],
    9 => [
        'title' => 'Recruiting Tactics',
        'content' => 'Identifying Potential Users:<br>- Social media, events, referral programs.<br>Building Relationships:<br>- Engaging with potential users, understanding their needs.<br>Leveraging Social Media and Online Platforms:<br>- Facebook, Instagram, LinkedIn campaigns.',
        'notes' => 'Recruiting new users involves identifying potential customers through social media, events, and referral programs. Building relationships by engaging with potential users and understanding their needs is crucial. Leverage social media platforms like Facebook, Instagram, and LinkedIn for targeted campaigns to reach a broader audience.'
    ],
    10 => [
        'title' => 'Recruiting Tactics (Cont.)',
        'content' => 'In-Person Recruitment Strategies:<br>- Hosting booths at events, handing out flyers.<br>Hosting Events and Workshops:<br>- Birthday planning workshops, partner collaboration events.<br>Utilizing Referral Programs:<br>- Incentives for existing users to refer friends and family.',
        'notes' => 'In-person recruitment strategies, such as hosting booths at events and handing out flyers, are effective ways to connect with potential users. Hosting events and workshops, like birthday planning sessions or partner collaboration events, can attract new users. Additionally, incentivizing existing users through referral programs can help expand our user base.'
    ],
    11 => [
        'title' => 'Signup Process',
        'content' => 'Step-by-Step Guide to Signing Up Users:<br>- Visit the website, select a plan, fill in details, confirm signup.<br>Important Details to Highlight:<br>- Easy process, secure payment, immediate access to perks.<br>Common Issues and Solutions:<br>- Troubleshooting signup issues, contacting support.',
        'notes' => 'The signup process is straightforward: visit our website, select a plan, fill in the details, and confirm signup. Highlight the ease of the process, secure payment options, and immediate access to perks. Be prepared to troubleshoot common signup issues and provide contact information for support if needed.'
    ],
    12 => [
        'title' => 'Commission Structure',
        'content' => 'Overview of the Commission Structure:<br>- Percentages for different plans: Free (small bonus), Premium (higher), VIP (highest).<br>- Additional bonuses: Monthly targets, referral bonuses.<br>Payment Schedule:<br>- Monthly payouts, detailed reports.',
        'notes' => 'Let’s discuss the commission structure. You earn different percentages for each plan: a small bonus for Free Plan signups, higher for Premium, and the highest for VIP. Additional bonuses are available for meeting monthly targets and referring new users. Payments are made monthly, with detailed reports provided.'
    ],
    13 => [
        'title' => 'Commission Structure (Examples)',
        'content' => 'Example Calculations for Different Scenarios:<br>- Signing up 10 Premium users vs. 5 VIP users.<br>How to Maximize Earnings:<br>- Focus on higher-tier plans, encourage referrals.',
        'notes' => 'Here are some example calculations. Signing up 10 Premium users versus 5 VIP users can yield different earnings. To maximize your earnings, focus on promoting higher-tier plans and encourage referrals. This strategy can significantly boost your commissions.'
    ],
    14 => [
        'title' => 'Policies',
        'content' => 'Company Policies on Sales and Recruitment:<br>- Honesty, transparency, user-first approach.<br>Ethical Guidelines:<br>- No pressure tactics, respect user privacy.<br>Handling Customer Data:<br>- Secure storage, compliance with data protection laws.',
        'notes' => 'Birthday.Gold is committed to honesty, transparency, and a user-first approach in all sales and recruitment efforts. Our ethical guidelines prohibit pressure tactics and emphasize respecting user privacy. We ensure secure storage of customer data and comply with all data protection laws.'
    ],
    15 => [
        'title' => 'Policies (Cont.)',
        'content' => 'Policies on Refunds and Cancellations:<br>- Clear terms, easy process.<br>Conflict Resolution Procedures:<br>- Steps to resolve issues, support contacts.<br>Compliance Requirements:<br>- Adhering to legal standards, company policies.',
        'notes' => 'Our policies on refunds and cancellations are designed to be clear and straightforward, making the process easy for users. We have established procedures for conflict resolution, including steps to resolve issues and support contacts. Compliance with legal standards and company policies is a priority.'
    ],
    16 => [
        'title' => 'Tools and Resources',
        'content' => 'Sales Tools Provided by Birthday.Gold:<br>- CRM systems for tracking leads.<br>- Marketing materials: brochures, digital ads.<br>Training Resources:<br>- Online training modules, workshops.',
        'notes' => 'To support your sales efforts, Birthday.Gold provides various tools and resources. These include CRM systems for tracking leads, marketing materials like brochures and digital ads, and comprehensive training resources such as online modules and workshops.'
    ],
    17 => [
        'title' => 'Tools and Resources (Cont.)',
        'content' => 'Support Channels:<br>- Help desk: phone, email support.<br>- Peer support groups: online forums, social media groups.<br>Continuous Learning Opportunities:<br>- Webinars, advanced training sessions.',
        'notes' => 'We also offer robust support channels, including a help desk with phone and email support, and peer support groups on online forums and social media. Continuous learning opportunities are available through webinars and advanced training sessions to help you stay updated and improve your skills.'
    ],
    18 => [
        'title' => 'Success Stories',
        'content' => 'Case Studies of Successful Sales Representatives:<br>- Detailed stories, key strategies used.<br>Testimonials from Happy Users:<br>- Quotes, satisfaction levels, benefits experienced.',
        'notes' => 'Let’s look at some success stories. We have detailed case studies of successful sales representatives and the key strategies they used to achieve their goals. Additionally, testimonials from happy users highlight their satisfaction and the benefits they’ve experienced with Birthday.Gold.'
    ],
    19 => [
        'title' => 'Overcoming Challenges',
        'content' => 'Common Challenges in Sales:<br>- Rejection, competition, market saturation.<br>Strategies to Overcome Them:<br>- Persistence, improving pitches, focusing on user needs.<br>Maintaining Motivation:<br>- Setting goals, celebrating small wins.',
        'notes' => 'Sales can come with challenges like rejection, competition, and market saturation. To overcome these, practice persistence, continually improve your pitches, and focus on user needs. Maintain motivation by setting achievable goals and celebrating your small wins along the way.'
    ],
    20 => [
        'title' => 'Q&A',
        'content' => 'Open Floor for Questions:<br>- Encourage engagement, provide clear answers.<br>Addressing Common Queries:<br>- Pre-prepared answers for frequently asked questions.',
        'notes' => 'Now, we’d like to open the floor for questions. Please feel free to ask anything related to today’s presentation or any other queries you might have. We have also prepared answers for some common questions you might have.'
    ],
    21 => [
        'title' => 'Conclusion',
        'content' => 'Recap of Key Points:<br>- Summary of plans, target audience, sales tactics, policies.<br>Encouragement and Motivational Message:<br>- Empowering sales reps, highlighting potential success.<br>Contact Information for Further Support:<br>- Email, phone, support website.',
        'notes' => 'To conclude, we’ve covered the key points regarding our plans, target audience, sales tactics, and policies. We are here to empower you as sales representatives and highlight the potential success you can achieve. For any further support, you can reach out to us via email, phone, or visit our support website. Thank you for attending, and good luck!'
    ]
];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
} elseif ($page > max(array_keys($slides))) {
    $page = max(array_keys($slides));
}

// Calculate progress
$total_slides = count($slides);
$progress_percentage = ($page / $total_slides) * 100;

// Include page structure
$additionalstyles = '
<style>
    .presentation-container {
        min-height: 80vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 1rem;
        margin: 2rem auto;
        max-width: 1200px;
    }
    .slide-content {
        text-align: center;
        max-width: 900px;
    }
    .slide-title {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 2rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    .slide-body {
        font-size: 1.25rem;
        line-height: 1.8;
        text-align: left;
        background: rgba(255,255,255,0.1);
        padding: 2rem;
        border-radius: 0.5rem;
        backdrop-filter: blur(10px);
    }
    .slide-navigation {
        margin-top: 3rem;
        display: flex;
        justify-content: space-between;
        width: 100%;
        max-width: 600px;
    }
    .nav-button {
        padding: 0.75rem 2rem;
        background: rgba(255,255,255,0.2);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        transition: all 0.3s;
        border: 2px solid white;
        font-weight: 600;
    }
    .nav-button:hover {
        background: white;
        color: #764ba2;
        transform: translateY(-2px);
    }
    .nav-button.disabled {
        opacity: 0.3;
        pointer-events: none;
    }
    .slide-counter {
        text-align: center;
        margin-top: 1rem;
        font-size: 1.1rem;
        opacity: 0.9;
    }
    .progress-bar-container {
        width: 100%;
        max-width: 600px;
        height: 8px;
        background: rgba(255,255,255,0.2);
        border-radius: 4px;
        margin: 2rem auto;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        background: white;
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    .speaker-notes {
        margin-top: 2rem;
        padding: 1rem;
        background: rgba(0,0,0,0.2);
        border-radius: 0.5rem;
        font-size: 0.9rem;
        font-style: italic;
        max-width: 900px;
    }
    .controls {
        position: fixed;
        bottom: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
    }
    .control-button {
        padding: 0.5rem 1rem;
        background: rgba(0,0,0,0.5);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .control-button:hover {
        background: rgba(0,0,0,0.7);
    }
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/staff/">Staff Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Sales Training</li>
        </ol>
    </nav>
    
    <div class="presentation-container">
        <div class="slide-content">
            <h1 class="slide-title">' . htmlspecialchars($slides[$page]['title']) . '</h1>
            <div class="slide-body">' . nl2br(htmlspecialchars_decode($slides[$page]['content'])) . '</div>
            
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: ' . $progress_percentage . '%"></div>
            </div>
            
            <div class="slide-navigation">';

if ($page > min(array_keys($slides))) {
    echo '<a href="?page=' . ($page - 1) . '" class="nav-button">
            <i class="bi bi-chevron-left"></i> Previous
          </a>';
} else {
    echo '<span class="nav-button disabled">
            <i class="bi bi-chevron-left"></i> Previous
          </span>';
}

echo '<span class="slide-counter">Slide ' . $page . ' of ' . $total_slides . '</span>';

if ($page < max(array_keys($slides))) {
    echo '<a href="?page=' . ($page + 1) . '" class="nav-button">
            Next <i class="bi bi-chevron-right"></i>
          </a>';
} else {
    echo '<span class="nav-button disabled">
            Next <i class="bi bi-chevron-right"></i>
          </span>';
}

echo '
            </div>
            
            <details class="speaker-notes">
                <summary style="cursor: pointer; font-weight: bold;">Speaker Notes</summary>
                <p class="mt-2">' . htmlspecialchars($slides[$page]['notes']) . '</p>
            </details>
        </div>
    </div>
    
    <div class="controls">
        <button class="control-button" onclick="toggleSpeech()">
            <i class="bi bi-volume-up"></i> Read Aloud
        </button>
        <button class="control-button" onclick="toggleFullscreen()">
            <i class="bi bi-fullscreen"></i> Fullscreen
        </button>
    </div>
</div>

<script>
let speechUtterance = null;
let isSpeaking = false;

function toggleSpeech() {
    if (isSpeaking) {
        window.speechSynthesis.cancel();
        isSpeaking = false;
    } else {
        const notes = ' . json_encode($slides[$page]['notes']) . ';
        speechUtterance = new SpeechSynthesisUtterance(notes);
        speechUtterance.rate = 0.9;
        speechUtterance.pitch = 1;
        window.speechSynthesis.speak(speechUtterance);
        isSpeaking = true;
    }
}

function toggleFullscreen() {
    const elem = document.querySelector(".presentation-container");
    if (!document.fullscreenElement) {
        elem.requestFullscreen().catch(err => {
            console.log("Error attempting to enable fullscreen:", err);
        });
    } else {
        document.exitFullscreen();
    }
}

// Keyboard navigation
document.addEventListener("keydown", function(e) {
    if (e.key === "ArrowLeft" && ' . $page . ' > 1) {
        window.location.href = "?page=' . ($page - 1) . '";
    } else if (e.key === "ArrowRight" && ' . $page . ' < ' . $total_slides . ') {
        window.location.href = "?page=' . ($page + 1) . '";
    }
});
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
