<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$pagetitle = "Your @birthday.gold Email Address";
$bodycontentclass = 'bg-light';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container py-5">
  <div class="text-center mb-5">
    <h1 class="display-5 fw-bold text-gold">Your @birthday.gold Email Address</h1>
    <p class="lead">Stay organized. Stay private. Never miss a reward again.</p>
  </div>

  <div class="row align-items-center mb-5">
    <div class="col-md-6">
      <h3 class="fw-semibold">📥 One Email. Zero Clutter.</h3>
      <p class="mb-3">Use your @birthday.gold address when signing up for rewards programs. Businesses send their offers to this email instead of your personal one — keeping your real inbox clean and private.</p>
      <ul class="list-group list-group-flush">
        <li class="list-group-item">✅ Protects your real email from spam</li>
        <li class="list-group-item">✅ Keeps all rewards and deals in one place</li>
        <li class="list-group-item">✅ AI filters only the most valuable messages</li>
      </ul>
    </div>
    <div class="col-md-6 text-center">
      <img src="/public/images/feature-inbox_inbox.png" alt="Organized Inbox" class="img-fluid" style="max-height:300px">
    </div>
  </div>

  <div class="row mb-5">
    <div class="col-md-6 text-center">
      <img src="/public/images/feature-inbox-ai.png" alt="Goldie AI Email Sorting" class="img-fluid" style="max-height:300px">
    </div>
    <div class="col-md-6">
      <h3 class="fw-semibold">🧠 Powered by Goldie, Your AI Inbox Assistant</h3>
      <p>Goldie automatically sorts every message that comes into your @birthday.gold inbox, so you don't have to dig through junk mail or worry about missing your rewards.</p>
      <ul class="list-group list-group-flush">
        <li class="list-group-item">📌 Filters: Rewards, Deals, and Other</li>
        <li class="list-group-item">🔕 Silently blocks pure marketing fluff</li>
        <li class="list-group-item">🔔 Sends timely notifications only when it matters</li>
      </ul>
    </div>
  </div>

  <div class="row mb-5 bg-white p-4 rounded shadow-sm">
    <div class="col-md-12">
      <h3 class="fw-semibold text-center mb-4">📊 Why It Matters</h3>
      <div class="row text-center">
        <div class="col-md-4 mb-3">
          <h1 class="display-6 fw-bold text-primary">121</h1>
          <p class="mb-0">marketing emails the average person receives *per day*</p>
        </div>
        <div class="col-md-4 mb-3">
          <h1 class="display-6 fw-bold text-danger">85%</h1>
          <p class="mb-0">of people miss at least one reward offer due to inbox clutter</p>
        </div>
        <div class="col-md-4 mb-3">
          <h1 class="display-6 fw-bold text-success">3x</h1>
          <p class="mb-0">more likely to redeem a reward when it's separated from noise</p>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center my-5">
    <h4 class="fw-semibold">🎁 Available Only on Gold Plans</h4>
    <p class="mb-4">Get your personalized <strong>you@birthday.gold</strong> email address when you upgrade to any Gold Plan.</p>
    <a href="/plans" class="btn btn-lg btn-warning px-4">Upgrade to Gold</a>
  </div>
</div>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage(); exit;
?>
