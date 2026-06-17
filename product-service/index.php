<?php

header("Content-Type: application/json");

require_once "db.php";

/*
|--------------------------------------------------------------------------
| REQUEST INFO
|--------------------------------------------------------------------------
*/

$method = $_SERVER["REQUEST_METHOD"];

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

/*
|--------------------------------------------------------------------------
| FIX PATH FOR LAMP / APACHE
|--------------------------------------------------------------------------
| Example:
| /product-service/products
| becomes:
| /products
|--------------------------------------------------------------------------
*/

$path = str_replace("/product-service", "", $path);

/*
|--------------------------------------------------------------------------
| GET ALL PRODUCTS
|--------------------------------------------------------------------------
*/

if ($method === "GET" && $path === "/products") {

    $stmt = $pdo->query("
        SELECT
            id,
            name,
            price,
            stock,
            created_at
        FROM products
        ORDER BY id ASC
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "service" => "Product Service",
        "data" => $products
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| GET PRODUCT DETAIL
|--------------------------------------------------------------------------
*/

if ($method === "GET" && $path === "/products/detail") {

    $id = $_GET["id"] ?? null;

    if (!$id) {

        http_response_code(400);

        echo json_encode([
            "message" => "Parameter id wajib diisi"
        ]);

        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            price,
            stock,
            created_at
        FROM products
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {

        http_response_code(404);

        echo json_encode([
            "message" => "Produk tidak ditemukan"
        ]);

        exit;
    }

    echo json_encode([
        "service" => "Product Service",
        "data" => $product
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CREATE PRODUCT
|--------------------------------------------------------------------------
*/

if ($method === "POST" && $path === "/products") {

    $input = json_decode(file_get_contents("php://input"), true);

    $name = $input["name"] ?? null;
    $price = $input["price"] ?? null;
    $stock = $input["stock"] ?? 0;

    if (!$name || !$price) {

        http_response_code(400);

        echo json_encode([
            "message" => "Name dan price wajib diisi"
        ]);

        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO products (
            name,
            price,
            stock
        )
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $name,
        $price,
        $stock
    ]);

    http_response_code(201);

    echo json_encode([
        "message" => "Produk berhasil dibuat",
        "data" => [
            "id" => $pdo->lastInsertId(),
            "name" => $name,
            "price" => $price,
            "stock" => $stock
        ]
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| REDUCE STOCK
|--------------------------------------------------------------------------
*/

if ($method === "POST" && $path === "/products/reduce-stock") {

    $input = json_decode(file_get_contents("php://input"), true);

    $productId = $input["product_id"] ?? null;
    $quantity = $input["quantity"] ?? null;

    if (!$productId || !$quantity) {

        http_response_code(400);

        echo json_encode([
            "message" => "product_id dan quantity wajib diisi"
        ]);

        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            stock
        FROM products
        WHERE id = ?
    ");

    $stmt->execute([$productId]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {

        http_response_code(404);

        echo json_encode([
            "message" => "Produk tidak ditemukan"
        ]);

        exit;
    }

    if ($product["stock"] < $quantity) {

        http_response_code(400);

        echo json_encode([
            "message" => "Stok tidak mencukupi"
        ]);

        exit;
    }

    $newStock = $product["stock"] - $quantity;

    $stmt = $pdo->prepare("
        UPDATE products
        SET stock = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $newStock,
        $productId
    ]);

    echo json_encode([
        "message" => "Stok berhasil dikurangi",
        "data" => [
            "product_id" => $productId,
            "old_stock" => $product["stock"],
            "new_stock" => $newStock
        ]
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| ENDPOINT NOT FOUND
|--------------------------------------------------------------------------
*/

http_response_code(404);

echo json_encode([
    "message" => "Endpoint Product Service tidak ditemukan",
    "path" => $path,
    "method" => $method
]);