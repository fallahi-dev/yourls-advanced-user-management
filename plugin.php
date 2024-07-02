<?php
/*
Plugin Name: YOURLS Advanced User Management
Plugin URI: 
Description: A powerful plugin for YOURLS to manage users, roles, capabilities, and password changes with advanced control and security features.
Version: 0.0.1
Author: Fallahi Dev
Author URI: https://fallahi.dev
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) die();

// Define constants for directories
define('YAUM_INCLUDES', dirname(__FILE__) . '/includes/');
define('YAUM_TEMPLATES', dirname(__FILE__) . '/templates/');
define('YAUM_ASSETS', dirname(__FILE__) . '/assets/');

// Include necessary files
require_once YAUM_INCLUDES . 'class-database-manager.php';
require_once YAUM_INCLUDES . 'class-roles-capabilities.php';
require_once YAUM_INCLUDES . 'class-user-management.php';
require_once YAUM_INCLUDES . 'class-role-management.php';
require_once YAUM_INCLUDES . 'functions.php'; 

start_session();

global $yaum_db;

// Function to get database instance
function yaum_db() {
    global $yaum_db;
    if(!$yaum_db) $yaum_db = DatabaseManager::get_instance();
    return $yaum_db;
}

// Hooks and filters

yourls_add_action('pre_login', 'yaum_admin_init');
yourls_add_action('admin_init', 'yaum_admin_init');
yourls_add_action('login', 'yaum_login');
yourls_add_action('plugins_loaded', 'yaum_init_plugin');
yourls_add_action('activated_yourls-advanced-user-management/plugin.php', 'yaum_activate_plugin');

function yaum_pages_config(){
    return [
        'user_management' => [
            'title' => 'Users',
            'display' => 'yaum_display_user_management_page',
            'menu_link' => true,
            'logout_link' => false,
            'capability' => Capabilities::ManageUsers
        ],
        'role_management' => [
            'title' => 'Roles',
            'display' => 'yaum_display_role_management_page',
            'menu_link' => true,
            'logout_link' => false,
            'capability' => Capabilities::ManageRoles
        ],
        'change_password' => [
            'title' => 'Change Password',
            'display' => 'yaum_display_change_password_page',
            'menu_link' => false,
            'logout_link' => true,
            'capability' => Capabilities::ChangePassword
        ]
    ];
}

function yaum_init_plugin() {

    // Register pages, hooks, and filters
    foreach (yaum_pages_config() as $key => $config) {
        yourls_register_plugin_page($key, $config['title'], $config['display']);
    }

    yourls_add_filter('logout_link', 'yaum_manage_logout_link');
    yourls_add_filter('admin_sublinks', 'yaum_manage_menu_sublinks');
    yourls_add_filter('admin_links', 'yaum_manage_menu_links');

    // Control access to add new link form
    yourls_add_action('pre_add_new_link', 'yaum_check_add_url_capability');
    // Control access to link list
    yourls_add_filter('admin_list_where', 'yaum_check_manage_links_capability');
    // Control access to plugin management
    yourls_add_filter('admin_links', 'yaum_check_manage_plugins_capability');
    // Control access to user management
    yourls_add_action('plugins_loaded', 'yaum_check_manage_users_capability');

    // Add user column to link table
    yourls_add_filter('table_head_cells', 'yaum_username_table_head');
    yourls_add_filter('table_add_row_cell_array', 'yaum_add_user_row');
}

function get_page_url($page_key){
    $admin_pages = yourls_list_plugin_admin_pages();
    return $admin_pages[$page_key]['url'];
}

function yaum_manage_menu_links($links) {
    // check we need to add main plugin menu or not?
    foreach (yaum_pages_config() as $key => $config) {
        if($config['menu_link'] === false || !RolesCapabilities::has_capability($config['capability'])) continue;
        $links['yaum']=array(
            'url'    => yourls_admin_url( 'index.php' ),
            'title'  => yourls__( 'Advanced User Management' ),
            'anchor' => yourls__( 'AUM' )
        );
        break;
    }

    if (!RolesCapabilities::has_capability(Capabilities::ManageTools)) {
        unset($links['tools']);
    }

    if (!RolesCapabilities::has_capability(Capabilities::ManagePlugins)) {
        unset($links['plugins']);
    }

    return $links;
}

function yaum_manage_menu_sublinks($sublinks) {
    $plugin_links = $sublinks['plugins'];

    $yaum_links = [];
    foreach (yaum_pages_config() as $key => $config) {
        if(!array_key_exists($key,$plugin_links)) continue;
        $link = $plugin_links[$key];
        unset($plugin_links[$key]);
        if($config['menu_link'] === false || !RolesCapabilities::has_capability($config['capability'])) continue;
        $yaum_links[$key] = $link;
    }

    $sublinks['plugins'] = $plugin_links;
    $sublinks['yaum'] = $yaum_links;

    return $sublinks;
}

// Add change password link
function yaum_manage_logout_link($logout_link) {
    $logout_link = rtrim($logout_link, ')');
    foreach (yaum_pages_config() as $page_key => $config) {
        $page_link = get_page_url($page_key);
        if(!$page_link || $config['logout_link'] === false || !RolesCapabilities::has_capability($config['capability'])) continue;
        $logout_link .= sprintf(' | <a href="%s">%s</a>',$page_link, $config['title']);
    }
    $logout_link .= ')';
    return $logout_link;
}

function yaum_admin_init(){
    global $yourls_user_passwords;

    $users = yaum_db()->fetchAll(TableNames::USERS);

    foreach ($users as $user) {
        $yourls_user_passwords[$user['username']] = $user['password'];
    }
}

function yaum_login(){
    if ( yourls_is_API() ) return;

    UserManager::yaum_user_login(YOURLS_USER);
}

// Transfer users from config file to database
function yaum_transfer_users_from_config() {
    global $yourls_user_passwords;
    
    $db = yaum_db();
    $admin_role_id = $db->fetch(TableNames::ROLES, 'name = ?', [Roles::Administrator])['id'];

    foreach ($yourls_user_passwords as $username => $password) {
        $user = $db->fetch(TableNames::USERS, 'username = ?', [$username]);
        if (!$user) {
            UserManager::add_user($username, $password, $admin_role_id);
        }
    }

    // Get the current logged in user
    if (yourls_is_valid_user()) {
        $current_username = YOURLS_USER;
        $current_user = $db->fetch(TableNames::USERS, 'username = ?', [$current_username]);
        if ($current_user) {
            // Store user_id in session
            start_session();
            $_SESSION['user_id'] = $current_user['id'];
        }
    }
}

// Function to be called on plugin activation
function yaum_activate_plugin() {
    // Initialize roles and capabilities from config file
    RolesCapabilities::initialize_roles_and_capabilities();
    yaum_transfer_users_from_config();
}

// Control access to add new link form
function yaum_check_add_url_capability() {
    if (!RolesCapabilities::has_capability(Capabilities::AddURL)) {
        yourls_die('Access denied. You do not have permission to add URLs.', 'Access Denied', 403);
    }
}

// Control access to link list
function yaum_check_manage_links_capability($where) {
    if (!RolesCapabilities::has_capability(Capabilities::ViewURLs)) {
        return '1=0'; // This will make sure no links are shown
    }
    return $where;
}

// Control access to plugin management
function yaum_check_manage_plugins_capability($sublinks) {
    if (!RolesCapabilities::has_capability(Capabilities::ManagePlugins)) {
        unset($sublinks['plugins']);
    }
    return $sublinks;
}

// Control access to user management
function yaum_check_manage_users_capability() {
    if (!RolesCapabilities::has_capability(Capabilities::ManageUsers)) {
        yourls_die('Access denied. You do not have permission to manage users.', 'Access Denied', 403);
    }
}



// Display user management page
function yaum_display_user_management_page() {
    UserManager::display_user_management_page();
}

// Display role management page
function yaum_display_role_management_page() {
    RoleManager::display_role_management_page();
}

// Display change password page
function yaum_display_change_password_page() {
    UserManager::display_change_password_page();
}

// Hook to assign link to user
yourls_add_action('insert_link', 'yaum_assign_link_to_user');

function yaum_assign_link_to_user($actions) {
    if (isset($_SESSION['user_id'])) {
        $keyword = $actions[2];
        $user_id = $_SESSION['user_id'];
        UserManager::assign_link_to_user($keyword, $user_id);
    }
}

// Hook for login to store user ID in session
yourls_add_action('logout', 'yaum_logout');

function yaum_logout() {
    start_session();
    // Remove user_id from session
    unset($_SESSION['user_id']);
    session_destroy();
}

// Add user column to link table
function yaum_username_table_head($cells) {
    $user_head = array('username' => 'Username');
    $cells = array_insert($cells, 5, $user_head);
    return $cells;
}

function yaum_add_user_row($cells, $keyword) {
    $username = UserManager::get_username_by_keyword($keyword);
    $user_cell = array(
        'username' => array(
            'template' => '%username%',
            'username' => $username,
        )
    );
    $cells = array_insert($cells, 5, $user_cell);
    return $cells;
}


?>
