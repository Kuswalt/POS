<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

class Get {
    public function getMenuItems() {
        global $conn;

        $sql = "SELECT * FROM product";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getItems() {
        global $conn;
        
        $sql = "SELECT inventory_id, item_name, stock_quantity FROM inventory ORDER BY item_name";
        $stmt = $conn->prepare($sql);
        
        try {
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "data" => $items];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to fetch items: " . $e->getMessage()];
        }
    }
}
