# DepEd Inventory Management System (Version 2.0)

## Overview
The DepEd Inventory Management System is a comprehensive, professional-grade web application designed to streamline inventory tracking, requisition management, and reporting for educational institutions.

## Features
- **Real-time Tracking**: Monitor stock levels dynamically with automated alerts and precision tracking.
- **Smart Requisitions**: Simplified multi-item requests with real-time status updates for all employees.
- **Predictive Alerts**: Always stay ahead of shortages with intelligent threshold-based notifications.
- **Comprehensive Reporting**: Generates various DepEd-standard reports and appendices (RPCI, RSMI, RIS, etc.) in Excel format.

## Technology Stack
- **Backend**: PHP (Object-Oriented)
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Architecture**: MVC (Model-View-Controller) Pattern

## Directory Structure
- `api/` - Backend API endpoints for asynchronous requests.
- `assets/`, `css/`, `js/`, `images/`, `img/` - Frontend assets and logic.
- `controller/` - Application logic and request routing.
- `model/` - Database interactions and data models.
- `view/` - Admin UI views, components, and dashboard interfaces.
- `db/` - Database connection and configuration scripts.
- `files/` - System templates, generated Excel reports, and user uploads.
- `includes/` - Reusable PHP components (headers, footers, security initialization).
- `logs/` - Security and system event logs.

## Setup Instructions
1. Clone or copy the repository to your local web server directory (e.g., `c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System`).
2. Import the database schema into your MySQL instance.
3. Update database credentials in `db/database.php` if required.
4. Access the system via your local server (e.g., `http://localhost/OJT%20DEVELOPMENT/Inventory_System/`).

## Security
- Features secure session initialization.
- Built-in `security.php` enforcing authentication constraints.
- Dedicated `logs/security.log` tracking authorization events.
