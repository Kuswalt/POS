<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

class Update {
    public function updateItemStock($data) {
        global $conn;
        $stock_quantity = $data['stock_quantity'];

        $sql = "UPDATE inventory SET stock_quantity = :stock_quantity, last_updated = NOW() WHERE inventory_id = :inventory_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':inventory_id', $inventory_id);
        $stmt->bindParam(':stock_quantity', $stock_quantity);

        try {
            $stmt->execute();
            return ["status" => true, "message" => "Item stock updated successfully"];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to update item stock: " . $e->getMessage()];
        }
    }

    public function updateMenuItem($data) {
        global $conn;
        $id = $data['product_id'];
        $name = $data['name'];
        $image = $data['image'];
        $price = $data['price'];
        $category = $data['category'];

        $sql = "UPDATE product SET name = :name, image = :image, price = :price, category = :category WHERE product_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':image', $image);
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
