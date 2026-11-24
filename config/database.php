<?php
// config/database.php
// Gestion sécurisée de la connexion PDO avec MySQL (Singleton)
// Compatible Local (Laragon) et Cloud (Render, Railway, Aiven, PlanetScale, Supabase)

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // 1. Détection via DATABASE_URL (format mysql://user:pass@host:port/dbname)
            $dbUrl = getenv('DATABASE_URL') ?: getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL');
            
            if ($dbUrl) {
                $parsed = parse_url($dbUrl);
                $host    = $parsed['host'] ?? '127.0.0.1';
                $port    = $parsed['port'] ?? 3306;
                $user    = $parsed['user'] ?? 'root';
                $pass    = $parsed['pass'] ?? '';
                $dbname  = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'eglise_app';
            } else {
                // 2. Variables individuelles d'environnement
                $host    = getenv('DB_HOST') ?: '127.0.0.1';
                $port    = getenv('DB_PORT') ?: 3306;
                $dbname  = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: 'eglise_app';
                $user    = getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root';
                $pass    = getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
            }

            $charset = 'utf8mb4';
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $dbname, $charset);

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            // Configuration SSL obligatoire pour TiDB Cloud Serverless & services Cloud
            $isCloudDb = (getenv('DB_SSL') === 'true') 
                || (stripos($host, 'tidbcloud.com') !== false) 
                || (stripos($host, 'aivencloud.com') !== false);

            if ($isCloudDb) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                // Active le mode chiffré SSL exigé par TiDB
                if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = true;
                }
            }

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('Database Connection Error: ' . $e->getMessage());
                die('Une erreur est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.');
            }
        }

        return self::$instance;
    }
}

// Fonction d'aide globale pour obtenir PDO
function db(): PDO {
    return Database::getConnection();
}
