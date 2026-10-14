<?php
require_once __DIR__ . '/includes/db.php';

$sku = $_GET['sku'] ?? ($_POST['sku'] ?? '');
if (!$sku) {
    header('Location: stock.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM productos WHERE sku = ?");
$stmt->execute([$sku]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: stock.php');
    exit;
}

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_product') {
    $nombre = $_POST['nombre'] ?? $product['nombre'];
    $tag = $_POST['tag'] ?? $product['tag'];
    $talla = $_POST['talla'] ?? $product['talla'];
    $proveedor = $_POST['proveedor'] ?? $product['proveedor'];
    $precio = (int) ($_POST['precio_cop'] ?? $product['precio_cop']);
    $costoLana = (int) ($_POST['costo_lana_cop'] ?? $product['costo_lana_cop']);
    $stockActual = (int) ($_POST['stock_actual'] ?? $product['stock_actual']);
    $stockMinimo = (int) ($_POST['stock_minimo'] ?? $product['stock_minimo']);
    $imagenUrl = $_POST['imagen_url'] ?? $product['imagen_url'];

    $categoria = ($stockActual === 0) ? 'agotado' : (($stockActual <= $stockMinimo) ? 'critico' : 'optimo');
    $estadoLabel = ($stockActual === 0) ? 'Agotado' : (($stockActual <= $stockMinimo) ? "Stock Mínimo: {$stockActual} / {$stockMinimo}" : "Stock Óptimo ({$stockActual} unidades)");

    $upd = $pdo->prepare("UPDATE productos SET 
        nombre = ?, tag = ?, talla = ?, proveedor = ?, precio_cop = ?, costo_lana_cop = ?,
        stock_actual = ?, stock_minimo = ?, categoria = ?, estado_label = ?, imagen_url = ?
        WHERE sku = ?");
    $upd->execute([$nombre, $tag, $talla, $proveedor, $precio, $costoLana, $stockActual, $stockMinimo, $categoria, $estadoLabel, $imagenUrl, $sku]);

    header("Location: stock.php?updated=" . urlencode($sku));
    exit;
}

$page_title = 'Editar Stock: ' . $product['nombre'];
$current_tab = 'stock';

include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md md:max-w-2xl px-4 space-y-4">
  
  <div class="flex items-center justify-between">
    <a href="stock.php" class="inline-flex items-center gap-1 text-xs font-bold text-[#7b2cbf] hover:underline">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span>
      Volver al Inventario
    </a>
    <span class="text-xs font-mono font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200">
      <?= htmlspecialchars($product['sku']) ?>
    </span>
  </div>

  <div class="bg-white rounded-3xl p-6 border border-[#f6e9ff] shadow-sm">
    <h2 class="font-rubik text-xl font-black text-[#211630]">
      Editar Ficha de Producción
    </h2>
    <p class="text-xs text-[#6f5092] mt-0.5">
      Los cambios se guardan directamente en la base de datos MySQL de XAMPP.
    </p>

    <form method="POST" action="editar_stock.php" class="mt-5 space-y-4">
      <input type="hidden" name="action" value="save_product">
      <input type="hidden" name="sku" value="<?= htmlspecialchars($product['sku']) ?>">

      <!-- Nombre -->
      <div>
        <label class="block text-xs font-bold text-[#211630] mb-1">Nombre de la Pieza Artesanal</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($product['nombre']) ?>" required class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
      </div>

      <!-- Tag y Talla -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Colección / Tag</label>
          <input type="text" name="tag" value="<?= htmlspecialchars($product['tag'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Dimensiones / Talla</label>
          <input type="text" name="talla" value="<?= htmlspecialchars($product['talla'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
      </div>

      <!-- Proveedor -->
      <div>
        <label class="block text-xs font-bold text-[#211630] mb-1">Hilado / Proveedor</label>
        <input type="text" name="proveedor" value="<?= htmlspecialchars($product['proveedor'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
      </div>

      <!-- Precios -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Precio Venta (COP)</label>
          <input type="number" name="precio_cop" value="<?= $product['precio_cop'] ?>" required class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Costo Insumos (COP)</label>
          <input type="number" name="costo_lana_cop" value="<?= $product['costo_lana_cop'] ?>" required class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
      </div>

      <!-- Stock Actual y Mínimo -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Stock Disponible en Bodega</label>
          <input type="number" name="stock_actual" value="<?= $product['stock_actual'] ?>" min="0" required class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Alerta Stock Mínimo</label>
          <input type="number" name="stock_minimo" value="<?= $product['stock_minimo'] ?>" min="0" required class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
      </div>

      <!-- Imagen URL -->
      <div>
        <label class="block text-xs font-bold text-[#211630] mb-1">URL de la Fotografía</label>
        <input type="text" name="imagen_url" value="<?= htmlspecialchars($product['imagen_url'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-2xl bg-[#faf5ff] border border-[#ebd2ff] text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
      </div>

      <!-- Botones de Acción -->
      <div class="pt-3 flex items-center justify-end gap-2.5">
        <a href="stock.php" class="px-4 py-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-700 transition-colors">
          Cancelar
        </a>
        <button type="submit" class="px-5 py-2.5 rounded-2xl bg-[#7b2cbf] hover:bg-[#6100a4] text-white text-xs font-bold shadow-md shadow-purple-900/20 active:scale-95 transition-all flex items-center gap-1.5">
          <span class="material-symbols-outlined text-[18px]">save</span>
          Guardar Cambios en MySQL
        </button>
      </div>

    </form>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
