<?php
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function formatProductRow($row) {
    return [
        'sku' => $row['sku'],
        'name' => $row['nombre'],
        'tag' => $row['tag'] ?? '',
        'size' => $row['talla'] ?? '',
        'supplier' => $row['proveedor'] ?? '',
        'priceCOP' => (int) $row['precio_cop'],
        'yarnCostCOP' => (int) $row['costo_lana_cop'],
        'currentStock' => (int) $row['stock_actual'],
        'minStockAlert' => (int) $row['stock_minimo'],
        'category' => $row['categoria'],
        'statusLabel' => $row['estado_label'] ?? '',
        'imageUrl' => $row['imagen_url'] ?? '',
        'waitingListCount' => isset($row['lista_espera']) ? (int) $row['lista_espera'] : 0,
        'leadTimeDays' => isset($row['tiempo_entrega_dias']) ? (int) $row['tiempo_entrega_dias'] : null,
        'monthlyQuota' => isset($row['cupo_mensual']) ? (int) $row['cupo_mensual'] : null,
    ];
}

if ($method === 'GET') {
    $sku = $_GET['sku'] ?? null;
    if ($sku) {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE sku = ? AND activo = 1");
        $stmt->execute([$sku]);
        $prod = $stmt->fetch();
        if (!$prod) {
            sendJson(['success' => false, 'error' => 'Producto no encontrado'], 404);
        }
        sendJson(['success' => true, 'data' => formatProductRow($prod)]);
    }

    $stmt = $pdo->query("SELECT * FROM productos WHERE activo = 1 ORDER BY sku ASC");
    $rows = $stmt->fetchAll();
    $data = array_map('formatProductRow', $rows);
    sendJson(['success' => true, 'data' => $data]);
}

if ($method === 'POST') {
    $body = getJsonInput();
    if (empty($body['sku']) || empty($body['name'])) {
        sendJson(['success' => false, 'error' => 'SKU y Nombre son requeridos'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO productos 
        (sku, nombre, tag, talla, proveedor, precio_cop, costo_lana_cop, stock_actual, stock_minimo, categoria, estado_label, imagen_url, lista_espera, tiempo_entrega_dias, cupo_mensual)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            nombre=VALUES(nombre), tag=VALUES(tag), talla=VALUES(talla), proveedor=VALUES(proveedor),
            precio_cop=VALUES(precio_cop), costo_lana_cop=VALUES(costo_lana_cop), stock_actual=VALUES(stock_actual),
            stock_minimo=VALUES(stock_minimo), categoria=VALUES(categoria), estado_label=VALUES(estado_label),
            imagen_url=VALUES(imagen_url), lista_espera=VALUES(lista_espera)");

    $stmt->execute([
        $body['sku'],
        $body['name'],
        $body['tag'] ?? '',
        $body['size'] ?? '',
        $body['supplier'] ?? '',
        $body['priceCOP'] ?? 0,
        $body['yarnCostCOP'] ?? 0,
        $body['currentStock'] ?? 0,
        $body['minStockAlert'] ?? 2,
        $body['category'] ?? 'optimo',
        $body['statusLabel'] ?? '',
        $body['imageUrl'] ?? '',
        $body['waitingListCount'] ?? 0,
        $body['leadTimeDays'] ?? null,
        $body['monthlyQuota'] ?? null,
    ]);

    sendJson(['success' => true, 'message' => 'Producto guardado con éxito']);
}

if ($method === 'PUT' || $method === 'PATCH') {
    $body = getJsonInput();
    $sku = $body['sku'] ?? ($_GET['sku'] ?? null);

    if (!$sku) {
        sendJson(['success' => false, 'error' => 'SKU es requerido'], 400);
    }

    // Permite actualizar solo el stock o todo el producto
    if (isset($body['currentStock'])) {
        $stock = (int) $body['currentStock'];
        $cat = $body['category'] ?? null;
        $label = $body['statusLabel'] ?? null;

        if (!$cat) {
            $cat = ($stock === 0) ? 'agotado' : (($stock <= 2) ? 'critico' : 'optimo');
        }
        if (!$label) {
            $label = ($stock === 0) ? 'Agotado' : (($stock <= 2) ? "Stock Mínimo: {$stock}" : "Stock Óptimo ({$stock} unidades)");
        }

        $stmt = $pdo->prepare("UPDATE productos SET stock_actual = ?, categoria = ?, estado_label = ? WHERE sku = ?");
        $stmt->execute([$stock, $cat, $label, $sku]);
        sendJson(['success' => true, 'message' => 'Stock actualizado en base de datos MySQL']);
    }

    // Actualización completa
    $stmt = $pdo->prepare("UPDATE productos SET 
        nombre = COALESCE(?, nombre),
        tag = COALESCE(?, tag),
        talla = COALESCE(?, talla),
        proveedor = COALESCE(?, proveedor),
        precio_cop = COALESCE(?, precio_cop),
        costo_lana_cop = COALESCE(?, costo_lana_cop),
        stock_actual = COALESCE(?, stock_actual),
        stock_minimo = COALESCE(?, stock_minimo),
        categoria = COALESCE(?, categoria),
        estado_label = COALESCE(?, estado_label),
        imagen_url = COALESCE(?, imagen_url)
        WHERE sku = ?");

    $stmt->execute([
        $body['name'] ?? null,
        $body['tag'] ?? null,
        $body['size'] ?? null,
        $body['supplier'] ?? null,
        $body['priceCOP'] ?? null,
        $body['yarnCostCOP'] ?? null,
        $body['currentStock'] ?? null,
        $body['minStockAlert'] ?? null,
        $body['category'] ?? null,
        $body['statusLabel'] ?? null,
        $body['imageUrl'] ?? null,
        $sku
    ]);

    sendJson(['success' => true, 'message' => 'Producto actualizado en MySQL']);
}

if ($method === 'DELETE') {
    $sku = $_GET['sku'] ?? null;
    if (!$sku) {
        sendJson(['success' => false, 'error' => 'SKU es requerido'], 400);
    }
    $stmt = $pdo->prepare("UPDATE productos SET activo = 0 WHERE sku = ?");
    $stmt->execute([$sku]);
    sendJson(['success' => true, 'message' => 'Producto archivado']);
}

sendJson(['success' => false, 'error' => 'Método no permitido'], 405);
