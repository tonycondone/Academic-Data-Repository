- No transitive dependencies exist
- All non-key attributes depend only on the primary key

**Denormalization Decisions:**
While normalization is important, strategic denormalization was applied for performance:
- Download and view counts are stored directly in the datasets table to avoid expensive aggregation queries
- User's last login is stored in the users table for quick access
- Average ratings could be calculated and cached to improve performance

## 3.6 User Interface Design

### 3.6.1 Design Principles

The user interface design follows established principles to ensure usability and accessibility:

**Consistency:**
- Uniform navigation patterns across all pages
- Consistent color scheme and typography
- Standardized button styles and form elements
- Predictable layout structures

**Visibility:**
- Clear visual hierarchy using size, color, and spacing
- Important actions prominently displayed
- System status always visible to users
- Search functionality accessible from all pages

**Feedback:**
- Immediate response to user actions
- Clear success and error messages
- Progress indicators for long operations
- Hover states for interactive elements

**Simplicity:**
- Minimal cognitive load through clear labeling
- Progressive disclosure of advanced features
- Uncluttered layouts with adequate whitespace
- Focus on primary user tasks

**Accessibility:**
- Semantic HTML for screen reader compatibility
- Sufficient color contrast ratios
- Keyboard navigation support
- Alternative text for images

### 3.6.2 Wireframes and Mockups

The design process included creation of detailed wireframes for key interfaces:

**Homepage Layout:**
- Hero section with search bar
- Featured datasets carousel
- Category grid for browsing
- Recent activity sidebar
- Call-to-action for registration

**Dataset Repository View:**
- Filter sidebar with categories
- Grid/list view toggle
- Dataset cards with preview
- Pagination controls
- Sort options dropdown

**Dataset Detail Page:**
- Dataset information header
- Preview section for supported formats
- Download and version history
- Reviews and ratings section
- Related datasets suggestions

**Upload Interface:**
- Drag-and-drop file area
- Metadata input form
- Category selection
- Privacy settings
- Upload progress indicator

**User Dashboard:**
- Personal statistics overview
- Recent uploads grid
- Download history
- Project memberships
- Activity timeline

## 3.7 Use Case Modeling

### 3.7.1 Actor Identification

The system identifies four primary actors with distinct roles and capabilities:

**Administrator:**
- System configuration and maintenance
- User management and role assignment
- Content moderation and quality control
- Platform analytics and reporting
- Security monitoring and incident response

**Faculty Member:**
- Dataset upload and management
- Project creation and administration
- Student invitation and oversight
- Course-specific dataset curation
- Progress monitoring and assessment

**Student:**
- Dataset discovery and download
- Personal dataset uploads
- Project participation
- Review and rating submission
- Profile management

**Public User:**
- Browse public datasets
- Preview available content
- Limited download access
- Registration capability
- Read reviews and ratings

### 3.7.2 Use Case Diagrams

Key use cases were identified and modeled to capture system functionality:

**UC1: User Registration**
- Actor: Public User
- Precondition: User has valid email address
- Main Flow:
  1. User accesses registration page
  2. User provides required information
  3. System validates input
  4. System creates account
  5. System sends verification email
  6. User confirms email address
- Postcondition: User account created and activated

**UC2: Dataset Upload**
- Actor: Faculty/Student
- Precondition: User is authenticated
- Main Flow:
  1. User selects upload option
  2. User provides file and metadata
  3. System validates file type and size
  4. System processes upload
  5. System stores file and metadata
  6. System confirms successful upload
- Alternative Flow: File conversion for Excel files
- Postcondition: Dataset available in repository

**UC3: Dataset Search**
- Actor: All authenticated users
- Precondition: User is logged in
- Main Flow:
  1. User enters search terms
  2. User applies filters (optional)
  3. System executes search query
  4. System returns matching results
  5. User reviews results
  6. User selects dataset
- Postcondition: User views dataset details

**UC4: Project Collaboration**
- Actor: Faculty Member
- Precondition: User has faculty role
- Main Flow:
  1. Faculty creates new project
  2. Faculty invites students
  3. Students accept invitations
  4. Members upload datasets
  5. Members access project resources
  6. Faculty monitors progress
