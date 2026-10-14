<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_name = 'marana_crochet';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border-radius:12px;margin:20px;'>
        <h3>Error de conexión a MySQL (XAMPP)</h3>
        <p>Asegúrate de que el módulo MySQL esté encendido en XAMPP Control Panel.</p>
        <small>Detalle: " . htmlspecialchars($e->getMessage()) . "</small>
    </div>");
}

// Configurar usuario por defecto en sesión si no existe
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 'u-001',
        'name' => 'Maraña Admin',
        'username' => 'marana_admin',
        'email' => 'admin@marana.co',
        'role' => 'admin',
        'roleTitle' => 'Artesana & Fundadora',
        'avatarUrl' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
    ];
}

// Helper para formatear precio COP
function formatCOP($val) {
    return '$' . number_format((int)$val, 0, ',', '.') . ' COP';
}
