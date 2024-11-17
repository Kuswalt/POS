<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

class Get {
    public function getMenuItems() {
        global $conn;

        $sql = "SELECT p.*, 
                CASE 
                    WHEN MIN(i.stock_quantity / NULLIF(pi.quantity_needed, 0)) < 1 THEN false 
                    ELSE true 
                END as is_available
                FROM product p
                LEFT JOIN product_ingredients pi ON p.product_id = pi.product_id
                LEFT JOIN inventory i ON pi.inventory_id = i.inventory_id
                GROUP BY p.product_id";
        
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
                    o.order_id,
                    ua.username,
                    c.Name as customer_name,
                    c.total_amount as amount_paid,
                    o.total_amount,
                    o.order_date,
                    p.name as product_name,
                    p.price as unit_price,
                    oi.quantity,
                    (p.price * oi.quantity) as line_total
                FROM `order` o
                JOIN user_acc ua ON o.user_id = ua.User_id
                JOIN order_item oi ON o.order_id = oi.order_id
                JOIN product p ON oi.product_id = p.product_id
                JOIN customer c ON o.customer_id = c.customer_id
                ORDER BY o.order_date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group results by order_id for table display
            $groupedResults = [];
            foreach ($results as $row) {
                $orderId = $row['order_id'];
                if (!isset($groupedResults[$orderId])) {
                    $groupedResults[$orderId] = [
                        'username' => $row['username'],
                        'customer_name' => $row['customer_name'],
                        'amount_paid' => $row['amount_paid'],
                        'total_amount' => $row['total_amount'],
                        'order_date' => $row['order_date'],
                        'products' => [],
                        'quantities' => []
                    ];
                }
                $groupedResults[$orderId]['products'][] = $row['product_name'];
                $groupedResults[$orderId]['quantities'][] = $row['quantity'];
            }
            
            // Format results for table display
            $formattedResults = array_map(function($group) {
                return [
                    'username' => $group['username'],
                    'product_name' => implode(', ', $group['products']),
                    'quantity' => implode(', ', $group['quantities']),
                    'customer_name' => $group['customer_name'],
                    'amount_paid' => $group['amount_paid'],
                    'total_amount' => $group['total_amount'],
                    'order_date' => $group['order_date']
                ];
            }, $groupedResults);
            
            // Calculate chart data using actual unit prices and quantities
            $chartData = [];
            foreach ($results as $row) {
                $date = date('Y-m', strtotime($row['order_date'])); // For monthly data
                $product = $row['product_name'];
                $actualTotal = floatval($row['unit_price']) * floatval($row['quantity']); // Calculate using unit price
                
                if (!isset($chartData[$date])) {
                    $chartData[$date] = [];
                }
                if (!isset($chartData[$date][$product])) {
                    $chartData[$date][$product] = 0;
                }
                $chartData[$date][$product] += $actualTotal;
            }
            
            // Add daily chart data
            $dailyChartData = [];
            foreach ($results as $row) {
                $date = date('Y-m-d', strtotime($row['order_date']));
                $product = $row['product_name'];
                $actualTotal = floatval($row['unit_price']) * floatval($row['quantity']);
                
                if (!isset($dailyChartData[$date])) {
                    $dailyChartData[$date] = [];
                }
                if (!isset($dailyChartData[$date][$product])) {
                    $dailyChartData[$date][$product] = 0;
                }
                $dailyChartData[$date][$product] += $actualTotal;
            }
            
            // Add chart data to the response
            return [
                "status" => true,
                "data" => array_values($formattedResults),
                "chartData" => $chartData,
                "dailyChartData" => $dailyChartData
            ];
        } catch (PDOException $e) {
            return [
                "status" => false,
                "message" => "Failed to fetch sales data: " . $e->getMessage()
            ];
        }
    }
    public function checkIngredientAvailability($product_id, $quantity) {
        global $conn;
        
        $sql = "SELECT i.item_name, i.stock_quantity, (pi.quantity_needed * :order_quantity) as needed_quantity 
                FROM inventory i 
                JOIN product_ingredients pi ON i.inventory_id = pi.inventory_id 
                WHERE pi.product_id = :product_id 
                HAVING stock_quantity < needed_quantity";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':order_quantity', $quantity);
        $stmt->execute();
        
        $insufficient = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($insufficient) > 0) {
            return [
                "status" => false,
                "message" => "Insufficient ingredients",
                "details" => $insufficient
            ];
        }
        
        return ["status" => true];
    }
}
