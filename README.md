# Academic Data Repository: A Web-Based Platform for Dataset Sharing and Collaboration

[![PHP Version](https://img.shields.io/badge/PHP-8.0+-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-8.0+-green.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen.svg)]()

> **A comprehensive web-based platform designed for educational institutions to facilitate collaborative data projects between faculty and students.**

## 🎓 Project Overview

**Academic Data Repository** is a specialized platform developed for **Takoradi Technical University** that enables secure file sharing, version control, and project management capabilities specifically tailored for academic research and data analysis. The platform bridges the gap between faculty research initiatives and student learning outcomes through structured collaboration.

### 🏫 **Institutional Focus**
- **Target**: Academic institutions, particularly African universities
- **Users**: Faculty members, students, and administrators
- **Purpose**: Research collaboration, dataset sharing, and educational outcomes tracking

## ✨ Key Features

### 🔐 **Authentication & Authorization**
- **Role-based Access Control**: Admin, Faculty, Student roles with hierarchical permissions
- **Secure Authentication**: Bcrypt password hashing with salt
- **Session Management**: Secure session handling with timeout protection
- **CSRF Protection**: Token-based request validation

### 📊 **Dataset Management**
- **Multi-format Support**: CSV, Excel (XLSX, XLS), JSON, PDF, DOC, DOCX, TXT, Images (PNG, JPG, JPEG, GIF)
- **Excel Integration**: Full Excel file support with PhpSpreadsheet library
- **File Preview**: Built-in preview for various file types
- **Version Control**: Track changes, branching, merging, and rollback capabilities
- **Upload Restrictions**: Role-based upload permissions

### 👥 **Collaboration Tools**
- **Project Management**: Create, manage, and collaborate on research projects
- **Member Invitations**: Faculty can invite students to projects
- **Activity Tracking**: Monitor project progress and member contributions
- **Permission System**: Granular permissions for different user roles

### 📱 **User Experience**
- **Responsive Design**: Modern, mobile-friendly interface using Bootstrap 5.3
- **Real-time Updates**: Live download counts and review updates
- **Duplicate Prevention**: Smart handling of duplicate reviews and downloads
- **Search & Filter**: Advanced dataset browsing and filtering

### 🏢 **Academic Features**
- **Department Integration**: University department support
- **Research Workflow**: Complete research lifecycle tracking
- **Educational Analytics**: Learning outcomes and collaboration metrics
- **Faculty Mentorship**: Structured mentorship tools

## 🛠 Technology Stack

### **Backend**
- **PHP**: 8.0+ with modern syntax and features
- **Database**: MySQL 8.0+ with optimized queries
- **Security**: Prepared statements, input validation, secure file handling

### **Frontend**
- **HTML5**: Semantic markup with accessibility features
- **CSS3**: Modern styling with Bootstrap 5.3 framework
- **JavaScript**: ES6+ with progressive enhancement
- **Icons**: Font Awesome 6.0 for consistent iconography

### **Libraries & Dependencies**
- **PhpSpreadsheet**: Excel file processing and manipulation
- **Bootstrap**: Responsive UI framework
- **Font Awesome**: Icon library
- **Custom Autoloader**: Efficient class loading system

## 🚀 Quick Start Guide

### **Prerequisites**
- PHP 8.0 or higher (tested with PHP 8.4.8)
- MySQL 8.0 or higher
- Web server (Apache/Nginx or PHP built-in server)
- Composer (for dependency management)

### **Installation Steps**

#### **1. Download & Extract**
```bash
# Clone the repository
git clone <repository-url>
cd academic-data-repository

# Or download and extract ZIP file to your web server directory
```

#### **2. Database Setup**

**Option A: Web-based Installation (Recommended)**
1. Navigate to `http://localhost/[project-folder]/install.php`
2. Follow the installation wizard
3. The installer will create database and configure everything automatically

**Option B: Manual Database Setup**
```sql
-- Create database
CREATE DATABASE academic_collaboration;

-- Import schema
mysql -u root -p academic_collaboration < database/schema.sql
```

#### **3. Configuration**
Update `config/config.php` with your settings:
```php
// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'academic_collaboration');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Base URL
define('BASE_URL', 'http://localhost/[your-project-folder]/');
```

#### **4. File Permissions**
```bash
# Create required directories
mkdir uploads versions

# Set permissions (Linux/Mac)
chmod 755 uploads versions

# Windows: Ensure web server has write access
```

#### **5. Access the Platform**
1. Open your web browser
2. Navigate to `http://localhost/[project-folder]/`
3. Login with default admin credentials:
   - **Username**: `admin`
   - **Password**: `admin123`

⚠️ **Important**: Change the default admin password immediately after installation!

## 👥 User Roles & Permissions

### **🔧 Administrator**
- **Full system access** and user management
- **Dataset uploads** and system oversight
- **User role management** and permissions
- **System monitoring** and performance tracking

### **👨‍🏫 Faculty**
- **Create and manage** research projects
- **Invite students** to collaborate
- **Upload datasets** and research materials
- **Monitor project progress** and student contributions

### **🎓 Student**
- **Join projects** invited by faculty
- **Download and review** datasets
- **Contribute to projects** within assigned permissions
- **Track learning outcomes** and collaboration metrics

## 📁 Project Structure

```
academic-data-repository/
├── config/
│   ├── config.php              # Main configuration
│   └── database.php            # Database connection
├── database/
│   ├── schema.sql              # Database schema
│   ├── migrations.sql          # Database migrations
│   └── sample_data_schema.sql  # Sample data
├── includes/
│   ├── auth.php               # Authentication system
│   ├── functions.php          # Utility functions
│   └── footer.php             # Common footer
├── uploads/                   # File upload directory
├── versions/                  # Version control storage
├── vendor/                    # Composer dependencies
│   └── phpoffice/phpspreadsheet/  # Excel processing library
├── assets/                    # Static assets (CSS, JS, images)
├── image/                     # Platform images
├── index.php                  # Main homepage
├── login.php                  # Login page
├── register.php               # Registration page
├── dashboard.php              # User dashboard
├── admin.php                  # Admin dashboard
├── upload.php                 # File upload system
├── preview.php                # File preview system
├── projects.php               # Projects listing
├── project.php                # Individual project view
├── create-project.php         # Project creation
├── review.php                 # Review system
├── autoload.php               # Custom autoloader
└── README.md                  # This file
```

## 🔧 Configuration

### **Database Configuration**
```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'academic_collaboration');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### **File Upload Settings**
```php
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', [
    'csv', 'xlsx', 'xls', 'json', 'pdf', 
    'png', 'jpg', 'jpeg', 'gif', 'txt', 
    'doc', 'docx'
]);
```

### **Security Settings**
```php
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_NAME', 'csrf_token');
```

## 📊 Dataset Categories

The platform supports the following academic categories:
- **AI** (Artificial Intelligence)
- **ICT** (Information & Communication Technology)
- **AGRICULTURE**
- **TRANSPORT**
- **HEALTH**
- **EDUCATION**
- **BUSINESS**
- **ENGINEERING**

## 🔒 Security Features

### **Authentication Security**
- **Bcrypt Hashing**: Secure password storage with salt
- **Session Management**: Secure session handling with timeout
- **CSRF Protection**: Token-based request validation
- **Input Validation**: Comprehensive input sanitization

### **File Security**
- **Type Validation**: Strict file type checking
- **Size Limits**: Configurable file size restrictions
- **Secure Storage**: Files stored outside web root
- **Access Control**: Role-based file access permissions

### **Database Security**
- **Prepared Statements**: SQL injection prevention
- **Input Sanitization**: Comprehensive data cleaning
- **Access Control**: Role-based database access
- **Backup Protection**: Regular database backups

## 📈 Recent Updates (Version 1.0.0)

### **✅ New Features**
- **Excel Integration**: Full Excel file support with PhpSpreadsheet
- **Enhanced Preview**: Improved file preview with tabular display
- **Category Updates**: Updated categories (AI, ICT, AGRICULTURE, TRANSPORT)
- **Live Updates**: Real-time download counts and review updates
- **Duplicate Prevention**: Smart handling of duplicate submissions

### **🔧 Technical Improvements**
- **Memory Management**: Optimized Excel file processing
- **Error Handling**: Comprehensive error handling and user feedback
- **Performance**: Improved file upload and processing speed
- **Code Cleanup**: Removed temporary files and optimized codebase

### **📱 User Experience**
- **Responsive Design**: Enhanced mobile experience
- **Loading States**: Better user feedback during operations
- **Error Messages**: Clear and helpful error notifications
- **Accessibility**: Improved screen reader support

## 🚀 Development Roadmap

### **Phase 1: Core Platform ✅**
- [x] User authentication and authorization
- [x] Project management system
- [x] File upload and management
- [x] Excel file integration
- [x] Responsive user interface

### **Phase 2: Advanced Features (In Progress)**
- [ ] Real-time notifications
- [ ] Advanced search and filtering
- [ ] Data export and backup
- [ ] Analytics and reporting

### **Phase 3: Enhanced Collaboration**
- [ ] Real-time collaboration tools
- [ ] Advanced version control
- [ ] Integration with external tools
- [ ] Mobile application

### **Phase 4: Enterprise Features**
- [ ] API development
- [ ] Multi-institution support
- [ ] Advanced security features
- [ ] Performance optimization

## 🐛 Troubleshooting

### **Common Issues**

**Database Connection Failed**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Verify credentials in config/config.php
# Ensure database exists
mysql -u root -p -e "SHOW DATABASES;"
```

