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
| /user-service/users
| becomes:
| /users
|--------------------------------------------------------------------------
*/

$path = str_replace("/user-service", "", $path);

/*
|--------------------------------------------------------------------------
| GET ALL USERS
|--------------------------------------------------------------------------
*/

if ($method === "GET" && $path === "/users") {

    $stmt = $pdo->query("
        SELECT
            id,
            name,
            email,
            created_at
        FROM users
        ORDER BY id ASC
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "service" => "User Service",
        "data" => $users
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| GET USER DETAIL
|--------------------------------------------------------------------------
*/

if ($method === "GET" && $path === "/users/detail") {

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
            email,
            created_at
        FROM users
        WHERE id = ?
    ");

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

/*
|--------------------------------------------------------------------------
| CREATE USER
|--------------------------------------------------------------------------
*/

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

    try {

        $stmt = $pdo->prepare(" 
            INSERT INTO users (name, email)
            VALUES (?, ?)
        ");

        $stmt->execute([
            $name,
            $email
        ]);

    } catch (PDOException $e) {

        if ((int) $e->errorInfo[1] === 1062) {

            http_response_code(409);

            echo json_encode([
                "message" => "Email sudah terdaftar"
            ]);

            exit;
        }

        http_response_code(500);

        echo json_encode([
            "message" => "Gagal membuat user",
            "error" => $e->getMessage()
        ]);

        exit;
    }

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

/*
|--------------------------------------------------------------------------
| ENDPOINT NOT FOUND
|--------------------------------------------------------------------------
*/

http_response_code(404);

echo json_encode([
    "message" => "Endpoint User Service tidak ditemukan",
    "path" => $path,
    "method" => $method
]);