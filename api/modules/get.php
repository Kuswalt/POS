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
        
        $sql = "SELECT inventory_id, item_name, stock_quantity, last_updated FROM inventory ORDER BY item_name";
        $stmt = $conn->prepare($sql);
        
        try {
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "data" => $items];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to fetch items: " . $e->getMessage()];
        }
    }
    public function getSalesData() {
        global $conn;
        
        try {
            $sql = "SELECT 
                    ua.username,
                    p.name as product_name,
                    oi.quantity,
                    c.Name as customer_name,
                    c.total_amount as amount_paid,
                    o.total_amount,
                    o.order_date
                FROM `order` o
                JOIN user_acc ua ON o.user_id = ua.User_id
                JOIN order_item oi ON o.order_id = oi.order_id
                JOIN product p ON oi.product_id = p.product_id
                JOIN customer c ON o.customer_id = c.customer_id
                ORDER BY o.order_date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "status" => true,
                "data" => $result
            ];
        } catch (PDOException $e) {
            return [
                "status" => false,
                "message" => "Failed to fetch sales data: " . $e->getMessage()
            ];
        }
    }
}
