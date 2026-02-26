# Codebase Audit Report - Academic Data Repository

## Executive Summary

This audit covers the Academic Dataset Sharing Platform, a PHP-based web application deployed on Vercel with Supabase/PostgreSQL database integration. The application provides dataset upload, preview, download, and collaboration features.

---

## PHASE 1: SCAN & AUDIT FINDINGS

### 1. Architecture Analysis

#### Directory Structure
```
/
├── api/index.php           # Vercel serverless entry point
├── config/
│   ├── config.php          # App configuration
│   └── database.php        # Database connection class
├── includes/
│   ├── auth.php            # Authentication class
│   ├── csrf.php            # CSRF protection
│   ├── session.php         # Session management
│   ├── storage.php         # Supabase Storage integration
│   ├── functions.php       # Utility functions
│   ├── header.php          # Common header
│   └── footer.php          # Common footer
├── assets/                 # Static files (CSS, JS, images)
├── *.php                   # Page-level PHP files (20+ files)
└── vercel.json             # Vercel configuration
```

#### Issues Identified

| Severity | Issue | Location |
|----------|-------|----------|
| **HIGH** | No MVC pattern - business logic mixed with presentation | All page files |
| **MEDIUM** | Multiple Database instantiations per request | Various files |
| **MEDIUM** | Dead code - unused files (project.php, projects.php) | Root directory |
| **LOW** | Inconsistent file naming conventions | Root directory |

---

### 2. Supabase Integration Analysis

#### Current Implementation
- **Database**: Uses PDO with PostgreSQL connection string from environment variables
- **Storage**: [`includes/storage.php`](includes/storage.php:1) implements Supabase Storage API via REST
- **Auth**: Custom session-based auth, NOT using Supabase Auth

#### Critical Issues

| Severity | Issue | Details |
|----------|-------|---------|
| **CRITICAL** | No Row Level Security (RLS) | Direct database queries bypass Supabase RLS |
| **CRITICAL** | Service Role Key in backend | Using `SUPABASE_SERVICE_ROLE_KEY` which bypasses all security |
| **HIGH** | Custom auth instead of Supabase Auth | Missing JWT validation, no OAuth support |
| **HIGH** | No Supabase client singleton | Multiple database connections per request |
| **MEDIUM** | Emulated prepares enabled | [`config/database.php:98`](config/database.php:98) - potential SQL injection risk |

#### Code Examples of Concern

```php
// includes/storage.php:10 - Service role key usage
$this->key = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '';

// config/database.php:98 - Emulated prepares
PDO::ATTR_EMULATE_PREPARES => true,
```

---

### 3. Vercel Configuration Analysis

#### Current [`vercel.json`](vercel.json:1)
```json
{
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.9.0"
    }
  },
  "routes": [...]
}
```

#### Issues Identified

| Severity | Issue | Recommendation |
|----------|-------|----------------|
| **MEDIUM** | Outdated PHP runtime | Upgrade to `vercel-php@0.10.0` or newer |
| **MEDIUM** | No cache headers | Add cache headers for static assets |
| **LOW** | No environment variable validation | Add startup validation |
| **LOW** | Missing health check endpoint | Add `/api/health` endpoint |

---

### 4. Code Quality & Security Analysis

#### SQL Injection Analysis

**Status: MOSTLY SAFE** - Using prepared statements throughout

| File | Line | Status |
|------|------|--------|
| [`browse.php`](browse.php:50) | 50 | ✅ Safe - parameterized |
| [`admin.php`](admin.php:75) | 75 | ✅ Safe - parameterized |
| [`login.php`](login.php:28) | 28 | ✅ Safe - parameterized |

**Concern**: [`config/database.php:98`](config/database.php:98) enables emulated prepares which can be risky with certain character encodings.

#### XSS Vulnerability Analysis

**Status: GOOD** - Consistent use of `htmlspecialchars()`

Found 106 instances of `htmlspecialchars()` usage across the codebase. Output is properly escaped.

#### CSRF Protection

**Status: IMPLEMENTED BUT NOT ENFORCED**

- [`includes/csrf.php`](includes/csrf.php:1) provides CSRF functions
- **Issue**: CSRF validation is NOT called on most POST handlers
- Only [`includes/csrf.php:36-42`](includes/csrf.php:36-42) defines `checkCSRFToken()` but it's not invoked

#### Missing CSRF Protection Locations

| File | Line | Issue |
|------|------|-------|
| [`admin.php`](admin.php:29) | 29 | POST upload_dataset - no CSRF |
| [`login.php`](login.php:15) | 15 | POST login - no CSRF |
| [`register.php`](register.php:14) | 14 | POST registration - no CSRF |
| [`review.php`](review.php:64) | 64 | POST review - no CSRF |
| [`profile.php`](profile.php:47) | 47 | POST update_profile - no CSRF |

#### Input Validation Issues

| Severity | Issue | Location |
|----------|-------|----------|
| **HIGH** | No file type validation on upload | [`admin.php:42-46`](admin.php:42-46) |
| **MEDIUM** | No rate limiting on auth endpoints | [`login.php`](login.php:1) |
| **MEDIUM** | Email validation only on client side | [`register.php`](register.php:1) |

#### Error Handling Issues

