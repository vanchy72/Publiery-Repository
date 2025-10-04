<?php
class Usuario {
    private $conn;
    private $table = 'usuarios';

    // Properties
    public $id;
    public $nombre;
    public $email;
    public $password;
    public $rol;
    public $estado;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create usuario
    public function create() {
        $query = 'INSERT INTO ' . $this->table . '
            SET
                nombre = :nombre,
                email = :email,
                password = :password,
                rol = :rol,
                estado = :estado,
                fecha_registro = NOW()';

        try {
            $stmt = $this->conn->prepare($query);

            // Clean and bind data
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
            $this->rol = htmlspecialchars(strip_tags($this->rol));
            $this->estado = htmlspecialchars(strip_tags($this->estado));

            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password', $this->password);
            $stmt->bindParam(':rol', $this->rol);
            $stmt->bindParam(':estado', $this->estado);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Read single usuario
    public function read_single() {
        $query = 'SELECT
                    *
                FROM ' . $this->table . '
                WHERE id = :id
                LIMIT 0,1';

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row) {
                $this->id = $row['id'];
                $this->nombre = $row['nombre'];
                $this->email = $row['email'];
                $this->rol = $row['rol'];
                $this->estado = $row['estado'];
                $this->fecha_registro = $row['fecha_registro'];
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Read all usuarios
    public function read() {
        $query = 'SELECT
                    *
                FROM ' . $this->table . '
                ORDER BY fecha_registro DESC';

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Update usuario
    public function update() {
        $query = 'UPDATE ' . $this->table . '
            SET
                nombre = :nombre,
                email = :email,
                rol = :rol,
                estado = :estado
            WHERE id = :id';

        try {
            $stmt = $this->conn->prepare($query);

            // Clean data
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->rol = htmlspecialchars(strip_tags($this->rol));
            $this->estado = htmlspecialchars(strip_tags($this->estado));
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind data
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':rol', $this->rol);
            $stmt->bindParam(':estado', $this->estado);
            $stmt->bindParam(':id', $this->id);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Delete usuario
    public function delete() {
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Search usuarios
    public function search($searchTerm) {
        $query = 'SELECT
                    *
                FROM ' . $this->table . '
                WHERE nombre LIKE :searchTerm
                OR email LIKE :searchTerm
                OR rol LIKE :searchTerm
                ORDER BY fecha_registro DESC';

        try {
            $searchTerm = "%{$searchTerm}%";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':searchTerm', $searchTerm);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Change password
    public function changePassword($newPassword) {
        $query = 'UPDATE ' . $this->table . '
            SET password = :password
            WHERE id = :id';

        try {
            $stmt = $this->conn->prepare($query);
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $this->id);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Validate usuario exists
    public function exists() {
        $query = 'SELECT id FROM ' . $this->table . ' WHERE id = :id LIMIT 0,1';
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Check if email exists
    public function emailExists($excludeId = null) {
        $query = 'SELECT id FROM ' . $this->table . ' WHERE email = :email';
        if($excludeId) {
            $query .= ' AND id != :id';
        }
        $query .= ' LIMIT 0,1';

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $this->email);
            if($excludeId) {
                $stmt->bindParam(':id', $excludeId);
            }
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
}