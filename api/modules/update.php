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
        $unit_of_measure = $data['unit_of_measure'];

        $sql = "UPDATE inventory 
                SET stock_quantity = :stock_quantity, 
                    unit_of_measure = :unit_of_measure,
                    last_updated = NOW() 
                WHERE inventory_id = :inventory_id";
                
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':inventory_id', $inventory_id);
        $stmt->bindParam(':stock_quantity', $stock_quantity);
        $stmt->bindParam(':unit_of_measure', $unit_of_measure);

        try {
            $stmt->execute();
            return [
                "status" => true, 
                "message" => "Item stock updated successfully"
            ];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to update item stock: " . $e->getMessage()];
        }
    }

    public function updateMenuItem($data) {
        global $conn;
        
        // Debug log
        error_log('Update data received: ' . print_r($data, true));
        
        // First get the original product data
        $getOriginalSql = "SELECT category, size FROM product WHERE product_id = :product_id";
        $origStmt = $conn->prepare($getOriginalSql);
        $origStmt->execute([':product_id' => $data['product_id']]);
        $originalProduct = $origStmt->fetch(PDO::FETCH_ASSOC);
        
        // Determine the new size
        $newSize = 'base-size';
        if (in_array($data['category'], ['Drinks', 'Pizza'])) {
            // If new category is Drinks/Pizza, use the provided size
            $newSize = $data['size'] ?? 'base-size';
        }
        
        // First check if another product exists with the same specifications
        $checkSql = "SELECT * FROM product 
                     WHERE name = :name 
                     AND category = :category 
                     AND price = :price 
                     AND product_id != :product_id";
        
        $checkStmt = $conn->prepare($checkSql);
        try {
            $checkStmt->execute([
                ':name' => $data['name'],
                ':category' => $data['category'],
                ':price' => $data['price'],
                ':product_id' => $data['product_id']
            ]);
            
            if ($checkStmt->fetch()) {
                return [
                    "status" => false,
                    "message" => "Another product already exists with the same specifications"
                ];
            }
        } catch (PDOException $e) {
            error_log('Error checking product existence: ' . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error checking product existence: " . $e->getMessage()
            ];
        }
        
        // If we get here, proceed with the update
        $sql = "UPDATE product SET 
                name = :name,
                price = :price,
                category = :category,
                size = :size";
        
        // Add image to update if provided
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
                "message" => "Menu item updated successfully",
                "debug" => [
                    "original_category" => $originalProduct['category'],
                    "original_size" => $originalProduct['size'],
                    "new_category" => $data['category'],
                    "new_size" => $newSize
                ]
            ];
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return [
                "status" => false,
                "message" => "Failed to update menu item: " . $e->getMessage()
            ];
        }
    }
}
