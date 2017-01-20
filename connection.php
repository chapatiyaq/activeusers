<?php
// Wiki user name and password
$loginName = 'Chapabot';
$loginPass = 'WZZkhkcVMs34';

// Database name, user name, and password
define ('DBNAME', 'tolueno_liquipedia');
define ('DBUSER', 'tolueno_admin');
define ('DBPASSWORD', 'ZbuH^Hb-,2o@');

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