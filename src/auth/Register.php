<?php
require_once __DIR__ . '/../config/db.php';

function registerUser($pdo) {
    header("Content-Type: application/json");

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["username"], $data["password"])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing fields"]);
        return;
    }

    $username = trim($data["username"]);
    $password = password_hash($data["password"], PASSWORD_BCRYPT);

    try {
        // транзакция
        $pdo->beginTransaction();

        // 1. создаём пользователя
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, password) VALUES (:u, :p)"
        );
        $stmt->execute([
            ":u" => $username,
            ":p" => $password
        ]);

        $userId = $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "INSERT INTO account (name, currency, user_id, balance) VALUES (:name, :cur, :uid, :balance)"
        );
        $stmt->execute([
            ":uid" => $userId,
            ":name" => $username,
            ":cur" => "USD" ,
            ":balance" => 0
        ]);

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            "status" => "ok",
            "user_id" => $userId
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();

        if (str_contains($e->getMessage(), "unique")) {
            http_response_code(409);
            echo json_encode(["error" => "User already exists"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}