- Postcondition: Collaborative project established

## 3.8 System Flow and Process Design

### 3.8.1 Data Flow Diagrams

**Level 0 - Context Diagram:**
The system context shows the Academic Data Repository as a central system interacting with four external entities:
- Users (all types) providing input and receiving datasets
- File System storing actual dataset files
- Email Service for notifications
- Database for persistent storage

**Level 1 - Major Processes:**
1. **User Management Process**
   - Handles registration, authentication, and profile management
   - Inputs: User credentials, profile information
   - Outputs: Session tokens, user data

2. **Dataset Management Process**
   - Manages upload, storage, and retrieval
   - Inputs: Files, metadata
   - Outputs: Stored datasets, download streams

3. **Search and Discovery Process**
   - Processes queries and returns results
   - Inputs: Search terms, filters
   - Outputs: Matching datasets

4. **Collaboration Process**
   - Manages projects and permissions
   - Inputs: Project data, member lists
   - Outputs: Access controls, activity logs

5. **Review and Rating Process**
   - Handles quality feedback
   - Inputs: Ratings, comments
   - Outputs: Aggregate scores, reviews

### 3.8.2 Activity Diagrams

**File Upload Activity:**
1. Start
2. User initiates upload
3. System displays upload interface
4. User selects file
5. System validates file type
   - If invalid: Display error, return to step 4
   - If valid: Continue
6. User enters metadata
7. System validates metadata
8. If Excel file:
   - Convert to CSV
   - Store both versions
9. Store file in filesystem
10. Save metadata to database
11. Update search index
12. Display success message
13. End

**Dataset Search Activity:**
1. Start
2. User enters search criteria
3. System parses query
4. Apply security filters
5. Execute database search
6. Retrieve matching records
7. Calculate relevance scores
8. Sort results
9. Apply pagination
10. Render results page
11. End

### 3.8.3 Sequence Diagrams

**User Authentication Sequence:**
```
User -> LoginPage: Access login
LoginPage -> User: Display form
User -> LoginPage: Submit credentials
LoginPage -> AuthController: Validate credentials
AuthController -> Database: Query user
Database -> AuthController: Return user data
AuthController -> AuthController: Verify password
AuthController -> SessionManager: Create session
SessionManager -> AuthController: Return session ID
AuthController -> LoginPage: Authentication result
LoginPage -> User: Redirect to dashboard
```

**File Download Sequence:**
```
User -> DatasetPage: Click download
DatasetPage -> DownloadController: Request file
DownloadController -> AuthService: Verify permissions
AuthService -> DownloadController: Permission granted
DownloadController -> Database: Log download
DownloadController -> FileSystem: Retrieve file
FileSystem -> DownloadController: File stream
DownloadController -> User: Send file
```

## 3.9 Security Design Considerations

### 3.9.1 Authentication Mechanism

The authentication system implements defense-in-depth principles:

**Password Security:**
- Minimum 8 characters with complexity requirements
- Bcrypt hashing with cost factor 12
- Password strength meter on registration
- Secure password reset via email tokens

**Session Management:**
- Secure session cookies (httpOnly, secure flags)
- Session regeneration on privilege changes
- Automatic timeout after 30 minutes inactivity
- Concurrent session limiting

**Account Security:**
- Email verification required
- Account lockout after failed attempts
- CAPTCHA on repeated failures
- Security questions for recovery

### 3.9.2 Authorization Framework

Role-based access control (RBAC) implementation:

**Permission Matrix:**
| Action | Admin | Faculty | Student | Public |
|--------|-------|---------|---------|--------|
| View Public Datasets | ✓ | ✓ | ✓ | ✓ |
| Download Public | ✓ | ✓ | ✓ | Limited |
| Upload Datasets | ✓ | ✓ | ✓ | ✗ |
| Create Projects | ✓ | ✓ | ✗ | ✗ |
| Manage Users | ✓ | ✗ | ✗ | ✗ |
| System Config | ✓ | ✗ | ✗ | ✗ |

**Access Control Implementation:**
- Middleware-based permission checking
- Resource-level access controls
- Dynamic permission evaluation
- Audit logging of access attempts

### 3.9.3 Data Protection Strategies

