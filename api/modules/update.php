<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

class Update {
    public function updateItemStock($data) {
        global $conn;
        $inventory_id = $data['inventory_id'];
        $stock_quantity = $data['stock_quantity'];

        $sql = "UPDATE inventory SET stock_quantity = :stock_quantity, last_updated = NOW() WHERE inventory_id = :inventory_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':inventory_id', $inventory_id);
        $stmt->bindParam(':stock_quantity', $stock_quantity);

        try {
            $stmt->execute();
            
            // Fetch the updated record to get the new timestamp
            $sql = "SELECT last_updated FROM inventory WHERE inventory_id = :inventory_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':inventory_id', $inventory_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                "status" => true, 
                "message" => "Item stock updated successfully",
                "last_updated" => $result['last_updated']
            ];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to update item stock: " . $e->getMessage()];
        }
    }

    public function updateMenuItem($data) {
        global $conn;
        
        // Get the original product data
        $stmt = $conn->prepare("SELECT category, size FROM product WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->execute();
        $originalProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Determine the size value
        $newSize = isset($data['size']) && !empty($data['size']) ? $data['size'] : 'base-size';
        if (in_array($data['category'], ['Drinks', 'Pizza']) && empty($data['size'])) {
            return [
                "status" => false,
                "message" => "Size is required for {$data['category']}"
            ];
        }
        
        $sql = "UPDATE product SET 
                name = :name,
                price = :price,
                category = :category,
                size = :size";
        
        if (isset($data['image']) && !empty($data['image'])) {
            $sql .= ", image = :image";
        }
        
        $sql .= " WHERE product_id = :product_id";
        
        $stmt = $conn->prepare($sql);
        
        $params = [
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':category' => $data['category'],
            ':size' => $newSize,
            ':product_id' => $data['product_id']
        ];
        
        if (isset($data['image']) && !empty($data['image'])) {
            $params[':image'] = $data['image'];
        }
        
        try {
            $stmt->execute($params);
            return [
                "status" => true,
                "message" => "Menu item updated successfully"
            ];
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return [
                "status" => false,
                "message" => "Failed to update menu item: " . $e->getMessage()
            ];
        }
    }

    public function updateProductIngredient($data) {
        global $conn;
        
        try {
            // Get the ingredient name if inventory_id is provided
            if (isset($data['inventory_id'])) {
                $getNameSql = "SELECT item_name FROM inventory WHERE inventory_id = :inventory_id";
                $stmt = $conn->prepare($getNameSql);
                $stmt->bindParam(':inventory_id', $data['inventory_id']);
                $stmt->execute();
                $ingredient = $stmt->fetch(PDO::FETCH_ASSOC);
                $data['ingredient_name'] = $ingredient['item_name'];
            }

            $sql = "UPDATE product_ingredients 
                    SET inventory_id = :inventory_id,
                        ingredient_name = :ingredient_name,
                        quantity_needed = :quantity_needed
                    WHERE product_ingredient_id = :product_ingredient_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':product_ingredient_id', $data['product_ingredient_id']);
            $stmt->bindParam(':inventory_id', $data['inventory_id']);
            $stmt->bindParam(':ingredient_name', $data['ingredient_name']);
            $stmt->bindParam(':quantity_needed', $data['quantity_needed']);
            
            $stmt->execute();
            return ["status" => true, "message" => "Product ingredient updated successfully"];
            
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to update product ingredient: " . $e->getMessage()];
        }
    }
}
