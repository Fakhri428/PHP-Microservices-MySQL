<?php

header("Content-Type: application/json");

function callService($url, $method = "GET", $data = null)
{
    $options = [
        "http" => [
            "method" => $method,
            "header" => "Content-Type: application/json\r\n",
            "ignore_errors" => true
        ]
    ];

    if ($data !== null) {
        $options["http"]["content"] = json_encode($data);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        http_response_code(500);
        return [
            "message" => "Service tidak dapat diakses",
            "url" => $url
        ];
    }

    return json_decode($response, true);
}

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$input = json_decode(file_get_contents("php://input"), true);

if ($method === "GET" && $path === "/users") {
    echo json_encode(callService("http://localhost:8001/users"));
    exit;
}

if ($method === "GET" && $path === "/users/detail") {
    $id = $_GET["id"] ?? null;
    echo json_encode(callService("http://localhost:8001/users/detail?id=" . $id));
    exit;
}

if ($method === "POST" && $path === "/users") {
    echo json_encode(callService("http://localhost:8001/users", "POST", $input));
    exit;
}

if ($method === "GET" && $path === "/products") {
    echo json_encode(callService("http://localhost:8002/products"));
    exit;
}

if ($method === "GET" && $path === "/products/detail") {
    $id = $_GET["id"] ?? null;
    echo json_encode(callService("http://localhost:8002/products/detail?id=" . $id));
    exit;
}

if ($method === "POST" && $path === "/products") {
    echo json_encode(callService("http://localhost:8002/products", "POST", $input));
    exit;
}

if ($method === "GET" && $path === "/orders") {
    echo json_encode(callService("http://localhost:8003/orders"));
    exit;
}

if ($method === "GET" && $path === "/orders/detail") {
    $id = $_GET["id"] ?? null;
    echo json_encode(callService("http://localhost:8003/orders/detail?id=" . $id));
    exit;
}

if ($method === "POST" && $path === "/orders") {
    echo json_encode(callService("http://localhost:8003/orders", "POST", $input));
    exit;
}

if ($method === "POST" && $path === "/orders/update-status") {
    echo json_encode(callService("http://localhost:8003/orders/update-status", "POST", $input));
    exit;
}

if ($method === "GET" && $path === "/summary") {
    $users = callService("http://localhost:8001/users");
    $products = callService("http://localhost:8002/products");
    $orders = callService("http://localhost:8003/orders");

    echo json_encode([
        "service" => "API Gateway",
        "summary" => [
            "total_users" => count($users["data"] ?? []),
            "total_products" => count($products["data"] ?? []),
            "total_orders" => count($orders["data"] ?? [])
        ],
        "data" => [
            "users" => $users["data"] ?? [],
            "products" => $products["data"] ?? [],
            "orders" => $orders["data"] ?? []
        ]
    ]);
    exit;
}

if ($method === "GET" && $path === "/status") {
    $userService = callService("http://localhost:8001/users");
    $productService = callService("http://localhost:8002/products");
    $orderService = callService("http://localhost:8003/orders");

    echo json_encode([
        "service" => "API Gateway",
        "status" => [
            "user_service" => isset($userService["data"]) ? "running" : "down",
            "product_service" => isset($productService["data"]) ? "running" : "down",
            "order_service" => isset($orderService["data"]) ? "running" : "down"
        ]
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    "message" => "Endpoint API Gateway tidak ditemukan"
]);