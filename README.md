# YOURLS Advanced User Management

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

### User Management

- **Accessing the User Management Page:**
  - Navigate to the YOURLS Admin interface.
  - Click on "User Management" in the plugin menu.
  - On this page, you can add new users, update existing users, and delete users.

- **Adding a User:**
  - Enter a username, password, and select a role.
  - Click "Add User" to create the new user.

- **Updating a User:**
  - Click the "Edit" button next to the user you want to update.
  - Modify the username, password, or role as needed.
  - Click "Update User" to save changes.

- **Deleting a User:**
  - Click the "Delete" button next to the user you want to delete.
  - Confirm the deletion. You will need to transfer the user's links to another user.

### Role Management

- **Accessing the Role Management Page:**
  - Navigate to the YOURLS Admin interface.
  - Click on "Role Management" in the plugin menu.
  - On this page, you can manage roles and their associated capabilities.

- **Modifying Roles:**
  - Add new roles by defining their capabilities.
  - Update existing roles by adding or removing capabilities.
  - Roles define what actions users assigned to them can perform.

### Capability Management

- **Managing Capabilities:**
  - Capabilities define specific actions that users can perform (e.g., adding URLs, managing plugins).
  - Assign capabilities to roles through the Role Management page.

### Password Change

- **Accessing the Password Change Page:**
  - Navigate to the YOURLS Admin interface.
  - Click on "Change Password" in the plugin menu.
  - On this page, users can update their password.

- **Changing Password:**
  - Enter the current password, new password, and confirm the new password.
  - Click "Change Password" to update.

### Initial Configuration

- **Role-Capabilities Configuration:**
  - The initial roles and capabilities are configured through a JSON file located at `includes/role-capabilities-config.json`.
  - Modify this file to change the default roles and their capabilities.
  - Example configuration:
    ```json
    {
        "roles": {
            "Administrator": {
                "capabilities": ["ShowAdmin", "AddURL", "DeleteURL", "EditURL", "ManageUsers", "ManageRoles", "ManagePlugins"]
            },
            "Editor": {
                "capabilities": ["ShowAdmin", "AddURL", "EditURL", "ViewStats"]
            },
            "Contributor": {
                "capabilities": ["ShowAdmin", "AddURL"]
            }
        }
    }
    ```
  - After modifying the configuration file, reinitialize the roles and capabilities by calling the `RolesCapabilities::reset_roles_and_capabilities()` method.

### Access Control

- **Admin Interface Access:**
  - Access to the admin interface is controlled by the `ShowAdmin` capability.
  - Only users with this capability can access the admin pages.

- **Managing Plugins:**
  - The `ManagePlugins` capability controls access to the plugin management page.
  - Users without this capability will not see the plugin management links.

## Contributing

Contributions are welcome! Please fork the repository and submit pull requests.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
