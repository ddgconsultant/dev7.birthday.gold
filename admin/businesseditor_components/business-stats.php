<?php
if (!isset($componentmode) || $componentmode != 'include') {
// Include the site-controller.php file
include_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';
}

#include($dir['core_components'] . '/bg_pagestart.inc');
#include($dir['core_components'] . '/bg_header.inc');

$additionalstyles.='
    <style>
        body {
            padding-top: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        .chart-container {
            width: 100%;
            height: 300px;
        }
    </style>
    ';
    ?>
    <div class="container">
        <h1 class="text-center mb-4">Business Dashboard</h1>
        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Users Signed Up</h5>
                        <p class="card-text" id="users-signed-up">15,000</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Number of Rewards</h5>
                        <p class="card-text" id="rewards-count">2,500</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Value of Rewards</h5>
                        <p class="card-text" id="rewards-value">$45,000</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Registration Speed</h5>
                        <p class="card-text" id="registration-speed">2 mins</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">User Growth Over Time</h5>
                        <canvas id="user-growth-chart" class="chart-container"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Rewards Distribution</h5>
                        <canvas id="rewards-distribution-chart" class="chart-container"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sample data for charts
        const userGrowthData = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Users Signed Up',
                data: [1000, 2000, 3000, 4000, 5000, 6000],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        };

        const rewardsDistributionData = {
            labels: ['Reward A', 'Reward B', 'Reward C', 'Reward D'],
            datasets: [{
                label: 'Rewards',
                data: [1000, 500, 800, 200],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        };

        // Rendering charts
        const userGrowthChart = new Chart(document.getElementById('user-growth-chart'), {
            type: 'line',
            data: userGrowthData,
        });

        const rewardsDistributionChart = new Chart(document.getElementById('rewards-distribution-chart'), {
            type: 'bar',
            data: rewardsDistributionData,
        });
    </script>
    