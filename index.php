<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Boutique de Amigurumis';
$current_tab = 'catalogo';

// Procesar pedido desde la tienda vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerAddress = trim($_POST['customer_address'] ?? '');
    $customerCity = trim($_POST['customer_city'] ?? 'Neiva, Huila');
    $shippingMethod = $_POST['shipping_method'] ?? 'Servientrega Nacional Estándar';
    $paymentMethod = $_POST['payment_method'] ?? 'Bancolombia Transfer';
    $sku = $_POST['product_sku'] ?? '';
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if ($customerName && $sku) {
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE sku = ?");
        $stmtProd->execute([$sku]);
        $prod = $stmtProd->fetch();

        if ($prod) {
            $shippingCost = ($shippingMethod === 'Retiro en Taller') ? 0 : 14000;
            $itemsTotal = ((int)$prod['precio_cop']) * $quantity;
            $totalAmount = $itemsTotal + $shippingCost;

            $orderCount = (int) $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
            $orderId = 'MC-2024-' . (885 + $orderCount);
            $voucherId = 'VCH-00' . ($orderCount + 1);
            $approvalCode = '94' . rand(1000, 9999);
            $initials = strtoupper(substr($customerName, 0, 2));

            $pdo->beginTransaction();
            try {
                // 1. Insertar pedido
                $stmtOrder = $pdo->prepare("INSERT INTO pedidos 
                    (id, fecha, cliente_nombre, cliente_iniciales, cliente_telefono, cliente_ubicacion, cliente_direccion, estado, estado_label, transportista, costo_envio, guia, pago_verificado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'preparacion', 'En Taller · Esperando Validación', ?, ?, ?, 0)");
                
                $dateStr = 'Hoy · ' . date('h:i A');
                $stmtOrder->execute([
                    $orderId,
                    $dateStr,
                    $customerName,
                    $initials,
                    $customerPhone,
                    $customerCity,
                    $customerAddress,
                    $shippingMethod,
                    $shippingCost,
                    '9482-0198-' . rand(1000, 9999)
                ]);

                // 2. Insertar item
                $stmtItem = $pdo->prepare("INSERT INTO pedido_items (pedido_id, nombre, especificaciones, cantidad, precio, imagen_url, tag) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtItem->execute([
                    $orderId,
                    $prod['nombre'],
                    $prod['talla'] . ' · ' . $prod['proveedor'],
                    $quantity,
                    $prod['precio_cop'],
                    $prod['imagen_url'],
                    $prod['tag']
                ]);

                // 3. Insertar comprobante
                $stmtVoucher = $pdo->prepare("INSERT INTO comprobantes 
                    (id, pedido_id, tiempo_hace, cliente_nombre, cliente_telefono, cliente_direccion, metodo_envio, monto_total, monto_esperado, metodo_pago, cuenta_banco, codigo_aprobacion, timestamp_banco, estado, recibo_thumb_url, recibo_full_url)
                    VALUES (?, ?, 'Hace unos instantes', ?, ?, ?, ?, ?, ?, ?, 'Bancolombia 912-384729-10', ?, ?, 'pendiente', ?, ?)");
                
                $stmtVoucher->execute([
                    $voucherId,
                    $orderId,
                    $customerName,
                    $customerPhone,
                    $customerAddress,
                    $shippingMethod,
                    $totalAmount,
                    $totalAmount,
                    $paymentMethod,
                    $approvalCode,
                    $dateStr,
                    $prod['imagen_url'],
                    $prod['imagen_url']
                ]);

                // Descontar stock si hay disponible
                if ($prod['stock_actual'] >= $quantity) {
                    $newStock = $prod['stock_actual'] - $quantity;
                    $newCat = ($newStock === 0) ? 'agotado' : (($newStock <= $prod['stock_minimo']) ? 'critico' : 'optimo');
                    $updStock = $pdo->prepare("UPDATE productos SET stock_actual = ?, categoria = ? WHERE sku = ?");
                    $updStock->execute([$newStock, $newCat, $sku]);
                }

                $pdo->commit();
                header("Location: index.php?order_success=" . urlencode($orderId));
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $orderError = $e->getMessage();
            }
        }
    }
}

