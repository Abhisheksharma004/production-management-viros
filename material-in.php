<?php
session_start();

// Check if line user is logged in
if (!isset($_SESSION['line_id']) || $_SESSION['user_type'] !== 'line') {
    header("Location: line-login.php");
    exit();
}

// Include database configuration
require_once 'config/database.php';

$line_id = $_SESSION['line_id'];
$line_name = $_SESSION['line_name'];
$line_email = $_SESSION['line_email'];
$activePage = 'material-in';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $conn = getSQLSrvConnection();
    $materialId = intval($_POST['material_id']);
    
    $deleteSql = "DELETE FROM material_in WHERE id = ? AND line_id = ?";
    if (sqlsrv_query($conn, $deleteSql, array($materialId, $line_id))) {
        $_SESSION['message'] = "Material record deleted successfully!";
        $_SESSION['messageType'] = 'success';
    } else {
        $_SESSION['message'] = "Error deleting material record!";
        $_SESSION['messageType'] = 'error';
    }
    
    header("Location: material-in.php");
    exit();
}

// Handle close production action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'close_production') {
    $conn = getSQLSrvConnection();
    $materialId = intval($_POST['material_id']);
    $finalProductionQuantity = intval($_POST['final_production_quantity']);
    $scrapQuantity = intval($_POST['scrap_quantity']);
    $closedAt = date('Y-m-d H:i:s');
    
    // Update the material record with production closure data
    $updateSql = "UPDATE material_in 
                  SET final_production_quantity = ?, 
                      scrap_quantity = ?, 
                      production_status = 'Closed',
                      closed_at = ?
                  WHERE id = ? AND line_id = ?";
    
    $params = array($finalProductionQuantity, $scrapQuantity, $closedAt, $materialId, $line_id);
    
    if (sqlsrv_query($conn, $updateSql, $params)) {
        $_SESSION['message'] = "Production closed successfully! Final Production: $finalProductionQuantity, Scrap: $scrapQuantity";
        $_SESSION['messageType'] = 'success';
    } else {
        $_SESSION['message'] = "Error closing production!";
        $_SESSION['messageType'] = 'error';
    }
    
    header("Location: material-in.php");
    exit();
}

// Handle update action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $conn = getSQLSrvConnection();
    
    $materialId = intval($_POST['material_id']);
    $partId = intval($_POST['part_id']);
    $in_quantity = intval($_POST['in_quantity']);
    $in_units = trim($_POST['in_units']);
    $production_quantity = intval($_POST['production_quantity']);
    $production_units = trim($_POST['production_units']);
    $receivedDate = $_POST['received_date'];
    $receivedTime = $_POST['received_time'];
    $receivedDateTime = $receivedDate . ' ' . $receivedTime . ':00';
    $batchNumber = trim($_POST['batch_number']);
    
    // Get part information
    $sql = "SELECT part_code, part_name FROM parts WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, array($partId));
    
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partCode = $row['part_code'];
        $partName = $row['part_name'];
        
        // Update material receipt record
        $updateSql = "UPDATE material_in SET part_id = ?, part_code = ?, part_name = ?, in_quantity = ?, in_units = ?, production_quantity = ?, production_units = ?, received_date = ?, batch_number = ? WHERE id = ? AND line_id = ?";
        $params = array($partId, $partCode, $partName, $in_quantity, $in_units, $production_quantity, $production_units, $receivedDateTime, $batchNumber, $materialId, $line_id);
        
        if (sqlsrv_query($conn, $updateSql, $params)) {
            $_SESSION['message'] = "Material record updated successfully! Batch: $batchNumber";
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = "Error updating material record!";
            $_SESSION['messageType'] = 'error';
        }
        
        sqlsrv_free_stmt($stmt);
    }
    
    header("Location: material-in.php");
    exit();
}