| Severity | Issue | Location |
|----------|-------|----------|
| **MEDIUM** | Display errors enabled in production | [`config/config.php:6`](config/config.php:6) |
| **LOW** | Silent failures in try-catch blocks | Multiple files |

```php
// config/config.php:5-6 - Should be disabled in production
error_reporting(E_ALL);
ini_set('display_errors', '1');
```

---

### 5. N+1 Query Analysis

**Issues Found:**

| File | Lines | Issue |
|------|-------|-------|
| [`index.php`](index.php:11-26) | 11-26 | 5 separate COUNT queries - should be 1 |
| [`admin_dashboard.php`](admin_dashboard.php:23-44) | 23-44 | 7 separate queries for dashboard stats |
| [`user_dashboard.php`](user_dashboard.php:38-90) | 38-90 | 6 separate queries for user stats |

---

### 6. PSR-12 Violations

| Issue | Files Affected |
|-------|----------------|
| No namespace declarations | All PHP files |
| Mixed indentation (tabs/spaces) | Multiple files |
| Missing type declarations | Most functions |
| Long lines (>120 chars) | Multiple files |
| Inline CSS in PHP files | admin.php, dashboard.php, browse.php, etc. |

---

## PHASE 2: REFACTOR RECOMMENDATIONS

### Priority 1: Critical Security Fixes

#### 1.1 Implement CSRF Protection on All Forms

```php
// Add to all POST handlers
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}
```

#### 1.2 Disable Display Errors in Production

```php
// config/config.php
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
```

#### 1.3 Add Rate Limiting for Auth Endpoints

Create middleware for login/register endpoints.

---

### Priority 2: Architecture Improvements

#### 2.1 Implement Proper MVC Structure

```
/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DatasetController.php
│   │   └── DashboardController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Dataset.php
│   │   └── Review.php
│   ├── Services/
│   │   ├── SupabaseService.php
│   │   └── AuthService.php
│   └── Middleware/
│       ├── CSRF.php
│       └── Auth.php
├── config/
├── public/
│   └── index.php (single entry point)
└── views/
```

#### 2.2 Create Supabase Service Singleton

```php
// app/Services/SupabaseService.php
class SupabaseService {
    private static ?PDO $connection = null;
    
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }
}
```

---

### Priority 3: Supabase Best Practices

#### 3.1 Switch to Supabase Auth

Replace custom session auth with Supabase Auth:
- Use Supabase GoTrue for authentication
- Validate JWTs server-side
- Remove custom password hashing

#### 3.2 Implement RLS Policies

```sql
-- Example RLS policy for datasets
CREATE POLICY "Users can view active datasets"
ON datasets FOR SELECT
USING (is_active = true);

CREATE POLICY "Only admins can insert datasets"
ON datasets FOR INSERT
WITH CHECK (
    EXISTS (
        SELECT 1 FROM users 
        WHERE id = auth.uid() 
        AND role = 'admin'
    )
);
```

#### 3.3 Use Supabase Client Library

Replace raw PDO with Supabase PHP SDK or use REST API with proper RLS.

---

### Priority 4: Vercel Optimization

#### 4.1 Update vercel.json

```json
{
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.10.1",
      "memory": 1024,
      "maxDuration": 10
    }
  },
  "headers": [
    {
      "source": "/assets/(.*)",
      "headers": [
        { "key": "Cache-Control", "value": "public, max-age=31536000, immutable" }
      ]
    }
  ],
  "routes": [...]
}
```

#### 4.2 Add Health Check Endpoint

```php
// api/health.php
header('Content-Type: application/json');
echo json_encode(['status' => 'healthy', 'timestamp' => time()]);
```

---

### Priority 5: Code Quality Improvements

#### 5.1 Extract Inline CSS to External Files

Move all inline `<style>` blocks from PHP files to dedicated CSS files.

#### 5.2 Add Type Declarations

```php
function sanitizeInput(string $data): string {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
```

#### 5.3 Consolidate Database Queries

```php
// Before: 5 separate queries
$stmt = $pdo->query("SELECT COUNT(*) FROM datasets");
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
// ...

// After: Single query
$stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM datasets) as total_datasets,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT SUM(download_count) FROM datasets) as total_downloads,
        (SELECT COUNT(*) FROM reviews) as total_reviews
");
```

---

## Summary of Findings

| Category | Critical | High | Medium | Low |
|----------|----------|------|--------|-----|
| Security | 2 | 3 | 2 | 1 |
| Architecture | 0 | 1 | 2 | 1 |
| Supabase | 2 | 2 | 1 | 0 |
| Vercel | 0 | 0 | 2 | 2 |
| Code Quality | 0 | 0 | 3 | 2 |

## Recommended Action Order

1. **Immediate**: Add CSRF protection to all forms
2. **Immediate**: Disable display_errors in production
3. **Short-term**: Implement rate limiting on auth endpoints
4. **Short-term**: Create SupabaseService singleton
5. **Medium-term**: Migrate to Supabase Auth
6. **Medium-term**: Implement RLS policies
7. **Long-term**: Refactor to MVC architecture
8. **Long-term**: Extract inline CSS to external files

---

*Report generated: 2026-02-19*
*Auditor: Kilo Code Architect Mode*
