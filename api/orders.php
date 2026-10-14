<?php
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function getOrderItems($pdo, $orderId) {
    $stmt = $pdo->prepare("SELECT nombre AS name, especificaciones AS specs, cantidad AS quantity, precio AS price, imagen_url AS imageUrl, tag, nota_artesana AS artisanNote FROM pedido_items WHERE pedido_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    return array_map(function($it) {
        $it['quantity'] = (int) $it['quantity'];
        $it['price'] = (int) $it['price'];
        return $it;
    }, $items);
}

function formatOrderRow($pdo, $row) {
    return [
        'id' => $row['id'],
        'date' => $row['fecha'],
        'customerName' => $row['cliente_nombre'],
        'customerInitials' => $row['cliente_iniciales'] ?? substr($row['cliente_nombre'], 0, 2),
        'customerPhone' => $row['cliente_telefono'] ?? '',
        'customerLocation' => $row['cliente_ubicacion'] ?? '',
        'customerAddress' => $row['cliente_direccion'] ?? '',
        'status' => $row['estado'],
        'statusLabel' => $row['estado_label'] ?? '',
        'priorityPercent' => isset($row['prioridad_pct']) ? (int) $row['prioridad_pct'] : null,
        'readyTime' => $row['hora_lista'] ?? null,
        'carrier' => $row['transportista'] ?? '',
        'shippingCost' => (int) ($row['costo_envio'] ?? 0),
        'trackingNumber' => $row['guia'] ?? null,
        'trackingStatus' => $row['estado_guia'] ?? null,
        'specialNote' => $row['nota_especial'] ?? null,
        'artisanName' => $row['artesana'] ?? null,
        'paymentVerified' => (bool) $row['pago_verificado'],
        'items' => getOrderItems($pdo, $row['id']),
    ];
}

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            sendJson(['success' => false, 'error' => 'Pedido no encontrado'], 404);
        }
        sendJson(['success' => true, 'data' => formatOrderRow($pdo, $row)]);
    }

    $stmt = $pdo->query("SELECT * FROM pedidos ORDER BY created_at DESC");
    $orders = [];
    while ($row = $stmt->fetch()) {
        $orders[] = formatOrderRow($pdo, $row);
    }
    sendJson(['success' => true, 'data' => $orders]);
}

if ($method === 'POST') {
    $body = getJsonInput();
    if (empty($body['id']) || empty($body['customerName'])) {
        sendJson(['success' => false, 'error' => 'id y customerName son requeridos'], 400);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO pedidos 
            (id, fecha, cliente_nombre, cliente_iniciales, cliente_telefono, cliente_ubicacion, cliente_direccion, estado, estado_label, prioridad_pct, hora_lista, transportista, costo_envio, guia, estado_guia, nota_especial, artesana, pago_verificado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $body['id'],
            $body['date'] ?? ('Hoy · ' . date('h:i A')),
            $body['customerName'],
            $body['customerInitials'] ?? substr($body['customerName'], 0, 2),
            $body['customerPhone'] ?? '',
            $body['customerLocation'] ?? '',
            $body['customerAddress'] ?? '',
            $body['status'] ?? 'preparacion',
            $body['statusLabel'] ?? 'En Taller · Esperando Validación',
            $body['priorityPercent'] ?? null,
            $body['readyTime'] ?? '2-3 días hábiles',
            $body['carrier'] ?? 'Servientrega Nacional',
            $body['shippingCost'] ?? 0,
            $body['trackingNumber'] ?? null,
            $body['trackingStatus'] ?? null,
            $body['specialNote'] ?? null,
            $body['artisanName'] ?? null,
            !empty($body['paymentVerified']) ? 1 : 0
        ]);

        if (!empty($body['items']) && is_array($body['items'])) {
            $itemStmt = $pdo->prepare("INSERT INTO pedido_items (pedido_id, nombre, especificaciones, cantidad, precio, imagen_url, tag, nota_artesana) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($body['items'] as $it) {
                $itemStmt->execute([
                    $body['id'],
                    $it['name'] ?? '',
                    $it['specs'] ?? '',
                    $it['quantity'] ?? 1,
                    $it['price'] ?? 0,
                    $it['imageUrl'] ?? '',
                    $it['tag'] ?? null,
                    $it['artisanNote'] ?? null
                ]);
            }
        }

        $pdo->commit();
        sendJson(['success' => true, 'message' => 'Pedido creado correctamente en MySQL', 'id' => $body['id']], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($method === 'PUT' || $method === 'PATCH') {
    $body = getJsonInput();
    $id = $body['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        sendJson(['success' => false, 'error' => 'ID de pedido requerido'], 400);
    }

    $stmt = $pdo->prepare("UPDATE pedidos SET 
        estado = COALESCE(?, estado),
        estado_label = COALESCE(?, estado_label),
        transportista = COALESCE(?, transportista),
        guia = COALESCE(?, guia),
        estado_guia = COALESCE(?, estado_guia),
        pago_verificado = COALESCE(?, pago_verificado),
        artesana = COALESCE(?, artesana)
        WHERE id = ?");

    $stmt->execute([
        $body['status'] ?? null,
        $body['statusLabel'] ?? null,
        $body['carrier'] ?? null,
        $body['trackingNumber'] ?? null,
        $body['trackingStatus'] ?? null,
        isset($body['paymentVerified']) ? ($body['paymentVerified'] ? 1 : 0) : null,
        $body['artisanName'] ?? null,
        $id
    ]);

    sendJson(['success' => true, 'message' => 'Pedido actualizado en MySQL']);
}

sendJson(['success' => false, 'error' => 'Método no permitido'], 405);
