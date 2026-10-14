<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Control de Stock & Inventario';
$current_tab = 'stock';

// Manejar ajuste rápido de stock si se envía vía POST tradicional
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $sku = $_POST['sku'] ?? '';
    $delta = (int) ($_POST['delta'] ?? 0);
    if ($sku) {
        $stmt = $pdo->prepare("SELECT stock_actual, stock_minimo FROM productos WHERE sku = ?");
        $stmt->execute([$sku]);
        $curr = $stmt->fetch();
        if ($curr) {
            $newStock = max(0, ((int)$curr['stock_actual']) + $delta);
            $minAlert = (int) $curr['stock_minimo'];
            $newCat = ($newStock === 0) ? 'agotado' : (($newStock <= $minAlert) ? 'critico' : 'optimo');
            $newLabel = ($newStock === 0) ? 'Agotado' : (($newStock <= $minAlert) ? "Stock Mínimo: {$newStock} / {$minAlert}" : "Stock Óptimo ({$newStock} unidades)");

            $upd = $pdo->prepare("UPDATE productos SET stock_actual = ?, categoria = ?, estado_label = ? WHERE sku = ?");
            $upd->execute([$newStock, $newCat, $newLabel, $sku]);
            header("Location: stock.php?updated=" . urlencode($sku));
            exit;
        }
    }
}

// Filtro por categoría
$catFilter = $_GET['cat'] ?? 'todos';
$search = trim($_GET['q'] ?? '');

$query = "SELECT * FROM productos WHERE activo = 1";
$params = [];

if ($catFilter !== 'todos') {
    $query .= " AND categoria = ?";
    $params[] = $catFilter;
}

