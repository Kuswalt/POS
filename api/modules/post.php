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
        $stock_quantity = $data['stock_quantity'];
        $item_name = $data['item_name'];

        $sql = "INSERT INTO inventory (item_name, stock_quantity, last_updated) VALUES (:item_name, :stock_quantity, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':item_name', $item_name);
        $stmt->bindParam(':stock_quantity', $stock_quantity);

        try {
            $stmt->execute();
            $inventory_id = $conn->lastInsertId();
            return ["status" => true, "message" => "Item stock added successfully", "inventory_id" => $inventory_id];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to add item stock: " . $e->getMessage()];
        }
    }

    // public function addItem($data) {
    //     global $conn;
    //     $name = $data['name'];
    //     $image = $data['image'];
    //     $price = $data['price'];

    //     $sql = "INSERT INTO product (name, image, Price, category ) VALUES (:name, :image, :Price, :category)";
    //     $stmt = $conn->prepare($sql);
    //     $stmt->bindParam(':name', $name);
    //     $stmt->bindParam(':image', $image);
    //     $stmt->bindParam(':Price', $price);

    //     try {
    //         $stmt->execute();
    //         return ["status" => true, "message" => "Item added successfully"];
    //     } catch (PDOException $e) {
    //         return ["status" => false, "message" => "Failed to add item: " . $e->getMessage()];
    //     }
    // }

    public function addMenuItem($data) {
        global $conn;
        
        if (!isset($data['name'], $data['image'], $data['price'], $data['category'])) {
            error_log('Missing fields. Available data: ' . print_r($data, true));
            return ["status" => false, "message" => "Missing required fields"];
        }

        $name = $data['name'];
        $image = $data['image'];
        $price = $data['price'];
        $category = $data['category'];

        $sql = "INSERT INTO product (name, image, price, category) VALUES (:name, :image, :price, :category)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':category', $category);

        try {
            $stmt->execute();
            $product_id = $conn->lastInsertId();
            return ["status" => true, "message" => "Menu item added successfully", "product_id" => $product_id];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to add menu item: " . $e->getMessage()];
        }
    }
}
?>