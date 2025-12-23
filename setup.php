<?php
/**
 * Database Setup Script
 * Run this file once through browser to create all necessary tables
 * URL: http://localhost/production-management/setup.php
 */

// Include database configuration
require_once 'config/database.php';

$setupStatus = [];
$errors = [];

// Function to execute SQL query
function executeQuery($conn, $sql, $description) {
    global $setupStatus, $errors;
    
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        $errors[] = $description . " - Failed: " . print_r(sqlsrv_errors(), true);
        $setupStatus[] = ['status' => 'error', 'message' => $description . " - Failed"];
        return false;
    } else {
        $setupStatus[] = ['status' => 'success', 'message' => $description . " - Success"];
        return true;
    }
}

// Get connection
$conn = getSQLSrvConnection();

// 1. Create Users Table
$sql = "
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='users' AND xtype='U')
BEGIN
    CREATE TABLE users (
        id INT IDENTITY(1,1) PRIMARY KEY,
        username NVARCHAR(100) NOT NULL,
        email NVARCHAR(100) NOT NULL UNIQUE,
        password NVARCHAR(255) NOT NULL,
        role NVARCHAR(50) DEFAULT 'user',
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
    )
END
";
executeQuery($conn, $sql, "Create Users Table");

// 2. Insert Default Admin User
$sql = "
IF NOT EXISTS (SELECT * FROM users WHERE email = 'production@viros.com')
BEGIN
    INSERT INTO users (username, email, password, role, created_at, updated_at)
    VALUES ('Admin', 'production@viros.com', 'Admin@2025', 'admin', GETDATE(), GETDATE())
END
";
executeQuery($conn, $sql, "Insert Default Admin User");

// 3. Create Lines Table
$sql = "
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='lines' AND xtype='U')
BEGIN
    CREATE TABLE lines (
        id INT IDENTITY(1,1) PRIMARY KEY,
        line_name NVARCHAR(100) NOT NULL,
        user_email NVARCHAR(100) NOT NULL UNIQUE,
        password NVARCHAR(255) NOT NULL,
        status NVARCHAR(20) DEFAULT 'Active',
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
    )
END
";
executeQuery($conn, $sql, "Create Lines Table");

// Close connection
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Production Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        
        .setup-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .status-list {
            list-style: none;
            padding: 0;
        }
        
        .status-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
            display: inline-block;
            width: 120px;
        }
        
        .btn-login {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .summary {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>🚀 Database Setup Complete</h1>
            <p>Production Management System - SQL Server</p>
        </div>
        
        <div class="summary">
            <h3>Setup Summary</h3>
            <div class="summary-stats">
                <div class="stat">
                    <div class="stat-number"><?php echo count(array_filter($setupStatus, fn($s) => $s['status'] === 'success')); ?></div>
                    <div class="stat-label">Successful</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo count(array_filter($setupStatus, fn($s) => $s['status'] === 'error')); ?></div>
                    <div class="stat-label">Errors</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo count($setupStatus); ?></div>
                    <div class="stat-label">Total Operations</div>
                </div>
            </div>
        </div>
        
        <h3>Setup Operations:</h3>
        <ul class="status-list">
            <?php foreach ($setupStatus as $status): ?>
                <li class="status-item <?php echo $status['status']; ?>">
                    <span class="status-icon"><?php echo $status['status'] === 'success' ? '✓' : '✗'; ?></span>
                    <span><?php echo htmlspecialchars($status['message']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <?php if (!empty($errors)): ?>
            <div class="credentials-box" style="background: #fee; border-color: #dc2626;">
                <h3 style="color: #991b1b;">⚠️ Errors Encountered:</h3>
                <?php foreach ($errors as $error): ?>
                    <div style="color: #991b1b; padding: 5px 0; font-size: 12px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="credentials-box">
            <h3>🔐 Default Login Credentials</h3>
            <div class="credential-item">
                <strong>Email:</strong> production@viros.com
            </div>
            <div class="credential-item">
                <strong>Password:</strong> Admin@2025
            </div>
            <div class="credential-item">
                <strong>Role:</strong> Administrator
            </div>
        </div>
        
        <div style="background: #fef3c7; padding: 15px; border-radius: 5px; border-left: 4px solid #f59e0b; margin: 20px 0;">
            <strong>⚠️ Security Notice:</strong> For security reasons, please delete this setup.php file after successful setup.
        </div>
        
        <div style="text-align: center;">
            <a href="index.php" class="btn-login">Go to Login Page →</a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 12px; text-align: center;">
            <p><strong>Database:</strong> production_viros</p>
            <p><strong>Server:</strong> MSI\SQLEXPRESS</p>
            <p><strong>Tables Created:</strong> users</p>
        </div>
    </div>
</body>
</html>
