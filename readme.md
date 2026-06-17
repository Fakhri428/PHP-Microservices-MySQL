# PHP Microservices MySQL

Proyek ini merupakan contoh praktikum sederhana untuk memahami arsitektur **microservices** menggunakan **PHP Native**, **MySQL**, dan **API Gateway**. Sistem terdiri dari tiga service utama, yaitu User Service, Product Service, dan Order Service. Setiap service menggunakan database sendiri agar prinsip pemisahan tanggung jawab pada microservices dapat terlihat dengan jelas.

## Tujuan Praktikum

Setelah menyelesaikan praktikum ini, mahasiswa diharapkan mampu:

1. Memahami konsep dasar microservices.
2. Membuat beberapa service sederhana menggunakan PHP Native.
3. Menghubungkan setiap service dengan database MySQL yang berbeda.
4. Membuat API Gateway sebagai pintu masuk utama client.
5. Menguji komunikasi antarservice menggunakan browser, Thunder Client, Postman, atau tools sejenis.

## Teknologi yang Digunakan

| Teknologi | Fungsi |
|---|---|
| PHP Native | Membuat service REST API sederhana |
| MySQL | Menyimpan data setiap service |
| XAMPP | Menjalankan PHP dan MySQL secara lokal |
| PDO | Menghubungkan PHP dengan database MySQL |
| Thunder Client/Postman | Menguji endpoint API |

## Arsitektur Sistem

```text
Client
  |
  v
API Gateway : localhost:8000
  |
  |-- User Service    : localhost:8001 -> db_user_service
  |-- Product Service : localhost:8002 -> db_product_service
  |-- Order Service   : localhost:8003 -> db_order_service
```

API Gateway menjadi pintu masuk utama bagi client. Client tidak perlu mengakses setiap service secara langsung. Request dari client diterima oleh API Gateway, kemudian diteruskan ke service yang sesuai.

## Struktur Folder

Buat folder utama dengan nama `php-microservices-mysql`, kemudian buat struktur folder berikut.

```text
php-microservices-mysql/
|
├── api-gateway/
│   └── index.php
|
├── user-service/
│   ├── db.php
│   └── index.php
|
├── product-service/
│   ├── db.php
│   └── index.php
|
└── order-service/
    ├── db.php
    └── index.php
```

## Penjelasan Struktur Folder

| Folder | Keterangan |
|---|---|
| `api-gateway` | Mengatur request dari client ke service |
| `user-service` | Mengelola data user |
| `product-service` | Mengelola data produk |
| `order-service` | Mengelola data pesanan |

## Persiapan Database

Masuk ke **phpMyAdmin** atau **MySQL terminal**, kemudian buat tiga database berikut.

```sql
CREATE DATABASE db_user_service;
CREATE DATABASE db_product_service;
CREATE DATABASE db_order_service;
```

## Membuat Tabel User Service

Gunakan database `db_user_service`.

```sql
USE db_user_service;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email) VALUES
('Budi Santoso', 'budi@example.com'),
('Siti Aminah', 'siti@example.com');

SELECT * FROM users;
```

## Membuat Tabel Product Service

Gunakan database `db_product_service`.

```sql
USE db_product_service;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    price INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (name, price, stock) VALUES
('Laptop Lenovo ThinkPad', 8500000, 10),
('Mouse Wireless Logitech', 175000, 50),
('Keyboard Mechanical', 450000, 25);

SELECT * FROM products;
```

## Membuat Tabel Order Service

Gunakan database `db_order_service`.

```sql
USE db_order_service;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO orders (user_id, product_id, quantity, status) VALUES
(1, 1, 1, 'PAID'),
(2, 3, 2, 'PENDING');

SELECT * FROM orders;
```

> Catatan: Pada arsitektur microservices, tabel `orders` tidak menggunakan foreign key langsung ke tabel `users` atau `products`, karena tabel tersebut berada pada database service lain. Order Service hanya menyimpan `user_id` dan `product_id` sebagai referensi.

# User Service

## File `user-service/db.php`

```php
<?php

$host = "localhost";
$dbname = "db_user_service";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Koneksi database User Service gagal",
        "error" => $e->getMessage()
    ]);
    exit;
}
```

## File `user-service/index.php`

