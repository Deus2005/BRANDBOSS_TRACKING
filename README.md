# Installation & Maintenance Tracking System

A comprehensive web-based system for managing installation workflows, inspections, and maintenance operations with GPS-enabled photo documentation.

## 🎯 Features

### Role-Based Access Control
- **Super Admin**: Full system access
- **User 1 (Manager)**: Inventory control, user management, assignments, full monitoring
- **User 2 (Installer)**: Installation execution with GPS-tagged photos
- **User 3 (Inspector)**: Monthly inspections for 6 months, issue escalation
- **User 4 (Maintenance)**: Repair/replacement handling, item requests

### Core Modules

1. **Inventory Management**
   - Item categories and stock tracking
   - Stock transactions (in/out/reserved)
   - Low stock alerts
   - Optimized for large datasets

2. **User Management**
   - Role-based permissions
   - User status management
   - Activity logging

3. **Installation Areas**
   - GPS coordinates storage
   - Area categorization by city/province/region

4. **Assignments**
   - Item allocation to installers
   - Priority levels (low, normal, high, urgent)
   - Due date tracking
   - Stock reservation

5. **Installation Reports**
   - Before/After photo capture
   - Automatic GPS watermarking on photos
   - Per-item reporting with quantities and remarks
   - Automatic inspection schedule creation (6 months)

6. **Inspections**
   - 6-month inspection cycle per installation
   - Status tracking (Intact/Damaged/Missing/Needs Replacement)
   - Issue escalation to maintenance
   - GPS-tagged inspection photos

7. **Maintenance**
   - Ticket management with priority levels
   - Item request from inventory
   - Action logging with photos
   - Ticket lifecycle tracking

## Tech Stack

- **Backend**: PHP 7.4+ with PDO (MySQL)
- **Frontend**: Bootstrap 5 (Mobile-First)
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Maps**: Leaflet.js with OpenStreetMap
- **JavaScript**: Vanilla JS with AJAX
- **Theme**: Red & White color scheme

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ / MariaDB 10.3+
- Apache/Nginx web server
- GD Library (for image watermarking)
- mod_rewrite enabled (optional)

## Installation

1. **Upload Files**
   ```bash
   # Upload all files to your web server's document root
   ```

2. **Create Database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Configure Database Connection**
   Edit `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'installation_tracking');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/before/
   chmod 755 uploads/after/
   chmod 755 uploads/maintenance/
   chmod 755 uploads/inspections/
   ```

5. **Access the System**
   ```
   URL: http://your-domain/
   Default Login:
   Username: superadmin
   Password: password
   ```

## Directory Structure

```
installation-tracking-system/
├── ajax/                    # AJAX handlers
├── assets/
│   ├── css/style.css       # Custom styles (Red/White theme)
│   ├── js/app.js           # Main JavaScript
│   └── images/
├── classes/
│   ├── Database.php        # PDO wrapper with pagination
│   └── Auth.php            # Authentication & authorization
├── config/
│   └── config.php          # System configuration
├── database/
│   └── schema.sql          # Complete database schema
├── includes/
│   ├── header.php          # Page header with navigation
│   ├── footer.php          # Page footer
│   └── helpers.php         # Utility functions
├── modules/
│   ├── inventory/          # Item management
│   ├── users/              # User management
│   ├── areas/              # Installation areas
│   ├── assignments/        # Work assignments
│   ├── installations/      # Installation reports
│   ├── inspections/        # Monthly inspections
│   ├── maintenance/        # Maintenance tickets
│   └── reports/            # Reporting module
├── uploads/
│   ├── before/             # Before installation photos
│   ├── after/              # After installation photos
│   ├── maintenance/        # Maintenance photos
│   └── inspections/        # Inspection photos
├── index.php               # Dashboard
├── login.php               # Login page
├── logout.php              # Logout handler
└── README.md
```

## Process Flow

```
1. User 1 creates inventory items and installation areas
2. User 1 creates assignment with items → assigns to User 2
3. User 2 goes to location and submits installation report
   - Captures before/after photos (GPS watermarked)
   - System auto-creates 6-month inspection schedule
4. User 3 conducts monthly inspections
   - Checks item status (intact/damaged/missing)
   - Escalates issues to maintenance if needed
5. User 4 handles maintenance tickets
   - Can request additional items from inventory
   - Logs all maintenance actions
6. User 1 monitors all activities and reports
```

## Security Features

- Password hashing with `password_hash()`
- Prepared statements (SQL injection prevention)
- XSS protection with output escaping
- Session-based authentication
- Role-based access control
- Activity logging/audit trail

## Mobile-First Design

The system is designed with mobile users in mind:
- Responsive Bootstrap 5 layout
- Touch-friendly buttons and controls
- Camera integration for photo capture
- GPS location services
- Offline-capable photo uploads

## Customization

### Change Theme Colors
Edit `assets/css/style.css`:
```css
:root {
    --primary: #DC3545;      /* Main red */
    --primary-dark: #B02A37; /* Darker red */
    --primary-light: #F8D7DA; /* Light red */
}
```

### Add Custom Roles
Edit `config/config.php` to add new roles and permissions.

