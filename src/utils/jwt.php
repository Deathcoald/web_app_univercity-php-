<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../../vendor/autoload.php';

function generateJWT(array $payload): string {
    $secret = getenv('JWT_SECRET') ?: 'your_secret_key'; 
    $issuedAt = time();
    $expire = $issuedAt + 3600;

    $tokenPayload = array_merge($payload, [
        "iat" => $issuedAt,
        "exp" => $expire
    ]);

    return JWT::encode($tokenPayload, $secret, 'HS256');
}

function verifyJWT(string $token) {
    $secret = getenv('JWT_SECRET');

    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        return (array) $decoded;
    } catch (\Exception $e) {
        return null; 
    }
}

