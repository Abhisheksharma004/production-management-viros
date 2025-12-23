<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Initialize variables for dashboard stats (static values for now)
$total_products = 0;
$total_orders = 0;
$pending_orders = 0;
$completed_orders = 0;

$current_user = $_SESSION['username'];
$activePage = 'dashboard';
$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Production Management System</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $total_products; ?></h3>
                        <p>Total Products</p>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i> 12%
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i> 8%
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    <div class="stat-trend down">
                        <i class="fas fa-arrow-down"></i> 3%
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $completed_orders; ?></h3>
                        <p>Completed Orders</p>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i> 15%
                    </div>
                </div>
            </div>

            <!-- Charts and Tables -->
            <div class="content-grid">
                <!-- Production Overview -->
                <div class="card chart-card">
                    <div class="card-header">
                        <h3>Production Overview</h3>
                        <select class="filter-select">
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                            <option>Last 3 Months</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <div class="production-stats">
                            <div class="production-day">
                                <span class="day-label">Mon</span>
                                <div class="bar-container">
                                    <div class="bar production" style="height: 65%;"></div>
                                    <div class="bar target" style="height: 70%;"></div>
                                </div>
                                <span class="day-value">65</span>
                            </div>
                            <div class="production-day">
                                <span class="day-label">Tue</span>
                                <div class="bar-container">
                                    <div class="bar production" style="height: 78%;"></div>
                                    <div class="bar target" style="height: 75%;"></div>
                                </div>
                                <span class="day-value">78</span>
                            </div>
                            <div class="production-day">
                                <span class="day-label">Wed</span>
                                <div class="bar-container">
                                    <div class="bar production" style="height: 85%;"></div>
                                    <div class="bar target" style="height: 80%;"></div>
                                </div>
                                <span class="day-value">85</span>
                            </div>
                            <div class="production-day">
                                <span class="day-label">Thu</span>
                                <div class="bar-container">
                                    <div class="bar production" style="height: 81%;"></div>
                                    <div class="bar target" style="height: 85%;"></div>
                                </div>
                                <span class="day-value">81</span>
                            </div>
                            <div class="production-day">
                                <span class="day-label">Fri</span>
                                <div class="bar-container">
                                    <div class="bar production" style="height: 92%;"></div>
                                    <div class="bar target" style="height: 90%;"></div>
                                </div>
                                <span class="day-value">92</span>
                            </div>
                            <div class="production-day">
                                <span class="day-label">Sat</span>
                                <div class="bar-container">
                                    <div class="bar production" style="height: 88%;"></div>
                                    <div class="bar target" style="height: 90%;"></div>
                                </div>
                                <span class="day-value">88</span>
                            </div>
                            <div class="production-day">
                                <span class="day-label">Sun</span>
                                <div class="bar-container">
                                    <div class="bar production" style="height: 95%;"></div>
                                    <div class="bar target" style="height: 90%;"></div>
                                </div>
                                <span class="day-value">95</span>
                            </div>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <span class="legend-color production"></span>
                                <span>Production Output</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color target"></span>
                                <span>Target</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="card">
                    <div class="card-header">
                        <h3>Low Stock Alert</h3>
                        <span class="alert-badge">5</span>
                    </div>
                    <div class="card-body">
                        <div class="alert-list">
                            <div class="alert-item">
                                <div class="alert-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="alert-details">
                                    <h4>Raw Material A</h4>
                                    <p>Only 15 units left</p>
                                </div>
                                <button class="btn-small">Order</button>
                            </div>
                            <div class="alert-item">
                                <div class="alert-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="alert-details">
                                    <h4>Component B</h4>
                                    <p>Only 8 units left</p>
                                </div>
                                <button class="btn-small">Order</button>
                            </div>
                            <div class="alert-item">
                                <div class="alert-icon danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="alert-details">
                                    <h4>Part C</h4>
                                    <p>Out of stock</p>
                                </div>
                                <button class="btn-small urgent">Urgent</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Activities</h3>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-icon blue">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>New order</strong> #ORD-005 created</p>
                                    <span class="time">2 minutes ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon green">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Order #ORD-002</strong> completed</p>
                                    <span class="time">1 hour ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon orange">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Inventory updated</strong> for Product A</p>
                                    <span class="time">3 hours ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon purple">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>New user</strong> John Doe added</p>
                                    <span class="time">5 hours ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>
</body>
</html>
