# Dataset Sharing and Collaboration Platform - Codebase Scan Report

## 📁 Project Overview

The **Dataset Sharing and Collaboration Platform** (formerly Academic Dataset Collaboration Platform) is a comprehensive web-based system designed for educational institutions to facilitate collaborative data projects between faculty and students.

## 🏗️ Architecture Overview

### Technology Stack
- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3
- **Security**: CSRF protection, bcrypt password hashing, prepared statements

## 📂 Directory Structure

```
DataShare Platform/
├── 📁 assets/                    # Static assets (CSS, JS, images)
│   ├── css/                      # Stylesheets
│   ├── img/                      # Images and icons
│   ├── js/                       # JavaScript files
│   └── scss/                     # SASS files
├── 📁 config/                    # Configuration files
│   ├── config.php                # Main configuration
│   ├── database.php              # Database connection
│   └── installed.lock            # Installation lock file
├── 📁 database/                  # Database schemas
│   ├── schema.sql                # Main database schema
│   ├── dataset_platform_schema.sql
│   ├── production_schema.sql
│   ├── project_schema.sql
│   ├── sample_data_schema.sql
│   └── migrations.sql
├── 📁 includes/                  # Core PHP includes
│   ├── auth.php                  # Authentication system
│   ├── csrf.php                  # CSRF protection
│   ├── excel_converter.php       # Excel to CSV converter
│   ├── footer.php                # Page footer template
│   ├── functions.php             # Utility functions
│   ├── header.php                # Page header template
│   ├── session.php               # Session management
│   └── version_control.php       # Version control system
├── 📁 uploads/                   # User uploaded files
├── 📁 versions/                  # File version storage
└── 📁 projext report writing templetes/  # Report templates
```

## 🔑 Core Components

### 1. **Authentication System** (`includes/auth.php`)
- **Class**: `Auth`
- **Features**:
  - User login/logout with email or username
  - User registration with validation
  - Role-based access control (Admin, Faculty, Student)
  - Password management
  - Session management
  - Activity logging

### 2. **Database Schema** (`database/schema.sql`)
- **Tables**:
  - `users` - User accounts with roles
  - `projects` - Collaborative projects
  - `project_members` - Project membership and permissions
  - `files` - File metadata
  - `file_versions` - Version control
  - `branches` - Version control branches
  - `activity_log` - Activity tracking
  - `file_comments` - Collaboration comments
  - `user_sessions` - Session management
  - `notifications` - User notifications

### 3. **Configuration** (`config/config.php`)
- **Settings**:
  - Application name: "Academic Dataset Collaboration Platform"
  - Database: MySQL (localhost)
  - File upload: 50MB max, supports CSV, Excel, JSON, PDF, images, documents
  - Security: CSRF tokens, 8-hour sessions, bcrypt hashing
  - Version control: Max 100 versions per file

### 4. **Excel Converter** (`includes/excel_converter.php`)
- **Features**:
  - Converts Excel files to CSV
  - Supports PhpSpreadsheet library (if installed)
  - Fallback to basic conversion if library not available
  - Auto-conversion on upload

## 📄 Main PHP Files

### User Interface Pages
1. **index.php** - Homepage
2. **login.php** / **login_new.php** - User login
3. **register.php** - User registration
4. **dashboard.php** - Main user dashboard
5. **admin.php** / **admin_dashboard.php** - Admin panel
6. **user_dashboard.php** - User-specific dashboard

### Dataset Management
1. **datasets.php** - Dataset listing
2. **upload.php** - File upload interface
3. **download.php** - File download handler
4. **preview.php** - File preview
5. **browse.php** - Browse datasets

### Project Management
1. **projects.php** - Project listing
2. **project.php** - Individual project view
3. **create-project.php** - Create new project

### User Management
1. **profile.php** - User profile
2. **review.php** - Review system
3. **logout.php** - Logout handler

### Utility Scripts
1. **install.php** - Installation wizard
2. **install_with_samples.php** - Installation with sample data
3. **test_db.php** - Database connection test
4. **scrape_datasets.php** - Dataset scraping utility
5. **create_admin.php** - Admin user creation

## 🔒 Security Features

1. **CSRF Protection** - Token-based request validation
2. **Password Security** - Bcrypt hashing with salt
3. **SQL Injection Prevention** - Prepared statements
4. **Session Security** - Secure session handling with timeout
5. **File Upload Security** - Type validation and secure storage
6. **Role-Based Access Control** - Admin, Faculty, Student roles

## 📊 Supported File Types

- **Data Files**: CSV, Excel (XLSX, XLS), JSON
- **Documents**: PDF, DOC, DOCX, TXT
- **Images**: PNG, JPG, JPEG, GIF

## 🚀 Key Features

1. **User Authentication & Authorization**
2. **Project Management** with collaboration
3. **File Management** with upload/download
4. **Version Control** with branching and merging
5. **Activity Tracking** and logging
6. **Notification System**
7. **Comment System** for files
8. **Excel to CSV Conversion**
9. **Responsive Design**

## 📝 Documentation Files

- **README.md** - Main documentation
- **FINAL_PROJECT_DOCUMENTATION.md** - Detailed project documentation
- **PROJECT_SUMMARY.md** - Project summary
- **the documentation for the project.md** - Additional documentation
- Multiple report files for DataShare Platform submission

## 🔧 Installation & Setup

The platform includes an installation wizard (`install.php`) that:
1. Sets up the database
2. Creates necessary tables
3. Configures the application
4. Creates admin user
5. Sets up upload directories

## 📈 Database Statistics

- **11 main tables** for complete functionality
- **Comprehensive indexing** for performance
- **Foreign key constraints** for data integrity
- **JSON fields** for flexible data storage
- **Default admin user** (admin/admin123)

## 🎯 Current Status

The platform appears to be a complete, production-ready system with:
- ✅ Full authentication system
- ✅ Role-based access control
- ✅ File upload/download functionality
- ✅ Version control system
- ✅ Project collaboration features
- ✅ Activity tracking
- ✅ Excel conversion support
- ✅ Responsive UI design

## 🔄 Recent Updates

Based on the codebase:
- Project name standardized to "Dataset Sharing and Collaboration Platform"
- Excel converter integrated into upload.php and admin.php
- Comprehensive documentation added
- Multiple report formats generated for DataShare Platform submission
