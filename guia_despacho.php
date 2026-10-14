<?php
require_once __DIR__ . '/includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: pedidos.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: pedidos.php');
    exit;
}

$stmtItems = $pdo->prepare("SELECT * FROM pedido_items WHERE pedido_id = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

$page_title = 'Guía de Despacho ' . $order['id'];
$current_tab = 'pedidos';

include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md md:max-w-2xl px-4 space-y-4">
  
  <div class="flex items-center justify-between no-print">
    <a href="pedidos.php" class="inline-flex items-center gap-1 text-xs font-bold text-[#7b2cbf] hover:underline">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span>
      Volver a Pedidos
    </a>

    <button onclick="window.print()" class="px-4 py-2 rounded-2xl bg-[#7b2cbf] hover:bg-[#6100a4] text-white text-xs font-bold shadow-md shadow-purple-900/20 active:scale-95 transition-all flex items-center gap-1.5">
      <span class="material-symbols-outlined text-[18px]">print</span>
      Imprimir Rótulo Térmico
    </button>
  </div>

  <!-- Contenedor del Rótulo de Envío (Estilo Guía Servientrega / Maraña Atelier) -->
  <div id="printable-guide" class="bg-white rounded-3xl p-6 sm:p-8 border-2 border-dashed border-[#d8b4fe] shadow-lg text-[#211630] space-y-6">
    
    <!-- Header del Rótulo -->
    <div class="flex items-center justify-between border-b-2 border-purple-100 pb-5">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 p-1 flex items-center justify-center">
          <img src="logo.png" alt="Maraña" class="w-full h-full object-contain" onerror="this.src='https://images.unsplash.com/photo-1584992236310-6edddc08acff?w=100&fit=crop'">
        </div>
        <div>
          <h2 class="font-rubik text-base font-extrabold text-[#7b2cbf] leading-tight">MARAÑA CROCHET ATELIER</h2>
          <span class="text-[10px] text-[#6f5092] tracking-wider uppercase font-semibold">Taller Artesanal · Neiva, Huila · NIT 901.482.109-2</span>
        </div>
      </div>
      <div class="text-right">
        <span class="text-[10px] uppercase font-bold text-gray-500 block">Transportadora</span>
        <span class="text-xs font-black text-[#211630]"><?= htmlspecialchars($order['carrier'] ?? 'Servientrega Nacional') ?></span>
      </div>
    </div>

    <!-- Código de Barras Simulado & Número de Guía -->
    <div class="p-4 rounded-2xl bg-[#faf5ff] border border-purple-200 text-center space-y-2">
      <span class="text-[10px] font-bold uppercase tracking-widest text-purple-700">Número de Rastreo & Guía Nacional</span>
      <div class="font-mono text-xl sm:text-2xl font-black text-[#211630] tracking-wider">
        <?= htmlspecialchars($order['trackingNumber'] ?? ($order['guia'] ?? '9482-0198-4820')) ?>
      </div>
      <!-- Simulación de Código de Barras con SVG -->
      <div class="flex justify-center py-1">
        <svg class="h-10 w-64" viewBox="0 0 200 40" preserveAspectRatio="none">
          <rect x="0" y="0" width="3" height="40" fill="#211630"/>
          <rect x="5" y="0" width="2" height="40" fill="#211630"/>
          <rect x="9" y="0" width="6" height="40" fill="#211630"/>
          <rect x="18" y="0" width="2" height="40" fill="#211630"/>
          <rect x="23" y="0" width="4" height="40" fill="#211630"/>
          <rect x="30" y="0" width="1" height="40" fill="#211630"/>
          <rect x="34" y="0" width="5" height="40" fill="#211630"/>
          <rect x="42" y="0" width="3" height="40" fill="#211630"/>
          <rect x="48" y="0" width="2" height="40" fill="#211630"/>
          <rect x="53" y="0" width="6" height="40" fill="#211630"/>
          <rect x="62" y="0" width="4" height="40" fill="#211630"/>
          <rect x="69" y="0" width="1" height="40" fill="#211630"/>
          <rect x="73" y="0" width="5" height="40" fill="#211630"/>
          <rect x="81" y="0" width="3" height="40" fill="#211630"/>
          <rect x="87" y="0" width="2" height="40" fill="#211630"/>
          <rect x="92" y="0" width="6" height="40" fill="#211630"/>
          <rect x="101" y="0" width="3" height="40" fill="#211630"/>
          <rect x="107" y="0" width="5" height="40" fill="#211630"/>
          <rect x="115" y="0" width="2" height="40" fill="#211630"/>
          <rect x="120" y="0" width="4" height="40" fill="#211630"/>
          <rect x="127" y="0" width="6" height="40" fill="#211630"/>
          <rect x="136" y="0" width="2" height="40" fill="#211630"/>
          <rect x="141" y="0" width="5" height="40" fill="#211630"/>
          <rect x="149" y="0" width="3" height="40" fill="#211630"/>
          <rect x="155" y="0" width="2" height="40" fill="#211630"/>
          <rect x="160" y="0" width="6" height="40" fill="#211630"/>
          <rect x="169" y="0" width="4" height="40" fill="#211630"/>
          <rect x="176" y="0" width="1" height="40" fill="#211630"/>
          <rect x="180" y="0" width="5" height="40" fill="#211630"/>
          <rect x="188" y="0" width="3" height="40" fill="#211630"/>
          <rect x="194" y="0" width="4" height="40" fill="#211630"/>
        </svg>
      </div>
      <span class="text-[10px] text-purple-600 font-mono block">REF PEDIDO: <?= htmlspecialchars($order['id']) ?></span>
    </div>

    <!-- Destinatario y Remitente -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
      <!-- Destinatario -->
      <div class="p-4 rounded-2xl bg-[#faf5ff] border border-purple-200 space-y-1">
        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 block">DESTINATARIO / CLIENTA:</span>
        <h4 class="font-bold text-sm text-[#211630]"><?= htmlspecialchars($order['cliente_nombre']) ?></h4>
        <p class="text-[#6f5092] font-semibold"><?= htmlspecialchars($order['cliente_telefono']) ?></p>
        <p class="text-[#211630] mt-1">
          <?= htmlspecialchars($order['cliente_direccion'] ?? $order['cliente_ubicacion']) ?>
        </p>
        <p class="text-[11px] font-bold text-purple-800"><?= htmlspecialchars($order['cliente_ubicacion']) ?></p>
      </div>

      <!-- Remitente -->
      <div class="p-4 rounded-2xl bg-white border border-purple-200 space-y-1">
        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block">REMITENTE (TALLER):</span>
        <h4 class="font-bold text-sm text-[#211630]">Maraña Crochet Atelier</h4>
        <p class="text-[#6f5092]">+57 311 000 0000</p>
        <p class="text-[#211630] mt-1">Calle 8 # 14-20, Taller de Hilados</p>
        <p class="text-[11px] font-bold text-purple-800">Neiva, Huila · Colombia</p>
      </div>
    </div>

    <!-- Contenido y Cuidados -->
    <div class="border-t border-purple-100 pt-4 space-y-3">
      <h4 class="text-xs font-bold uppercase text-[#6f5092] tracking-wider">Contenido Artesanal</h4>
      <div class="space-y-1.5">
        <?php foreach ($items as $it): ?>
          <div class="flex items-center justify-between text-xs">
            <span class="font-medium text-[#211630]">
              <strong class="text-purple-700"><?= $it['cantidad'] ?>x</strong> <?= htmlspecialchars($it['nombre']) ?> 
              <span class="text-[10px] text-[#6f5092]">(<?= htmlspecialchars($it['especificaciones']) ?>)</span>
            </span>
            <span class="text-xs font-bold text-[#211630]"><?= formatCOP($it['precio'] * $it['cantidad']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Dedicatoria Caligrafiada -->
      <?php if (!empty($order['nota_especial'])): ?>
      <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 block">Dedicatoria Artesanal Caligrafiada:</span>
        <p class="italic text-amber-950 mt-0.5">
          <?= htmlspecialchars($order['nota_especial']) ?>
        </p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Footer del Rótulo -->
    <div class="border-t border-purple-100 pt-3 flex items-center justify-between text-[10px] text-[#6f5092]">
      <span>Pieza tejida con amor en algodón orgánico. Tratar con cuidado.</span>
      <span class="font-mono">Fecha: <?= htmlspecialchars($order['fecha']) ?></span>
    </div>

  </div>

</div>

<style>
@media print {
  body {
    background: white !important;
    padding: 0 !important;
  }
  header, nav, .no-print {
    display: none !important;
  }
  main {
    padding: 0 !important;
    margin: 0 !important;
  }
  #printable-guide {
    border: 1px solid #000 !important;
    box-shadow: none !important;
    width: 100% !important;
  }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