// Obtener productos desde MySQL
$products = $pdo->query("SELECT * FROM productos WHERE activo = 1 ORDER BY (stock_actual > 0) DESC, nombre ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md md:max-w-3xl lg:max-w-5xl px-4 space-y-6">
  
  <!-- Hero Banner Boutique -->
  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#211630] via-[#3c096c] to-[#6100a4] text-white p-6 sm:p-8 shadow-xl">
    <div class="max-w-xl space-y-2">
      <span class="inline-block text-[11px] font-extrabold tracking-widest uppercase text-purple-300 bg-white/10 px-3 py-1 rounded-full backdrop-blur-md">
        Boutique Artesanal Maraña
      </span>
      <h1 class="font-serif text-2xl sm:text-4xl font-bold leading-tight">
        Amigurumis de Colección Tejidos a Mano en Colombia
      </h1>
      <p class="text-xs sm:text-sm text-purple-200">
        Cada muñeco es una obra de arte única confeccionada con hilazas hipoalergénicas y tintes naturales en Neiva, Huila.
      </p>
    </div>
  </div>

  <!-- Barra de Filtros -->
  <div class="flex items-center justify-between gap-3">
    <div>
      <h2 class="font-rubik text-xl font-black text-[#211630]">Catálogo Disponible</h2>
      <p class="text-xs text-[#6f5092]">Existencias en tiempo real sincronizadas con MySQL</p>
    </div>
    <a href="panel.php" class="px-3.5 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-[#7b2cbf] text-xs font-bold border border-purple-200 transition-colors flex items-center gap-1">
      <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
      Modo Taller
    </a>
  </div>

  <!-- Cuadrícula de Productos -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($products as $p): ?>
      <div class="rounded-3xl bg-white border border-[#f6e9ff] shadow-xs hover:border-[#deb7ff] hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
        
        <div>
          <!-- Imagen del Producto -->
          <div class="relative h-56 bg-purple-50 overflow-hidden flex items-center justify-center">
            <img 
              src="<?= htmlspecialchars($p['imagen_url']) ?>" 
              alt="<?= htmlspecialchars($p['nombre']) ?>" 
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              onerror="this.src='https://images.unsplash.com/photo-1584992236310-6edddc08acff?w=400&fit=crop'"
            />
            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold shadow-xs <?= $p['stock_actual'] > 0 ? 'bg-white/90 text-purple-900 backdrop-blur-md' : 'bg-rose-600 text-white' ?>">
              <?= $p['stock_actual'] > 0 ? htmlspecialchars($p['tag']) : 'Agotado' ?>
            </span>
            <span class="absolute top-3 right-3 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[#211630]/80 text-white backdrop-blur-md">
              <?= htmlspecialchars($p['talla']) ?>
            </span>
          </div>

          <!-- Información -->
          <div class="p-4 space-y-1.5">
            <h3 class="font-rubik text-base font-bold text-[#211630] leading-snug">
              <?= htmlspecialchars($p['nombre']) ?>
            </h3>
            <p class="text-xs text-[#6f5092]">
              <?= htmlspecialchars($p['proveedor']) ?>
            </p>
            <div class="pt-2 flex items-baseline justify-between">
              <span class="font-rubik text-lg font-black text-[#7b2cbf]">
                <?= formatCOP($p['precio_cop']) ?>
              </span>
              <span class="text-[11px] font-bold <?= $p['stock_actual'] > 0 ? 'text-emerald-700' : 'text-rose-600' ?>">
                <?= $p['stock_actual'] > 0 ? "{$p['stock_actual']} disponibles" : "Por encargo" ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Botón Comprar / Reservar -->
        <div class="p-4 pt-0">
          <button 
            onclick="openCheckoutModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)"
            class="w-full py-2.5 rounded-2xl bg-[#7b2cbf] hover:bg-[#6100a4] text-white text-xs font-bold shadow-md shadow-purple-900/15 active:scale-95 transition-all flex items-center justify-center gap-1.5"
          >
            <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
            <?= $p['stock_actual'] > 0 ? 'Comprar Ahora' : 'Encargar Pieza' ?>
          </button>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

</div>

<!-- Modal de Checkout Rápido -->
<div id="checkout-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl max-w-md w-full p-6 border border-purple-200 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between border-b border-purple-100 pb-3">
      <h3 class="font-rubik text-base font-bold text-[#211630]">Confirmar Pedido</h3>
      <button onclick="closeCheckoutModal()" class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-500">
        &times;
      </button>
    </div>

    <!-- Resumen de Producto Seleccionado -->
    <div id="modal-product-summary" class="p-3 rounded-2xl bg-purple-50 flex items-center gap-3">
      <!-- Inyectado por JS -->
    </div>

    <form method="POST" action="index.php" class="space-y-3">
      <input type="hidden" name="action" value="place_order">
      <input type="hidden" id="modal-product-sku" name="product_sku" value="">

      <div>
        <label class="block text-xs font-bold text-[#211630] mb-1">Nombre Completo</label>
        <input type="text" name="customer_name" required placeholder="Ej: Camila Restrepo" class="w-full px-3.5 py-2 rounded-xl bg-[#faf5ff] border border-purple-200 text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Teléfono / WhatsApp</label>
          <input type="text" name="customer_phone" required placeholder="+57 318..." class="w-full px-3.5 py-2 rounded-xl bg-[#faf5ff] border border-purple-200 text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Cantidad</label>
          <input type="number" name="quantity" value="1" min="1" max="10" class="w-full px-3.5 py-2 rounded-xl bg-[#faf5ff] border border-purple-200 text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-[#211630] mb-1">Dirección de Entrega</label>
        <input type="text" name="customer_address" required placeholder="Calle, Barrio, Edificio, Apto" class="w-full px-3.5 py-2 rounded-xl bg-[#faf5ff] border border-purple-200 text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Ciudad / Municipio</label>
          <input type="text" name="customer_city" value="Neiva, Huila" class="w-full px-3.5 py-2 rounded-xl bg-[#faf5ff] border border-purple-200 text-xs font-medium focus:outline-none focus:border-[#7b2cbf]">
        </div>
        <div>
          <label class="block text-xs font-bold text-[#211630] mb-1">Método de Envío</label>
          <select name="shipping_method" class="w-full px-3 py-2 rounded-xl bg-[#faf5ff] border border-purple-200 text-xs font-medium">
            <option value="Servientrega Nacional Estándar">Servientrega (+$14.000)</option>
            <option value="Retiro en Taller">Retiro en Taller Maraña ($0)</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-[#211630] mb-1">Método de Pago</label>
        <select name="payment_method" class="w-full px-3 py-2 rounded-xl bg-[#faf5ff] border border-purple-200 text-xs font-medium">
          <option value="Bancolombia Transfer">Transferencia Bancolombia</option>
          <option value="Nequi Móvil">Nequi</option>
          <option value="Daviplata App">Daviplata</option>
        </select>
      </div>

      <div class="pt-2 flex items-center justify-end gap-2">
        <button type="button" onclick="closeCheckoutModal()" class="px-4 py-2 rounded-xl bg-gray-100 text-xs font-bold text-gray-700">
          Cancelar
        </button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-[#7b2cbf] hover:bg-[#6100a4] text-white text-xs font-bold shadow-md shadow-purple-900/20 active:scale-95 transition-all">
          Finalizar Pedido
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openCheckoutModal(prod) {
  document.getElementById('modal-product-sku').value = prod.sku;
  const summaryEl = document.getElementById('modal-product-summary');
  summaryEl.innerHTML = `
    <img src="${prod.imagen_url}" class="w-12 h-12 rounded-xl object-cover border border-purple-200 shrink-0">
    <div>
      <h4 class="font-bold text-xs text-[#211630]">${prod.nombre}</h4>
      <p class="text-[11px] text-purple-700 font-bold">$${Number(prod.precio_cop).toLocaleString('es-CO')} COP</p>
    </div>
  `;
  const modal = document.getElementById('checkout-modal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeCheckoutModal() {
  const modal = document.getElementById('checkout-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}
</script>

<?php if (isset($_GET['order_success'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    showToast('¡Pedido Recibido!', 'Tu pedido <?= htmlspecialchars($_GET['order_success']) ?> se guardó en MySQL y está en preparación.', 'verified');
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
