# Dataset Sharing and Collaboration Platform

A comprehensive web-based platform designed for educational institutions to facilitate collaborative data projects between faculty and students. The platform provides secure file sharing, version control, and project management capabilities specifically tailored for academic research and data analysis.

## Features

### Core Functionality
- **User Authentication & Authorization**: Role-based access control (Admin, Faculty, Student)
- **Project Management**: Create, manage, and collaborate on data projects
- **File Management**: Upload, share, and organize multiple file formats
- **Version Control**: Track changes, branching, merging, and rollback capabilities
- **Collaboration Tools**: Member invitations, permissions, and activity tracking
- **Responsive Design**: Modern, mobile-friendly interface

### Supported File Types
- **Data Files**: CSV, Excel (XLSX, XLS), JSON
- **Documents**: PDF, DOC, DOCX, TXT
- **Images**: PNG, JPG, JPEG, GIF

#### Excel File Support
The platform includes basic Excel to CSV conversion functionality:
- **Current Implementation**: Basic conversion that creates a placeholder CSV file with conversion information
- **Full Conversion**: To enable full Excel to CSV conversion with actual data extraction, install the PhpSpreadsheet library:
  ```bash
  composer require phpoffice/phpspreadsheet
  ```
- **Fallback Behavior**: If PhpSpreadsheet is not installed, Excel files are accepted but converted to a basic CSV format with file information only

### User Roles & Permissions
- **Admin**: Full system access, user management, system oversight
- **Faculty**: Create/manage projects, invite students, full project control
- **Student**: Join projects, upload/edit assigned data, collaborate within permissions

## Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.0
- **Security**: CSRF protection, prepared statements, bcrypt hashing

## Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (optional, for future dependencies)

### Step 1: Download & Extract
```bash
# Clone or download the project files
git clone <repository-url>
cd academic-collaboration-platform
```

### Step 2: Database Setup

You can set up the database either manually or using the web-based installation wizard.

#### Manual Setup
1. Create a MySQL database named `dataset_platform`:
```sql
CREATE DATABASE dataset_platform;
```

2. Import the database schema:
```bash
mysql -u root -p dataset_platform < database/schema.sql
```

3. Update database configuration in `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dataset_platform');
define('DB_USER', 'root');
define('DB_PASS', '1212');
```

#### Web-based Installation Wizard
Alternatively, you can use the web-based installation wizard by accessing `install.php` in your browser. This wizard will guide you through database setup, admin user creation, and final configuration.

### Step 3: File Permissions
Create and set permissions for upload directories:
```bash
mkdir uploads versions
chmod 755 uploads versions
```

### Step 4: Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}

# Security headers
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options DENY;
add_header X-XSS-Protection "1; mode=block";
```

### Step 5: Configuration
1. Update `config/config.php` with your settings:
   - Database credentials
   - Base URL
   - File upload limits
   - Email settings (for notifications)

2. Set appropriate timezone in `config/config.php`:
```php
date_default_timezone_set('Your/Timezone');
```

## Default Login Credentials

The system comes with a default admin account:
- **Username**: `admin`
- **Password**: `admin123`

**Important**: Change the default admin password immediately after installation!

## Quick Start Guide - How to Run the Platform

### Step-by-Step Instructions to Get the Platform Running:

#### 1. **Prerequisites Check**
Ensure you have the following installed:
- **PHP 8.0 or higher**
- **MySQL 8.0 or higher**
- **Apache or Nginx web server**
- **phpMyAdmin** (optional, for easier database management)

#### 2. **Download and Setup Project Files**
```bash
# Option A: Clone from repository
git clone <repository-url>
cd academic-collaboration-platform

# Option B: Download and extract ZIP file
# Extract to your web server directory (e.g., htdocs, www, public_html)
```

#### 3. **Configure Your Web Server**

**For XAMPP/WAMP/MAMP users:**
- Place the project folder in your `htdocs` directory
- Start Apache and MySQL services from the control panel

**For standalone Apache:**
- Copy project to `/var/www/html/` (Linux) or `C:/Apache24/htdocs/` (Windows)
- Ensure Apache is running

#### 4. **Database Setup**

**Option A: Using phpMyAdmin (Easier)**
1. Open phpMyAdmin in your browser (usually http://localhost/phpmyadmin)
2. Create a new database named `dataset_platform`
3. Select the database and click "Import"
4. Choose the file `database/schema.sql` from the project
5. Click "Go" to import

**Option B: Using Command Line**
```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE dataset_platform;
EXIT;

