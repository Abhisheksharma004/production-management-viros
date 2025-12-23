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
$activePage = 'stages-management';
$pageTitle = 'Stages Management';

// Fetch all parts for dropdown
$parts = [];
try {
    $conn = getSQLSrvConnection();
    if ($conn !== false) {
        $sql = "SELECT * FROM parts WHERE status = 'Active' ORDER BY part_code";
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

// Sample data for UI display (no backend functionality)
$stages = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stages Management - Production Management System</title>
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
            <div class="page-header">
                <div class="page-title">
                    <h2>Production Stages</h2>
                    <p>Manage your production stages and workflow</p>
                </div>
                <button class="btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Stage
                </button>
            </div>

            <!-- Stages Table -->
            <div class="table-container">
                <?php if (empty($stages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <h3>No Stages Yet</h3>
                        <p>Get started by adding your first production stage</p>
                        <button class="btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add Stage
                        </button>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Stage Name</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; ?>
                            <?php foreach ($stages as $stage): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td class="line-name"><?php echo htmlspecialchars($stage['stage_name']); ?></td>
                                    <td><?php echo isset($stage['created_at']) ? $stage['created_at']->format('Y-m-d H:i') : 'N/A'; ?></td>
                                    <td class="action-buttons">
                                        <button class="btn-edit" onclick='editStage(<?php echo json_encode($stage); ?>)' title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-delete" onclick="deleteStage(<?php echo $stage['id']; ?>, '<?php echo htmlspecialchars($stage['stage_name']); ?>')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal" id="stageModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3 id="modalTitle">Add Stages for Part</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="stageForm">
                <input type="hidden" name="action" id="formAction" value="add">
                
                <div class="form-group">
                    <label for="partId">Select Part *</label>
                    <select id="partId" name="part_id" required>
                        <option value="">-- Select Part --</option>
                        <?php foreach ($parts as $part): ?>
                            <option value="<?php echo $part['id']; ?>">
                                <?php echo htmlspecialchars($part['part_code']) . ' - ' . htmlspecialchars($part['part_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="stages-section">
                    <div class="section-header">
                        <label>Stages</label>
                    </div>
                    <div id="stagesContainer">
                        <!-- Stage rows will be added here dynamically -->
                    </div>
                    <div style="margin-top: 10px;">
                        <button type="button" class="btn-primary btn-sm" onclick="addStageRow()">
                            <i class="fas fa-plus"></i> Add Stage
                        </button>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary" id="submitBtn">Save Stages</button>
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
                <p>Are you sure you want to delete <strong id="deleteStageName"></strong>?</p>
                <p class="warning-text">This action cannot be undone.</p>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="stage_id" id="deleteStageId">
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>
    
    <style>
        .stages-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .section-header label {
            font-weight: 600;
            font-size: 16px;
            color: #1e293b;
            margin: 0;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        .stage-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: flex-end;
        }
        .stage-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        .stage-row .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stage-row .btn-remove:hover {
            background: #dc2626;
        }
        .stage-row-number {
            width: 40px;
            text-align: center;
            font-weight: 600;
            color: #64748b;
            line-height: 42px;
        }
    </style>
    
    <script>
        let stageCounter = 0;
        let stagesData = [];
        let stageIdCounter = 1;

        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Stages for Part';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = 'Save Stages';
            document.getElementById('stageForm').reset();
            document.getElementById('stagesContainer').innerHTML = '';
            stageCounter = 0;
            addStageRow(); // Add first stage row by default
            document.getElementById('stageModal').style.display = 'flex';
        }
        
        function addStageRow() {
            stageCounter++;
            const container = document.getElementById('stagesContainer');
            const stageRow = document.createElement('div');
            stageRow.className = 'stage-row';
            stageRow.id = 'stageRow' + stageCounter;
            stageRow.innerHTML = `
                <div class="stage-row-number">${stageCounter}</div>
                <div class="form-group" style="flex: 3;">
                    <input type="text" name="stage_names[]" placeholder="Stage Name" required>
                </div>
                <button type="button" class="btn-remove" onclick="removeStageRow(${stageCounter})" title="Remove">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(stageRow);
        }
        
        function removeStageRow(id) {
            const row = document.getElementById('stageRow' + id);
            if (row) {
                row.remove();
                renumberStageRows();
            }
        }
        
        function renumberStageRows() {
            const rows = document.querySelectorAll('.stage-row');
            rows.forEach((row, index) => {
                const numberElement = row.querySelector('.stage-row-number');
                if (numberElement) {
                    numberElement.textContent = index + 1;
                }
            });
        }
        
        // Handle form submission - wait for DOM to load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('stageForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const partId = document.getElementById('partId').value;
                const partText = document.getElementById('partId').options[document.getElementById('partId').selectedIndex].text;
                
                const stageNames = document.getElementsByName('stage_names[]');
                
                // Add all stages to the data array
                for (let i = 0; i < stageNames.length; i++) {
                    const newStage = {
                        id: stageIdCounter++,
                        part_id: partId,
                        part_text: partText,
                        stage_name: stageNames[i].value,
                        status: 'Active',
                        created_at: new Date()
                    };
                    stagesData.push(newStage);
                }
                
                renderStagesTable();
                closeModal();
            });
            
            // Handle delete confirmation
            document.getElementById('deleteForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const stageId = document.getElementById('deleteStageId').value;
                stagesData = stagesData.filter(s => s.id != stageId);
                
                renderStagesTable();
                closeDeleteModal();
            });
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('.data-table tbody tr');
                
                tableRows.forEach(row => {
                    const partText = row.cells[1].textContent.toLowerCase();
                    const stageName = row.cells[2].textContent.toLowerCase();
                    
                    if (partText.includes(searchTerm) || stageName.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        function closeModal() {
            document.getElementById('stageModal').style.display = 'none';
        }

        function deleteStage(id, name) {
            document.getElementById('deleteStageId').value = id;
            document.getElementById('deleteStageName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Render stages table
        function renderStagesTable() {
            const container = document.querySelector('.table-container');
            
            if (stagesData.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <h3>No Stages Yet</h3>
                        <p>Get started by adding your first production stages</p>
                        <button class="btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add Stages
                        </button>
                    </div>
                `;
            } else {
                let tableHTML = `
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part</th>
                                <th>Stage Name</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                stagesData.forEach((stage, index) => {
                    const createdDate = new Date(stage.created_at).toLocaleString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    tableHTML += `
                        <tr>
                            <td>${index + 1}</td>
                            <td class="line-code">${stage.part_text}</td>
                            <td class="line-name">${stage.stage_name}</td>
                            <td>${createdDate}</td>
                            <td class="action-buttons">
                                <button class="btn-delete" onclick="deleteStage(${stage.id}, '${stage.stage_name}')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                tableHTML += `
                        </tbody>
                    </table>
                `;
                
                container.innerHTML = tableHTML;
            }
        }

        // Close modals on outside click
        window.onclick = function(event) {
            const stageModal = document.getElementById('stageModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == stageModal) {
                closeModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
