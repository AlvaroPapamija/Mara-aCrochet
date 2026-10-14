<?php
if (!isset($current_tab)) $current_tab = 'panel';
?>
<!-- Mobile Bottom Navigation Bar -->
<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-[#fff7ff]/95 backdrop-blur-xl border-t border-[#f6e9ff] shadow-[0_-4px_20px_rgba(97,0,164,0.05)] pb-safe">
  <div class="max-w-md mx-auto h-16 px-2 flex items-center justify-around">
    
    <!-- Panel -->
    <a href="panel.php" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-2xl transition-all <?= $current_tab === 'panel' ? 'text-[#7b2cbf]' : 'text-[#6f5092] hover:text-[#7b2cbf]' ?>">
      <span class="material-symbols-outlined text-[22px] <?= $current_tab === 'panel' ? 'fill-1' : '' ?>">dashboard</span>
      <span class="text-[10px] font-bold mt-0.5">Panel</span>
    </a>

    <!-- Stock -->
    <a href="stock.php" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-2xl transition-all <?= $current_tab === 'stock' ? 'text-[#7b2cbf]' : 'text-[#6f5092] hover:text-[#7b2cbf]' ?>">
      <span class="material-symbols-outlined text-[22px] <?= $current_tab === 'stock' ? 'fill-1' : '' ?>">inventory_2</span>
      <span class="text-[10px] font-bold mt-0.5">Stock</span>
    </a>

    <!-- Pedidos -->
    <a href="pedidos.php" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-2xl transition-all <?= $current_tab === 'pedidos' ? 'text-[#7b2cbf]' : 'text-[#6f5092] hover:text-[#7b2cbf]' ?>">
      <span class="material-symbols-outlined text-[22px] <?= $current_tab === 'pedidos' ? 'fill-1' : '' ?>">local_shipping</span>
      <span class="text-[10px] font-bold mt-0.5">Pedidos</span>
    </a>

    <!-- Comprobantes -->
    <a href="vouchers.php" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-2xl transition-all <?= $current_tab === 'vouchers' ? 'text-[#7b2cbf]' : 'text-[#6f5092] hover:text-[#7b2cbf]' ?>">
      <span class="material-symbols-outlined text-[22px] <?= $current_tab === 'vouchers' ? 'fill-1' : '' ?>">receipt_long</span>
      <span class="text-[10px] font-bold mt-0.5">Vouchers</span>
    </a>

    <!-- Catálogo -->
    <a href="index.php" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-2xl transition-all <?= $current_tab === 'catalogo' ? 'text-[#7b2cbf]' : 'text-[#6f5092] hover:text-[#7b2cbf]' ?>">
      <span class="material-symbols-outlined text-[22px] <?= $current_tab === 'catalogo' ? 'fill-1' : '' ?>">storefront</span>
      <span class="text-[10px] font-bold mt-0.5">Tienda</span>
    </a>

  </div>
</nav>
