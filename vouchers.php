<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Validación de Comprobantes de Pago';
$current_tab = 'vouchers';

// Procesar aprobación o rechazo vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $voucherId = $_POST['voucher_id'] ?? '';
    $action = $_POST['action'];

    if ($voucherId) {
        if ($action === 'approve') {
            // Aprobar comprobante
            $stmt = $pdo->prepare("UPDATE comprobantes SET estado = 'aprobado' WHERE id = ?");
            $stmt->execute([$voucherId]);

            // Actualizar pedido relacionado a pago_verificado = 1 y listo despacho
            $stmtOrder = $pdo->prepare("UPDATE pedidos p 
                INNER JOIN comprobantes c ON c.pedido_id = p.id
                SET p.pago_verificado = 1, p.estado = 'listo', p.estado_label = 'Listo Despacho · Empacado'
                WHERE c.id = ?");
            $stmtOrder->execute([$voucherId]);

            header("Location: vouchers.php?approved=" . urlencode($voucherId));
            exit;
        } elseif ($action === 'reject') {
            $reason = $_POST['reason'] ?? 'Comprobante no coincide con extracto bancario';
            $stmt = $pdo->prepare("UPDATE comprobantes SET estado = 'rechazado', nota_interna = ? WHERE id = ?");
            $stmt->execute([$reason, $voucherId]);

            header("Location: vouchers.php?rejected=" . urlencode($voucherId));
            exit;
        }
    }
}

// Filtro de estado de comprobante
$statusFilter = $_GET['status'] ?? 'pendiente';
$query = "SELECT * FROM comprobantes";
$params = [];

