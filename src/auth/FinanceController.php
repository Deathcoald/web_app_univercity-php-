<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function createFinance($pdo) {
    header("Content-Type: application/json");

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(["error" => "Нет авторизации"]);
        exit;
    }

    $token = $matches[1];

    $secret = getenv('JWT_SECRET') ?: 'your_secret_key';
    try {
        $decoded = (array) JWT::decode($token, new Key($secret, 'HS256'));
    } catch (\Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Неверный токен"]);
        exit;
    }

    $userId = $decoded['id'] ?? null;
    if (!$userId) {
        http_response_code(401);
        echo json_encode(["error" => "Нет user_id в токене"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM account WHERE user_id = ?");
    $stmt->execute([$userId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        http_response_code(404);
        echo json_encode(["error" => "Аккаунт не найден"]);
        exit;
    }

    $accountId = $account['id'];

    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON"]);
        exit;
    }

    $type = $data['type'] ?? null;
    $amount = $data['amount'] ?? null;
    $category = $data['category'] ?? null;
    $date = $data['date'] ?? null;
    $comment = $data['comment'] ?? null;

    $stmt = $pdo->prepare(
        "INSERT INTO $type (account_id, type, received_at, amount, comment) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$accountId, $category, $date, $amount, $comment]);

    echo json_encode([
        "status" => "ok",
        "received" => $data
    ]);
}

function fetchFinance($pdo) {
    header("Content-Type: application/json");

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
        http_response_code(401);
        echo json_encode(["error" => "Нет авторизации"]);
        exit;
    }

    try {
        $decoded = (array) JWT::decode(
            $m[1],
            new Key(getenv('JWT_SECRET') ?: 'your_secret_key', 'HS256')
        );
    } catch (Exception) {
        http_response_code(401);
        echo json_encode(["error" => "Неверный токен"]);
        exit;
    }

    $userId = $decoded['id'] ?? null;
    if (!$userId) {
        http_response_code(401);
        echo json_encode(["error" => "Нет user_id"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM account WHERE user_id = ?");
    $stmt->execute([$userId]);
    $accountId = $stmt->fetchColumn();

    if (!$accountId) {
        http_response_code(404);
        echo json_encode(["error" => "Аккаунт не найден"]);
        exit;
    }

    $type = $_GET['type'] ?? 'incomes';
    if (!in_array($type, ['incomes', 'outcomes'], true)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid type"]);
        exit;
    }

    $period = $_GET['period'] ?? 'day';

    $now = new DateTime();
    $startDate = match ($period) {
        'day' => (clone $now)->modify('-1 day'),
        'week' => (clone $now)->modify('-7 days'),
        'month' => (clone $now)->modify('-1 month'),
        'year' => (clone $now)->modify('-1 year'),
        default => (clone $now)->modify('-1 day'),
    };

    $stmt = $pdo->prepare("
        SELECT type AS category_name, SUM(amount) AS amount
        FROM {$type}
        WHERE account_id = ?
          AND received_at >= ?
        GROUP BY type
    ");
    $stmt->execute([
        $accountId,
        $startDate->format('Y-m-d H:i:s')
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $colors = [
        'salary' => '#3b82f6',
        'present' => '#ef4444',
        'bank' => '#22c55e',
        'investments' => '#f97316',
        'etc' => '#6b7280',
    ];

    echo json_encode(array_map(fn($r) => [
        'category_name' => $r['category_name'],
        'amount' => (float)$r['amount'],
        'color' => $colors[$r['category_name']] ?? '#999',
    ], $rows));
}
