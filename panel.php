<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Panel de Control del Taller';
$current_tab = 'panel';

// Consultas directas a MySQL
$totalProds = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn();
$criticos = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1 AND categoria = 'critico'")->fetchColumn();
$agotados = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1 AND categoria = 'agotado'")->fetchColumn();

$pedidosPrep = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'preparacion'")->fetchColumn();
$pedidosListos = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'listo'")->fetchColumn();
$pedidosTransito = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'transito'")->fetchColumn();
$totalPedidos = $pedidosPrep + $pedidosListos + $pedidosTransito;

$vouchersPendientes = (int) $pdo->query("SELECT COUNT(*) FROM comprobantes WHERE estado = 'pendiente'")->fetchColumn();

// Calcular ingresos totales sumando pedidos
$ingresosStmt = $pdo->query("SELECT COALESCE(SUM(pi.precio * pi.cantidad), 0) FROM pedido_items pi");
$totalIngresos = (int) $ingresosStmt->fetchColumn();

// Pedidos recientes
$pedidosRecientes = $pdo->query("SELECT * FROM pedidos ORDER BY created_at DESC LIMIT 4")->fetchAll();

// Comprobantes recientes
$vouchersRecientes = $pdo->query("SELECT * FROM comprobantes ORDER BY created_at DESC LIMIT 3")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md md:max-w-3xl lg:max-w-5xl px-4 space-y-5">
  
  <!-- Welcome Banner -->
  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#6100a4] via-[#7b2cbf] to-[#9d4edd] text-white p-6 shadow-xl shadow-purple-950/10">
    <div class="absolute -right-10 -bottom-10 w-48 h-48 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase mb-2">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          Taller Maraña Neiva · Huila
        </div>
        <h2 class="font-rubik text-2xl sm:text-3xl font-extrabold leading-tight">
          ¡Hola, <?= htmlspecialchars($user['name']) ?>!
        </h2>
        <p class="text-sm text-purple-100 mt-1 max-w-xl">
          <?= $vouchersPendientes ?> comprobantes pendientes de validación y <?= $pedidosPrep ?> pedidos en telar hoy.
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <a href="vouchers.php" class="px-4 py-2.5 rounded-2xl bg-white text-[#6100a4] text-xs font-extrabold shadow-md hover:bg-purple-50 active:scale-95 transition-all flex items-center gap-1.5">
          <span class="material-symbols-outlined text-[18px]">receipt_long</span>
          Validar Pagos (<?= $vouchersPendientes ?>)
        </a>
      </div>
    </div>
  </div>

  <!-- KPI Grid -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
    
    <!-- KPI 1: Ventas / Ingresos -->
    <div class="p-4 rounded-3xl bg-white border border-[#f6e9ff] shadow-xs flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase text-[#6f5092]">Ventas Taller</span>
        <div class="w-8 h-8 rounded-xl bg-purple-50 text-[#7b2cbf] flex items-center justify-center">
          <span class="material-symbols-outlined text-[18px]">payments</span>
        </div>
      </div>
      <div class="mt-3">
        <div class="font-rubik text-xl sm:text-2xl font-black text-[#211630]">
          <?= formatCOP($totalIngresos) ?>
        </div>
        <span class="text-[11px] text-emerald-600 font-bold flex items-center gap-1 mt-0.5">
          <span class="material-symbols-outlined text-[14px]">trending_up</span> En tiempo real
        </span>
      </div>
    </div>

    <!-- KPI 2: Pedidos Activos -->
    <a href="pedidos.php" class="p-4 rounded-3xl bg-white border border-[#f6e9ff] shadow-xs flex flex-col justify-between hover:border-[#deb7ff] transition-all group">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase text-[#6f5092]">En Preparación</span>
        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-[18px]">handyman</span>
        </div>
      </div>
      <div class="mt-3">
        <div class="font-rubik text-xl sm:text-2xl font-black text-[#211630] group-hover:text-[#7b2cbf] transition-colors">
          <?= $pedidosPrep ?>
        </div>
        <span class="text-[11px] text-[#6f5092] font-semibold mt-0.5 block">
          <?= $totalPedidos ?> pedidos en total
        </span>
      </div>
    </a>

    <!-- KPI 3: Stock Alerta -->
    <a href="stock.php" class="p-4 rounded-3xl bg-white border border-[#f6e9ff] shadow-xs flex flex-col justify-between hover:border-[#deb7ff] transition-all group">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase text-[#6f5092]">Stock Crítico</span>
        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-[18px]">warning</span>
        </div>
      </div>
      <div class="mt-3">
        <div class="font-rubik text-xl sm:text-2xl font-black text-rose-600">
          <?= $criticos + $agotados ?>
        </div>
        <span class="text-[11px] text-[#6f5092] font-semibold mt-0.5 block">
          <?= $totalProds ?> referencias activas
        </span>
      </div>
    </a>

    <!-- KPI 4: Comprobantes por revisar -->
    <a href="vouchers.php" class="p-4 rounded-3xl bg-white border border-[#f6e9ff] shadow-xs flex flex-col justify-between hover:border-[#deb7ff] transition-all group">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase text-[#6f5092]">Comprobantes</span>
        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-[18px]">verified</span>
        </div>
      </div>
      <div class="mt-3">
        <div class="font-rubik text-xl sm:text-2xl font-black text-[#211630] group-hover:text-[#7b2cbf] transition-colors">
          <?= $vouchersPendientes ?>
        </div>
        <span class="text-[11px] text-amber-600 font-bold mt-0.5 block">
          Requieren revisión
        </span>
      </div>
    </a>

  </div>

  <!-- Content Split: Pedidos & Comprobantes -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    
    <!-- Pedidos Recientes -->
    <div class="bg-white rounded-3xl p-5 border border-[#f6e9ff] shadow-xs">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-rubik text-base font-bold text-[#211630]">Pedidos Recientes</h3>
          <span class="text-xs text-[#6f5092]">Sincronizados con MySQL</span>
        </div>
        <a href="pedidos.php" class="text-xs font-bold text-[#7b2cbf] hover:underline flex items-center gap-0.5">
          Ver todos <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        </a>
      </div>

      <div class="space-y-3">
        <?php foreach ($pedidosRecientes as $p): ?>
          <div class="p-3.5 rounded-2xl bg-[#faf5ff] border border-[#f3e8ff] flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-10 h-10 rounded-xl bg-[#7b2cbf] text-white flex items-center justify-center font-bold text-xs shrink-0">
                <?= htmlspecialchars($p['cliente_iniciales'] ?? substr($p['cliente_nombre'], 0, 2)) ?>
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                  <span class="font-bold text-xs text-[#211630] truncate"><?= htmlspecialchars($p['cliente_nombre']) ?></span>
                  <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-purple-100 text-[#7b2cbf] font-semibold"><?= htmlspecialchars($p['id']) ?></span>
                </div>
                <span class="text-[11px] text-[#6f5092] block truncate"><?= htmlspecialchars($p['cliente_ubicacion']) ?></span>
              </div>
            </div>
            <div class="text-right shrink-0">
              <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full <?= $p['estado'] === 'listo' ? 'bg-emerald-100 text-emerald-800' : ($p['estado'] === 'transito' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') ?>">
                <?= htmlspecialchars($p['estado_label'] ?? $p['estado']) ?>
              </span>
              <a href="guia_despacho.php?id=<?= urlencode($p['id']) ?>" class="block text-[10px] text-[#7b2cbf] font-bold mt-1 hover:underline">
                Ver Guía &rarr;
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Comprobantes Recientes -->
    <div class="bg-white rounded-3xl p-5 border border-[#f6e9ff] shadow-xs">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-rubik text-base font-bold text-[#211630]">Comprobantes de Pago</h3>
          <span class="text-xs text-[#6f5092]">Transferencias para validar</span>
        </div>
        <a href="vouchers.php" class="text-xs font-bold text-[#7b2cbf] hover:underline flex items-center gap-0.5">
          Ver cola <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        </a>
      </div>

      <div class="space-y-3">
        <?php foreach ($vouchersRecientes as $v): ?>
          <div class="p-3.5 rounded-2xl bg-[#faf5ff] border border-[#f3e8ff] flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-10 h-10 rounded-xl bg-purple-100 text-[#7b2cbf] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
              </div>
              <div class="min-w-0">
                <span class="font-bold text-xs text-[#211630] block truncate"><?= htmlspecialchars($v['cliente_nombre']) ?></span>
                <span class="text-[11px] text-[#6f5092] block truncate"><?= htmlspecialchars($v['metodo_pago']) ?> · <?= htmlspecialchars($v['tiempo_hace']) ?></span>
              </div>
            </div>
            <div class="text-right shrink-0">
              <div class="font-bold text-xs text-[#211630]">
                <?= formatCOP($v['monto_total']) ?>
              </div>
              <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full mt-0.5 <?= $v['estado'] === 'aprobado' ? 'bg-emerald-100 text-emerald-800' : ($v['estado'] === 'rechazado' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') ?>">
                <?= ucfirst($v['estado']) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- Quick Action Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
    <a href="stock.php" class="p-4 rounded-2xl bg-white border border-[#ebd2ff] hover:border-[#7b2cbf] transition-all flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-purple-100 text-[#7b2cbf] flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-[22px]">inventory_2</span>
      </div>
      <div>
        <h4 class="font-bold text-xs text-[#211630]">Gestionar Stock</h4>
        <p class="text-[11px] text-[#6f5092]">Ajustar unidades e inventario</p>
      </div>
    </a>
    <a href="pedidos.php" class="p-4 rounded-2xl bg-white border border-[#ebd2ff] hover:border-[#7b2cbf] transition-all flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-[22px]">local_shipping</span>
      </div>
      <div>
        <h4 class="font-bold text-xs text-[#211630]">Despachos y Guías</h4>
        <p class="text-[11px] text-[#6f5092]">Imprimir rótulos Servientrega</p>
      </div>
    </a>
    <a href="index.php" class="p-4 rounded-2xl bg-white border border-[#ebd2ff] hover:border-[#7b2cbf] transition-all flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-[22px]">storefront</span>
      </div>
      <div>
        <h4 class="font-bold text-xs text-[#211630]">Boutique en Vivo</h4>
        <p class="text-[11px] text-[#6f5092]">Ver catálogo de clientas</p>
      </div>
    </a>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