// Handle AJAX request to get material data
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['get_material']) && isset($_GET['id'])) {
    $conn = getSQLSrvConnection();
    $materialId = intval($_GET['id']);
    
    $sql = "SELECT * FROM material_in WHERE id = ? AND line_id = ?";
    $stmt = sqlsrv_query($conn, $sql, array($materialId, $line_id));
    
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Format received_date for output
        if ($row['received_date'] instanceof DateTime) {
            $dateTime = $row['received_date'];
            $row['received_date_only'] = $dateTime->format('Y-m-d');
            $row['received_time_only'] = $dateTime->format('H:i');
        } else {
            $timestamp = strtotime($row['received_date']);
            $row['received_date_only'] = date('Y-m-d', $timestamp);
            $row['received_time_only'] = date('H:i', $timestamp);
        }
        
        header('Content-Type: application/json');
        echo json_encode($row);
        sqlsrv_free_stmt($stmt);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Material not found']);
    }
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $conn = getSQLSrvConnection();
    
    $partId = intval($_POST['part_id']);
    $in_quantity = intval($_POST['in_quantity']);
    $in_units = trim($_POST['in_units']);
    $production_quantity = intval($_POST['production_quantity']);
    $production_units = trim($_POST['production_units']);
    $receivedDate = $_POST['received_date'];
    $receivedTime = $_POST['received_time'];
    $receivedDateTime = $receivedDate . ' ' . $receivedTime . ':00'; // Add seconds for proper datetime format
    $batchNumber = trim($_POST['batch_number']);
    
    // Get part information
    $sql = "SELECT part_code, part_name FROM parts WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, array($partId));
    
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $partCode = $row['part_code'];
        $partName = $row['part_name'];
        
        // Insert material receipt record
        $insertSql = "INSERT INTO material_in (line_id, part_id, part_code, part_name, in_quantity, in_units, production_quantity, production_units, received_date, batch_number, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array($line_id, $partId, $partCode, $partName, $in_quantity, $in_units, $production_quantity, $production_units, $receivedDateTime, $batchNumber, date('Y-m-d H:i:s'));
        
        if (sqlsrv_query($conn, $insertSql, $params)) {
            $_SESSION['message'] = "Material received successfully! Batch: $batchNumber";
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = "Error recording material receipt!";
            $_SESSION['messageType'] = 'error';
        }
        
        sqlsrv_free_stmt($stmt);
    }
    
    header("Location: material-in.php");
    exit();
}

// Fetch parts for dropdown
$parts = [];
try {
    $conn = getSQLSrvConnection();
    if ($conn !== false) {
        $sql = "SELECT id, part_code, part_name FROM parts WHERE status = 'Active' ORDER BY part_code";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $parts[] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
    }
} catch (Exception $e) {
    // Handle error
}

// Fetch recent material receipts
$recentMaterials = [];
try {
    if ($conn !== false) {
        $sql = "SELECT TOP 10 * FROM material_in WHERE line_id = ? ORDER BY created_at DESC";
        $stmt = sqlsrv_query($conn, $sql, array($line_id));
        
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $recentMaterials[] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
    }
} catch (Exception $e) {
    // Handle error
}

