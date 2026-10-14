<?php
if (!isset($page_title)) $page_title = 'Maraña Crochet Atelier';
if (!isset($current_tab)) $current_tab = 'panel';

$user = $_SESSION['user'] ?? [
    'name' => 'Maraña Admin',
    'role' => 'admin',
    'roleTitle' => 'Artesana & Fundadora',
    'avatarUrl' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> | Maraña Crochet</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            marana: {
              50: '#faf5ff',
              100: '#f3e8ff',
              200: '#e9d5ff',
              300: '#d8b4fe',
              400: '#c084fc',
              500: '#a855f7',
              600: '#9333ea',
              700: '#7b2cbf',
              800: '#6100a4',
              900: '#3c096c',
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            rubik: ['"Rubik"', 'sans-serif'],
            serif: ['"Playfair Display"', 'serif'],
          }
        }
      }
    }
  </script>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Rubik:wght@500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Material Symbols Outlined -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #fff7ff;
      color: #211630;
      -webkit-tap-highlight-color: transparent;
    }
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      line-height: 1;
    }
    .material-symbols-outlined.fill-1 {
      font-variation-settings: 'FILL' 1;
    }
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }
    .no-scrollbar {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
  </style>
</head>
<body class="min-h-screen flex flex-col items-center">

<!-- Top Header Navigation -->
<header class="fixed top-0 inset-x-0 z-40 bg-[#fff7ff]/95 backdrop-blur-xl border-b border-[#f6e9ff] shadow-[0_1px_12px_rgba(97,0,164,0.06)]">
  <div class="max-w-md md:max-w-3xl lg:max-w-5xl mx-auto h-16 px-3 sm:px-4 flex items-center justify-between gap-2">
    
    <!-- Branding / Logo -->
    <a href="panel.php" class="flex items-center gap-2.5 shrink-0 group">
      <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-white shadow-xs border border-[#ebd2ff] p-0.5 shrink-0 overflow-hidden group-hover:border-[#deb7ff] transition-all flex items-center justify-center">
        <img alt="Maraña" class="w-full h-full object-contain scale-110 group-hover:scale-125 transition-transform" src="logo.png" onerror="this.src='https://images.unsplash.com/photo-1584992236310-6edddc08acff?w=100&fit=crop'" />
      </div>
      <div class="flex flex-col truncate justify-center">
        <div class="flex items-center gap-1.5">
          <span class="font-rubik text-[15px] sm:text-[17px] text-[#7b2cbf] font-extrabold tracking-tight">
            Maraña Crochet
          </span>
          <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>PHP + MySQL</span>
          </span>
        </div>
        <span class="text-[9.5px] sm:text-[11px] text-[#6f5092] uppercase tracking-wider truncate font-semibold">
          <?= htmlspecialchars($user['roleTitle']) ?>
        </span>
      </div>
    </a>

    <!-- Desktop Navigation Links -->
    <nav class="hidden md:flex items-center gap-1 bg-[#f6e9ff]/70 p-1 rounded-full border border-[#f0dbff]">
      <a href="panel.php" class="px-3.5 py-1.5 rounded-full text-[12.5px] font-bold transition-all <?= $current_tab === 'panel' ? 'bg-[#7b2cbf] text-white shadow-xs' : 'text-[#4c4353] hover:text-[#7b2cbf] hover:bg-white/70' ?>">
        Panel
      </a>
      <a href="stock.php" class="px-3.5 py-1.5 rounded-full text-[12.5px] font-bold transition-all <?= $current_tab === 'stock' ? 'bg-[#7b2cbf] text-white shadow-xs' : 'text-[#4c4353] hover:text-[#7b2cbf] hover:bg-white/70' ?>">
        Stock
      </a>
      <a href="pedidos.php" class="px-3.5 py-1.5 rounded-full text-[12.5px] font-bold transition-all <?= $current_tab === 'pedidos' ? 'bg-[#7b2cbf] text-white shadow-xs' : 'text-[#4c4353] hover:text-[#7b2cbf] hover:bg-white/70' ?>">
        Pedidos
      </a>
      <a href="vouchers.php" class="px-3.5 py-1.5 rounded-full text-[12.5px] font-bold transition-all <?= $current_tab === 'vouchers' ? 'bg-[#7b2cbf] text-white shadow-xs' : 'text-[#4c4353] hover:text-[#7b2cbf] hover:bg-white/70' ?>">
        Comprobantes
      </a>
      <a href="index.php" class="px-3.5 py-1.5 rounded-full text-[12.5px] font-bold transition-all <?= $current_tab === 'catalogo' ? 'bg-[#7b2cbf] text-white shadow-xs' : 'text-[#4c4353] hover:text-[#7b2cbf] hover:bg-white/70' ?>">
        Ver Catálogo
      </a>
    </nav>

    <!-- User / Profile Pill -->
    <div class="flex items-center gap-2">
      <div class="flex items-center gap-2 px-2.5 py-1 rounded-full bg-white border border-[#ebd2ff] shadow-2xs">
        <img src="<?= htmlspecialchars($user['avatarUrl']) ?>" alt="Avatar" class="w-7 h-7 rounded-full object-cover border border-[#c084fc]" />
        <span class="text-[12px] font-bold text-[#211630] hidden sm:inline"><?= htmlspecialchars($user['name']) ?></span>
      </div>
      <a href="index.php" class="p-2 rounded-full hover:bg-[#f6e9ff] text-[#7b2cbf] transition-colors" title="Boutique Online">
        <span class="material-symbols-outlined text-[22px]">storefront</span>
      </a>
    </div>

  </div>
</header>

<main class="w-full pt-20 pb-24 flex-1 flex flex-col items-center">
