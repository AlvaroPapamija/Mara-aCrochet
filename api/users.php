<?php
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function formatUserRow($row) {
    return [
        'id' => $row['id'],
        'name' => $row['nombre'],
        'username' => $row['username'],
        'email' => $row['email'],
        'role' => $row['rol'],
        'roleTitle' => $row['rol_titulo'],
        'avatarUrl' => $row['avatar_url'] ?? '',
        'phone' => $row['telefono'] ?? '',
        'city' => $row['ciudad'] ?? '',
        'address' => $row['direccion'] ?? '',
    ];
}

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE activo = 1");
    $users = array_map('formatUserRow', $stmt->fetchAll());
    sendJson(['success' => true, 'data' => $users]);
}

if ($method === 'POST') {
    $body = getJsonInput();
    $action = $_GET['action'] ?? ($body['action'] ?? 'login');

    if ($action === 'login') {
        $usernameOrEmail = $body['username'] ?? ($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (empty($usernameOrEmail)) {
            sendJson(['success' => false, 'error' => 'Usuario o email requerido'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE (username = ? OR email = ?) AND activo = 1 LIMIT 1");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJson(['success' => false, 'error' => 'Usuario no encontrado'], 404);
        }

        // Si se provee contraseña, verificar hash o permitir master/demo password 'marana2024'
        if (!empty($password)) {
            $isMatch = password_verify($password, $user['password_hash']) || ($password === 'marana2024');
            if (!$isMatch) {
                sendJson(['success' => false, 'error' => 'Contraseña incorrecta'], 401);
            }
        }

        sendJson([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user' => formatUserRow($user)
        ]);
    }

    sendJson(['success' => false, 'error' => 'Acción no soportada'], 400);
}

sendJson(['success' => false, 'error' => 'Método no permitido'], 405);
