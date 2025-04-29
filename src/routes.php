<?php
require_once __DIR__ . '/controllers.php';

$requestMethod = $_SERVER["REQUEST_METHOD"];
$route = isset($_GET['route']) ? explode('/', $_GET['route']) : [];

// Handle OPTIONS requests for CORS preflight
if ($requestMethod === "OPTIONS") {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

if ($requestMethod === "GET" && count($route) == 1 && $route[0] === "user") {
    getUsers();
} elseif ($requestMethod === "GET" && count($route) == 1 && $route[0] === "userlist") {
    getUserlist();
} elseif ($requestMethod === "POST" && count($route) == 1 && $route[0] === "user") {
    createUser();
} elseif ($requestMethod === "PUT" && count($route) == 2 && $route[0] === "user") {
    updateUser($route[1]);
} else {
    http_response_code(404);
    echo json_encode(["message" => "Route not found"]);
}
