<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database configuration
require_once 'config/database.php';

$current_user = $_SESSION['username'];
$activePage = 'line-management';
$pageTitle = 'Line Management';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getSQLSrvConnection();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            // Add new line
            $lineName = trim($_POST['line_name']);
            $lineCode = trim($_POST['line_code']);
            $location = trim($_POST['location']);
            $capacity = (int)$_POST['capacity'];
            $status = $_POST['status'];
            
            $sql = "INSERT INTO lines (line_name, line_code, location, capacity, status) VALUES (?, ?, ?, ?, ?)";
            $params = [$lineName, $lineCode, $location, $capacity, $status];
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt) {
                $message = "Line added successfully!";
                $messageType = "success";
                sqlsrv_free_stmt($stmt);
            } else {
                $message = "Error adding line.";
                $messageType = "error";
            }
        } elseif ($_POST['action'] == 'edit') {
            // Update existing line
            $lineId = (int)$_POST['line_id'];
            $lineName = trim($_POST['line_name']);
            $lineCode = trim($_POST['line_code']);
            $location = trim($_POST['location']);
            $capacity = (int)$_POST['capacity'];
            $status = $_POST['status'];
            
            $sql = "UPDATE lines SET line_name = ?, line_code = ?, location = ?, capacity = ?, status = ?, updated_at = GETDATE() WHERE id = ?";
            $params = [$lineName, $lineCode, $location, $capacity, $status, $lineId];
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt) {
                $message = "Line updated successfully!";
                $messageType = "success";
                sqlsrv_free_stmt($stmt);
            } else {
                $message = "Error updating line.";
                $messageType = "error";
            }
        } elseif ($_POST['action'] == 'delete') {
            // Delete line
            $lineId = (int)$_POST['line_id'];
            
            $sql = "DELETE FROM lines WHERE id = ?";
            $params = [$lineId];
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt) {
                $message = "Line deleted successfully!";
                $messageType = "success";
                sqlsrv_free_stmt($stmt);
            } else {
                $message = "Error deleting line.";
                $messageType = "error";
            }
        }
    }
}

// Fetch all lines
$lines = [];
try {
    $conn = getSQLSrvConnection();
    if ($conn !== false) {
        $sql = "SELECT * FROM lines ORDER BY line_code";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $lines[] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
    }
} catch (Exception $e) {
    // Handle error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line Management - Production Management System</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/line-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div class="page-title">
                    <h2>Production Lines</h2>
                    <p>Manage your production lines and their configurations</p>
                </div>
                <button class="btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Line
                </button>
            </div>

            <!-- Lines Grid -->
            <div class="lines-grid">
                <?php if (empty($lines)): ?>
                    <div class="empty-state">
                        <i class="fas fa-layer-group"></i>
                        <h3>No Production Lines Yet</h3>
                        <p>Get started by adding your first production line</p>
                        <button class="btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add Line
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($lines as $line): ?>
                        <div class="line-card">
                            <div class="line-header">
                                <div class="line-code"><?php echo htmlspecialchars($line['line_code']); ?></div>
                                <span class="status-badge <?php echo strtolower($line['status']); ?>">
                                    <?php echo htmlspecialchars($line['status']); ?>
                                </span>
                            </div>
                            <h3 class="line-name"><?php echo htmlspecialchars($line['line_name']); ?></h3>
                            <div class="line-info">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($line['location']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Capacity: <?php echo $line['capacity']; ?> units/day</span>
                                </div>
                            </div>
                            <div class="line-actions">
                                <button class="btn-edit" onclick='editLine(<?php echo json_encode($line); ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete" onclick="deleteLine(<?php echo $line['id']; ?>, '<?php echo htmlspecialchars($line['line_name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal" id="lineModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Line</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="lineForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="line_id" id="lineId">
                
                <div class="form-group">
                    <label for="lineName">Line Name *</label>
                    <input type="text" id="lineName" name="line_name" required>
                </div>
                
                <div class="form-group">
                    <label for="lineCode">Line Code *</label>
                    <input type="text" id="lineCode" name="line_code" required>
                </div>
                
                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" id="location" name="location" required>
                </div>
                
                <div class="form-group">
                    <label for="capacity">Capacity (units/day) *</label>
                    <input type="number" id="capacity" name="capacity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary" id="submitBtn">Add Line</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteLineName"></strong>?</p>
                <p class="warning-text">This action cannot be undone.</p>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="line_id" id="deleteLineId">
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>
    
    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Line';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = 'Add Line';
            document.getElementById('lineForm').reset();
            document.getElementById('lineModal').style.display = 'flex';
        }

        function editLine(line) {
            document.getElementById('modalTitle').textContent = 'Edit Line';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('submitBtn').textContent = 'Update Line';
            document.getElementById('lineId').value = line.id;
            document.getElementById('lineName').value = line.line_name;
            document.getElementById('lineCode').value = line.line_code;
            document.getElementById('location').value = line.location;
            document.getElementById('capacity').value = line.capacity;
            document.getElementById('status').value = line.status;
            document.getElementById('lineModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('lineModal').style.display = 'none';
        }

        function deleteLine(id, name) {
            document.getElementById('deleteLineId').value = id;
            document.getElementById('deleteLineName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals on outside click
        window.onclick = function(event) {
            const lineModal = document.getElementById('lineModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == lineModal) {
                closeModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const lineCards = document.querySelectorAll('.line-card');
            
            lineCards.forEach(card => {
                const lineName = card.querySelector('.line-name').textContent.toLowerCase();
                const lineCode = card.querySelector('.line-code').textContent.toLowerCase();
                const location = card.querySelector('.info-item span').textContent.toLowerCase();
                
                if (lineName.includes(searchTerm) || lineCode.includes(searchTerm) || location.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Auto-hide alert messages
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>
