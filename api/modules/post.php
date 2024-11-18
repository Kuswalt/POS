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

    public function addToCart($data) {
        global $conn;
        
        // Debug logging
        error_log('Received data: ' . print_r($data, true));
        
        if (!isset($data['product_id'], $data['quantity'], $data['user_id'])) {
            $missing = array_diff(['product_id', 'quantity', 'user_id'], array_keys($data));
            return [
                "status" => false, 
                "message" => "Missing required fields: " . implode(', ', $missing),
                "received" => $data
            ];
        }
        
        $product_id = $data['product_id'];
        $quantity = $data['quantity'];
        $user_id = $data['user_id'];
        
        // First, check if the product exists
        $checkProduct = "SELECT product_id FROM product WHERE product_id = :product_id";
        $stmt = $conn->prepare($checkProduct);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            return ["status" => false, "message" => "Product not found"];
        }
        
        $sql = "INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (:user_id, :product_id, :quantity)
                ON DUPLICATE KEY UPDATE quantity = quantity + :new_quantity";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':new_quantity', $quantity);
        
        try {
            $stmt->execute();
            return ["status" => true, "message" => "Item added to cart successfully"];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to add item to cart: " . $e->getMessage()];
        }
    }

    public function createOrder($data) {
        global $conn;
        
        try {
            $conn->beginTransaction();
            
            // Create order (existing code)
            $sql = "INSERT INTO `order` (customer_id, order_date, total_amount, user_id, payment_status) 
                    VALUES (:customer_id, NOW(), :total_amount, :user_id, :payment_status)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':customer_id', $data['customer_id']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':payment_status', $data['payment_status']);
            $stmt->execute();
            
            $order_id = $conn->lastInsertId();
            
            // Create order items and update inventory
            foreach ($data['order_items'] as $item) {
                // Insert order item
                $sql = "INSERT INTO order_item (order_id, product_id, quantity, price) 
                        VALUES (:order_id, :product_id, :quantity, :price)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':order_id', $order_id);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':price', $item['price']);
                $stmt->execute();
                
                // Update inventory for each ingredient
                $sql = "UPDATE inventory i 
                       JOIN product_ingredients pi ON i.inventory_id = pi.inventory_id 
                       SET i.stock_quantity = i.stock_quantity - (pi.quantity_needed * :order_quantity),
                           i.last_updated = NOW()
                       WHERE pi.product_id = :product_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':order_quantity', $item['quantity']);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->execute();
            }
            
            // Create receipt (existing code)
            $sql = "INSERT INTO receipt (order_id, generated_at, total_amount) 
                    VALUES (:order_id, NOW(), :total_amount)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->execute();
            
            // Delete cart items for this user
            $deleteCartSql = "DELETE FROM cart WHERE user_id = :user_id";
            $deleteCartStmt = $conn->prepare($deleteCartSql);
            $deleteCartStmt->bindParam(':user_id', $data['user_id']);
            $deleteCartStmt->execute();
            
            $conn->commit();
            return ["status" => true, "message" => "Order created successfully", "order_id" => $order_id];
            
        } catch (PDOException $e) {
            $conn->rollBack();
            return ["status" => false, "message" => "Failed to create order: " . $e->getMessage()];
        }
    }

    public function addCustomer($data) {
        global $conn;
        
        if (!isset($data['Name']) || !isset($data['total_amount'])) {
            return [
                "status" => false,
                "message" => "Customer name and total amount are required"
            ];
        }

        try {
            $sql = "INSERT INTO customer (Name, total_amount) 
                    VALUES (:name, :total_amount)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':name', $data['Name']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            
            if ($stmt->execute()) {
                $customer_id = $conn->lastInsertId();
                return [
                    "status" => true,
                    "message" => "Customer added successfully",
                    "customer_id" => $customer_id
                ];
            } else {
                return [
                    "status" => false,
                    "message" => "Failed to add customer"
                ];
            }
        } catch (PDOException $e) {
            return [
                "status" => false,
                "message" => "Database error: " . $e->getMessage()
            ];
        }
    }

    public function addSale($data) {
        global $conn;
        
        try {
            $sql = "INSERT INTO sales (order_id, total_sales, sales_date, user_id) 
                    VALUES (:order_id, :total_sales, NOW(), :user_id)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->bindParam(':total_sales', $data['total_sales']);
            $stmt->bindParam(':user_id', $data['user_id']);
            
            if ($stmt->execute()) {
                return [
                    "status" => true,
                    "message" => "Sale recorded successfully"
                ];
            } else {
                return [
                    "status" => false,
                    "message" => "Failed to record sale"
                ];
            }
        } catch (PDOException $e) {
            return [
                "status" => false,
                "message" => "Database error: " . $e->getMessage()
            ];
        }
    }

    public function addReceipt($data) {
        global $conn;
        
        if (!isset($data['order_id']) || !isset($data['total_amount'])) {
            return [
                "status" => false,
                "message" => "Missing required fields: order_id and total_amount"
            ];
        }
        
        try {
            // First check if the order exists
            $checkOrder = "SELECT order_id FROM `order` WHERE order_id = :order_id";
            $stmt = $conn->prepare($checkOrder);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                return [
                    "status" => false,
                    "message" => "Invalid order ID"
                ];
            }

            $sql = "INSERT INTO receipt (order_id, generated_at, total_amount) 
                    VALUES (:order_id, NOW(), :total_amount)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            
            if ($stmt->execute()) {
                return [
                    "status" => true,
                    "message" => "Receipt generated successfully",
                    "receipt_id" => $conn->lastInsertId()
                ];
            } else {
                return [
                    "status" => false,
                    "message" => "Failed to generate receipt"
                ];
            }
        } catch (PDOException $e) {
            return [
                "status" => false,
                "message" => "Database error: " . $e->getMessage()
            ];
        }
    }

    public function addProductIngredients($data) {
        global $conn;
        
        try {
            $conn->beginTransaction();
            
            // Delete existing ingredients for this product
            $sql = "DELETE FROM product_ingredients WHERE product_id = :product_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->execute();
            
            // Add new ingredients
            $sql = "INSERT INTO product_ingredients (product_id, inventory_id, quantity_needed, unit_of_measure) 
                    VALUES (:product_id, :inventory_id, :quantity_needed, :unit_of_measure)";
            $stmt = $conn->prepare($sql);
            
            foreach ($data['ingredients'] as $ingredient) {
                $stmt->bindParam(':product_id', $data['product_id']);
                $stmt->bindParam(':inventory_id', $ingredient['inventory_id']);
                $stmt->bindParam(':quantity_needed', $ingredient['quantity_needed']);
                $stmt->bindParam(':unit_of_measure', $ingredient['unit_of_measure']);
                $stmt->execute();
            }
            
            $conn->commit();
            return ["status" => true, "message" => "Product ingredients updated successfully"];
        } catch (PDOException $e) {
            $conn->rollBack();
            return ["status" => false, "message" => "Failed to update product ingredients: " . $e->getMessage()];
        }
    }

    public function updateProductIngredients() {
        global $conn;
        
        // Get JSON data from request
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['product_id']) || !isset($data['ingredients'])) {
            return ["status" => false, "message" => "Missing required data"];
        }
        
        try {
            $conn->beginTransaction();
            
            // First, delete existing ingredients for this product
            $deleteSql = "DELETE FROM product_ingredients WHERE product_id = :product_id";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bindParam(':product_id', $data['product_id']);
            $deleteStmt->execute();
            
            // Then insert new ingredients
            $insertSql = "INSERT INTO product_ingredients (product_id, inventory_id, quantity_needed, unit_of_measure) 
                         VALUES (:product_id, :inventory_id, :quantity_needed, :unit_of_measure)";
            $insertStmt = $conn->prepare($insertSql);
            
            foreach ($data['ingredients'] as $ingredient) {
                $insertStmt->bindParam(':product_id', $data['product_id']);
                $insertStmt->bindParam(':inventory_id', $ingredient['inventory_id']);
                $insertStmt->bindParam(':quantity_needed', $ingredient['quantity_needed']);
                $insertStmt->bindParam(':unit_of_measure', $ingredient['unit_of_measure']);
                $insertStmt->execute();
            }
            
            $conn->commit();
            return ["status" => true, "message" => "Ingredients updated successfully"];
            
        } catch (PDOException $e) {
            $conn->rollBack();
            return ["status" => false, "message" => "Failed to update ingredients: " . $e->getMessage()];
        }
    }

    public function addProductSizes($data) {
        global $conn;
        
        try {
            $conn->beginTransaction();
            
            // Delete existing sizes
            $sql = "DELETE FROM product_sizes WHERE product_id = :product_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->execute();
            
            // Add new sizes
            $sql = "INSERT INTO product_sizes (product_id, size_name, price) 
                    VALUES (:product_id, :size_name, :price)";
            $stmt = $conn->prepare($sql);
            
            foreach ($data['sizes'] as $size) {
                $stmt->bindParam(':product_id', $data['product_id']);
                $stmt->bindParam(':size_name', $size['size_name']);
                $stmt->bindParam(':price', $size['price']);
                $stmt->execute();
                
                $size_id = $conn->lastInsertId();
                
                // Add ingredients for this size
                if (!empty($size['ingredients'])) {
                    $ingredientSql = "INSERT INTO product_size_ingredients 
                                    (size_id, inventory_id, quantity_needed, unit_of_measure) 
                                    VALUES (:size_id, :inventory_id, :quantity_needed, :unit_of_measure)";
                    $ingredientStmt = $conn->prepare($ingredientSql);
                    
                    foreach ($size['ingredients'] as $ingredient) {
                        $ingredientStmt->bindParam(':size_id', $size_id);
                        $ingredientStmt->bindParam(':inventory_id', $ingredient['inventory_id']);
                        $ingredientStmt->bindParam(':quantity_needed', $ingredient['quantity_needed']);
                        $ingredientStmt->bindParam(':unit_of_measure', $ingredient['unit_of_measure']);
                        $ingredientStmt->execute();
                    }
                }
            }
            
            $conn->commit();
            return ["status" => true, "message" => "Product sizes added successfully"];
        } catch (PDOException $e) {
            $conn->rollBack();
            return ["status" => false, "message" => "Failed to add product sizes: " . $e->getMessage()];
        }
    }

    public function addSizeIngredients($data) {
        global $conn;
        
        try {
            $conn->beginTransaction();
            
            // Delete existing ingredients for this size
            $sql = "DELETE FROM product_size_ingredients WHERE size_id = :size_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':size_id', $data['size_id']);
            $stmt->execute();
            
            if (!empty($data['ingredients'])) {
                // Add new ingredients
                $sql = "INSERT INTO product_size_ingredients (size_id, inventory_id, quantity_needed, unit_of_measure) 
                        VALUES (:size_id, :inventory_id, :quantity_needed, :unit_of_measure)";
                $stmt = $conn->prepare($sql);
                
                foreach ($data['ingredients'] as $ingredient) {
                    $stmt->bindParam(':size_id', $data['size_id']);
                    $stmt->bindParam(':inventory_id', $ingredient['inventory_id']);
                    $stmt->bindParam(':quantity_needed', $ingredient['quantity_needed']);
                    $stmt->bindParam(':unit_of_measure', $ingredient['unit_of_measure']);
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            return ["status" => true, "message" => "Size ingredients updated successfully"];
        } catch (PDOException $e) {
            $conn->rollBack();
            return ["status" => false, "message" => "Failed to update size ingredients: " . $e->getMessage()];
        }
    }

    public function deleteProductSize($data) {
        global $conn;
        
        try {
            $conn->beginTransaction();
            
            // Delete size ingredients first
            $sql = "DELETE FROM product_size_ingredients WHERE size_id = :size_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':size_id', $data['size_id']);
            $stmt->execute();
            
            // Then delete the size
            $sql = "DELETE FROM product_sizes WHERE size_id = :size_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':size_id', $data['size_id']);
            $stmt->execute();
            
            $conn->commit();
            return ["status" => true, "message" => "Size deleted successfully"];
        } catch (PDOException $e) {
            $conn->rollBack();
            return ["status" => false, "message" => "Failed to delete size: " . $e->getMessage()];
        }
    }
}
?>