```php
<?php

header("Content-Type: application/json");

require_once "db.php";

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($method === "GET" && $path === "/users") {
    $stmt = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "service" => "User Service",
        "data" => $users
    ]);
    exit;
}

if ($method === "GET" && $path === "/users/detail") {
    $id = $_GET["id"] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            "message" => "Parameter id wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "message" => "User tidak ditemukan"
        ]);
        exit;
    }

    echo json_encode([
        "service" => "User Service",
        "data" => $user
    ]);
    exit;
}

if ($method === "POST" && $path === "/users") {
    $input = json_decode(file_get_contents("php://input"), true);

    $name = $input["name"] ?? null;
    $email = $input["email"] ?? null;

    if (!$name || !$email) {
        http_response_code(400);
        echo json_encode([
            "message" => "Name dan email wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
    $stmt->execute([$name, $email]);

    http_response_code(201);
    echo json_encode([
        "message" => "User berhasil dibuat",
        "data" => [
            "id" => $pdo->lastInsertId(),
            "name" => $name,
            "email" => $email
        ]
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    "message" => "Endpoint User Service tidak ditemukan"
]);
```

## Menjalankan User Service

Jalankan perintah berikut pada terminal.

```bash
cd user-service
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8001
```

## Uji User Service

```http
GET http://localhost:8001/users
GET http://localhost:8001/users/detail?id=1
POST http://localhost:8001/users
```

Body JSON untuk menambahkan user.

```json
{
  "name": "Andi Pratama",
  "email": "andi@example.com"
}
```

# Product Service

## File `product-service/db.php`

```php
<?php

$host = "localhost";
$dbname = "db_product_service";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Koneksi database Product Service gagal",
        "error" => $e->getMessage()
    ]);
    exit;
}
```

## File `product-service/index.php`

```php
<?php

header("Content-Type: application/json");

require_once "db.php";

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($method === "GET" && $path === "/products") {
    $stmt = $pdo->query("SELECT id, name, price, stock, created_at FROM products ORDER BY id ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "service" => "Product Service",
        "data" => $products
    ]);
    exit;
}

if ($method === "GET" && $path === "/products/detail") {
    $id = $_GET["id"] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            "message" => "Parameter id wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, price, stock, created_at FROM products WHERE id = ?");
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

    $stmt = $pdo->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
    $stmt->execute([$name, $price, $stock]);

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

    $stmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = ?");
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

    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$newStock, $productId]);

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

http_response_code(404);
echo json_encode([
    "message" => "Endpoint Product Service tidak ditemukan"
]);
```

## Menjalankan Product Service

```bash
cd product-service
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8002
```

## Uji Product Service

```http
GET http://localhost:8002/products
GET http://localhost:8002/products/detail?id=1
POST http://localhost:8002/products
POST http://localhost:8002/products/reduce-stock
```

Body JSON untuk menambahkan produk.

```json
{
  "name": "Monitor 24 Inch",
  "price": 1600000,
  "stock": 12
}
```

Body JSON untuk mengurangi stok.

```json
{
  "product_id": 1,
  "quantity": 2
}
```

# Order Service

Order Service memvalidasi user dan produk melalui API. Order Service tidak membaca langsung database `db_user_service` atau `db_product_service`.

## File `order-service/db.php`

```php
<?php

$host = "localhost";
$dbname = "db_order_service";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Koneksi database Order Service gagal",
        "error" => $e->getMessage()
    ]);
    exit;
}
```

## File `order-service/index.php`

