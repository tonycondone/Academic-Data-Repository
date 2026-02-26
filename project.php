<?php
require_once __DIR__ . '/config/config.php';

// Require login
$auth->requireLogin();
$user = $auth->getCurrentUser();

// Get project ID
$projectId = (int)($_GET['id'] ?? 0);

if (!$projectId) {
    redirect('projects.php', 'Project not found.', 'error');
}

// Check project access
if (!hasProjectPermission($user['id'], $projectId, 'read')) {
    redirect('projects.php', 'Access denied. You do not have permission to view this project.', 'error');
}

try {
    $db = SupabaseService::getConnection();
    
    // Get project details
    $query = "SELECT p.*, u.name as owner_name,
                     pm.role as user_role, pm.permissions as user_permissions
              FROM projects p
              JOIN users u ON p.owner_id = u.id
              LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = :user_id
              WHERE p.id = :project_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    $project = $stmt->fetch();
    
    if (!$project) {
        redirect('projects.php', 'Project not found.', 'error');
    }
    
    // Get project members
    $query = "SELECT pm.*, u.name as user_name, u.email, u.role as user_system_role
              FROM project_members pm
              JOIN users u ON pm.user_id = u.id
              WHERE pm.project_id = :project_id AND pm.status = 'active'
              ORDER BY pm.role DESC, u.name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    $members = $stmt->fetchAll();
    
    // Get project files
    $query = "SELECT f.*, u.name as uploader_name,
                     (SELECT COUNT(*) FROM file_versions fv WHERE fv.file_id = f.id) as version_count
              FROM files f
              JOIN users u ON f.uploaded_by = u.id
              WHERE f.project_id = :project_id AND f.is_deleted = 0
              ORDER BY f.updated_at DESC
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    $files = $stmt->fetchAll();
    
    // Get recent activity
    $query = "SELECT al.*, u.name as user_name
              FROM activity_log al
              JOIN users u ON al.user_id = u.id
              WHERE al.project_id = :project_id
              ORDER BY al.created_at DESC
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    // Get project statistics
    $query = "SELECT 
                (SELECT COUNT(*) FROM project_members WHERE project_id = :project_id AND status = 'active') as member_count,
                (SELECT COUNT(*) FROM files WHERE project_id = :project_id AND is_deleted = 0) as file_count,
                (SELECT COUNT(*) FROM file_versions fv JOIN files f ON fv.file_id = f.id WHERE f.project_id = :project_id) as version_count";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Project page error: " . $e->getMessage());
    redirect('projects.php', 'Error loading project.', 'error');
}