**File Upload Issues**
```bash
# Check directory permissions
ls -la uploads/
chmod 755 uploads versions

# Check PHP upload limits
php -i | grep upload
```

**Excel File Processing Issues**
```bash
# Verify PhpSpreadsheet installation
composer require phpoffice/phpspreadsheet

# Check autoloader
php autoload.php
```

### **Performance Optimization**
- Enable PHP OPcache
- Configure MySQL query cache
- Use CDN for static assets
- Implement file compression

## 🤝 Contributing

We welcome contributions from the academic community!

### **Development Setup**
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Make your changes following the coding standards
4. Test thoroughly
5. Commit your changes (`git commit -am 'Add new feature'`)
6. Push to the branch (`git push origin feature/new-feature`)
7. Create a Pull Request

### **Coding Standards**
- Follow PSR-12 coding standards
- Add comprehensive comments
- Include error handling
- Test all functionality
- Update documentation

## 📞 Support

### **Technical Support**
- **Issues**: Create an issue in the repository
- **Documentation**: Check the documentation wiki
- **Email**: Contact the development team

### **Academic Support**
- **Institutional Integration**: Contact for university-specific features
- **Training**: Available for faculty and staff
- **Customization**: Custom features for specific academic needs

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Development Team

**Takoradi Technical University - Computer Science Department**

- **Alexander Essien** - BC/ICT/21/075
- **Anthony Ofori Owusu** - BC/ICT/21/076  
- **McCarthy Mawuko Kwesi Defor** - BC/ICT/21/045
- **Broni John Eyra Kobby** - BC/ICT/21/081
- **Sylvia Esi Amoah** - BC/ICT/21/157

**Supervisor**: [Supervisor Name]
**Department**: Computer Science
**Institution**: Takoradi Technical University
**Year**: 2025

## 📚 Academic Context

This platform is specifically designed for **African academic institutions**, with particular focus on:
- **Ghanaian educational context**
- **Faculty-student collaboration patterns**
- **Research workflow requirements**
- **Educational outcome tracking**
- **Institutional integration needs**

## 🔄 Changelog

### **Version 1.0.0 (December 2025)**
- **Initial Release**: Complete academic collaboration platform
- **Excel Integration**: Full Excel file support with PhpSpreadsheet
- **Category Updates**: Updated academic categories
- **Security Enhancements**: Comprehensive security features
- **Performance Optimization**: Improved speed and reliability

---

**🎓 Academic Data Repository** - Empowering educational institutions with collaborative data management tools.

**Powered by TTU** | © 2025 Takoradi Technical University. All rights reserved.