**Input Validation:**
- Whitelist validation for all inputs
- Parameterized queries prevent SQL injection
- HTML purification prevents XSS
- File type validation using MIME detection

**Output Encoding:**
- Context-aware output escaping
- Content Security Policy headers
- X-Frame-Options to prevent clickjacking
- Secure headers configuration

**File Security:**
- Files stored outside web root
- Randomized filenames prevent enumeration
- Virus scanning integration ready
- Size and type restrictions enforced

**Communication Security:**
- HTTPS enforcement recommended
- Secure cookie attributes
- HSTS header support
- Certificate pinning ready

## 3.10 File Management System Design

### 3.10.1 File Storage Architecture

The file storage system balances security, performance, and maintainability:

**Directory Structure:**
```
/storage/
  /datasets/
    /2024/
      /01/
        /[random_hash]_[timestamp]/
          original_file.csv
          metadata.json
          versions/
            v1_[timestamp].csv
            v2_[timestamp].csv
  /temp/
    /uploads/
    /conversions/
  /archives/
```

**Storage Strategy:**
- Hierarchical organization by year/month
- Random hash prevents direct access
- Metadata stored alongside files
- Temporary storage for processing
- Archive system for deleted files

**File Processing Pipeline:**
1. Upload to temporary storage
2. Virus scan (when configured)
3. File type verification
4. Format conversion if needed
5. Move to permanent storage
6. Generate metadata file
7. Update database records
8. Clean temporary files

### 3.10.2 Version Control Implementation

The version control system provides Git-like functionality for datasets:

**Version Creation:**
- Automatic versioning on updates
- Manual version creation option
- Descriptive commit messages
- Author attribution tracking

**Storage Mechanism:**
- Delta storage for efficiency
- Full snapshots at intervals
- Checksum verification
- Compression for old versions

**Version Operations:**
- View version history
- Compare versions (diff)
- Rollback to previous version
- Branch creation for experiments
- Merge capabilities for collaboration

**Implementation Details:**
```php
class VersionControl {
    public function createVersion($datasetId, $file, $description) {
        // Calculate file checksum
        $checksum = hash_file('sha256', $file);
        
        // Check if content changed
        if ($this->hasChanged($datasetId, $checksum)) {
            // Store new version
            $version = $this->storeVersion($file);
            
            // Update version history
            $this->updateHistory($datasetId, $version, $description);
            
            // Maintain version limit
            $this->pruneOldVersions($datasetId);
        }
    }
}
```

## 3.11 Algorithm Design

### 3.11.1 Search Algorithm

The search implementation combines full-text search with metadata filtering:

**Search Strategy:**
1. Parse search query for terms and operators
2. Build full-text search query
3. Apply category and date filters
4. Execute optimized SQL query
5. Calculate relevance scores
6. Apply user permission filters
7. Sort by relevance or user preference
8. Return paginated results

**Relevance Scoring:**
```
relevance_score = (
    title_match_weight * 3 +
    description_match_weight * 2 +
    tag_match_weight * 1 +
    recency_boost +
    popularity_boost
) * quality_rating_multiplier
```

**Query Optimization:**
- Full-text indexes on searchable fields
- Query result caching
- Lazy loading of related data
- Prepared statement reuse

### 3.11.2 File Conversion Algorithm

Excel to CSV conversion handles various edge cases:

**Conversion Process:**
1. Detect Excel format (XLS vs XLSX)
2. Load appropriate parser library
3. Identify data sheets (skip empty)
4. For each sheet:
   - Detect data boundaries
   - Identify header row
   - Convert formulas to values
   - Handle date formatting
   - Escape special characters
5. Generate CSV with proper encoding
6. Validate output integrity

**Error Handling:**
- Corrupted file detection
- Memory limit management
- Encoding detection and conversion
- Formula evaluation errors
- Large file streaming

### 3.11.3 Rating Calculation Algorithm

The rating system uses weighted averaging with credibility factors:

**Rating Algorithm:**
```
weighted_rating = Σ(rating * user_credibility) / Σ(user_credibility)

where user_credibility = base_credibility * 
    (1 + contribution_score * 0.1) * 
    (1 + accuracy_score * 0.1)
```

