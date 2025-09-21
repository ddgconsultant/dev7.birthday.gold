<?php
$user_name = "John Doe";
$user_birthday = "June 22, 1990";
$rewards_earned = 22.35;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birthday Rewards Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #343a40;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #fff;
        }
        .nav-tabs .nav-link.active {
            background-color: #ffc107;
            color: #343a40;
        }
        .nav-link {
            color: #007bff;
        }
        .card {
            margin-bottom: 20px;
        }
        .gold-text {
            color: #ffc107;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-md-3 header">
            <img src="profile-pic.jpg" alt="Profile Picture" class="profile-pic">
            <h1>Happy Birthday!</h1>
            <p><?php echo $user_name; ?></p>
            <p><?php echo $user_birthday; ?></p>
        </div>
        <div class="col-md-9">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="collect-tab" data-bs-toggle="tab" href="#collect" role="tab" aria-controls="collect" aria-selected="true">Collect</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="redeem-tab" data-bs-toggle="tab" href="#redeem" role="tab" aria-controls="redeem" aria-selected="false">Redeem</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history" role="tab" aria-controls="history" aria-selected="false">History</a>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="collect" role="tabpanel" aria-labelledby="collect-tab">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Rewards Earned</h5>
                            <p class="card-text"><?php echo $rewards_earned; ?> points</p>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="redeem" role="tabpanel" aria-labelledby="redeem-tab">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Available Offers</h5>
                            <p class="card-text">Here are the offers you can redeem with your points.</p>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Redeemed Rewards</h5>
                            <p class="card-text">Here is the history of your redeemed rewards.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
