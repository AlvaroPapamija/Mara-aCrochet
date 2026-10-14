<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Gestión de Pedidos & Envíos';
$current_tab = 'pedidos';

// Manejar cambio de estado de pedido vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = $_POST['order_id'] ?? '';
    $newStatus = $_POST['new_status'] ?? '';
    $statusLabels = [
        'preparacion' => 'En Taller · Tejido y Ensamble',
        'listo' => 'Listo Despacho · Empacado',
        'transito' => 'En Tránsito con Transportadora',
        'entregado' => 'Entregado a Clienta',
    ];

    if ($orderId && isset($statusLabels[$newStatus])) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ?, estado_label = ? WHERE id = ?");
        $stmt->execute([$newStatus, $statusLabels[$newStatus], $orderId]);
        header("Location: pedidos.php?status_updated=" . urlencode($orderId));
        exit;
    }
}

// Filtro de estado
$filter = $_GET['filter'] ?? 'todos';
$query = "SELECT * FROM pedidos";
$params = [];

if ($filter !== 'todos') {
    $query .= " WHERE estado = ?";
    $params[] = $filter;
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Cargar ítems de cada pedido
foreach ($orders as &$order) {
    $itemStmt = $pdo->prepare("SELECT * FROM pedido_items WHERE pedido_id = ?");
    $itemStmt->execute([$order['id']]);
    $order['items'] = $itemStmt->fetchAll();
    
    $total = 0;
    foreach ($order['items'] as $it) {
        $total += ((int)$it['precio']) * ((int)$it['cantidad']);
    }
    $order['calculated_total'] = $total + (int)$order['costo_envio'];
}
unset($order);

// Conteos por estado
$cntPrep = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'preparacion'")->fetchColumn();
$cntListo = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'listo'")->fetchColumn();
$cntTransito = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'transito'")->fetchColumn();
$cntEntregado = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'entregado'")->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md md:max-w-3xl lg:max-w-5xl px-4 space-y-4">
  
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
    <div>
      <h2 class="font-rubik text-2xl font-black text-[#211630]">Pedidos & Logística</h2>
      <p class="text-xs text-[#6f5092]">Control de preparación de amigurumis y guías de transporte nacional.</p>
    </div>
  </div>

  <!-- Barra de Filtros de Estado -->
  <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1">
    <a href="pedidos.php" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $filter === 'todos' ? 'bg-[#7b2cbf] text-white shadow-xs' : 'bg-white text-[#6f5092] border border-[#ebd2ff] hover:bg-purple-50' ?>">
      Todos (<?= count($orders) ?>)
    </a>
    <a href="pedidos.php?filter=preparacion" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $filter === 'preparacion' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white text-amber-700 border border-amber-200 hover:bg-amber-50' ?>">
      En Taller (<?= $cntPrep ?>)
    </a>
    <a href="pedidos.php?filter=listo" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $filter === 'listo' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50' ?>">
      Listos Despacho (<?= $cntListo ?>)
    </a>
    <a href="pedidos.php?filter=transito" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $filter === 'transito' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-blue-700 border border-blue-200 hover:bg-blue-50' ?>">
      En Tránsito (<?= $cntTransito ?>)
    </a>
  </div>

  <!-- Lista de Tarjetas de Pedidos -->
  <div class="space-y-4">
    <?php if (empty($orders)): ?>
      <div class="p-8 text-center bg-white rounded-3xl border border-[#ebd2ff] shadow-2xs">
        <span class="material-symbols-outlined text-4xl text-purple-300">inventory</span>
        <p class="text-sm font-bold text-[#211630] mt-2">No se encontraron pedidos en esta categoría.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($orders as $o): ?>
      <?php
        $badgeColor = 'bg-amber-100 text-amber-800 border-amber-200';
        if ($o['estado'] === 'listo') $badgeColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';
        if ($o['estado'] === 'transito') $badgeColor = 'bg-blue-100 text-blue-800 border-blue-200';
        if ($o['estado'] === 'entregado') $badgeColor = 'bg-gray-100 text-gray-800 border-gray-200';
      ?>
      <div class="p-5 rounded-3xl bg-white border border-[#f6e9ff] shadow-xs hover:border-[#deb7ff] transition-all space-y-4">
        
        <!-- Header de la Tarjeta -->
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#7b2cbf] to-[#6100a4] text-white flex items-center justify-center font-bold text-sm shadow-md shadow-purple-900/20">
              <?= htmlspecialchars($o['cliente_iniciales'] ?? substr($o['cliente_nombre'], 0, 2)) ?>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-rubik text-sm font-bold text-[#211630]"><?= htmlspecialchars($o['cliente_nombre']) ?></h3>
                <span class="font-mono text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200">
                  <?= htmlspecialchars($o['id']) ?>
                </span>
              </div>
              <p class="text-xs text-[#6f5092] mt-0.5"><?= htmlspecialchars($o['cliente_ubicacion']) ?> · <?= htmlspecialchars($o['cliente_telefono']) ?></p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-xs font-bold px-2.5 py-1 rounded-full border <?= $badgeColor ?>">
              <?= htmlspecialchars($o['estado_label'] ?? $o['estado']) ?>
            </span>
          </div>
        </div>

        <!-- Detalle de Productos del Pedido -->
        <div class="p-3.5 rounded-2xl bg-[#faf5ff] border border-[#f0dbff] space-y-2">
          <?php foreach ($o['items'] as $it): ?>
            <div class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-2">
                <span class="font-bold text-purple-700"><?= $it['cantidad'] ?>x</span>
                <span class="font-medium text-[#211630]"><?= htmlspecialchars($it['nombre']) ?></span>
                <span class="text-[10px] text-[#6f5092] hidden sm:inline">(<?= htmlspecialchars($it['especificaciones']) ?>)</span>
              </div>
              <span class="font-bold text-[#211630]"><?= formatCOP($it['precio'] * $it['cantidad']) ?></span>
            </div>
          <?php endforeach; ?>

          <div class="pt-2 border-t border-purple-100 flex items-center justify-between text-xs font-extrabold text-[#211630]">
            <span>Total Pedido (con envío):</span>
            <span class="text-[#7b2cbf] text-sm"><?= formatCOP($o['calculated_total']) ?></span>
          </div>
        </div>

        <!-- Logística y Botones de Acción -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
          <div class="text-xs text-[#6f5092]">
            <span class="font-semibold text-[#211630]">Transporte:</span> <?= htmlspecialchars($o['transportista']) ?>
            <?php if (!empty($o['guia'])): ?>
              · <span class="font-mono font-bold text-purple-700">Guía: <?= htmlspecialchars($o['guia']) ?></span>
            <?php endif; ?>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <!-- Formulario para cambiar estado -->
            <form method="POST" action="pedidos.php" class="inline">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['id']) ?>">
              <?php if ($o['estado'] === 'preparacion'): ?>
                <input type="hidden" name="new_status" value="listo">
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs active:scale-95 transition-all flex items-center gap-1">
                  <span class="material-symbols-outlined text-[16px]">check</span>
                  Listo Despacho
                </button>
              <?php elseif ($o['estado'] === 'listo'): ?>
                <input type="hidden" name="new_status" value="transito">
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs active:scale-95 transition-all flex items-center gap-1">
                  <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                  Despachar
                </button>
              <?php endif; ?>
            </form>

            <a href="guia_despacho.php?id=<?= urlencode($o['id']) ?>" class="px-3.5 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-[#7b2cbf] text-xs font-bold border border-purple-200 transition-colors flex items-center gap-1">
              <span class="material-symbols-outlined text-[16px]">print</span>
              Ver Guía PDF
            </a>
          </div>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php if (isset($_GET['status_updated'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    showToast('Estado Actualizado', 'El pedido <?= htmlspecialchars($_GET['status_updated']) ?> fue actualizado en MySQL.', 'local_shipping');
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
