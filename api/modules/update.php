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
        
        if (!isset($data['product_id'], $data['name'], $data['price'], $data['category'])) {
            return ["status" => false, "message" => "Missing required fields"];
        }

        $id = $data['product_id'];
        $name = $data['name'];
        $price = $data['price'];
        $category = $data['category'];
        
        // Handle image upload if a new image was provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
            
            // Update with new image
            $sql = "UPDATE product SET name = :name, image = :image, price = :price, category = :category WHERE product_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':image', $fileName);
        } else {
            // Update without changing image
            $sql = "UPDATE product SET name = :name, price = :price, category = :category WHERE product_id = :id";
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':category', $category);

        try {
            $stmt->execute();
            return ["status" => true, "message" => "Menu item updated successfully"];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to update menu item: " . $e->getMessage()];
        }
    }
}
