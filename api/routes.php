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
                        case 'add-item-stock':
                            echo json_encode($post->addItemStock($data));
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
                    echo json_encode($get->getMenuItems());
                    break;
                case 'get-items':
                    echo json_encode($get->getItems());
                    break;
                default:
                    echo json_encode(["error" => "Invalid GET request"]);
                    http_response_code(404);
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
    echo json_encode([
        "status" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
    http_response_code(500);
}
?>