if ($search !== '') {
    $query .= " AND (nombre LIKE ? OR sku LIKE ? OR proveedor LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$query .= " ORDER BY (categoria = 'critico') DESC, (categoria = 'agotado') DESC, nombre ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Métricas de conteo
$cntOptimo = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1 AND categoria = 'optimo'")->fetchColumn();
$cntCritico = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1 AND categoria = 'critico'")->fetchColumn();
$cntAgotado = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1 AND categoria = 'agotado'")->fetchColumn();
$cntPedido = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1 AND categoria = 'pedido'")->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md md:max-w-3xl lg:max-w-5xl px-4 space-y-4">
  
  <!-- Banner de Atención si hay críticos -->
  <?php if ($cntCritico > 0): ?>
  <div class="bg-gradient-to-r from-[#f1dbff] via-[#f5e6ff] to-[#edd6ff] rounded-3xl p-4 border border-[#deb7ff] shadow-sm flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-2xl bg-[#6100a4] text-white flex items-center justify-center shadow-md shadow-purple-900/20 shrink-0">
        <span class="material-symbols-outlined text-[20px]">warning</span>
      </div>
      <div>
        <span class="text-[11px] font-extrabold text-[#6100a4] uppercase tracking-wider block">Atención de Taller</span>
        <p class="text-sm font-bold text-[#211630]">
          <?= $cntCritico ?> referencias con stock mínimo alcanzado
        </p>
      </div>
    </div>
    <a href="stock.php?cat=critico" class="px-3.5 py-1.5 rounded-xl bg-[#6100a4] text-white text-xs font-bold shadow-md hover:bg-[#7b2cbf] active:scale-95 transition-all shrink-0">
      Filtrar críticos
    </a>
  </div>
  <?php endif; ?>

  <!-- Strips de Métricas -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
    <a href="stock.php" class="p-3.5 rounded-2xl bg-white border border-[#ebd2ff] shadow-2xs hover:border-[#7b2cbf] transition-all">
      <span class="text-[11px] font-bold text-[#6f5092] block">Total Referencias</span>
      <span class="font-rubik text-xl font-black text-[#211630] mt-1 block"><?= $cntOptimo + $cntCritico + $cntAgotado + $cntPedido ?></span>
    </a>
    <a href="stock.php?cat=critico" class="p-3.5 rounded-2xl bg-white border <?= $catFilter === 'critico' ? 'border-amber-400 ring-2 ring-amber-100' : 'border-[#ebd2ff]' ?> shadow-2xs hover:border-amber-400 transition-all">
      <span class="text-[11px] font-bold text-amber-700 block">Stock Crítico</span>
      <span class="font-rubik text-xl font-black text-amber-600 mt-1 block"><?= $cntCritico ?></span>
    </a>
    <a href="stock.php?cat=agotado" class="p-3.5 rounded-2xl bg-white border <?= $catFilter === 'agotado' ? 'border-rose-400 ring-2 ring-rose-100' : 'border-[#ebd2ff]' ?> shadow-2xs hover:border-rose-400 transition-all">
      <span class="text-[11px] font-bold text-rose-700 block">Agotados</span>
      <span class="font-rubik text-xl font-black text-rose-600 mt-1 block"><?= $cntAgotado ?></span>
    </a>
    <a href="stock.php?cat=optimo" class="p-3.5 rounded-2xl bg-white border <?= $catFilter === 'optimo' ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-[#ebd2ff]' ?> shadow-2xs hover:border-emerald-400 transition-all">
      <span class="text-[11px] font-bold text-emerald-700 block">Stock Óptimo</span>
      <span class="font-rubik text-xl font-black text-emerald-600 mt-1 block"><?= $cntOptimo ?></span>
    </a>
  </div>

  <!-- Buscador y Filtros -->
  <div class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
    <form method="GET" action="stock.php" class="relative flex-1">
      <input type="hidden" name="cat" value="<?= htmlspecialchars($catFilter) ?>">
      <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-purple-400 text-[20px]">search</span>
      <input 
        type="text" 
        name="q" 
        value="<?= htmlspecialchars($search) ?>" 
        placeholder="Buscar por amigurumi, SKU o hilaza..." 
        class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-white border border-[#ebd2ff] text-xs font-medium text-[#211630] placeholder-purple-300 focus:outline-none focus:border-[#7b2cbf] shadow-2xs"
      />
    </form>

    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1">
      <a href="stock.php" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $catFilter === 'todos' ? 'bg-[#7b2cbf] text-white shadow-xs' : 'bg-white text-[#6f5092] border border-[#ebd2ff] hover:bg-purple-50' ?>">
        Todos (<?= count($products) ?>)
      </a>
      <a href="stock.php?cat=critico" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $catFilter === 'critico' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white text-amber-700 border border-amber-200 hover:bg-amber-50' ?>">
        Crítico (<?= $cntCritico ?>)
      </a>
      <a href="stock.php?cat=agotado" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $catFilter === 'agotado' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white text-rose-700 border border-rose-200 hover:bg-rose-50' ?>">
        Agotado (<?= $cntAgotado ?>)
      </a>
      <a href="stock.php?cat=optimo" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $catFilter === 'optimo' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50' ?>">
        Óptimo (<?= $cntOptimo ?>)
      </a>
    </div>
  </div>

  <!-- Listado de Productos -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
    <?php foreach ($products as $p): ?>
      <?php 
        $badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
        if ($p['categoria'] === 'critico') $badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
        if ($p['categoria'] === 'agotado') $badgeClass = 'bg-rose-100 text-rose-800 border-rose-200';
        if ($p['categoria'] === 'pedido')  $badgeClass = 'bg-purple-100 text-[#7b2cbf] border-purple-200';
      ?>
      <div class="p-4 rounded-3xl bg-white border border-[#f6e9ff] shadow-xs hover:border-[#deb7ff] transition-all flex flex-col justify-between gap-3">
        
        <div class="flex items-start gap-3.5">
          <div class="w-20 h-20 rounded-2xl bg-purple-50 border border-[#ebd2ff] overflow-hidden shrink-0 flex items-center justify-center">
            <img 
              src="<?= htmlspecialchars($p['imagen_url']) ?>" 
              alt="<?= htmlspecialchars($p['nombre']) ?>" 
              class="w-full h-full object-cover"
              onerror="this.src='https://images.unsplash.com/photo-1584992236310-6edddc08acff?w=200&fit=crop'"
            />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border <?= $badgeClass ?>">
                <?= ucfirst($p['categoria']) ?>
              </span>
              <span class="text-[10px] font-mono text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded-md font-semibold">
                <?= htmlspecialchars($p['sku']) ?>
              </span>
            </div>

            <h3 class="font-rubik text-sm font-bold text-[#211630] mt-1 truncate">
              <?= htmlspecialchars($p['nombre']) ?>
            </h3>

            <p class="text-[11px] text-[#6f5092] truncate mt-0.5">
              <?= htmlspecialchars($p['proveedor']) ?> · <?= htmlspecialchars($p['talla']) ?>
            </p>

            <div class="flex items-center gap-2 mt-2">
              <span class="text-xs font-extrabold text-[#211630]"><?= formatCOP($p['precio_cop']) ?></span>
              <span class="text-[10px] text-[#6f5092]">Costo: <?= formatCOP($p['costo_lana_cop']) ?></span>
            </div>
          </div>
        </div>

        <!-- Barra inferior de control de unidades directas -->
        <div class="pt-3 border-t border-[#f6e9ff] flex items-center justify-between">
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-[#6f5092]">Stock en Taller:</span>
            <span id="stock-val-<?= $p['sku'] ?>" class="font-rubik text-sm font-black text-[#211630]">
              <?= $p['stock_actual'] ?>
            </span>
          </div>

          <div class="flex items-center gap-2">
            <!-- Formulario Ajuste Rápido - / + -->
            <div class="flex items-center bg-[#faf5ff] p-1 rounded-xl border border-[#ebd2ff]">
              <form method="POST" action="stock.php" class="inline">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" name="sku" value="<?= htmlspecialchars($p['sku']) ?>">
                <input type="hidden" name="delta" value="-1">
                <button type="submit" class="w-7 h-7 rounded-lg bg-white border border-[#ebd2ff] text-[#211630] font-bold hover:bg-purple-100 flex items-center justify-center text-xs active:scale-90 transition-transform">
                  -
                </button>
              </form>

              <span class="w-8 text-center font-bold text-xs text-[#211630]">
                <?= $p['stock_actual'] ?>
              </span>

              <form method="POST" action="stock.php" class="inline">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" name="sku" value="<?= htmlspecialchars($p['sku']) ?>">
                <input type="hidden" name="delta" value="1">
                <button type="submit" class="w-7 h-7 rounded-lg bg-[#7b2cbf] text-white font-bold hover:bg-[#6100a4] flex items-center justify-center text-xs active:scale-90 transition-transform shadow-xs">
                  +
                </button>
              </form>
            </div>

            <!-- Botón Edición Completa -->
            <a href="editar_stock.php?sku=<?= urlencode($p['sku']) ?>" class="p-2 rounded-xl bg-purple-50 text-[#7b2cbf] hover:bg-purple-100 transition-colors" title="Editar detalles">
              <span class="material-symbols-outlined text-[18px]">edit</span>
            </a>
          </div>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php if (isset($_GET['updated'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    showToast('Stock Actualizado', 'Modificación sincronizada en MySQL para <?= htmlspecialchars($_GET['updated']) ?>', 'check_circle');
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
