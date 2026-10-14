</main>

<?php include __DIR__ . '/bottom_nav.php'; ?>

<!-- Toast Notification Floating Widget -->
<div id="toast-container" class="fixed top-20 right-4 z-50 max-w-sm w-full pointer-events-none transition-all duration-300 transform translate-y-[-20px] opacity-0 flex items-start gap-3 p-4 rounded-2xl bg-[#211630] text-white shadow-xl border border-purple-800">
  <span id="toast-icon" class="material-symbols-outlined text-[24px] text-purple-300 shrink-0">check_circle</span>
  <div class="flex-1">
    <h4 id="toast-title" class="text-sm font-bold leading-tight">Éxito</h4>
    <p id="toast-message" class="text-xs text-purple-200 mt-0.5 leading-snug">Operación completada.</p>
  </div>
</div>

<script>
function showToast(title, message, icon = 'check_circle') {
  const container = document.getElementById('toast-container');
  const titleEl = document.getElementById('toast-title');
  const msgEl = document.getElementById('toast-message');
  const iconEl = document.getElementById('toast-icon');

  if (!container) return;
  titleEl.textContent = title;
  msgEl.textContent = message;
  iconEl.textContent = icon;

  container.classList.remove('opacity-0', 'translate-y-[-20px]', 'pointer-events-none');
  container.classList.add('opacity-100', 'translate-y-0');

  setTimeout(() => {
    container.classList.remove('opacity-100', 'translate-y-0');
    container.classList.add('opacity-0', 'translate-y-[-20px]', 'pointer-events-none');
  }, 3500);
}
</script>

</body>
</html>
