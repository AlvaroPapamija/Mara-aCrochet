<?php
require_once __DIR__ . '/db.php';

try {
    $counts = [];
    foreach (['productos', 'pedidos', 'comprobantes', 'usuarios'] as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM `{$table}`");
        $counts[$table] = (int) $stmt->fetchColumn();
    }

    sendJson([
        'success' => true,
        'status' => 'connected',
        'database' => $db_name,
        'server' => 'MySQL (XAMPP)',
        'timestamp' => date('Y-m-d H:i:s'),
        'counts' => $counts
    ]);
} catch (Exception $e) {
    sendJson([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
