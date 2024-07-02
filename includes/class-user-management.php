<?php
class UserManager {
    
    private static function verify_nonce($action) {
        if (!yourls_verify_nonce($action, $_POST['nonce'])) {
            yourls_die('Unauthorized action or expired link', 'Access Denied', 403);
        }
    }

    public static function yaum_user_login($username) {
        $username = yaum_sanitize_username($username);

        $user = yaum_db()->fetch(TableNames::USERS, 'username = ?', [$username]);
        
        if($user === false) return false;

        // Store user_id and username in session
        start_session();
        $_SESSION['user_id'] = $user['id'];

        return $user['password'];
    }

    public static function get_phpass_hashed_password($password) {
        $hash = $password;
        // Check if the password is already hashed (starts with 'phpass:')
        if (strpos($password, 'phpass:') !== 0) {
            $hash = yourls_phpass_hash($password);
            // PHP would interpret $ as a variable, so replace it in storage.
            $hash = 'phpass:' . str_replace('$', '!', $hash);
        }

        return $hash;
    }

    public static function get_normal_hashed_password($password) {
        $hash = $password;
        // Check if the password is already hashed (starts with 'phpass:')
        if (strpos($password, 'phpass:') === 0) {
            $hash = str_replace('phpass:', '', $password);
            $hash = str_replace('!', '$', $hash); // Replace ! with $ for storage
        } else if (substr($password, 0, 1) !== '$') {
            // Check if the password is already hashed (starts with '$')
            $hash = password_hash($password, PASSWORD_DEFAULT);
        }

        return $hash;
    }

    public static function add_user($username, $password, $role_id) {
        // Check if the username already exists
        $existing_user = yaum_db()->fetch(TableNames::USERS, 'username = ?', [$username]);
        if ($existing_user) {
            yourls_die('Username already exists', 'User Creation Error', 400);
        }

        $hash = self::get_phpass_hashed_password($password);
        yaum_db()->insert(TableNames::USERS, ['username' => $username, 'password' => $hash, 'role_id' => $role_id]);
    }

    public static function update_user($user_id, $password, $role_id) {
        if ($password) {
            $hash = self::get_phpass_hashed_password($password);
            yaum_db()->execute(TableNames::USERS, ['password' => $hash, 'role_id' => $role_id], 'id = ?', [$user_id]);
        } else {
            yaum_db()->execute(TableNames::USERS, ['role_id' => $role_id], 'id = ?', [$user_id]);
        }
    }

    public static function delete_user($user_id, $new_user_id) {
        // Transfer user's links to the new user in the URL_USER table
        yaum_db()->execute(TableNames::URL_USER, ['user_id' => $new_user_id], 'user_id = ?', [$user_id]);
        // Delete user
        yaum_db()->delete(TableNames::USERS, 'id = ?', [$user_id]);
    }

    public static function get_user($user_id) {
        return yaum_db()->fetch(TableNames::USERS, 'id = ?', [$user_id]);
    }

    public static function get_users() {
        return yaum_db()->fetchAll(TableNames::USERS);
    }

    public static function get_username_by_keyword($keyword) {
        $user_id = yaum_db()->fetch(TableNames::URL_USER, 'keyword = ?', [$keyword])['user_id'];
        return yaum_db()->fetch(TableNames::USERS, 'id = ?', [$user_id])['username'];
    }

    public static function change_password($user_id, $current_password, $new_password) {
        self::verify_nonce('change_password');
        $user = self::get_user($user_id);
        // check user found!

        $user_password = self::get_normal_hashed_password($user['password']);
        
        if (!yourls_phpass_check($current_password, $user_password)) {
            return false;
        }
        $hash = self::get_phpass_hashed_password($new_password);
        yaum_db()->execute(TableNames::USERS, ['password' => $hash], 'id = ?', [$user_id]);
        return true;
    }

    public static function assign_link_to_user($keyword, $user_id) {
        yaum_db()->insert(TableNames::URL_USER, ['keyword' => $keyword, 'user_id' => $user_id]);
    }

    public static function display_user_management_page() {
        if (!RolesCapabilities::has_capability(Capabilities::ManageUsers)) {
            echo '<p>Access denied. You do not have permission to view this page.</p>';
            return;
        }
        $edit_user = null;
        if (isset($_POST['add_user'])) {
            self::verify_nonce('add_user');
            self::add_user($_POST['yaum_username'], $_POST['yaum_password'], $_POST['role_id']);
        }
        if (isset($_POST['update_user'])) {
            self::verify_nonce('update_user');
            $password = isset($_POST['yaum_password']) ? $_POST['yaum_password'] : '';
            self::update_user($_POST['user_id'], $password, $_POST['role_id']);
        }
        if (isset($_POST['delete_user'])) {
            self::delete_user($_POST['user_id'], $_POST['new_user_id']);
        }
        if (isset($_POST['edit_user'])) {
            $edit_user = self::get_user($_POST['user_id']);
        }
        if (isset($_POST['cancel_edit'])) {
            $edit_user = null;
        }
        $users = self::get_users();
        $roles = RoleManager::get_roles();

        // Get current logged in user's id
        $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

        include YAUM_TEMPLATES . 'user-management-template.php';
    }

    public static function display_change_password_page() {
        $error_message = '';
        if (isset($_POST['submit'])) {
            if (!self::change_password($_SESSION['user_id'], $_POST['current_password'], $_POST['new_password'])) {
                $error_message = 'Current password is incorrect.';
            } else {
                echo '<p>Password changed successfully.</p>';
            }
        }
        include YAUM_TEMPLATES . 'change-password-template.php';
    }
}
?>
