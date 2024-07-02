<?php
class RoleManager {
    public static function add_role($name) {
        yaum_db()->insert(TableNames::ROLES, ['name' => $name]);
    }

    public static function update_role($role_id, $name) {
        yaum_db()->execute(TableNames::ROLES, ['name' => $name], 'id = ?', [$role_id]);
    }

    public static function delete_role($role_id) {
        yaum_db()->delete(TableNames::ROLES, 'id = ?', [$role_id]);
    }

    public static function get_role($role_id) {
        return yaum_db()->fetch(TableNames::ROLES, 'id = ?', [$role_id]);
    }

    public static function get_roles() {
        return yaum_db()->fetchAll(TableNames::ROLES);
    }

    public static function reset_roles() {
        // Initialize roles and capabilities from config file
        RolesCapabilities::reset_roles_and_capabilities();
    }

    public static function assign_capability_to_role($role_id, $capability_id) {
        yaum_db()->insert(TableNames::ROLE_CAPABILITIES, ['role_id' => $role_id, 'capability_id' => $capability_id]);
    }

    public static function remove_capability_from_role($role_id, $capability_id) {
        yaum_db()->delete(TableNames::ROLE_CAPABILITIES, 'role_id = ? AND capability_id = ?', [$role_id, $capability_id]);
    }

    public static function display_role_management_page() {
        if (!RolesCapabilities::has_capability(Capabilities::ManageRoles)) {
            echo '<p>Access denied. You do not have permission to view this page.</p>';
            return;
        }
        if (isset($_POST['reset_roles'])) {
            self::reset_roles();
        }
        if (isset($_POST['add_role'])) {
            self::add_role($_POST['role_name']);
        }
        if (isset($_POST['update_role'])) {
            self::update_role($_POST['role_id'], $_POST['role_name']);
        }
        if (isset($_POST['delete_role'])) {
            self::delete_role($_POST['role_id']);
        }
        if (isset($_POST['assign_capability'])) {
            self::assign_capability_to_role($_POST['role_id'], $_POST['capability_id']);
        }
        if (isset($_POST['remove_capability'])) {
            self::remove_capability_from_role($_POST['role_id'], $_POST['capability_id']);
        }
        $roles = self::get_roles();
        $capabilities = RolesCapabilities::get_capabilities();
        include YAUM_TEMPLATES . 'role-management-template.php';
    }
}
?>
