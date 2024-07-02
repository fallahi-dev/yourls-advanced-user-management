<?php
class Roles {
    const Administrator = 'Administrator';
    const Editor = 'Editor';
    const Contributor = 'Contributor';
}

class Capabilities {
    const ShowAdmin = 'ShowAdmin';
    const AddURL = 'AddURL';
    const DeleteURL = 'DeleteURL';
    const EditURL = 'EditURL';
    const ShareURL = 'ShareURL';
    const API = 'API';
    const APIu = 'APIu';
    const ViewStats = 'ViewStats';
    const ViewAll = 'ViewAll';
    const ViewURLs = 'ViewURLs';
    const ManageTools = 'ManageTools';
    const ManageUsers = 'ManageUsers';
    const ManageRoles = 'ManageRoles';
    const ManagePlugins = 'ManagePlugins';
    const ChangePassword = 'ChangePassword';
}

class RolesCapabilities {

    public static function initialize_roles_and_capabilities($first_init_check = true) {
        if ($first_init_check === true && yaum_db()->count(TableNames::ROLES) > 0) return;
        
        $config_file = YAUM_INCLUDES . 'role-capabilities-config.json';
        if (!file_exists($config_file)) {
            yourls_die("Configuration file not found: $config_file", 'Configuration Error', 503);
        }

        $config = json_decode(file_get_contents($config_file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            yourls_die("Error parsing JSON configuration file: " . json_last_error_msg(), 'Configuration Error', 503);
        }

        if (!isset($config['roles']) || !is_array($config['roles'])) {
            yourls_die("Invalid configuration file format: 'roles' key not found or not an array", 'Configuration Error', 503);
        }

        // Initialize capabilities
        $capabilities = array_unique(array_merge(...array_column($config['roles'], 'capabilities')));
        foreach ($capabilities as $name) {
            $existing_capability = yaum_db()->fetch(TableNames::CAPABILITIES, 'name = ?', [$name]);
            if (!$existing_capability) {
                yaum_db()->insert(TableNames::CAPABILITIES, ['name' => $name]);
            }
        }

        // Initialize roles and assign capabilities
        foreach ($config['roles'] as $role_name => $role_data) {
            $existing_role = yaum_db()->fetch(TableNames::ROLES, 'name = ?', [$role_name]);
            if (!$existing_role) {
                yaum_db()->insert(TableNames::ROLES, ['name' => $role_name]);
                $role_id = yaum_db()->fetch(TableNames::ROLES, 'name = ?', [$role_name])['id'];
            } else {
                $role_id = $existing_role['id'];
            }

            // Clear existing capabilities for the role
            yaum_db()->delete(TableNames::ROLE_CAPABILITIES, 'role_id = ?', [$role_id]);

            // Assign new capabilities to the role
            foreach ($role_data['capabilities'] as $capability_name) {
                $capability_id = yaum_db()->fetch(TableNames::CAPABILITIES, 'name = ?', [$capability_name])['id'];
                if (!self::role_has_capability($role_id, $capability_id)) {
                    yaum_db()->insert(TableNames::ROLE_CAPABILITIES, ['role_id' => $role_id, 'capability_id' => $capability_id]);
                }
            }
        }
    }

    public static function reset_roles_and_capabilities() {
        self::initialize_roles_and_capabilities(false);
    }

    public static function has_capability($capability) {
        // Check if the users table is empty
        if (self::is_users_table_empty()) {
            return true;
        }

        $user_role = UserManager::get_user($_SESSION['user_id'])['role_id'];
        $capabilities = self::get_capabilities_for_role($user_role);
        foreach ($capabilities as $cap) {
            if ($cap['name'] === $capability) {
                return true;
            }
        }
        return false;
    }

    private static function is_users_table_empty() {
        return yaum_db()->isEmpty(TableNames::USERS);
    }

    private static function role_has_capability($role_id, $capability_id) {
        return yaum_db()->fetch(TableNames::ROLE_CAPABILITIES, 'role_id = ? AND capability_id = ?', [$role_id, $capability_id]) ? true : false;
    }

    public static function get_capabilities_for_role($role_id) {
        return yaum_db()->fetchAll(TableNames::CAPABILITIES . ' c JOIN ' . TableNames::ROLE_CAPABILITIES . ' rc ON c.id = rc.capability_id', 'rc.role_id = ?', [$role_id]);
    }

    public static function get_capabilities() {
        return yaum_db()->fetchAll(TableNames::CAPABILITIES);
    }
}
?>