if ($statusFilter !== 'todos') {
    $query .= " WHERE estado = ?";
    $params[] = $statusFilter;
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$vouchers = $stmt->fetchAll();

// Conteos
$cntPendientes = (int) $pdo->query("SELECT COUNT(*) FROM comprobantes WHERE estado = 'pendiente'")->fetchColumn();
$cntAprobados = (int) $pdo->query("SELECT COUNT(*) FROM comprobantes WHERE estado = 'aprobado'")->fetchColumn();
$cntRechazados = (int) $pdo->query("SELECT COUNT(*) FROM comprobantes WHERE estado = 'rechazado'")->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md md:max-w-3xl lg:max-w-5xl px-4 space-y-4">
  
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
    <div>
      <h2 class="font-rubik text-2xl font-black text-[#211630]">Comprobantes de Pago</h2>
      <p class="text-xs text-[#6f5092]">Validación de transferencias Bancolombia, Nequi y Daviplata en MySQL.</p>
    </div>
  </div>

  <!-- Pestañas de Filtro -->
  <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1">
    <a href="vouchers.php?status=pendiente" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $statusFilter === 'pendiente' ? 'bg-[#7b2cbf] text-white shadow-xs' : 'bg-white text-[#6f5092] border border-[#ebd2ff] hover:bg-purple-50' ?>">
      Pendientes (<?= $cntPendientes ?>)
    </a>
    <a href="vouchers.php?status=aprobado" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $statusFilter === 'aprobado' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50' ?>">
      Aprobados (<?= $cntAprobados ?>)
    </a>
    <a href="vouchers.php?status=rechazado" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $statusFilter === 'rechazado' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white text-rose-700 border border-rose-200 hover:bg-rose-50' ?>">
      Rechazados (<?= $cntRechazados ?>)
    </a>
    <a href="vouchers.php?status=todos" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $statusFilter === 'todos' ? 'bg-[#211630] text-white shadow-xs' : 'bg-white text-[#6f5092] border border-[#ebd2ff] hover:bg-purple-50' ?>">
      Todos
    </a>
  </div>

  <!-- Lista de Comprobantes -->
  <div class="space-y-4">
    <?php if (empty($vouchers)): ?>
      <div class="p-8 text-center bg-white rounded-3xl border border-[#ebd2ff] shadow-2xs">
        <span class="material-symbols-outlined text-4xl text-emerald-500">task_alt</span>
        <p class="text-sm font-bold text-[#211630] mt-2">No hay comprobantes pendientes en esta cola.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($vouchers as $v): ?>
      <div class="p-5 rounded-3xl bg-white border border-[#f6e9ff] shadow-xs hover:border-[#deb7ff] transition-all flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <div class="flex items-start gap-4 min-w-0">
          <!-- Thumbnail Recibo -->
          <a href="detalle_comprobante.php?id=<?= urlencode($v['id']) ?>" class="w-16 h-20 rounded-2xl bg-purple-50 border border-purple-200 overflow-hidden shrink-0 group flex items-center justify-center relative">
            <?php if (!empty($v['recibo_thumb_url'])): ?>
              <img src="<?= htmlspecialchars($v['recibo_thumb_url']) ?>" alt="Comprobante" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
            <?php else: ?>
              <span class="material-symbols-outlined text-2xl text-purple-400">receipt</span>
            <?php endif; ?>
            <div class="absolute inset-0 bg-purple-950/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
              <span class="material-symbols-outlined text-[18px]">zoom_in</span>
            </div>
          </a>

          <!-- Información del Pago -->
          <div class="space-y-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="font-rubik text-sm font-bold text-[#211630]"><?= htmlspecialchars($v['cliente_nombre']) ?></h3>
              <span class="font-mono text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200">
                <?= htmlspecialchars($v['id']) ?>
              </span>
              <span class="text-[10px] text-[#6f5092]">Ref: <?= htmlspecialchars($v['pedido_id']) ?></span>
            </div>

            <p class="text-xs text-[#6f5092]">
              <?= htmlspecialchars($v['metodo_pago']) ?> · Código de Aprobación: <strong class="text-[#211630] font-mono"><?= htmlspecialchars($v['codigo_aprobacion']) ?></strong>
            </p>

            <div class="flex items-center gap-3 pt-1">
              <div>
                <span class="text-[10px] text-[#6f5092] block">Monto Transferido:</span>
                <span class="font-rubik text-sm font-black text-emerald-700"><?= formatCOP($v['monto_total']) ?></span>
              </div>
              <div>
                <span class="text-[10px] text-[#6f5092] block">Hora Banco:</span>
                <span class="text-xs font-semibold text-[#211630]"><?= htmlspecialchars($v['timestamp_banco']) ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones de Aprobación / Rechazo -->
        <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
          <a href="detalle_comprobante.php?id=<?= urlencode($v['id']) ?>" class="px-3.5 py-2 rounded-xl bg-purple-50 hover:bg-purple-100 text-[#7b2cbf] text-xs font-bold transition-colors">
            Ver Recibo
          </a>

          <?php if ($v['estado'] === 'pendiente'): ?>
            <form method="POST" action="vouchers.php" class="inline">
              <input type="hidden" name="action" value="approve">
              <input type="hidden" name="voucher_id" value="<?= htmlspecialchars($v['id']) ?>">
              <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs active:scale-95 transition-all flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">verified</span>
                Aprobar Pago
              </button>
            </form>

            <form method="POST" action="vouchers.php" class="inline" onsubmit="return confirm('¿Segura de rechazar este comprobante?')">
              <input type="hidden" name="action" value="reject">
              <input type="hidden" name="voucher_id" value="<?= htmlspecialchars($v['id']) ?>">
              <button type="submit" class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-colors">
                Rechazar
              </button>
            </form>
          <?php else: ?>
            <span class="px-3 py-1.5 rounded-xl text-xs font-bold <?= $v['estado'] === 'aprobado' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
              <?= ucfirst($v['estado']) ?>
            </span>
          <?php endif; ?>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php if (isset($_GET['approved'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    showToast('Pago Aprobado', 'Comprobante validado y pedido marcado listo para despacho en MySQL.', 'verified');
  });
</script>
<?php elseif (isset($_GET['rejected'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    showToast('Comprobante Rechazado', 'El estado fue registrado en MySQL.', 'cancel');
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
