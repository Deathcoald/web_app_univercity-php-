<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/jwt.php'; 

function loginUser($pdo) {
    header("Content-Type: application/json");

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["username"], $data["password"])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing fields"]);
        return;
    }

    $username = trim($data["username"]);
    $password = $data["password"];

    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :u");
        $stmt->execute([":u" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401); 
            echo json_encode(["error" => "Invalid username or password"]);
            return;
        }
        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid username or password"]);
            return;
        }
        $token = generateJWT(['id' => $user['id'], 'username' => $username]);

        echo json_encode([
            "message" => "Login successful",
            "token" => $token
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
