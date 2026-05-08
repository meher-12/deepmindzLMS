<?php
class User {
    private $id;
    private $name;
    private $phone;
    private $password;
    public function __construct($id = null, $name = '', $phone = '', $password = '') {
        $this->id = $id;
        $this->name = $name;
        $this->phone = $phone;
        $this->password = $password;
    }
    // Getters
    public function getId() {
        return $this->id;
    }
    public function getName() {
        return $this->name;
    }
    public function getPhone() {
        return $this->phone;
    }
    // Setters
    public function setName($name) {
        $this->name = $name;
    }
    public function setPhone($phone) {
        $this->phone = $phone;
    }
    public function setPassword($password) {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
    // Method to verify password
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
    // Static method to find user by name
    public static function findByName($conn, $name) {
        $stmt = $conn->prepare("SELECT id, name, phone, password FROM users WHERE name = ?");
        if (!$stmt) {
            error_log("Failed to prepare statement in findByName: " . $conn->error);
            return null;
        }
        $stmt->bind_param("s", $name);
        if (!$stmt->execute()) {
            error_log("Failed to execute statement in findByName: " . $stmt->error);
            return null;
        }
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return new User($row['id'], $row['name'], $row['phone'], $row['password']);
        }
        return null;
    }
    // Static method to find user by ID
    public static function findById($conn, $id) {
        $stmt = $conn->prepare("SELECT id, name, phone, password FROM users WHERE id = ?");
        if (!$stmt) {
            error_log("Failed to prepare statement in findById: " . $conn->error);
            return null;
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Failed to execute statement in findById: " . $stmt->error);
            return null;
        }
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return new User($row['id'], $row['name'], $row['phone'], $row['password']);
        }
        return null;
    }
    // Static method to return all users
    public static function getAll($conn) {
        $users = [];
        $stmt = $conn->prepare("SELECT id, name, phone FROM users ORDER BY id ASC");
        if (!$stmt) {
            error_log("Failed to prepare statement in getAll: " . $conn->error);
            return $users;
        }
        if (!$stmt->execute()) {
            error_log("Failed to execute statement in getAll: " . $stmt->error);
            return $users;
        }
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }
    // Static method to delete user by ID
    public static function deleteById($conn, $id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if (!$stmt) {
            error_log("Failed to prepare statement in deleteById: " . $conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Failed to execute statement in deleteById: " . $stmt->error);
            return false;
        }
        return true;
    }
    // Method to save user to database
    public function save($conn) {
        // Check for duplicate name during registration
        if (!$this->id) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
            $stmt->bind_param("s", $this->name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return false; // Duplicate name
            }
        }
        if ($this->id) {
            // Update existing user (only update provided fields)
            $query = "UPDATE users SET name = ?, phone = ?";
            $params = [$this->name, $this->phone];
            $types = "ss";
            if (!empty($this->password)) {
                $query .= ", password = ?";
                $params[] = $this->password;
                $types .= "s";
            }
            $query .= " WHERE id = ?";
            $params[] = $this->id;
            $types .= "i";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, phone, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $this->name, $this->phone, $this->password);
        }
        $result = $stmt->execute();
        if (!$result) {
            error_log("Database error in User::save(): " . $stmt->error);
        }
        return $result;
    }
}
?>