```php
<?php

header("Content-Type: application/json");

require_once "db.php";

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
        return null;
    }

    return json_decode($response, true);
}

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($method === "GET" && $path === "/orders") {
    $stmt = $pdo->query("SELECT id, user_id, product_id, quantity, status, created_at FROM orders ORDER BY id ASC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "service" => "Order Service",
        "data" => $orders
    ]);
    exit;
}

if ($method === "GET" && $path === "/orders/detail") {
    $id = $_GET["id"] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            "message" => "Parameter id wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, user_id, product_id, quantity, status, created_at FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            "message" => "Order tidak ditemukan"
        ]);
        exit;
    }

    $userResponse = callService("http://localhost:8001/users/detail?id=" . $order["user_id"]);
    $productResponse = callService("http://localhost:8002/products/detail?id=" . $order["product_id"]);

    echo json_encode([
        "service" => "Order Service",
        "data" => [
            "order" => $order,
            "user" => $userResponse["data"] ?? null,
            "product" => $productResponse["data"] ?? null
        ]
    ]);
    exit;
}

if ($method === "POST" && $path === "/orders") {
    $input = json_decode(file_get_contents("php://input"), true);

    $userId = $input["user_id"] ?? null;
    $productId = $input["product_id"] ?? null;
    $quantity = $input["quantity"] ?? null;

    if (!$userId || !$productId || !$quantity) {
        http_response_code(400);
        echo json_encode([
            "message" => "user_id, product_id, dan quantity wajib diisi"
        ]);
        exit;
    }

    $userResponse = callService("http://localhost:8001/users/detail?id=" . $userId);

    if (!$userResponse || !isset($userResponse["data"])) {
        http_response_code(400);
        echo json_encode([
            "message" => "User tidak valid atau User Service tidak dapat diakses"
        ]);
        exit;
    }

    $productResponse = callService("http://localhost:8002/products/detail?id=" . $productId);

    if (!$productResponse || !isset($productResponse["data"])) {
        http_response_code(400);
        echo json_encode([
            "message" => "Produk tidak valid atau Product Service tidak dapat diakses"
        ]);
        exit;
    }

    $product = $productResponse["data"];

    if ($product["stock"] < $quantity) {
        http_response_code(400);
        echo json_encode([
            "message" => "Stok produk tidak mencukupi"
        ]);
        exit;
    }

    $reduceStockResponse = callService(
        "http://localhost:8002/products/reduce-stock",
        "POST",
        [
            "product_id" => $productId,
            "quantity" => $quantity
        ]
    );

    if (!$reduceStockResponse || !isset($reduceStockResponse["data"])) {
        http_response_code(500);
        echo json_encode([
            "message" => "Order gagal karena stok produk tidak dapat diperbarui"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, product_id, quantity, status)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$userId, $productId, $quantity, "PENDING"]);

    http_response_code(201);
    echo json_encode([
        "message" => "Order berhasil dibuat",
        "data" => [
            "id" => $pdo->lastInsertId(),
            "user" => $userResponse["data"],
            "product" => $product,
            "quantity" => $quantity,
            "status" => "PENDING",
            "stock_update" => $reduceStockResponse["data"]
        ]
    ]);
    exit;
}

if ($method === "POST" && $path === "/orders/update-status") {
    $input = json_decode(file_get_contents("php://input"), true);

    $orderId = $input["order_id"] ?? null;
    $status = $input["status"] ?? null;

    if (!$orderId || !$status) {
        http_response_code(400);
        echo json_encode([
            "message" => "order_id dan status wajib diisi"
        ]);
        exit;
    }

    $allowedStatus = ["PENDING", "PAID", "CANCELLED", "SHIPPED"];

    if (!in_array($status, $allowedStatus)) {
        http_response_code(400);
        echo json_encode([
            "message" => "Status tidak valid",
            "allowed_status" => $allowedStatus
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            "message" => "Order tidak ditemukan"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);

    echo json_encode([
        "message" => "Status order berhasil diperbarui",
        "data" => [
            "order_id" => $orderId,
            "status" => $status
        ]
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    "message" => "Endpoint Order Service tidak ditemukan"
]);
```

## Menjalankan Order Service

```bash
cd order-service
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8003
```

## Uji Order Service

```http
GET http://localhost:8003/orders
GET http://localhost:8003/orders/detail?id=1
POST http://localhost:8003/orders
POST http://localhost:8003/orders/update-status
```

Body JSON untuk membuat order.

```json
{
  "user_id": 1,
  "product_id": 1,
  "quantity": 2
}
```

Body JSON untuk mengubah status order.

```json
{
  "order_id": 1,
  "status": "PAID"
}
```

# API Gateway

API Gateway adalah pintu masuk utama bagi client. Client cukup mengakses `http://localhost:8000`, kemudian API Gateway meneruskan request ke service yang sesuai.

## File `api-gateway/index.php`

```php
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
```

## Menjalankan API Gateway

```bash
cd api-gateway
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000
```

# Menjalankan Semua Service

Buka empat terminal berbeda. Terminal dapat dibuka melalui tab terminal di VS Code.

## Terminal 1: API Gateway

```bash
cd api-gateway
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000
```

## Terminal 2: User Service

```bash
cd user-service
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8001
```

## Terminal 3: Product Service

```bash
cd product-service
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8002
```

## Terminal 4: Order Service

```bash
cd order-service
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8003
```

