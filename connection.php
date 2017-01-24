<?php
// Wiki user name and password
$loginName = '';
$loginPass = '';

// Database name, user name, and password
define ('DBNAME', '');
define ('DBUSER', '');
define ('DBPASSWORD', '');

class Connection {

    protected static $db;

    private function __construct() {
        try {
            self::$db = new PDO( 'mysql:host=localhost;dbname=' . DBNAME, DBUSER, DBPASSWORD);
            self::$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        }
        catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

    }

    public static function getConnection() {
        if (!self::$db) {
            new Connection();
        }
        return self::$db;
    }
}
?>