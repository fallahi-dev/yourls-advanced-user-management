# YOURLS Advanced User Management

**Plugin URI:** 
**Description:** A powerful plugin for YOURLS to manage users, roles, capabilities, and password changes with advanced control and security features.
**Version:** 0.0.1
**Author:** Fallahi Dev
**Author URI:** https://fallahi.dev

## Description

The YOURLS Advanced User Management plugin provides comprehensive user, role, and capability management for YOURLS. This plugin enhances the default YOURLS installation with advanced control and security features.

## Features

### User Management
- Add, update, and delete users.
- Manage user roles and capabilities.
- User-specific link assignment and tracking.
- Password change functionality for users.

### Role Management
- Define and manage multiple roles.
- Assign capabilities to roles.
- Default roles: Administrator, Editor, Contributor.

### Capability Management
- Predefined capabilities to control access to various parts of YOURLS.
- Custom capabilities can be defined and assigned.

### Security
- Encrypted password storage using phpass.
- Session management and validation.
- Nonce verification for secure actions.

### Database Integration
- Custom tables for users, roles, capabilities, and role-capability assignments.
- Foreign key constraints for data integrity.

### Admin Interface
- User management page for adding, updating, and deleting users.
- Role management page for assigning capabilities to roles.
- Password change page for users.

## Installation

1. Download the plugin and unzip it.
2. Upload the plugin files to the `/user/plugins/yourls-advanced-user-management` directory.
3. Activate the plugin through the YOURLS Admin interface.

## Usage

1. Navigate to the YOURLS Admin interface.
2. Use the provided pages to manage users, roles, and capabilities.
3. Assign links to users and manage access through the defined capabilities.

## Files and Directories

### Includes
- `class-database-manager.php`: Manages database interactions and schema creation.
- `class-roles-capabilities.php`: Defines and manages roles and capabilities.
- `class-user-management.php`: Handles user management operations.
- `class-role-management.php`: Manages role-related operations.
- `functions.php`: Contains helper functions and initialization routines.

### Templates
- `user-management-template.php`: Template for the user management page.
- `role-management-template.php`: Template for the role management page.
- `change-password-template.php`: Template for the password change page.

### Assets
- `assets/`: Directory for storing plugin assets such as CSS and JavaScript files.

## Contributing

Contributions are welcome! Please fork the repository and submit pull requests.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