// Display session messages
$message = '';
$messageType = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material In - <?php echo htmlspecialchars($line_name); ?></title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/line-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom styling for material in page */
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

        .sidebar .logo {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px 20px;
        }

        .sidebar .logo h2 {
            font-size: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .recent-materials-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 0;
        }

        .recent-materials-table th,
        .recent-materials-table td {
            padding: 14px 16px;
            text-align: left;
        }

        .recent-materials-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .recent-materials-table th {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .recent-materials-table th:first-child {
            border-radius: 8px 0 0 0;
        }

        .recent-materials-table th:last-child {
            border-radius: 0 8px 0 0;
        }

        .recent-materials-table tbody tr {
            background: white;
            transition: all 0.2s;
            border-bottom: 1px solid #e2e8f0;
        }

        .recent-materials-table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .recent-materials-table tbody tr:last-child td:first-child {
            border-radius: 0 0 0 8px;
        }

        .recent-materials-table tbody tr:last-child td:last-child {
            border-radius: 0 0 8px 0;
        }

        .recent-materials-table td {
            color: #1e293b;
            font-size: 14px;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-top: 20px;
        }

        .table-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-header h3 {
            margin: 0 0 4px 0;
            font-size: 18px;
            color: #1e293b;
            font-weight: 600;
        }

        .table-header p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        .table-container {
            overflow-x: auto;
        }

        .part-code {
            font-weight: 600;
            color: #3b82f6;
            font-family: monospace;
            font-size: 13px;
        }

        .batch-badge {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            color: #475569;
            font-size: 12px;
            font-family: monospace;
        }

        .quantity-cell {
            font-weight: 600;
            color: #059669;
        }

        .quantity-unit {
            font-size: 12px;
            color: #64748b;
            font-weight: 400;
        }

        .date-cell {
            color: #64748b;
            font-size: 13px;
        }

        .date-cell .date-day {
            display: block;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            margin: 0 0 8px 0;
            color: #475569;
            font-size: 18px;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background: #3b82f6;
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-update {
            background: #10b981;
            color: white;
        }

        .btn-update:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-close-production {
            background: #ef4444;
            color: white;
        }

        .btn-close-production:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-open {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-closed {
            background: #f3f4f6;
            color: #4b5563;
        }

        /* Error Panel Styles */
        .error-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fee2e2;
            border-top: 3px solid #ef4444;
            box-shadow: 0 -4px 12px rgba(239, 68, 68, 0.15);
            z-index: 9999;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }
            to {
                transform: translateY(0);
            }
        }

        .error-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 30px;
        }

        .error-header {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #991b1b;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .error-header i {
            font-size: 20px;
        }

        .error-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 28px;
            color: #991b1b;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .error-close:hover {
            background: #fca5a5;
        }

        .error-message {
            background: white;
            padding: 12px;
            border-radius: 6px;
            color: #1e293b;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
            border-left: 4px solid #ef4444;
            max-height: 200px;
            overflow-y: auto;
        }

        .error-timestamp {
            margin-top: 8px;
            font-size: 11px;
            color: #991b1b;
            font-style: italic;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 25px;
        }

        .btn-add-material {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-add-material:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="images/logo.jpg" alt="Viros Logo" class="logo-img">
            <h2><?php echo htmlspecialchars($line_name); ?></h2>
        </div>
        <nav class="nav-menu">
            <a href="line-dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="material-in.php" class="nav-item active">
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

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <div class="page-title">
                    <h1><i class="fas fa-plus-circle"></i> Material In</h1>
                    <p>Record incoming materials and parts</p>
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

        <div class="dashboard-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div class="page-title">
                    <h2>Material In Records</h2>
                    <p>View and manage incoming materials</p>
                </div>
                <button class="btn-add-material" onclick="openModal()">
                    <i class="fas fa-plus-circle"></i> Add New Material
                </button>
            </div>

            <!-- Modal Form -->
            <div id="materialModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modal_title"><i class="fas fa-plus-circle"></i> Record Material Receipt</h2>
                        <button class="modal-close" onclick="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" id="materialInForm">
                            <input type="hidden" name="action" id="form_action" value="add">
                            <input type="hidden" name="material_id" id="material_id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="part_id">Select Part *</label>
                            <select id="part_id" name="part_id" required>
                                <option value="">-- Select Part --</option>
                                <?php foreach ($parts as $part): ?>
                                    <option value="<?php echo $part['id']; ?>">
                                        <?php echo htmlspecialchars($part['part_code'] . ' - ' . $part['part_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="received_date">Received Date *</label>
                            <input type="date" id="received_date" name="received_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="received_time">Received Time *</label>
                            <input type="time" id="received_time" name="received_time" 
                                   value="<?php echo date('H:i'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="in_quantity">In Quantity *</label>
                            <input type="number" id="in_quantity" name="in_quantity" min="1" required placeholder="Enter quantity">
                        </div>

                        <div class="form-group">
                            <label for="in_units">In Units *</label>
                            <select id="in_units" name="in_units" required>
                                <option value="">-- Select Unit --</option>
                                <option value="Pcs">Pieces (Pcs)</option>
                                <option value="Kg">Kilograms (Kg)</option>
                                <option value="Gm">Grams (Gm)</option>
                                <option value="Ltr">Liters (Ltr)</option>
                                <option value="Mtr">Meters (Mtr)</option>
                                <option value="Box">Box</option>
                                <option value="Set">Set</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="production_quantity">Production Quantity *</label>
                            <input type="number" id="production_quantity" name="production_quantity" min="1" required placeholder="Enter production quantity">
                        </div>

                        <div class="form-group">
                            <label for="production_units">Production Units *</label>
                            <select id="production_units" name="production_units" required>
                                <option value="">-- Select Unit --</option>
                                <option value="Pcs">Pieces (Pcs)</option>
                                <option value="Kg">Kilograms (Kg)</option>
                                <option value="Gm">Grams (Gm)</option>
                                <option value="Ltr">Liters (Ltr)</option>
                                <option value="Mtr">Meters (Mtr)</option>
                                <option value="Box">Box</option>
                                <option value="Set">Set</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="batch_number">Batch Number *</label>
                            <input type="text" id="batch_number" name="batch_number" required 
                                   placeholder="Enter batch/lot number">
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: 20px; display: flex; gap: 15px;">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Record Material
                        </button>
                        <button type="reset" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                        <button type="button" class="btn-secondary" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Close Production Modal -->
            <div id="closeProductionModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><i class="fas fa-check-circle"></i> Close Production</h2>
                        <button class="modal-close" onclick="closeProductionModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" id="closeProductionForm">
                            <input type="hidden" name="action" value="close_production">
                            <input type="hidden" name="material_id" id="close_material_id" value="">
                            
                            <div class="info-section" style="background: #f0f9ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                                <h3 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;"><i class="fas fa-info-circle"></i> Batch Information</h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <strong>Batch Number:</strong>
                                        <p id="close_batch_number" style="margin: 5px 0; color: #334155;">-</p>
                                    </div>
                                    <div>
                                        <strong>Part Code:</strong>
                                        <p id="close_part_code" style="margin: 5px 0; color: #334155;">-</p>
                                    </div>
                                    <div>
                                        <strong>Expected Production:</strong>
                                        <p id="close_expected_quantity" style="margin: 5px 0; color: #334155;">-</p>
                                    </div>
                                    <div>
                                        <strong>Material In:</strong>
                                        <p id="close_in_quantity" style="margin: 5px 0; color: #334155;">-</p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="final_production_quantity">Final Production Quantity *</label>
                                    <input type="number" id="final_production_quantity" name="final_production_quantity" 
                                           min="0" required placeholder="Enter actual production quantity">
                                    <small style="color: #64748b;">Enter the total quantity successfully produced</small>
                                </div>

                                <div class="form-group">
                                    <label for="scrap_quantity">Scrap Quantity *</label>
                                    <input type="number" id="scrap_quantity" name="scrap_quantity" 
                                           min="0" required placeholder="Enter scrap/waste quantity">
                                    <small style="color: #64748b;">Enter the quantity rejected or wasted</small>
                                </div>
                            </div>

                            <div class="form-actions" style="margin-top: 20px; display: flex; gap: 15px;">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-check-circle"></i> Close Production
                                </button>
                                <button type="button" class="btn-secondary" onclick="closeProductionModal()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Material In Records Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3><i class="fas fa-clipboard-list"></i> Material In Records</h3>
                    <p>Recent material receipts and inventory tracking</p>
                </div>
                
                <?php if (!empty($recentMaterials)): ?>
                <div class="table-container">
                    <table class="recent-materials-table">
                        <thead>
                            <tr>
                                <th><i class="far fa-calendar-alt"></i> Date & Time</th>
                                <th><i class="fas fa-barcode"></i> Part Code</th>
                                <th style="display: none;"><i class="fas fa-box"></i> Part Name</th>
                                <th style="width: 120px;"><i class="fas fa-arrow-down"></i> In Quantity</th>
                                <th style="width: 120px;"><i class="fas fa-industry"></i> Production Qty</th>
                                <th style="width: 120px;"><i class="fas fa-check-double"></i> Final Production</th>
                                <th style="width: 100px;"><i class="fas fa-trash-alt"></i> Scrap</th>
                                <th><i class="fas fa-tag"></i> Batch Number</th>
                                <th><i class="fas fa-check-circle"></i> Status</th>
                                <th><i class="fas fa-cog"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentMaterials as $material): ?>
                            <tr>
                                <td class="date-cell">
                                    <?php 
                                    $receivedDate = $material['received_date'];
                                    if ($receivedDate instanceof DateTime) {
                                        echo '<span class="date-day">' . $receivedDate->format('d M Y') . '</span>';
                                        echo $receivedDate->format('H:i');
                                    } else {
                                        $timestamp = strtotime($receivedDate);
                                        echo '<span class="date-day">' . date('d M Y', $timestamp) . '</span>';
                                        echo date('H:i', $timestamp);
                                    }
                                    ?>
                                </td>
                                <td><span class="part-code"><?php echo htmlspecialchars($material['part_code']); ?></span></td>
                                <td style="display: none;"><?php echo htmlspecialchars($material['part_name']); ?></td>
                                <td class="quantity-cell">
                                    <?php echo number_format($material['in_quantity']); ?> 
                                    <span class="quantity-unit"><?php echo htmlspecialchars($material['in_units'] ?? 'Pcs'); ?></span>
                                </td>
                                <td class="quantity-cell">
                                    <?php echo number_format($material['production_quantity']); ?> 
                                    <span class="quantity-unit"><?php echo htmlspecialchars($material['production_units'] ?? 'Pcs'); ?></span>
                                </td>
                                <td class="quantity-cell">
                                    <?php if (!empty($material['final_production_quantity'])): ?>
                                        <?php echo number_format($material['final_production_quantity']); ?> 
                                        <span class="quantity-unit"><?php echo htmlspecialchars($material['production_units'] ?? 'Pcs'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="quantity-cell">
                                    <?php if (!empty($material['scrap_quantity']) || $material['scrap_quantity'] === 0): ?>
                                        <span style="color: #ef4444; font-weight: 600;"><?php echo number_format($material['scrap_quantity']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="batch-badge"><?php echo htmlspecialchars($material['batch_number']); ?></span></td>
                                <td>
                                    <?php 
                                    $status = $material['production_status'] ?? 'Open';
                                    if ($status === 'Closed'): 
                                    ?>
                                        <span class="badge badge-closed"><i class="fas fa-check-double"></i> Closed</span>
                                    <?php else: ?>
                                        <span class="badge badge-open"><i class="fas fa-clock"></i> Open</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewMaterial(<?php echo $material['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($status !== 'Closed'): ?>
                                        <button class="btn-action btn-update" onclick="updateMaterial(<?php echo $material['id']; ?>)" title="Update">
                                            <i class="fas fa-edit"></i> Update
                                        </button>
                                        <button class="btn-action btn-close-production" onclick="closeProduction(<?php echo $material['id']; ?>, '<?php echo htmlspecialchars($material['batch_number']); ?>')" title="Close Production">
                                            <i class="fas fa-check-circle"></i> Close
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted" style="font-size: 12px; color: #94a3b8;">
                                            <i class="fas fa-lock"></i> Production Closed
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Materials Recorded Yet</h3>
                    <p>Click "Add New Material" to record your first material receipt</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Error Display Panel -->
    <div id="errorPanel" class="error-panel" style="display: none;">
        <div class="error-content">
            <div class="error-header">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Error Details</span>
                <button class="error-close" onclick="closeErrorPanel()">&times;</button>
            </div>
            <div id="errorMessage" class="error-message"></div>
            <div class="error-timestamp" id="errorTimestamp"></div>
        </div>
    </div>

    <script>
        // Error Display Functions
        function showError(message, error) {
            const errorPanel = document.getElementById('errorPanel');
            const errorMessage = document.getElementById('errorMessage');
            const errorTimestamp = document.getElementById('errorTimestamp');
            
            let fullMessage = message;
            if (error) {
                fullMessage += '\n\nError Details:\n' + error.toString();
                if (error.stack) {
                    fullMessage += '\n\nStack Trace:\n' + error.stack;
                }
            }
            
            errorMessage.textContent = fullMessage;
            errorTimestamp.textContent = 'Error occurred at: ' + new Date().toLocaleString('en-GB');
            errorPanel.style.display = 'block';
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                closeErrorPanel();
            }, 10000);
        }

        function closeErrorPanel() {
            const errorPanel = document.getElementById('errorPanel');
            errorPanel.style.display = 'none';
        }

        // Global error handler
        window.addEventListener('error', function(event) {
            showError('JavaScript Error: ' + event.message, event.error);
        });

        window.addEventListener('unhandledrejection', function(event) {
            showError('Promise Rejection: ' + event.reason, event.reason);
        });

        // Function to set current date and time
        function setCurrentDateTime() {
            const now = new Date();
            
            // Format date as YYYY-MM-DD
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const dateStr = `${year}-${month}-${day}`;
            
            // Format time as HH:MM
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const timeStr = `${hours}:${minutes}`;
            
            // Set the values
            document.getElementById('received_date').value = dateStr;
            document.getElementById('received_time').value = timeStr;
        }

        // Modal functions
        function openModal() {
            document.getElementById('materialModal').classList.add('show');
            // Only set current date/time if in add mode
            if (document.getElementById('form_action').value === 'add') {
                setCurrentDateTime();
            }
        }

        function closeModal() {
            document.getElementById('materialModal').classList.remove('show');
            // Reset form to add mode
            document.getElementById('materialInForm').reset();
            document.getElementById('form_action').value = 'add';
            document.getElementById('material_id').value = '';
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-plus-circle"></i> Record Material Receipt';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('materialModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Auto-hide alert messages
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 5000);

        // Generate batch number suggestion
        document.getElementById('part_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const partText = selectedOption.text;
                const partCode = partText.split(' - ')[0];
                const date = new Date();
                const dateStr = date.getFullYear() + 
                               String(date.getMonth() + 1).padStart(2, '0') + 
                               String(date.getDate()).padStart(2, '0');
                const timeStr = String(date.getHours()).padStart(2, '0') + 
                               String(date.getMinutes()).padStart(2, '0');
                
                const batchSuggestion = partCode + '-' + dateStr + '-' + timeStr;
                document.getElementById('batch_number').placeholder = 'e.g., ' + batchSuggestion;
            }
        });

        // View material details
        function viewMaterial(id) {
            alert('View material details for ID: ' + id + '\n\nThis will open a detailed view of the material record.');
        }

        // Update material
        function updateMaterial(id) {
            try {
                // Fetch material data via AJAX
                fetch('material-in.php?get_material=1&id=' + id)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            showError('Failed to fetch material data for update', new Error(data.error));
                            return;
                        }
                        
                        // Populate form fields
                        document.getElementById('form_action').value = 'update';
                        document.getElementById('material_id').value = data.id;
                        document.getElementById('part_id').value = data.part_id;
                        document.getElementById('in_quantity').value = data.in_quantity;
                        document.getElementById('in_units').value = data.in_units;
                        document.getElementById('production_quantity').value = data.production_quantity;
                        document.getElementById('production_units').value = data.production_units;
                        document.getElementById('received_date').value = data.received_date_only;
                        document.getElementById('received_time').value = data.received_time_only;
                        document.getElementById('batch_number').value = data.batch_number;
                        
                        // Update modal title
                        document.getElementById('modal_title').innerHTML = '<i class="fas fa-edit"></i> Update Material Receipt';
                        
                        // Open modal
                        openModal();
                    })
                    .catch(error => {
                        showError('Error fetching material data for update', error);
                    });
            } catch (error) {
                showError('Unexpected error in updateMaterial function', error);
            }
        }

        // Close Production
        function closeProduction(id, batchNumber) {
            try {
                // Fetch material data via AJAX
                fetch('material-in.php?get_material=1&id=' + id)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            showError('Failed to fetch material data for closing production', new Error(data.error));
                            return;
                        }
                        
                        // Populate modal with material information
                        document.getElementById('close_material_id').value = data.id;
                        document.getElementById('close_batch_number').textContent = data.batch_number;
                        document.getElementById('close_part_code').textContent = data.part_code;
                        document.getElementById('close_expected_quantity').textContent = data.production_quantity + ' ' + data.production_units;
                        document.getElementById('close_in_quantity').textContent = data.in_quantity + ' ' + data.in_units;
                        
                        // Reset input fields
                        document.getElementById('final_production_quantity').value = '';
                        document.getElementById('scrap_quantity').value = '';
                        
                        // Open modal
                        document.getElementById('closeProductionModal').style.display = 'block';
                    })
                    .catch(error => {
                        showError('Error fetching material data for closing production', error);
                    });
            } catch (error) {
                showError('Unexpected error in closeProduction function', error);
            }
        }

        // Close Production Modal
        function closeProductionModal() {
            document.getElementById('closeProductionModal').style.display = 'none';
        }

        // Delete material
        function deleteMaterial(id, batchNumber) {
            if (confirm('Are you sure you want to delete material record?\n\nBatch Number: ' + batchNumber + '\n\nThis action cannot be undone.')) {
                // Submit delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'material-in.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'material_id';
                idInput.value = id;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const materialModal = document.getElementById('materialModal');
            const closeProdModal = document.getElementById('closeProductionModal');
            
            if (event.target == materialModal) {
                closeModal();
            }
            if (event.target == closeProdModal) {
                closeProductionModal();
            }
        }
    </script>
</body>
</html>
