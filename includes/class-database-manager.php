<?php
use PDO;
use PDOException;

class TableNames
{
    const USERS = YOURLS_DB_PREFIX . 'yaum_users';
    const ROLES = YOURLS_DB_PREFIX . 'yaum_roles';
    const CAPABILITIES = YOURLS_DB_PREFIX . 'yaum_capabilities';
    const ROLE_CAPABILITIES = YOURLS_DB_PREFIX . 'yaum_role_capabilities';
    const URL_USER = YOURLS_DB_PREFIX . 'url_user';
}

class DatabaseManager
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $dbhost = YOURLS_DB_HOST;
        $user = YOURLS_DB_USER;
        $pass = YOURLS_DB_PASS;
        $dbname = YOURLS_DB_NAME;

        // Get custom port if any
        if (false !== strpos($dbhost, ':')) {
            list($dbhost, $dbport) = explode(':', $dbhost);
            $dbhost = sprintf('%1$s;port=%2$d', $dbhost, $dbport);
        }

        $charset = yourls_apply_filter('db_connect_charset', 'utf8mb4');
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbhost, $dbname, $charset);
        $dsn = yourls_apply_filter('db_connect_custom_dsn', $dsn);
        $driver_options = yourls_apply_filter('db_connect_driver_option', array());

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $driver_options);
            $this->create_tables_if_missing();
        } catch (PDOException $e) {
            var_dump($e->getMessage());
            yourls_die(yourls__('Could not connect to database.'), yourls__('Fatal error'), 503);
        }
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($table, $where = '', $params = [])
    {
        $sql = "SELECT * FROM $table" . ($where ? " WHERE $where" : '');
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($table, $where = '', $params = [])
    {
        $sql = "SELECT * FROM $table" . ($where ? " WHERE $where" : '');
        return $this->query($sql, $params)->fetchAll();
    }

    public function execute($table, $data, $where = '', $params = [])
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $set = implode(' = ?, ', $columns) . ' = ?';
        $sql = "UPDATE $table SET $set" . ($where ? " WHERE $where" : '');
        $this->query($sql, array_merge($values, $params));
    }

    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->query($sql, array_values($data));
    }

    public function delete($table, $where = '', $params = [])
    {
        $sql = "DELETE FROM $table" . ($where ? " WHERE $where" : '');
        $this->query($sql, $params);
    }

    public function deleteAll($table)
    {
        $sql = "DELETE FROM $table";
        $this->query($sql);
    }

    public function count($table, $where = '', $params = [])
    {
        $sql = "SELECT COUNT(*) FROM $table" . ($where ? " WHERE $where" : '');
        return (int) $this->query($sql, $params)->fetchColumn();
    }

    public function isEmpty($table)
    {
        return $this->count($table) === 0;
    }

    private function create_tables_if_missing()
    {
        $charset = yourls_apply_filter('db_connect_charset', 'utf8mb4');

        $users_table = TableNames::USERS;
        $roles_table = TableNames::ROLES;
        $capabilities_table = TableNames::CAPABILITIES;
        $role_capabilities_table = TableNames::ROLE_CAPABILITIES;
        $url_user_table = TableNames::URL_USER;
        $url_table = YOURLS_DB_TABLE_URL;

        // Create Roles table
        $sql = <<<EOT
CREATE TABLE IF NOT EXISTS `$roles_table` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL
) ENGINE=InnoDB CHARACTER SET $charset;
EOT;
        $this->pdo->exec($sql);

        // Create Users table
        $sql = <<<EOT
CREATE TABLE IF NOT EXISTS `$users_table` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(200),
    password VARCHAR(255),
    role_id INT(11) UNSIGNED NOT NULL,
    FOREIGN KEY (role_id) REFERENCES `$roles_table`(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET $charset;
EOT;
        $this->pdo->exec($sql);

        // Create Capabilities table
        $sql = <<<EOT
CREATE TABLE IF NOT EXISTS `$capabilities_table` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL
) ENGINE=InnoDB CHARACTER SET $charset;
EOT;
        $this->pdo->exec($sql);

        // Create Role Capabilities table
        $sql = <<<EOT
CREATE TABLE IF NOT EXISTS `$role_capabilities_table` (
    role_id INT(11) UNSIGNED NOT NULL,
    capability_id INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, capability_id),
    FOREIGN KEY (role_id) REFERENCES `$roles_table`(id) ON DELETE CASCADE,
    FOREIGN KEY (capability_id) REFERENCES `$capabilities_table`(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET $charset;
EOT;
        $this->pdo->exec($sql);

        // Create URL User table with utf8mb4_bin collation for keyword
        $sql = <<<EOT
CREATE TABLE IF NOT EXISTS `$url_user_table` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) UNSIGNED NOT NULL,
    keyword VARCHAR(200) COLLATE utf8mb4_bin NOT NULL,
    FOREIGN KEY (user_id) REFERENCES `$users_table`(id),
    FOREIGN KEY (keyword) REFERENCES `$url_table`(keyword)
) ENGINE=InnoDB CHARACTER SET $charset;
EOT;
        $this->pdo->exec($sql);
    }

    private function convert_tables_to_innodb()
    {
        $tables = [YOURLS_DB_TABLE_URL, TableNames::USERS, TableNames::ROLES, TableNames::CAPABILITIES, TableNames::ROLE_CAPABILITIES, TableNames::URL_USER];

        foreach ($tables as $table) {
            if ($this->table_exists($table)) {
                $table_info = $this->get_table_info($table);
                if ($table_info['engine'] !== 'InnoDB') {
                    $sql = "ALTER TABLE $table ENGINE=InnoDB";
                    $this->pdo->exec($sql);
                }
            }
        }
    }

    private function table_exists($table)
    {
        try {
            $result = $this->pdo->query("SELECT 1 FROM $table LIMIT 1");
        } catch (Exception $e) {
            return false;
        }
        return $result !== false;
    }

    private function get_table_info($table_name)
    {
        $sql = "SHOW TABLE STATUS LIKE :table_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['table_name' => $table_name]);
        $table_info = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check columns
        $sql = "SHOW COLUMNS FROM $table_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'engine' => $table_info['Engine'],
            'columns' => $columns,
        ];
    }
}
?>