**Factors Considered:**
- User contribution history
- Previous rating accuracy
- Account age and activity
- Verification status
- Expertise indicators

**Anti-Gaming Measures:**
- One rating per user per dataset
- Minimum account age required
- Rate limiting on submissions
- Anomaly detection for patterns
- Manual review triggers

---

# CHAPTER FOUR
# IMPLEMENTATION AND RESULTS

## 4.1 Introduction

This chapter presents the detailed implementation of the Academic Data Repository platform and demonstrates the results achieved through systematic development efforts. The implementation phase transformed the design specifications outlined in Chapter Three into a fully functional web-based system. This chapter documents the development environment setup, core module implementations, testing procedures, and presents comprehensive results through screenshots and performance metrics. The successful implementation validates the design decisions and demonstrates the platform's capability to address the identified requirements for academic dataset management.

## 4.2 Development Environment Setup

### 4.2.1 Hardware Requirements

The development and testing environment utilized the following hardware specifications:

**Development Machine:**
- Processor: Intel Core i5-8250U (8th Generation) or equivalent
- RAM: 8GB DDR4 minimum (16GB recommended)
- Storage: 256GB SSD with at least 50GB free space
- Display: 1920x1080 resolution for optimal development experience
- Network: Stable internet connection for package downloads and testing

**Test Server Specifications:**
- Processor: Intel Xeon E5-2620 or equivalent
- RAM: 16GB ECC memory
- Storage: 500GB SSD for OS and applications, 2TB HDD for data storage
- Network: 1Gbps ethernet connection
- Operating System: Ubuntu 20.04 LTS Server Edition

### 4.2.2 Software Requirements

The following software stack was utilized for development:

**Core Technologies:**
- PHP 8.0.25 with essential extensions (PDO, MySQL, GD, JSON, Session, FileInfo)
- MySQL 8.0.31 Community Edition
- Apache 2.4.54 with mod_rewrite enabled
- Git 2.34.1 for version control

**Development Tools:**
- Visual Studio Code 1.74 with PHP Intelephense extension
- phpMyAdmin 5.2.0 for database management
- Composer 2.4.4 for dependency management
- Chrome DevTools for frontend debugging

**Additional Libraries:**
- Bootstrap 5.3.0 for responsive design
- Font Awesome 6.2.1 for icons
- PhpSpreadsheet 1.25.2 for Excel file handling
- jQuery 3.6.3 for enhanced interactivity (optional)

### 4.2.3 Development Tools

**Integrated Development Environment (IDE):**
Visual Studio Code was selected as the primary IDE due to its excellent PHP support, integrated terminal, and extensive extension ecosystem. Key extensions included:
- PHP Intelephense for intelligent code completion
- PHP Debug for step-through debugging
- GitLens for version control integration
- Prettier for code formatting
- Live Server for rapid frontend development

**Database Management:**
phpMyAdmin provided a web-based interface for database administration tasks including:
- Schema design and modification
- Query execution and optimization
- Data import/export operations
- User privilege management
- Performance monitoring

**Version Control:**
Git was used for source code management with the following workflow:
- Feature branches for new functionality
- Develop branch for integration
- Master branch for stable releases
- Semantic versioning for releases
- Comprehensive commit messages

## 4.3 System Implementation

### 4.3.1 Database Implementation

The database implementation began with creating the schema structure defined in the design phase:

**Schema Creation Process:**
```sql
-- Create database
CREATE DATABASE IF NOT EXISTS academic_data_repository 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE academic_data_repository;

-- Implementation of all tables as designed
-- (Full schema provided in Appendix C)
```

**Initial Data Population:**
Essential data was inserted to bootstrap the system:
```sql
-- Insert default categories
INSERT INTO categories (name, description, icon, color) VALUES
('CSV Files', 'Comma-separated values datasets', 'fa-file-csv', '#28a745'),
('Excel Files', 'Microsoft Excel spreadsheets', 'fa-file-excel', '#1a73e8'),
('JSON Files', 'JavaScript Object Notation data', 'fa-file-code', '#ff6b6b'),
('PDF Documents', 'Portable Document Format files', 'fa-file-pdf', '#dc3545'),
('Text Files', 'Plain text datasets', 'fa-file-alt', '#6c757d'),
('Images', 'Image datasets and visualizations', 'fa-file-image', '#007bff'),
('Other', 'Miscellaneous file formats', 'fa-file', '#6610f2');

-- Create admin user (password: admin123 - to be changed on first login)
INSERT INTO users (username, email, password_hash, role, first_name, last_name) 
VALUES ('admin', 'admin@university.edu', 
        '$2y$12$YourHashedPasswordHere', 
        'admin', 'System', 'Administrator');
```