# Daftar Endpoint Melalui API Gateway

| No | Method | Endpoint | Fungsi |
|---:|---|---|---|
| 1 | GET | `http://localhost:8000/users` | Menampilkan semua user |
| 2 | GET | `http://localhost:8000/users/detail?id=1` | Menampilkan detail user |
| 3 | POST | `http://localhost:8000/users` | Menambahkan user |
| 4 | GET | `http://localhost:8000/products` | Menampilkan semua produk |
| 5 | GET | `http://localhost:8000/products/detail?id=1` | Menampilkan detail produk |
| 6 | POST | `http://localhost:8000/products` | Menambahkan produk |
| 7 | GET | `http://localhost:8000/orders` | Menampilkan semua order |
| 8 | GET | `http://localhost:8000/orders/detail?id=1` | Menampilkan detail order |
| 9 | POST | `http://localhost:8000/orders` | Membuat order |
| 10 | POST | `http://localhost:8000/orders/update-status` | Mengubah status order |
| 11 | GET | `http://localhost:8000/summary` | Menampilkan ringkasan data |
| 12 | GET | `http://localhost:8000/status` | Mengecek status service |

# Skenario Pengujian Praktikum

## 1. Menampilkan Semua User

```http
GET http://localhost:8000/users
```

Contoh hasil yang diharapkan.

```json
{
  "service": "User Service",
  "data": [
    {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@example.com",
      "created_at": "2026-05-06 10:00:00"
    }
  ]
}
```

## 2. Menambahkan User

```http
POST http://localhost:8000/users
```

Body JSON.

```json
{
  "name": "Rina Maharani",
  "email": "rina@example.com"
}
```

Contoh hasil yang diharapkan.

```json
{
  "message": "User berhasil dibuat",
  "data": {
    "id": 3,
    "name": "Rina Maharani",
    "email": "rina@example.com"
  }
}
```

## 3. Menampilkan Semua Produk

```http
GET http://localhost:8000/products
```

## 4. Menambahkan Produk

```http
POST http://localhost:8000/products
```

Body JSON.

```json
{
  "name": "Webcam 4K",
  "price": 950000,
  "stock": 8
}
```

## 5. Membuat Order

```http
POST http://localhost:8000/orders
```

Body JSON.

```json
{
  "user_id": 1,
  "product_id": 1,
  "quantity": 2
}
```

Contoh hasil yang diharapkan.

```json
{
  "message": "Order berhasil dibuat",
  "data": {
    "id": 3,
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@example.com"
    },
    "product": {
      "id": 1,
      "name": "Laptop Lenovo ThinkPad",
      "price": 8500000,
      "stock": 10
    },
    "quantity": 2,
    "status": "PENDING"
  }
}
```

Setelah order dibuat, stok produk harus berkurang.

## 6. Melihat Detail Order

```http
GET http://localhost:8000/orders/detail?id=1
```

Hasil yang diharapkan adalah data order, data user, dan data produk.

## 7. Mengubah Status Order

```http
POST http://localhost:8000/orders/update-status
```

Body JSON.

```json
{
  "order_id": 1,
  "status": "PAID"
}
```

## 8. Mengecek Status Service

```http
GET http://localhost:8000/status
```

Contoh hasil yang diharapkan.

```json
{
  "service": "API Gateway",
  "status": {
    "user_service": "running",
    "product_service": "running",
    "order_service": "running"
  }
}
```

# Catatan Penting

1. Pastikan MySQL pada XAMPP sudah berjalan sebelum menjalankan service.
2. Pastikan setiap service berjalan pada port yang berbeda.
3. Jalankan service melalui path folder masing-masing.
4. API Gateway harus dijalankan jika pengujian dilakukan melalui `localhost:8000`.
5. Jika terminal menampilkan pesan `command not found: php`, gunakan path PHP dari XAMPP berikut.

```bash
/Applications/XAMPP/xamppfiles/bin/php
```

# Kesimpulan

Praktikum ini menunjukkan bahwa microservices memisahkan tanggung jawab sistem ke dalam beberapa service kecil. User Service bertanggung jawab terhadap data user. Product Service bertanggung jawab terhadap data produk. Order Service bertanggung jawab terhadap data pesanan. API Gateway bertindak sebagai pintu masuk utama yang meneruskan request client ke service yang sesuai.
#   P H P - M i c r o s e r v i c e s - M y S Q L  
 