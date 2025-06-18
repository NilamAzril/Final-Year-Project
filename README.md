# Final-Year-Project

# Contractor Billing & Expense Management System

## Overview

This is a web-based platform for managing contractor billing, project expenses, and financial reporting. It supports multiple user roles (Admin, Project Manager, Contractor), provides dashboards for each, and automates invoice, project, and client management. The system is built with PHP and uses MySQL for data storage. PDF generation is handled via the [TCPDF](https://github.com/tecnickcom/TCPDF) library.

## Features

- **User Authentication**: Secure login and signup for Project Managers and Contractors; Admin creation via script.
- **Role-Based Dashboards**: Separate dashboards for Admin, Project Manager, and Contractor, each with tailored features.
- **Project Management**: Add, edit, view, and delete projects; assign contractors and clients.
- **Client Management**: Add, edit, view, and delete clients.
- **User Management**: Admin can manage Project Managers and Contractors.
- **Invoice Management**: Create, approve, download, and view invoices; upload supporting documents.
- **Expense Tracking**: Track project expenses and generate financial reports.
- **PDF Generation**: Generate project statements and financial reports as PDFs using TCPDF.
- **Audit Logging**: Actions are logged for traceability.
- **Responsive UI**: Modern, mobile-friendly interface using Bootstrap.

## Directory Structure

```
htdocs/
  ├── admin/            # Admin dashboard and management modules
  ├── contractor/       # Contractor dashboard and modules
  ├── projectmanager/   # Project Manager dashboard and modules
  ├── config/           # Database and utility functions
  ├── includes/         # Additional config files
  ├── Uploads/          # Uploaded files (invoices, profile pictures, etc.)
  ├── vendor/           # Composer dependencies (TCPDF, etc.)
  ├── index.php         # Landing page
  ├── login.php         # User login
  ├── signup.php        # User registration (Project Manager/Contractor)
  ├── logout.php        # Logout script
  ├── create_admin.php  # Script to create/reset admin user
  ├── test_db.php       # Script to test database connection
  ├── composer.json     # PHP dependencies
```

## Installation

1. **Clone the repository** and place it in your web server's root directory.
2. **Install dependencies** (TCPDF is included via Composer):
   ```
   php composer.phar install
   ```
3. **Configure the database**:
   - Edit `config/database.php` with your MySQL credentials and database name.
   - Import the required database schema (not included here; see your SQL migration files or ask the maintainer).
4. **Create the admin user**:
   - Run `create_admin.php` in your browser or via CLI to create/reset the default admin account.
   - Default credentials: `admin` / `admin123` (change after first login).
5. **Test the database connection** (optional):
   - Run `test_db.php` to verify your database setup.

## Usage

- **Access the system** via `index.php` in your browser.
- **Sign up** as a Project Manager or Contractor via `signup.php`.
- **Admin login** via `login.php` (use credentials from `create_admin.php`).
- **Admin Panel**: Manage users, clients, projects, and view financial reports.
- **Project Manager/Contractor Panels**: Manage assigned projects, tasks, invoices, and profile.

## User Roles

- **Admin**: Full access to all modules, user and client management, financial reports, and audit logs.
- **Project Manager**: Manage projects, assign contractors, track progress, and approve invoices.
- **Contractor**: View assigned projects, submit invoices, track tasks, and update profile.

## PDF Generation

- The system uses [TCPDF](https://github.com/tecnickcom/TCPDF) for generating PDF reports and project statements.
- PDF errors are logged in `admin/pdf_errors.log` and `projectmanager/pdf_errors.log`.
- If you encounter PDF generation issues, check these logs for troubleshooting.

## Troubleshooting

- **Database Connection Issues**: Check your credentials in `config/database.php` and run `test_db.php`.
- **PDF Generation Issues**: Ensure the `vendor/` directory exists and contains TCPDF. Check the error logs mentioned above.
- **File Uploads**: Ensure the `Uploads/` directory is writable by the web server.
- **Session Issues**: Make sure PHP sessions are enabled and configured correctly on your server.

## License

- The main application is released under your chosen license (add here if applicable).
- [TCPDF](https://github.com/tecnickcom/TCPDF) is LGPL v3. See `vendor/tecnickcom/tcpdf/LICENSE.TXT` for details. 