# Import schema
mysql -u root -p dataset_platform < database/schema.sql
```

#### 5. **Configure Database Connection**
Edit `config/config.php` and update these lines with your MySQL credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dataset_platform');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '1212');        // Your MySQL password
```

#### 6. **Create Required Directories**
In the project root directory, create these folders if they don't exist:
```bash
mkdir uploads versions
# On Windows, create these folders manually
```

#### 7. **Access the Platform**
1. Open your web browser
2. Navigate to:
   - **XAMPP/WAMP:** `http://localhost/academic-collaboration-platform/`
   - **Custom setup:** `http://localhost/[your-project-folder]/`
3. You should see the platform homepage

#### 8. **Login to the Platform**
Use the default admin credentials:
- **Username:** `admin`
- **Password:** `admin123`

⚠️ **Important:** Change the admin password immediately after first login!

#### 9. **Alternative: Use Web Installer**
If you prefer automated setup:
1. Navigate to `http://localhost/[your-project-folder]/install.php`
2. Follow the installation wizard steps
3. The installer will create the database and configure everything for you

### Common Issues and Solutions

**Issue: "Database connection failed"**
- Check MySQL is running
- Verify credentials in `config/config.php`
- Ensure database `dataset_platform` exists

**Issue: "Page not found" errors**
- Check if `.htaccess` file exists (for Apache)
- Enable `mod_rewrite` in Apache
- Verify project is in correct web directory

**Issue: "Permission denied" for uploads**
- Set proper permissions: `chmod 755 uploads versions`
- On Windows, ensure web server has write access

### Next Steps After Setup
1. Change admin password
2. Create user accounts for faculty and students
3. Start uploading datasets
4. Create projects and invite collaborators
5. Explore the platform features!

## Usage Guide

### For Faculty Members
1. **Create Account**: Register with faculty role
2. **Create Project**: Use "Create Project" to start new collaboration
3. **Invite Students**: Add students to your projects
4. **Manage Files**: Upload, organize, and version control data files
5. **Monitor Activity**: Track project progress and member contributions

### For Students
1. **Create Account**: Register with student role
2. **Join Projects**: Accept invitations from faculty
3. **Collaborate**: Upload files, contribute to data analysis
4. **Track Changes**: View version history and project updates

### For Administrators
1. **User Management**: Oversee all users and their roles
2. **System Monitoring**: Track platform usage and performance
3. **Project Oversight**: Monitor all projects across the institution
4. **Security Management**: Manage system security and permissions

## File Structure

```
academic-collaboration-platform/
├── config/
│   ├── config.php          # Main configuration
│   └── database.php        # Database connection
├── database/
│   └── schema.sql          # Database schema
├── includes/
│   ├── auth.php           # Authentication system
│   └── functions.php      # Utility functions
├── uploads/               # File upload directory
├── versions/              # Version control storage
├── login.php             # Login page
├── register.php          # Registration page
├── dashboard.php         # Main dashboard
├── projects.php          # Projects listing
├── project.php           # Individual project view
├── create-project.php    # Project creation
└── README.md            # This file
```

## Security Features

- **Password Security**: Bcrypt hashing with salt
- **CSRF Protection**: Token-based request validation
- **SQL Injection Prevention**: Prepared statements
- **File Upload Security**: Type validation and secure storage
- **Session Management**: Secure session handling with timeout
- **Access Control**: Role-based permissions system

## Development Roadmap

### Phase 1: Core Platform ✅
- [x] User authentication and authorization
- [x] Project management system
- [x] Basic file operations
- [x] Responsive user interface

### Phase 2: Advanced Features (In Progress)
- [ ] File upload system with validation
- [ ] Version control with branching/merging
- [ ] Member invitation system
- [ ] File preview and comparison tools

### Phase 3: Enhanced Collaboration
- [ ] Real-time notifications
- [ ] Advanced search and filtering
- [ ] Data export and backup
- [ ] Analytics and reporting

### Phase 4: Enterprise Features
- [ ] API development
- [ ] Integration capabilities
- [ ] Advanced security features
- [ ] Performance optimization

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## Support

For technical support or questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation wiki

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Changelog

### Version 1.0.0 (Current)
- Initial release with core functionality
- User authentication and project management
- Basic file operations and responsive design
- Role-based access control system

---

**Dataset Sharing and Collaboration Platform** - Empowering educational institutions with collaborative data management tools.
