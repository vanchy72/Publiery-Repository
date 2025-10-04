<?php
/**
 * Clase APIDatabase - Migrada a configuración moderna
 * Ahora usa config/database.php con variables de entorno
 * Renombrada para evitar conflicto con la clase Database principal
 */

// Cargar configuración moderna
require_once __DIR__ . '/../config/database.php';

class APIDatabase {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Usar variables de entorno desde la configuración moderna
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'publiery_db';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";dbname=" . $this->db_name . ";port=" . ($_ENV['DB_PORT'] ?? 5432),
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }

        return $this->conn;
    }

    public function disconnect() {
        $this->conn = null;
    }

    // Método estático para compatibilidad (delegado a la función principal)
    public static function getConnection() {
        return getDBConnection();
    }
}