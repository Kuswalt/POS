<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

class Delete {
    public function deleteItemStock($data) {
        global $conn;
        $inventory_id = $data['inventory_id'];

        $sql = "DELETE FROM inventory WHERE inventory_id = :inventory_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':inventory_id', $inventory_id);

        try {
            $stmt->execute();
            return ["status" => true, "message" => "Item stock deleted successfully"];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to delete item stock: " . $e->getMessage()];
        }
    }

    public function deleteMenuItem($data) {
        global $conn;
        $id = $data['product_id'];

        // First, delete related items in the cart
        $deleteCartSql = "DELETE FROM cart WHERE product_id = :id";
        $stmt = $conn->prepare($deleteCartSql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // Now delete the product
        $sql = "DELETE FROM product WHERE product_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);

        try {
            $stmt->execute();
            return ["status" => true, "message" => "Menu item deleted successfully"];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Failed to delete menu item: " . $e->getMessage()];
        }
    }

    public function deleteOrder($data) {
        global $conn;
        
        if (!isset($data['order_id'])) {
            return ["status" => false, "message" => "Order ID is required"];
        }
        
        try {
            $conn->beginTransaction();
            
            // Get customer_id before deleting the order
            $sql = "SELECT customer_id FROM `order` WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->execute();
            $customer_id = $stmt->fetchColumn();
            
            // Delete related records first (due to foreign key constraints)
            // Delete from receipt table
            $sql = "DELETE FROM receipt WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->execute();
            
            // Delete from sales table
            $sql = "DELETE FROM sales WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->execute();
            
            // Delete from order_item table
            $sql = "DELETE FROM order_item WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->execute();
            
            // Delete from order table
            $sql = "DELETE FROM `order` WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $data['order_id']);
            $stmt->execute();
            
            // Finally, delete the customer
            if ($customer_id) {
                $sql = "DELETE FROM customer WHERE customer_id = :customer_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':customer_id', $customer_id);
                $stmt->execute();
            }
            
            $conn->commit();
            return ["status" => true, "message" => "Order and related customer deleted successfully"];
            
        } catch (PDOException $e) {
            $conn->rollBack();
            return ["status" => false, "message" => "Failed to delete order: " . $e->getMessage()];
        }
    }

    public function deleteAllOrders() {
        global $conn;
        
        try {
            $conn->beginTransaction();
            
            // Delete from all related tables in the correct order
            // Delete from receipt table first
            $sql = "DELETE FROM receipt";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            // Delete from sales table
            $sql = "DELETE FROM sales";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            // Delete from order_item table
            $sql = "DELETE FROM order_item";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            // Delete from order table
            $sql = "DELETE FROM `order`";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            // Finally, delete all customers
            $sql = "DELETE FROM customer";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            $conn->commit();
            return ["status" => true, "message" => "All orders and customers deleted successfully"];
            
        } catch (PDOException $e) {
            $conn->rollBack();
            return ["status" => false, "message" => "Failed to delete orders: " . $e->getMessage()];
        }
    }
}
