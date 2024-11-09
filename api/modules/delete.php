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
}
