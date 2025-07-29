<?php
require_once 'config/config.php';

// Require faculty or admin access
$auth->requireFaculty();
$user = $auth->getCurrentUser();

$error = '';
$success = '';

// Handle project creation
if ($_POST && isset($_POST['create_project'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $deadline = sanitizeInput($_POST['deadline'] ?? '');
        $maxMembers = (int)($_POST['max_members'] ?? 50);
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        
        // Validate input
        if (empty($name)) {
            $error = 'Project name is required.';
        } elseif (strlen($name) < 3) {
            $error = 'Project name must be at least 3 characters long.';
        } elseif ($maxMembers < 1 || $maxMembers > 200) {
            $error = 'Maximum members must be between 1 and 200.';
        } else {
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                // Insert project
                $query = "INSERT INTO projects (name, description, owner_id, deadline, max_members, is_public) 
                          VALUES (:name, :description, :owner_id, :deadline, :max_members, :is_public)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':owner_id', $user['id']);
                $stmt->bindParam(':deadline', $deadline ?: null);
                $stmt->bindParam(':max_members', $maxMembers);
                $stmt->bindParam(':is_public', $isPublic);
                
                if ($stmt->execute()) {
                    $projectId = $db->lastInsertId();
                    
                    // Add owner as project member
                    $query = "INSERT INTO project_members (project_id, user_id, role, status, permissions) 
                              VALUES (:project_id, :user_id, 'owner', 'active', :permissions)";
                    
                    $permissions = json_encode([
                        'read' => true,
                        'write' => true,
                        'delete' => true,
                        'manage_members' => true,
                        'manage_settings' => true
                    ]);
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':project_id', $projectId);
                    $stmt->bindParam(':user_id', $user['id']);
                    $stmt->bindParam(':permissions', $permissions);
                    $stmt->execute();
                    
                    // Log activity
                    logActivity($projectId, $user['id'], 'create_project', 'project', $projectId, [
                        'project_name' => $name
                    ]);
                    
                    // Send notification
                    sendNotification(
                        $user['id'],
                        'project_created',
                        'Project Created Successfully',
                        "Your project '{$name}' has been created and is ready for collaboration."
                    );
                    
                    redirect("project.php?id={$projectId}", 'Project created successfully!', 'success');
                } else {
                    $error = 'Failed to create project. Please try again.';
                }
                
            } catch (Exception $e) {
                error_log("Create project error: " . $e->getMessage());
                $error = 'Failed to create project. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Project - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .main-content {
            margin-top: 2rem;
        }
        
        .create-project-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating input,
        .form-floating textarea,
        .form-floating select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .form-floating input:focus,
        .form-floating textarea:focus,
        .form-floating select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }
        
        .form-floating textarea {
            min-height: 120px;
        }
        
        .btn-create {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        
        .btn-cancel {
            border: 2px solid #6b7280;
            color: #6b7280;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #6b7280;
            color: white;
            text-decoration: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .form-check {
            margin-bottom: 1.5rem;
        }
        
        .form-check-input:checked {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }
        
        .project-info {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .project-info h5 {
            color: #4f46e5;
            margin-bottom: 1rem;
        }
        
        .project-info ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .project-info li {
            margin-bottom: 0.5rem;
            color: #6b7280;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-floating {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="create-project.php">
                            <i class="fas fa-plus me-1"></i>Create Project
                        </a>
                    </li>
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
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="create-project-card">
                    <div class="card-header">
                        <i class="fas fa-folder-plus fa-3x mb-3"></i>
                        <h2>Create New Project</h2>
                        <p>Start a new collaborative data project</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Project Information -->
                        <div class="project-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Project Features</h5>
                            <ul>
                                <li>Collaborative file sharing and editing</li>
                                <li>Version control with branching and merging</li>
                                <li>Member invitation and permission management</li>
                                <li>Activity tracking and notifications</li>
                                <li>Support for multiple file formats (CSV, Excel, JSON, PDF, images)</li>
                            </ul>
                        </div>
                        
                        <form method="POST" action="" id="createProjectForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Project Name -->
                            <div class="form-floating">
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="Project Name" required minlength="3" maxlength="200"
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                <label for="name">
                                    <i class="fas fa-folder me-2"></i>Project Name
                                </label>
                            </div>
                            
                            <!-- Project Description -->
                            <div class="form-floating">
                                <textarea class="form-control" id="description" name="description" 
                                          placeholder="Project Description" style="height: 120px"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <label for="description">
                                    <i class="fas fa-align-left me-2"></i>Project Description
                                </label>
                            </div>
                            
                            <!-- Deadline and Max Members -->
                            <div class="form-row">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="deadline" name="deadline" 
                                           placeholder="Deadline (Optional)"
                                           value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>">
                                    <label for="deadline">
                                        <i class="fas fa-calendar me-2"></i>Deadline (Optional)
                                    </label>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="max_members" name="max_members" 
                                           placeholder="Maximum Members" min="1" max="200" 
                                           value="<?php echo htmlspecialchars($_POST['max_members'] ?? '50'); ?>">
                                    <label for="max_members">
                                        <i class="fas fa-users me-2"></i>Max Members
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Public Project Option -->
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_public" name="is_public" 
                                       <?php echo isset($_POST['is_public']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_public">
                                    <i class="fas fa-globe me-2"></i>
                                    <strong>Make this project public</strong>
                                    <div class="small text-muted mt-1">
                                        Public projects can be discovered and joined by other users. 
                                        Private projects require invitation.
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex gap-3 justify-content-end">
                                <a href="dashboard.php" class="btn-cancel">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="create_project" class="btn btn-primary btn-create">
                                    <i class="fas fa-plus me-2"></i>Create Project
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus on project name field
            document.getElementById('name').focus();
            
            // Set minimum date to today for deadline
            const deadlineInput = document.getElementById('deadline');
            const today = new Date().toISOString().split('T')[0];
            deadlineInput.min = today;
            
            // Form validation
            const form = document.getElementById('createProjectForm');
            const nameInput = document.getElementById('name');
            const maxMembersInput = document.getElementById('max_members');
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate project name
                if (nameInput.value.trim().length < 3) {
                    alert('Project name must be at least 3 characters long.');
                    nameInput.focus();
                    isValid = false;
                }
                
                // Validate max members
                const maxMembers = parseInt(maxMembersInput.value);
                if (maxMembers < 1 || maxMembers > 200) {
                    alert('Maximum members must be between 1 and 200.');
                    maxMembersInput.focus();
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Character counter for project name
            nameInput.addEventListener('input', function() {
                const length = this.value.length;
                const maxLength = 200;
                
                if (length > maxLength - 20) {
                    console.log(`${length}/${maxLength} characters`);
                }
            });
        });
    </script>
</body>
</html>