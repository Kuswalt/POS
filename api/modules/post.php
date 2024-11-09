<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

class Post {
 
    public function registerUser($data) {
        global $conn;
        $username = $data['username'];
        $password = $data['password'];
        $role = isset($data['role']) ? $data['role'] : 0;

        $checkUserSql = "SELECT * FROM user_acc WHERE username = :username";
        $stmt = $conn->prepare($checkUserSql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            if ($result['role'] == 0) {
                return ["status" => false, "message" => "Account is still not approved by developers or Username already taken"];
            } else if ($result['role'] == 1) {
                return ["status" => false, "message" => "Account Username is already used"];
            } else {
                return ["status" => false, "message" => "Username already taken"];
            }
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO user_acc (username, password, role) VALUES (:username, :password, :role)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role', $role);
            try {
                $stmt->execute();
                return ["status" => true, "message" => "Account created successfully"];
            } catch (PDOException $e) {
                return ["status" => false, "message" => "Account creation failed: " . $e->getMessage()];
            }
        }
    }

    public function loginUser($data) {
        global $conn;
        $username = $data['username'];
        $password = $data['password'];

        $sql = "SELECT * FROM user_acc WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ["status" => false, "message" => "User not found"];
        } else {
            if (password_verify($password, $user['password'])) {
                return ["status" => true, "message" => "Login successful", "userId" => $user['User_id'], "role" => $user['role']];
            } else {
                return ["status" => false, "message" => "Incorrect credentials"];
            }
        }
    }

    public function addItemStock($data) {
        global $conn;
        $product_id = $data['product_id'];
        $stock_quantity = $data['stock_quantity'];

        $sql = "INSERT INTO inventory (product_id, stock_quantity, last_updated) VALUES (:product_id, :stock_quantity, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':stock_quantity', $stock_quantity);

        try {
            $stmt->execute();
            return ["status" => true, "message" => "Item stock added successfully"];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to add item stock: " . $e->getMessage()];
        }
    }
}
?>