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
        
        try {
            $conn->beginTransaction();
            
            // Insert into inventory
            $sql = "INSERT INTO inventory (item_name, stock_quantity, unit_of_measure, last_updated) 
                    VALUES (:item_name, :stock_quantity, :unit_of_measure, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':item_name', $data['item_name']);
            $stmt->bindParam(':stock_quantity', $data['stock_quantity']);
            $stmt->bindParam(':unit_of_measure', $data['unit_of_measure']);
            $stmt->execute();
            
            $inventory_id = $conn->lastInsertId();
            
            // Update product_ingredients with the ingredient name
            $updateSql = "UPDATE product_ingredients 
                         SET ingredient_name = :ingredient_name 
                         WHERE inventory_id = :inventory_id";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bindParam(':ingredient_name', $data['item_name']);
            $updateStmt->bindParam(':inventory_id', $inventory_id);
            $updateStmt->execute();
            
            $conn->commit();
            return ["status" => true, "message" => "Item stock added successfully"];
            
        } catch (PDOException $e) {
            $conn->rollBack();
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
        
        try {
            // Check for existing product with same attributes
            $sql = "SELECT * FROM product WHERE 
                    name = :name AND 
                    category = :category AND 
                    price = :price";
            
            // Add size check only for Pizza and Drinks
            if (in_array($data['category'], ['Pizza', 'Drinks'])) {
                $sql .= " AND size = :size";
            }
            
            $stmt = $conn->prepare($sql);
            $params = [
                ':name' => $data['name'],
                ':category' => $data['category'],
                ':price' => $data['price']
            ];
            
            // Add size parameter only for Pizza and Drinks
            if (in_array($data['category'], ['Pizza', 'Drinks'])) {
                $params[':size'] = $data['size'];
            }
            
            $stmt->execute($params);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                return [
                    "status" => false,
                    "message" => "A similar product already exists with these details",
                    "duplicate" => true
                ];
            }
            
            // If no duplicate found, proceed with insertion
            $sql = "INSERT INTO product (name, image, price, category, size) 
                    VALUES (:name, :image, :price, :category, :size)";
            $stmt = $conn->prepare($sql);
            
            $size = isset($data['size']) && !empty($data['size']) ? $data['size'] : 'base-size';
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':category', $data['category']);
            $stmt->bindParam(':size', $size);
            
            $stmt->execute();
            return [
                "status" => true,
                "message" => "Menu item added successfully",
                "product_id" => $conn->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            return [
                "status" => false,
                "message" => "Failed to add menu item: " . $e->getMessage()
            ];
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

    public function processCartCheckout($data) {
        global $conn;
        
        try {
            $conn->beginTransaction();
            
            // Existing checkout code...
            
            // After processing the order, update inventory
            $sql = "SELECT c.product_id, c.quantity, pi.inventory_id, pi.quantity_needed, i.stock_quantity 
                   FROM cart c
                   JOIN product_ingredients pi ON c.product_id = pi.product_id
                   JOIN inventory i ON pi.inventory_id = i.inventory_id
                   WHERE c.user_id = :user_id";
                   
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->execute();
            $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Update inventory quantities
            foreach ($ingredients as $ingredient) {
                $consumed_quantity = $ingredient['quantity_needed'] * $ingredient['quantity'];
                $new_stock = $ingredient['stock_quantity'] - $consumed_quantity;
                
                $updateSql = "UPDATE inventory 
                             SET stock_quantity = :new_stock,
                                 last_updated = NOW() 
                             WHERE inventory_id = :inventory_id";
                
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bindParam(':new_stock', $new_stock);
                $updateStmt->bindParam(':inventory_id', $ingredient['inventory_id']);
                $updateStmt->execute();
            }
            
            $conn->commit();
            return [
                "status" => true,
                "message" => "Order processed and inventory updated successfully"
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            return [
                "status" => false,
                "message" => "Error processing checkout: " . $e->getMessage()
            ];
        }
    }

    private function logInventoryUpdate($productId, $ingredientId, $oldQuantity, $newQuantity) {
        error_log(sprintf(
            "Inventory Update - Product: %d, Ingredient: %d, Old Qty: %f, New Qty: %f",
            $productId,
            $ingredientId,
            $oldQuantity,
            $newQuantity
        ));
    }

    public function addProductIngredient($data) {
        global $conn;
        
        try {
            // First get the ingredient name from inventory
            $getNameSql = "SELECT item_name FROM inventory WHERE inventory_id = :inventory_id";
            $stmt = $conn->prepare($getNameSql);
            $stmt->bindParam(':inventory_id', $data['inventory_id']);
            $stmt->execute();
            $ingredient = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql = "INSERT INTO product_ingredients 
                    (product_id, inventory_id, ingredient_name, quantity_needed) 
                    VALUES 
                    (:product_id, :inventory_id, :ingredient_name, :quantity_needed)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->bindParam(':inventory_id', $data['inventory_id']);
            $stmt->bindParam(':ingredient_name', $ingredient['item_name']);
            $stmt->bindParam(':quantity_needed', $data['quantity_needed']);
            
            $stmt->execute();
            return ["status" => true, "message" => "Product ingredient added successfully"];
            
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to add product ingredient: " . $e->getMessage()];
        }
    }
}
?>