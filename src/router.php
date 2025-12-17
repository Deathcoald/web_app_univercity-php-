<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/db.php';          
require_once __DIR__ . '/auth/Register.php';      
require_once __DIR__ . '/auth/LogIn.php';       
require_once __DIR__ . '/auth/FinanceController.php';          

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); 
$method = $_SERVER['REQUEST_METHOD'];

switch ($request) {
    case '/register':
        if ($method === 'POST') registerUser($pdo);
        break;

    case '/login':
        if ($method === 'POST') loginUser($pdo);
        break;

    case '/create':
        if ($method === 'POST') createFinance($pdo);
        break;

    case '/finance':
        if ($method === 'GET') fetchFinance($pdo);
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Not found"]);
}
