<?php
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function getVoucherItemsSummary($pdo, $voucherId) {
    $stmt = $pdo->prepare("SELECT nombre AS name, tag FROM comprobante_items_resumen WHERE comprobante_id = ?");
    $stmt->execute([$voucherId]);
    return $stmt->fetchAll();
}

function getVoucherItemsDetail($pdo, $voucherId) {
    $stmt = $pdo->prepare("SELECT nombre AS name, especificaciones AS specs, cantidad AS quantity, precio AS price, imagen_url AS imageUrl FROM comprobante_items_detalle WHERE comprobante_id = ?");
    $stmt->execute([$voucherId]);
    $items = $stmt->fetchAll();
    return array_map(function($it) {
        $it['quantity'] = (int) $it['quantity'];
        $it['price'] = (int) $it['price'];
        return $it;
    }, $items);
}

function formatVoucherRow($pdo, $row) {
    $summary = getVoucherItemsSummary($pdo, $row['id']);
    $detail = getVoucherItemsDetail($pdo, $row['id']);

    // Si no tiene registros en tablas de desglose pero tiene pedido relacionado, obtener de pedido_items
    if (empty($detail) && !empty($row['pedido_id'])) {
        $stmtOrder = $pdo->prepare("SELECT nombre AS name, especificaciones AS specs, cantidad AS quantity, precio AS price, imagen_url AS imageUrl FROM pedido_items WHERE pedido_id = ?");
        $stmtOrder->execute([$row['pedido_id']]);
        $detail = array_map(function($it) {
            $it['quantity'] = (int) $it['quantity'];
            $it['price'] = (int) $it['price'];
            return $it;
        }, $stmtOrder->fetchAll());

        if (empty($summary)) {
            $summary = array_map(function($d) {
                return [
                    'name' => "{$d['quantity']}x {$d['name']}",
                    'tag' => $d['specs'] ?? 'Hecho a mano'
                ];
            }, $detail);
        }
    }

    return [
        'id' => $row['id'],
        'orderId' => $row['pedido_id'],
        'timeAgo' => $row['tiempo_hace'] ?? 'Hace poco',
        'customerName' => $row['cliente_nombre'],
        'customerPhone' => $row['cliente_telefono'] ?? '',
        'customerAddress' => $row['cliente_direccion'] ?? '',
        'shippingMethod' => $row['metodo_envio'] ?? 'Servientrega',
        'totalAmount' => (int) $row['monto_total'],
        'expectedAmount' => (int) $row['monto_esperado'],
        'paymentMethod' => $row['metodo_pago'] ?? '',
        'bankAccount' => $row['cuenta_banco'] ?? '',
        'approvalCode' => $row['codigo_aprobacion'] ?? '',
        'bankTimestamp' => $row['timestamp_banco'] ?? '',
        'status' => $row['estado'],
        'receiptThumbUrl' => $row['recibo_thumb_url'] ?? '',
        'receiptFullUrl' => $row['recibo_full_url'] ?? '',
        'internalNote' => $row['nota_interna'] ?? null,
        'itemsSummary' => $summary,
        'itemsDetail' => $detail,
    ];
}

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM comprobantes WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            sendJson(['success' => false, 'error' => 'Comprobante no encontrado'], 404);
        }
        sendJson(['success' => true, 'data' => formatVoucherRow($pdo, $row)]);
    }

    $stmt = $pdo->query("SELECT * FROM comprobantes ORDER BY created_at DESC");
    $list = [];
    while ($row = $stmt->fetch()) {
        $list[] = formatVoucherRow($pdo, $row);
    }
    sendJson(['success' => true, 'data' => $list]);
}

if ($method === 'POST') {
    $body = getJsonInput();
    if (empty($body['id']) || empty($body['orderId'])) {
        sendJson(['success' => false, 'error' => 'id y orderId requeridos'], 400);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO comprobantes
            (id, pedido_id, tiempo_hace, cliente_nombre, cliente_telefono, cliente_direccion, metodo_envio, monto_total, monto_esperado, metodo_pago, cuenta_banco, codigo_aprobacion, timestamp_banco, estado, recibo_thumb_url, recibo_full_url, nota_interna)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $body['id'],
            $body['orderId'],
            $body['timeAgo'] ?? 'Hace unos instantes',
            $body['customerName'] ?? '',
            $body['customerPhone'] ?? '',
            $body['customerAddress'] ?? '',
            $body['shippingMethod'] ?? '',
            $body['totalAmount'] ?? 0,
            $body['expectedAmount'] ?? ($body['totalAmount'] ?? 0),
            $body['paymentMethod'] ?? 'Bancolombia Transfer',
            $body['bankAccount'] ?? 'Ahorros Bancolombia 912-384729-10',
            $body['approvalCode'] ?? '',
            $body['bankTimestamp'] ?? ('Hoy · ' . date('h:i A')),
            $body['status'] ?? 'pendiente',
            $body['receiptThumbUrl'] ?? '',
            $body['receiptFullUrl'] ?? '',
            $body['internalNote'] ?? null,
        ]);

        if (!empty($body['itemsDetail']) && is_array($body['itemsDetail'])) {
            $stmtDet = $pdo->prepare("INSERT INTO comprobante_items_detalle (comprobante_id, nombre, especificaciones, cantidad, precio, imagen_url) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($body['itemsDetail'] as $det) {
                $stmtDet->execute([
                    $body['id'],
                    $det['name'] ?? '',
                    $det['specs'] ?? '',
                    $det['quantity'] ?? 1,
                    $det['price'] ?? 0,
                    $det['imageUrl'] ?? ''
                ]);
            }
        }

        if (!empty($body['itemsSummary']) && is_array($body['itemsSummary'])) {
            $stmtSum = $pdo->prepare("INSERT INTO comprobante_items_resumen (comprobante_id, nombre, tag) VALUES (?, ?, ?)");
            foreach ($body['itemsSummary'] as $sum) {
                $stmtSum->execute([
                    $body['id'],
                    $sum['name'] ?? '',
                    $sum['tag'] ?? ''
                ]);
            }
        }

        $pdo->commit();
        sendJson(['success' => true, 'message' => 'Comprobante registrado en MySQL', 'id' => $body['id']], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($method === 'PUT' || $method === 'PATCH') {
    $body = getJsonInput();
    $id = $body['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        sendJson(['success' => false, 'error' => 'ID de comprobante requerido'], 400);
    }

    $stmt = $pdo->prepare("UPDATE comprobantes SET 
        estado = COALESCE(?, estado),
        nota_interna = COALESCE(?, nota_interna)
        WHERE id = ?");

    $stmt->execute([
        $body['status'] ?? null,
        $body['internalNote'] ?? null,
        $id
    ]);

    // Si se aprueba, también podemos sincronizar el pedido asociado
    if (isset($body['status']) && $body['status'] === 'aprobado') {
        $stmtOrder = $pdo->prepare("UPDATE pedidos p 
            INNER JOIN comprobantes c ON c.pedido_id = p.id
            SET p.pago_verificado = 1, p.estado = 'listo', p.estado_label = 'Listo Despacho · Empacado'
            WHERE c.id = ?");
        $stmtOrder->execute([$id]);
    }

    sendJson(['success' => true, 'message' => 'Comprobante y pedido actualizados en MySQL']);
}

sendJson(['success' => false, 'error' => 'Método no permitido'], 405);