// Check if user is project owner or has management permissions
$isOwner = $project['owner_id'] == $user['id'];
$canManage = $isOwner || ($project['user_role'] === 'owner');
$userPermissions = json_decode($project['user_permissions'] ?? '{}', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/project.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Academic Collaboration
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">
                            <i class="fas fa-folder me-1"></i>Projects
                        </a>
                    </li>
                    <?php if ($user['role'] === 'faculty' || $user['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="create-project.php">
                            <i class="fas fa-plus me-1"></i>Create Project
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="fas fa-cog me-1"></i>Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container main-content">
        <?php displayFlashMessage(); ?>
        
        <!-- Project Header -->
        <div class="project-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h1>
                    <div class="project-meta">
                        by <?php echo htmlspecialchars($project['owner_name']); ?>
                        • Created <?php echo timeAgo($project['created_at']); ?>
                        • Updated <?php echo timeAgo($project['updated_at']); ?>
                        <?php if ($project['deadline']): ?>
                        • Due <?php echo formatDate($project['deadline'], 'M j, Y'); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($project['description']): ?>
                    <div class="project-description">
                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex flex-column gap-2 align-items-end">
                        <span class="status-badge status-<?php echo $project['status']; ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                        <?php if ($canManage): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-cog me-1"></i>Manage
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="project-settings.php?id=<?php echo $project['id']; ?>">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a></li>
                                <li><a class="dropdown-item" href="invite-members.php?id=<?php echo $project['id']; ?>">
                                    <i class="fas fa-user-plus me-2"></i>Invite Members
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="archiveProject()">
                                    <i class="fas fa-archive me-2"></i>Archive Project
                                </a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="row stats-row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3 class="stat-number"><?php echo $stats['member_count']; ?></h3>
                        <p class="stat-label">Members</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3 class="stat-number"><?php echo $stats['file_count']; ?></h3>
                        <p class="stat-label">Files</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3 class="stat-number"><?php echo $stats['version_count']; ?></h3>
                        <p class="stat-label">Versions</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Main Content -->
            <div class="col-md-8">
                <!-- Files Section -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="section-title">
                                <i class="fas fa-file me-2"></i>Recent Files
                            </h2>
                            <div class="btn-group">
                                <?php if ($userPermissions['write'] ?? false): ?>
                                <a href="upload.php?project=<?php echo $project['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-upload me-1"></i>Upload
                                </a>
                                <?php endif; ?>
                                <a href="files.php?project=<?php echo $project['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-list me-1"></i>View All
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($files)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file"></i>
                            <p>No files uploaded yet.</p>
                            <?php if ($userPermissions['write'] ?? false): ?>
                            <a href="upload.php?project=<?php echo $project['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload First File
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                        <div class="file-item">
                            <div class="file-info">
                                <div class="file-icon <?php echo getFileExtension($file['filename']); ?>">
                                    <i class="fas fa-<?php 
                                        $ext = getFileExtension($file['filename']);
                                        echo match($ext) {
                                            'csv' => 'file-csv',
                                            'xlsx', 'xls' => 'file-excel',
                                            'json' => 'file-code',
                                            'pdf' => 'file-pdf',
                                            'png', 'jpg', 'jpeg', 'gif' => 'image',
                                            default => 'file'
                                        };
                                    ?>"></i>
                                </div>
                                <div class="file-details">
                                    <h6><?php echo htmlspecialchars($file['original_filename']); ?></h6>
                                    <small>
                                        by <?php echo htmlspecialchars($file['uploader_name']); ?>
                                        • <?php echo formatFileSize($file['file_size']); ?>
                                        • v<?php echo $file['current_version']; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="file-meta">
                                <div><?php echo timeAgo($file['updated_at']); ?></div>
                                <div class="mt-1">
                                    <a href="file.php?id=<?php echo $file['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Activity Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </h2>
                    </div>
                    
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>No activity yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $activity['action']; ?>">
                                <i class="fas fa-<?php 
                                    echo match($activity['action']) {
                                        'upload' => 'upload',
                                        'edit' => 'edit',
                                        'delete' => 'trash',
                                        'join' => 'user-plus',
                                        'create_project' => 'plus',
                                        default => 'circle'
                                    };
                                ?>"></i>
                            </div>
                            <div class="activity-text">
                                <div class="activity-user">
                                    <?php echo htmlspecialchars($activity['user_name']); ?>
                                </div>
                                <div class="activity-action">
                                    <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                                    <?php if ($activity['target_type'] === 'file'): ?>
                                        a file
                                    <?php elseif ($activity['target_type'] === 'project'): ?>
                                        the project
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo timeAgo($activity['created_at']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Members Section -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="section-title">
                                <i class="fas fa-users me-2"></i>Members
                            </h2>
                            <?php if ($canManage): ?>
                            <a href="invite-members.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-user-plus me-1"></i>Invite
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php foreach ($members as $member): ?>
                    <div class="member-item">
                        <div class="member-info">
                            <div class="member-avatar">
                                <?php echo strtoupper(substr($member['user_name'], 0, 1)); ?>
                            </div>
                            <div class="member-details">
                                <h6><?php echo htmlspecialchars($member['user_name']); ?></h6>
                                <small><?php echo htmlspecialchars($member['email']); ?></small>
                            </div>
                        </div>
                        <span class="role-badge role-<?php echo $member['role']; ?>">
                            <?php echo ucfirst($member['role']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function archiveProject() {
            if (confirm('Are you sure you want to archive this project? This action can be undone later.')) {
                // Implementation for archiving project
                window.location.href = 'project-actions.php?action=archive&id=<?php echo $project['id']; ?>';
            }
        }
    </script>
    
    <!-- Vercel Web Analytics -->
    <script>
      window.va = window.va || function () { (window.vaq = window.vaq || []).push(arguments); };
    </script>
    <script defer src="/_vercel/insights/script.js"></script>
</body>
</html>