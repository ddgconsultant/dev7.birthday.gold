// Analytics Dashboard Scripts

document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if data is available
    if (window.analyticsData) {
        initializeUserGrowthChart();
        initializeRewardDistributionChart();
    }
    
    // Time range buttons
    document.querySelectorAll('.time-range').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.time-range').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update chart based on selected range
            const range = this.dataset.range;
            updateUserGrowthChart(range);
        });
    });
});

// User Growth Chart
let userGrowthChart;

function initializeUserGrowthChart() {
    const ctx = document.getElementById('userGrowthChart');
    if (!ctx) return;
    
    const data = window.analyticsData.userGrowth || [];
    
    // Prepare data for Chart.js
    const labels = data.map(item => item.month);
    const values = data.map(item => parseInt(item.new_users));
    
    // Calculate cumulative values
    let cumulative = 0;
    const cumulativeValues = values.map(value => {
        cumulative += value;
        return cumulative;
    });
    
    userGrowthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Users',
                data: cumulativeValues,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#0d6efd',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }, {
                label: 'New Users',
                data: values,
                borderColor: '#6c757d',
                backgroundColor: 'rgba(108, 117, 125, 0.1)',
                tension: 0.3,
                fill: false,
                pointRadius: 3,
                pointHoverRadius: 5,
                borderDash: [5, 5]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#dee2e6',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 8
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

function updateUserGrowthChart(range) {
    if (!userGrowthChart) return;
    
    const data = window.analyticsData.userGrowth || [];
    let filteredData = data;
    
    // Filter data based on range
    if (range === '6m') {
        filteredData = data.slice(-6);
    } else if (range === '1y') {
        filteredData = data.slice(-12);
    }
    
    // Update chart data
    const labels = filteredData.map(item => item.month);
    const values = filteredData.map(item => parseInt(item.new_users));
    
    // Calculate cumulative values
    let cumulative = 0;
    const cumulativeValues = values.map(value => {
        cumulative += value;
        return cumulative;
    });
    
    userGrowthChart.data.labels = labels;
    userGrowthChart.data.datasets[0].data = cumulativeValues;
    userGrowthChart.data.datasets[1].data = values;
    userGrowthChart.update();
}

// Reward Distribution Chart
let rewardDistributionChart;

function initializeRewardDistributionChart() {
    const ctx = document.getElementById('rewardDistributionChart');
    if (!ctx) return;
    
    const data = window.analyticsData.rewardDistribution || [];
    
    // Prepare data for Chart.js
    const labels = data.map(item => item.category || 'Uncategorized');
    const values = data.map(item => parseInt(item.count));
    const totalValues = data.map(item => parseFloat(item.total_value || 0));
    
    // Generate colors
    const colors = [
        '#0d6efd',
        '#6610f2',
        '#6f42c1',
        '#d63384',
        '#dc3545',
        '#fd7e14',
        '#ffc107',
        '#28a745',
        '#20c997',
        '#17a2b8'
    ];
    
    rewardDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#dee2e6',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            const totalValue = totalValues[context.dataIndex];
                            
                            return [
                                `${label}: ${value} rewards (${percentage}%)`,
                                `Total Value: $${totalValue.toFixed(2)}`
                            ];
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
}