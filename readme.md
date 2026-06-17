# PHP Microservices MySQL

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://www.mysql.com/)

Contoh praktikum sederhana untuk memahami arsitektur **microservices** menggunakan **PHP Native**, **MySQL**, dan **API Gateway**. Sistem terdiri dari tiga service utama yang terpisah dengan database tersendiri untuk mendemonstrasikan prinsip pemisahan tanggung jawab pada arsitektur microservices.

## 📋 Daftar Isi

- [Fitur](#fitur)
- [Teknologi](#teknologi-yang-digunakan)
- [Arsitektur](#arsitektur-sistem)
- [Struktur Folder](#struktur-folder)
- [Quick Start](#quick-start)
- [Endpoint API](#daftar-endpoint-melalui-api-gateway)
- [Testing](#skenario-pengujian-praktikum)

## ✨ Fitur

- **User Service** - Manajemen data pengguna
- **Product Service** - Manajemen data produk dan stok
- **Order Service** - Manajemen pesanan dengan validasi antar service
- **API Gateway** - Router terpusat untuk semua request
- Service-to-Service Communication via HTTP

## 🎯 Tujuan Pembelajaran

- Memahami konsep dasar arsitektur microservices
- Membuat service REST API sederhana dengan PHP Native
- Menghubungkan setiap service dengan database MySQL yang terpisah
- Membuat API Gateway sebagai router terpusat
- Menguji komunikasi antar service menggunakan Postman/Thunder Client

## 🛠️ Teknologi yang Digunakan

| Teknologi | Fungsi | Versi |
|---|---|---|
| PHP Native | REST API Development | 7.4+ |
| MySQL | Database Management | 5.7+ |
| PDO | Database Connection | Built-in |
| XAMPP | Local Environment | Latest |
| HTTP Stream | Service Communication | Native |

## 🏗️ Arsitektur Sistem

```text
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────┐
│   API Gateway               │
│   localhost:8000            │
└──┬──────────────┬───────────┘
   │              │
   ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ User Service │ │Product Service│ │ Order Service│
│ :8001        │ │ :8002        │ │ :8003        │
│ db_user_*    │ │ db_product_* │ │ db_order_*   │
└──────────────┘ └──────────────┘ └──────────────┘
```

Setiap service berjalan independen dengan database tersendiri. API Gateway bertindak sebagai router terpusat yang meneruskan request ke service yang sesuai.

## 📁 Struktur Folder

```text
php-microservices-mysql/
│
├── api-gateway/
│   └── index.php                 # Router API Gateway
│
├── user-service/
│   ├── db.php                    # Database connection
│   └── index.php                 # User endpoints
│
├── product-service/
│   ├── db.php                    # Database connection
│   └── index.php                 # Product endpoints
│
├── order-service/
│   ├── db.php                    # Database connection
│   └── index.php                 # Order endpoints
│
├── readme.md                     # Dokumentasi
└── microservices-postman.json    # Postman collection
```

| Folder | Fungsi |
|---|---|
| `api-gateway` | Router terpusat untuk semua request |
| `user-service` | Manajemen data pengguna |
| `product-service` | Manajemen data produk & stok |
| `order-service` | Manajemen pesanan & validasi |

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Git

### Langkah Setup

1. **Setup Database** - Jalankan script SQL di phpMyAdmin:
```sql
CREATE DATABASE db_user_service;
CREATE DATABASE db_product_service;
CREATE DATABASE db_order_service;
```

2. **Buat Tabel** - Lihat section [Database Setup](#persiapan-database) untuk script lengkap

3. **Jalankan Service** - Buka 4 terminal:
```bash
# Terminal 1
cd api-gateway && php -S localhost:8000

# Terminal 2
cd user-service && php -S localhost:8001

# Terminal 3
cd product-service && php -S localhost:8002

# Terminal 4
cd order-service && php -S localhost:8003
```

4. **Test API** - Buka browser atau Postman:
```
http://localhost:8000/status
```

---

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

# 🔧 Menjalankan Semua Service

Buka **4 terminal** berbeda dan jalankan perintah berikut:

| Terminal | Service | Command |
|---|---|---|
| 1 | API Gateway | `cd api-gateway && php -S localhost:8000` |
| 2 | User Service | `cd user-service && php -S localhost:8001` |
| 3 | Product Service | `cd product-service && php -S localhost:8002` |
| 4 | Order Service | `cd order-service && php -S localhost:8003` |

**Catatan:** Jika `php` tidak ditemukan, gunakan path lengkap dari XAMPP:
```bash
# Windows
C:\xampp\php\php.exe -S localhost:8000

# macOS
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000
```

# 📡 Daftar Endpoint Melalui API Gateway

Base URL: `http://localhost:8000`

## User Endpoints

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/users` | Tampilkan semua user |
| `GET` | `/users/detail?id=1` | Tampilkan detail user |
| `POST` | `/users` | Tambah user baru |

## Product Endpoints

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/products` | Tampilkan semua produk |
| `GET` | `/products/detail?id=1` | Tampilkan detail produk |
| `POST` | `/products` | Tambah produk baru |

## Order Endpoints

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/orders` | Tampilkan semua order |
| `GET` | `/orders/detail?id=1` | Tampilkan detail order |
| `POST` | `/orders` | Buat order baru |
| `POST` | `/orders/update-status` | Ubah status order |

## Gateway Endpoints

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/status` | Cek status semua service |
| `GET` | `/summary` | Tampilkan ringkasan data |

# ✅ Skenario Pengujian Praktikum

### 1. Cek Status Service

```bash
curl http://localhost:8000/status
```

**Response yang diharapkan:**
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

### 2. Tampilkan Semua User

```bash
curl http://localhost:8000/users
```

### 3. Tambah User Baru

```bash
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Rina Maharani","email":"rina@example.com"}'
```

### 4. Tampilkan Semua Produk

```bash
curl http://localhost:8000/products
```

### 5. Tambah Produk Baru

```bash
curl -X POST http://localhost:8000/products \
  -H "Content-Type: application/json" \
  -d '{"name":"Webcam 4K","price":950000,"stock":8}'
```

### 6. Buat Order

```bash
curl -X POST http://localhost:8000/orders \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"product_id":1,"quantity":2}'
```

### 7. Lihat Detail Order

```bash
curl http://localhost:8000/orders/detail?id=1
```

### 8. Ubah Status Order

```bash
curl -X POST http://localhost:8000/orders/update-status \
  -H "Content-Type: application/json" \
  -d '{"order_id":1,"status":"PAID"}'
```

### 9. Lihat Ringkasan Data

```bash
curl http://localhost:8000/summary
```

# ⚠️ Catatan Penting

- Pastikan **MySQL** sudah running sebelum menjalankan service
- Setiap service berjalan pada **port yang berbeda** (8000-8003)
- Jalankan setiap service dari folder masing-masing
- API Gateway harus berjalan untuk mengakses semua endpoint
- Gunakan **Postman** atau **Thunder Client** untuk testing yang lebih mudah

# 📚 Pengetahuan Microservices

Praktikum ini mendemonstrasikan prinsip-prinsip kunci microservices:

✅ **Separation of Concerns** - Setiap service mengelola domain tertentu  
✅ **Independent Databases** - Setiap service memiliki database sendiri  
✅ **Service-to-Service Communication** - Komunikasi via HTTP  
✅ **API Gateway Pattern** - Router terpusat untuk client requests  
✅ **Loose Coupling** - Service independen dan dapat dikembangkan terpisah  

# 📖 Kesimpulan

Microservices memisahkan sistem besar menjadi service-service kecil yang fokus pada satu tanggung jawab:

- **User Service** → Manajemen user
- **Product Service** → Manajemen produk  
- **Order Service** → Manajemen pesanan
- **API Gateway** → Router dan aggregator

Dengan desain ini, setiap service dapat:
- Dikembangkan dan deploy secara independen
- Menggunakan teknologi berbeda
- Di-scale secara terpisah sesuai kebutuhan
- Di-test dengan lebih mudah

---

**Dibuat untuk keperluan edukasi Semester 6 - Web Service**
#   P H P - M i c r o s e r v i c e s - M y S Q L 
 
 