<?php
session_start();
require_once '../utils.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = connectDB();
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

// Get user info for display
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userInfo = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - VectorizeAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        .admin-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        
        .admin-nav .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.3s ease;
        }
        
        .admin-nav .nav-link:hover,
        .admin-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .admin-nav .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .admin-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .admin-table {
            margin-bottom: 0;
        }
        
        .admin-table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }
        
        .action-btn {
            background: var(--accent-color);
            color: var(--primary-color);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: #00e5bb;
            transform: translateY(-1px);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background-color: #fecaca;
            color: #991b1b;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .alert-custom {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .settings-nav .list-group-item {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .settings-nav .list-group-item.active {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        /* Dark mode fixes */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #121212 !important;
                color: #e0e0e0 !important;
            }
            
            .admin-card,
            .stat-card {
                background-color: #1e1e1e !important;
                color: #e0e0e0 !important;
            }
            
            .admin-table {
                color: #e0e0e0 !important;
            }
            
            .admin-table th {
                background-color: #2a2a2a !important;
                color: #e0e0e0 !important;
            }
            
            .admin-table td {
                color:rgb(0, 0, 0) !important;
                border-color: #333 !important;
            }
            
            .form-control,
            .form-select {
                background-color: #2a2a2a !important;
                border-color: #444 !important;
                color: #e0e0e0 !important;
            }
            
            .form-control:focus,
            .form-select:focus {
                background-color: #2a2a2a !important;
                color: #e0e0e0 !important;
                border-color: var(--accent-color) !important;
            }
            
            .modal-content {
                background-color: #1e1e1e !important;
                color: #e0e0e0 !important;
            }
            
            .modal-header {
                border-color: #333 !important;
            }
            
            .modal-footer {
                border-color: #333 !important;
            }
            
            .list-group-item {
                background-color: #2a2a2a !important;
                color: #e0e0e0 !important;
                border-color: #333 !important;
            }
            
            .list-group-item.active {
                background-color: var(--accent-color) !important;
                color: var(--primary-color) !important;
            }
            
            .text-muted {
                color: #adb5bd !important;
            }
            
            .stat-label {
                color: #adb5bd !important;
            }
            
            .table-striped tbody tr:nth-of-type(odd) {
                background-color: rgba(255, 255, 255, 0.05) !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="admin-sidebar p-3">
                    <div class="text-center mb-4">
                        <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-shield-alt text-primary fs-3"></i>
                        </div>
                        <h5 class="mt-2 mb-0">Admin Panel</h5>
                        <small class="text-light opacity-75">VectorizeAI Control Center</small>
                    </div>
                    
                    <div class="text-center mb-4 p-3 bg-white bg-opacity-10 rounded">
                        <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                            <i class="fas fa-user text-primary"></i>
                        </div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($userInfo['full_name']); ?></div>
                        <small class="text-light opacity-75">System Administrator</small>
                    </div>
                    
                    <nav class="admin-nav">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="#" data-section="overview">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-section="users">
                                    <i class="fas fa-users"></i> User Management
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-section="jobs">
                                    <i class="fas fa-tasks"></i> Job Management
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-section="subscriptions">
                                    <i class="fas fa-credit-card"></i> Subscriptions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-section="settings">
                                    <i class="fas fa-cogs"></i> System Settings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-section="analytics">
                                    <i class="fas fa-chart-bar"></i> Analytics
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-section="logs">
                                    <i class="fas fa-file-alt"></i> System Logs
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <div class="mt-auto pt-4">
                        <a href="../../dashboard.php" class="nav-link">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <a href="../php/auth/logout.php" class="nav-link text-warning">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4 flex-grow-1">
                    
                    <!-- Overview Section -->
                    <div id="overview-section" class="section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h2>
                            <div>
                                <button class="action-btn me-2" onclick="refreshStats()">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </button>
                                <button class="action-btn me-2" onclick="clearCache()">
                                    <i class="fas fa-broom me-1"></i> Clear Cache
                                </button>
                                <button class="action-btn" onclick="systemBackup()">
                                    <i class="fas fa-download me-1"></i> Backup
                                </button>
                            </div>
                        </div>
                        
                        <!-- Stats Cards -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number" id="totalUsers">...</div>
                                    <div class="stat-label">Total Users</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number" id="totalJobs">...</div>
                                    <div class="stat-label">Total Jobs</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number" id="activeSubscriptions">...</div>
                                    <div class="stat-label">Active Subscriptions</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number" id="monthlyRevenue">$...</div>
                                    <div class="stat-label">Monthly Revenue</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="admin-card">
                                    <h5 class="mb-3">System Status</h5>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Python API</span>
                                        <span class="status-badge status-inactive" id="pythonApiStatus">Checking...</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Database</span>
                                        <span class="status-badge status-active">Online</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>File Storage</span>
                                        <span class="status-badge status-active">Available</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="admin-card">
                                    <h5 class="mb-3">Recent Activity</h5>
                                    <div id="recentActivity">
                                        <div class="text-center py-3">
                                            <div class="spinner-border text-primary" role="status"></div>
                                            <p class="mt-2 text-muted">Loading activity...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Users Section -->
                    <div id="users-section" class="section d-none">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-users me-2"></i>User Management</h2>
                            <button class="action-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-1"></i> Add User
                            </button>
                        </div>
                        
                        <div class="admin-card">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="userSearch" placeholder="Search users...">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="userFilter">
                                        <option value="">All Users</option>
                                        <option value="admin">Admins</option>
                                        <option value="user">Regular Users</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary w-100" onclick="exportData()">
                                        <i class="fas fa-download me-1"></i> Export
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status"></div>
                                                <p class="mt-2 text-muted">Loading users...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <nav id="usersPagination" class="mt-3">
                                <ul class="pagination justify-content-center"></ul>
                            </nav>
                        </div>
                    </div>
                    
                    <!-- Jobs Section -->
                    <div id="jobs-section" class="section d-none">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-tasks me-2"></i>Job Management</h2>
                            <div>
                                <button class="action-btn me-2" onclick="retryFailedJobs()">
                                    <i class="fas fa-redo me-1"></i> Retry Failed
                                </button>
                                <button class="action-btn" onclick="clearOldJobs()">
                                    <i class="fas fa-trash me-1"></i> Clear Old
                                </button>
                            </div>
                        </div>
                        
                        <div class="admin-card">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <select class="form-select" id="jobStatusFilter">
                                        <option value="">All Status</option>
                                        <option value="queued">Queued</option>
                                        <option value="processing">Processing</option>
                                        <option value="done">Completed</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="date" class="form-control" id="jobDateFilter">
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-outline-primary w-100" onclick="exportJobs()">
                                        <i class="fas fa-download me-1"></i> Export Jobs
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Filename</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Coins</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="jobsTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status"></div>
                                                <p class="mt-2 text-muted">Loading jobs...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <nav id="jobsPagination" class="mt-3">
                                <ul class="pagination justify-content-center"></ul>
                            </nav>
                        </div>
                    </div>
                    
                    <!-- Subscriptions Section -->
                    <div id="subscriptions-section" class="section d-none">
                        <!-- Content will be loaded dynamically -->
                    </div>
                    
                    <!-- Settings Section -->
                    <div id="settings-section" class="section d-none">
                        <!-- Content will be loaded dynamically -->
                    </div>
                    
                    <!-- Analytics Section -->
                    <div id="analytics-section" class="section d-none">
                        <!-- Content will be loaded dynamically -->
                    </div>
                    
                    <!-- System Logs Section -->
                    <div id="logs-section" class="section d-none">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="coins" class="form-label">Initial Coins</label>
                            <input type="number" class="form-control" id="coins" name="coins" value="10">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">Add User</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin.js"></script>
</body>
</html>
