<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config.php';
require_once 'modules/post.php';
require_once 'modules/update.php';
require_once 'modules/delete.php';
require_once 'modules/get.php';

$post = new Post($conn);
$update = new Update($conn);
$delete = new Delete($conn);
$get = new Get($conn);

if (isset($_REQUEST['request'])) {
    $request = explode('/', $_REQUEST['request']);
} else {
    echo json_encode(["error" => "Request parameter not found"]);
    http_response_code(404);
    exit();
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            switch ($request[0]) {
                case 'add-menu-item':
                case 'update-menu-item':
                    // Handle form data for menu items
                    $data = array(
                        'product_id' => $_POST['product_id'] ?? null,
                        'name' => $_POST['name'] ?? null,
                        'image' => $_FILES['image']['name'] ?? null,
                        'price' => $_POST['price'] ?? null,
                        'category' => $_POST['category'] ?? null,
                        'size' => $_POST['size'] ?? 'base-size'
                    );
                    
                    // Debug log
                    error_log('Received POST data: ' . print_r($_POST, true));
                    
                    // Handle file upload
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileName = time() . '_' . $_FILES['image']['name'];
                        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
                        $data['image'] = $fileName;
                    }
                    
                    if ($request[0] === 'add-menu-item') {
                        echo json_encode($post->addMenuItem($data));
                    } else {
                        echo json_encode($update->updateMenuItem($data));
                    }
                    break;
                    
                case 'add-customer':
                    $data = json_decode(file_get_contents("php://input"), true);
                    echo json_encode($post->addCustomer($data));
                    break;
                    
                case 'add-receipt':
                    $data = json_decode(file_get_contents("php://input"), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        echo json_encode([
                            "status" => false,
                            "message" => "Invalid JSON data: " . json_last_error_msg()
                        ]);
                        break;
                    }
                    echo json_encode($post->addReceipt($data));
                    break;
                    
                case 'logout':
                    session_start();
                    session_destroy();
                    echo json_encode(["status" => true, "message" => "Logged out successfully"]);
                    break;
                    
                case 'add-product-ingredient':
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo json_encode($post->addProductIngredient($data));
                    break;
                    
                case 'add-item-stock':
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo json_encode($post->addItemStock($data));
                    break;
                    
                default:
                    // For other POST requests, use JSON
                    $data = json_decode(file_get_contents("php://input"), true);
                    switch ($request[0]) {
                        case 'add-account':
                            echo json_encode($post->registerUser($data));
                            break;
                        case 'login':
                            echo json_encode($post->loginUser($data));
                            break;
                        case 'add-to-cart':
                            echo json_encode($post->addToCart($data));
                            break;
                        case 'create-order':
                            echo json_encode($post->createOrder($data));
                            break;
                        case 'add-sale':
                            echo json_encode($post->addSale($data));
                            break;
                        default:
                            echo json_encode(["error" => "This is forbidden"]);
                            http_response_code(403);
                            break;
                    }
                    break;
            }
            break;
        case 'GET':
            switch ($request[0]) {
                case 'get-menu-items':
                    $get = new Get();
                    echo json_encode($get->getMenuItems());
                    break;
                case 'get-items':
                    echo json_encode($get->getItems());
                    break;
                case 'get-sales-data':
                    echo json_encode($get->getSalesData());
                    break;
                case 'get-product-ingredients':
                    if (!isset($_GET['product_id'])) {
                        echo json_encode(["status" => false, "message" => "Product ID is required"]);
                        exit;
                    }
                    
                    $product_id = $_GET['product_id'];
                    echo json_encode($get->getProductIngredients($product_id));
                    exit;
                case 'check-product-availability':
                    if (!isset($_GET['product_id'])) {
                        echo json_encode(["status" => false, "message" => "Product ID is required"]);
                        exit;
                    }
                    echo json_encode($get->checkIngredientAvailability($_GET['product_id'], 1));
                    break;
                case 'get-products-using-ingredient':
                    $inventory_id = $_GET['inventory_id'] ?? null;
                    if ($inventory_id) {
                        $result = $get->getProductsUsingIngredient($inventory_id);
                        echo json_encode($result);
                    } else {
                        echo json_encode([
                            "status" => false,
                            "message" => "Inventory ID is required"
                        ]);
                    }
                    break;
                case 'get-cart-item':
                    if (!isset($_GET['product_id']) || !isset($_GET['user_id'])) {
                        echo json_encode([
                            "status" => false,
                            "message" => "Product ID and User ID are required"
                        ]);
                        break;
                    }
                    echo json_encode($get->getCartItem($_GET['product_id'], $_GET['user_id']));
                    break;
                case 'get-cart-items':
                    if (!isset($_GET['user_id'])) {
                        echo json_encode([
                            "status" => false,
                            "message" => "User ID is required"
                        ]);
                        break;
                    }
                    echo json_encode($get->getCartItems($_GET['user_id']));
                    break;
                case 'check-ingredient-availability':
                    if (!isset($_GET['product_id']) || !isset($_GET['quantity'])) {
                        echo json_encode([
                            "status" => false,
                            "message" => "Product ID and quantity are required"
                        ]);
                        break;
                    }
                    $product_id = $_GET['product_id'];
                    $quantity = $_GET['quantity'];
                    echo json_encode($get->checkIngredientAvailability($product_id, $quantity));
                    break;
                case 'get-batch-product-ingredients':
                    $product_ids = json_decode($_GET['product_ids']);
                    echo json_encode($get->getBatchProductIngredients($product_ids));
                    break;
                default:
                    echo json_encode(["error" => "Invalid request"]);
                    http_response_code(400);
                    break;
            }
            break;
        case 'PUT':
            switch ($request[0]) {
                case 'update-menu-item':
                    // Handle form data for menu items
                    $data = array(
                        'product_id' => $_POST['product_id'] ?? null,
                        'name' => $_POST['name'] ?? null,
                        'image' => $_FILES['image']['name'] ?? null,
                        'price' => $_POST['price'] ?? null,
                        'category' => $_POST['category'] ?? null
                    );
                    
                    // Handle file upload
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileName = time() . '_' . $_FILES['image']['name'];
                        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
                        $data['image'] = $fileName;
                    }
                    
                    echo json_encode($update->updateMenuItem($data));
                    break;
                case 'update-product-ingredient':
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo json_encode($update->updateProductIngredient($data));
                    break;
                case 'update-item-stock':
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo json_encode($update->updateItemStock($data));
                    break;
                default:
                    $data = json_decode(file_get_contents("php://input"), true);
                    switch ($request[0]) {
                        case 'update-item-stock':
                            echo json_encode($update->updateItemStock($data));
                            break;
                        default:
                            echo json_encode(["error" => "This is forbidden"]);
                            http_response_code(403);
                            break;
                    }
                    break;
            }
            break;
        case 'DELETE':
            $data = json_decode(file_get_contents("php://input"), true);
            switch ($request[0]) {
                case 'delete-item-stock':
                    echo json_encode($delete->deleteItemStock($data));
                    break;
                case 'delete-menu-item':
                    echo json_encode($delete->deleteMenuItem($data));
                    break;
                case 'delete-order':
                    echo json_encode($delete->deleteOrder($data));
                    break;
                case 'delete-all-orders':
                    echo json_encode($delete->deleteAllOrders());
                    break;
                case 'delete-product-ingredient':
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo json_encode($delete->deleteProductIngredient($data));
                    break;
                case 'clear-cart':
                    if (!isset($data['user_id'])) {
                        echo json_encode([
                            "status" => false,
                            "message" => "User ID is required"
                        ]);
                        break;
                    }
                    echo json_encode($delete->clearCart($data['user_id']));
                    break;
                case 'remove-from-cart':
                    if (!isset($data['product_id']) || !isset($data['user_id'])) {
                        echo json_encode([
                            "status" => false,
                            "message" => "Product ID and User ID are required"
                        ]);
                        break;
                    }
                    echo json_encode($delete->removeFromCart($data['product_id'], $data['user_id']));
                    break;
                default:
                    echo json_encode(["error" => "This is forbidden"]);
                    http_response_code(403);
                    break;
            }
            break;
        default:
            echo json_encode(["error" => "Method not available"]);
            http_response_code(404);
            break;
    }
} catch (Exception $e) {
    error_log("Server error: " . $e->getMessage());
    echo json_encode([
        "status" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
    http_response_code(500);
}
?>