**Database Optimization:**
Performance optimizations were implemented:
- Composite indexes for common query patterns
- Full-text indexes for search functionality
- Query result caching configuration
- Connection pooling setup
- Slow query log enabled for monitoring

### 4.3.2 Backend Development

The backend implementation followed a modular approach with clear separation of concerns:

**Directory Structure Implementation:**
```
/academic-data-repository/
├── config/
│   ├── config.php          # Configuration constants
│   └── database.php        # Database connection
├── includes/
│   ├── auth.php           # Authentication functions
│   ├── functions.php      # Utility functions
│   ├── upload.php         # File upload handling
│   ├── search.php         # Search functionality
│   └── version_control.php # Version management
├── public/
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Static images
├── uploads/              # Dataset storage
├── templates/            # HTML templates
└── index.php            # Entry point
```

**Core Configuration (config/config.php):**
```php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'academic_data_repository');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// Application settings
define('SITE_NAME', 'Academic Data Repository');
define('SITE_URL', 'http://localhost/academic-data-repository');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['csv', 'xlsx', 'xls', 'json', 'pdf', 'txt']);

// Security settings
define('SESSION_LIFETIME', 1800); // 30 minutes
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Pagination
define('ITEMS_PER_PAGE', 12);
```

**Database Connection (config/database.php):**
```php
<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
```

**Authentication Implementation (includes/auth.php):**
```php
<?php
session_start();

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function register($username, $email, $password, $role = 'student') {
        // Validate inputs
        if (!$this->validateRegistration($username, $email, $password)) {
            return ['success' => false, 'message' => 'Invalid input data'];
        }
        
        // Check if user exists
        if ($this->userExists($username, $email)) {
            return ['success' => false, 'message' => 'User already exists'];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Insert user
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, role) 
                VALUES (:username, :email, :password_hash, :role)
            ");
            
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':role' => $role
            ]);
            
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, password_hash, role 
            FROM users 
            WHERE email = :email AND is_active = 1
        ");
        
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            return ['success' => true, 'message' => 'Login successful'];
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login.php');
            exit;
        }
    }
    
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    public function canUpload() {
        return $this->isLoggedIn() && in_array($_SESSION['role'], ['admin', 'faculty', 'student']);
    }
}
```

### 4.3.3 Frontend Implementation

The frontend implementation focused on creating an intuitive and responsive user interface:

**Main Layout Template (templates/layout.php):**
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Academic Data Repository'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-database"></i> Academic Data Repository
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/datasets.php">
                            <i class="fas fa-folder-open"></i> Datasets
                        </a>
                    </li>
                    <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/upload.php">
                            <i class="fas fa-upload"></i> Upload
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/projects.php">
                            <i class="fas fa-project-diagram"></i> Projects
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex me-3" action="<?php echo SITE_URL; ?>/search.php" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search datasets..." 
                           value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                <i class="fas fa-user-edit"></i> Profile
                            </a></li>
                            <?php if ($auth->hasRole('admin')): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/">
                                <i class="fas fa-cog"></i> Admin Panel
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="py-4">
        <?php echo $content; ?>
    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2024 Academic Data Repository. All rights reserved.</p>
            <p>Developed for academic dataset sharing and collaboration</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/public/js/main.js"></script>
</body>
</html>
```

**Custom CSS Styling (public/css/style.css):**
```css
/* Custom styles for Academic Data Repository */

:root {
    --primary-color: #4a90e2;
    --secondary-color: #7b68ee;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
    --dark-color: #343a40;
    --light-color: #f8f9fa;
}

/* Gradient backgrounds */
.bg-gradient-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
}

/* Dataset cards */
.dataset-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 100%;
}

.dataset-card:
