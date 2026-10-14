<?php
require_once __DIR__ . '/includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: vouchers.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM comprobantes WHERE id = ?");
$stmt->execute([$id]);
$voucher = $stmt->fetch();

if (!$voucher) {
    header('Location: vouchers.php');
    exit;
}

// Obtener detalles del pedido relacionado
$stmtOrder = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmtOrder->execute([$voucher['pedido_id']]);
$order = $stmtOrder->fetch();

$stmtItems = $pdo->prepare("SELECT * FROM pedido_items WHERE pedido_id = ?");
$stmtItems->execute([$voucher['pedido_id']]);
$items = $stmtItems->fetchAll();

$page_title = 'Detalle de Comprobante ' . $voucher['id'];
$current_tab = 'vouchers';

include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md md:max-w-2xl px-4 space-y-4">
  
  <div class="flex items-center justify-between">
    <a href="vouchers.php" class="inline-flex items-center gap-1 text-xs font-bold text-[#7b2cbf] hover:underline">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span>
      Volver a Comprobantes
    </a>
    <span class="text-xs font-mono font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200">
      <?= htmlspecialchars($voucher['id']) ?>
    </span>
  </div>

  <div class="bg-white rounded-3xl p-6 border border-[#f6e9ff] shadow-sm space-y-5">
    
    <div class="flex items-start justify-between gap-3 border-b border-purple-100 pb-4">
      <div>
        <h2 class="font-rubik text-xl font-black text-[#211630]">
          Comprobante de Transferencia
        </h2>
        <p class="text-xs text-[#6f5092] mt-0.5">
          Cliente: <strong><?= htmlspecialchars($voucher['cliente_nombre']) ?></strong> · <?= htmlspecialchars($voucher['cliente_telefono']) ?>
        </p>
      </div>

      <span class="text-xs font-bold px-3 py-1 rounded-full <?= $voucher['estado'] === 'aprobado' ? 'bg-emerald-100 text-emerald-800' : ($voucher['estado'] === 'rechazado' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') ?>">
        <?= ucfirst($voucher['estado']) ?>
      </span>
    </div>

    <!-- Imagen del Recibo -->
    <div class="rounded-2xl border border-purple-200 overflow-hidden bg-purple-50 text-center">
      <?php 
        $reciboImg = !empty($voucher['recibo_full_url']) ? $voucher['recibo_full_url'] : $voucher['recibo_thumb_url'];
      ?>
      <?php if (!empty($reciboImg)): ?>
        <img src="<?= htmlspecialchars($reciboImg) ?>" alt="Recibo de pago" class="w-full max-h-96 object-contain mx-auto">
      <?php else: ?>
        <div class="p-12 text-center text-purple-400">
          <span class="material-symbols-outlined text-4xl">receipt</span>
          <p class="text-xs font-bold text-[#211630] mt-2">Imagen de recibo no adjuntada</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Datos Bancarios -->
    <div class="grid grid-cols-2 gap-3 text-xs bg-[#faf5ff] p-4 rounded-2xl border border-purple-100">
      <div>
        <span class="text-[10px] text-[#6f5092] uppercase font-bold block">Método de Pago</span>
        <span class="font-bold text-[#211630]"><?= htmlspecialchars($voucher['metodo_pago']) ?></span>
      </div>
      <div>
        <span class="text-[10px] text-[#6f5092] uppercase font-bold block">Código de Aprobación</span>
        <span class="font-mono font-bold text-purple-800"><?= htmlspecialchars($voucher['codigo_aprobacion']) ?></span>
      </div>
      <div>
        <span class="text-[10px] text-[#6f5092] uppercase font-bold block">Monto Transferido</span>
        <span class="font-bold text-emerald-700 text-sm"><?= formatCOP($voucher['monto_total']) ?></span>
      </div>
      <div>
        <span class="text-[10px] text-[#6f5092] uppercase font-bold block">Hora Registrada</span>
        <span class="font-medium text-[#211630]"><?= htmlspecialchars($voucher['timestamp_banco']) ?></span>
      </div>
    </div>

    <!-- Desglose del Pedido Asociado -->
    <div class="space-y-2">
      <h3 class="text-xs font-bold uppercase text-[#6f5092] tracking-wider">
        Pedido Asociado: <?= htmlspecialchars($voucher['pedido_id']) ?>
      </h3>
      <div class="space-y-1.5 border border-purple-100 rounded-2xl p-3 bg-white">
        <?php foreach ($items as $it): ?>
          <div class="flex items-center justify-between text-xs">
            <span class="text-[#211630]">
              <strong class="text-purple-700"><?= $it['cantidad'] ?>x</strong> <?= htmlspecialchars($it['nombre']) ?>
            </span>
            <span class="font-bold text-[#211630]"><?= formatCOP($it['precio'] * $it['cantidad']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Acciones -->
    <?php if ($voucher['estado'] === 'pendiente'): ?>
      <div class="flex items-center justify-end gap-2.5 pt-2">
        <form method="POST" action="vouchers.php" class="inline" onsubmit="return confirm('¿Rechazar este pago?')">
          <input type="hidden" name="action" value="reject">
          <input type="hidden" name="voucher_id" value="<?= htmlspecialchars($voucher['id']) ?>">
          <button type="submit" class="px-4 py-2 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-colors">
            Rechazar
          </button>
        </form>

        <form method="POST" action="vouchers.php" class="inline">
          <input type="hidden" name="action" value="approve">
          <input type="hidden" name="voucher_id" value="<?= htmlspecialchars($voucher['id']) ?>">
          <button type="submit" class="px-5 py-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-900/20 active:scale-95 transition-all flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">verified</span>
            Aprobar y Despachar
          </button>
        </form>
      </div>
    <?php endif; ?>

  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
