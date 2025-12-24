<?php
session_start();

// Check if line user is logged in
if (!isset($_SESSION['line_id']) || $_SESSION['user_type'] !== 'line') {
    header("Location: line-login.php");
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Initialize variables for dashboard stats (will be populated later)
$total_production = 0;
$today_production = 0;
$pending_items = 0;
$completed_items = 0;

$line_name = $_SESSION['line_name'];
$line_email = $_SESSION['line_email'];
$activePage = 'dashboard';
$pageTitle = 'Line Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line Dashboard - <?php echo htmlspecialchars($line_name); ?></title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom styling for line dashboard header */
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            padding: 12px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .header-left .page-title h1 {
            color: white;
            font-size: 22px;
            font-weight: 600;
            margin: 0 0 3px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-left .page-title p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .user-avatar {
            font-size: 32px;
            color: white;
            display: flex;
            align-items: center;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        
        .user-name {
            color: white;
            font-size: 13px;
            font-weight: 600;
        }
        
        .user-role {
            color: rgba(255, 255, 255, 0.7);
            font-size: 11px;
        }
        
        .logout {
            color: white;
            font-size: 20px;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .logout:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        /* Improve sidebar for line dashboard */
        .sidebar .logo {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px 20px;
        }

        .sidebar .logo h2 {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <?php 
    // Create a custom sidebar for line users
    ?>
    <div class="sidebar">
        <div class="logo">
            <img src="images/logo.jpg" alt="Viros Logo" class="logo-img">
            <h2>Production Line</h2>
        </div>
        <nav class="nav-menu">
            <a href="line-dashboard.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="material-in.php" class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <span>Material In</span>
            </a>
            <a href="#" class="nav-item" onclick="alert('Production Records coming soon'); return false;">
                <i class="fas fa-list"></i>
                <span>Production Records</span>
            </a>
            <a href="#" class="nav-item" onclick="alert('Daily Report coming soon'); return false;">
                <i class="fas fa-chart-bar"></i>
                <span>Daily Report</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php 
        // Create a custom header for line users
        ?>
        <div class="header">
            <div class="header-left">
                <div class="page-title">
                    <h1><i class="fas fa-home"></i> <?php echo htmlspecialchars($line_name); ?></h1>
                    <p>Production Line Dashboard</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($line_email); ?></span>
                        <span class="user-role">Production Line Operator</span>
                    </div>
                </div>
                <a href="line-logout.php" class="logout" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $total_production; ?></h3>
                        <p>Total Production</p>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i> 12%
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $today_production; ?></h3>
                        <p>Today's Production</p>
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
                        <h3><?php echo $pending_items; ?></h3>
                        <p>Pending Items</p>
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
                        <h3><?php echo $completed_items; ?></h3>
                        <p>Completed Items</p>
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

                <!-- Today's Schedule -->
                <div class="card">
                    <div class="card-header">
                        <h3>Today's Schedule</h3>
                        <span class="badge">5 Tasks</span>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-icon blue">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Morning Shift</strong> starts at 8:00 AM</p>
                                    <span class="time">In 2 hours</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon green">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Quality Check</strong> completed</p>
                                    <span class="time">1 hour ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon orange">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Machine Maintenance</strong> scheduled</p>
                                    <span class="time">At 2:00 PM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Production -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Production Entries</h3>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-icon blue">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Batch #B-125</strong> Entry created</p>
                                    <span class="time">10 minutes ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon green">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Batch #B-124</strong> Completed</p>
                                    <span class="time">1 hour ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon purple">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong>Part A-100</strong> Production started</p>
                                    <span class="time">3 hours ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
