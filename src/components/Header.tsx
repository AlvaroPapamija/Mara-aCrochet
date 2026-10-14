import React from 'react';
import { TabType, ScreenType, UserRole, UserAccount } from '../types';
import { BRAND_LOGO } from '../data/mockData';

interface HeaderProps {
  activeTab: TabType;
  activeScreen: ScreenType;
  userRole: UserRole;
  currentUser?: UserAccount | null;
  onToggleRole?: (role: UserRole) => void;
  screenTitle?: string;
  onBack?: () => void;
  onNotificationClick?: () => void;
  onProfileClick?: () => void;
  onOpenOptions?: () => void;
  onDownloadGuide?: () => void;
  onPrintGuide?: () => void;
  clientSubTab?: 'catalogo' | 'mis-pedidos';
  onChangeClientSubTab?: (subTab: 'catalogo' | 'mis-pedidos') => void;
  isCartOpen?: boolean;
  onToggleCart?: (open: boolean) => void;
  cartCount?: number;
  onChangeTab?: (tab: TabType) => void;
  unreadNotificationCount?: number;
  dbConnected?: boolean;
}

export const Header: React.FC<HeaderProps> = ({
  activeTab,
  activeScreen,
  userRole,
  currentUser,
  onToggleRole,
  screenTitle,
  onBack,
  onNotificationClick,
  onProfileClick,
  onOpenOptions,
  onDownloadGuide,
  onPrintGuide,
  clientSubTab = 'catalogo',
  onChangeClientSubTab,
  isCartOpen = false,
  onToggleCart,
  cartCount = 0,
  onChangeTab,
  unreadNotificationCount = 0,
  dbConnected,
}) => {
  const isSubScreen = activeScreen !== 'main';

  const getSubtitle = () => {
    if (userRole === 'client') {
      return 'CATÁLOGO & TIENDA';
    }
    switch (activeTab) {
      case 'panel':
        return 'PANEL DE CONTROL';
      case 'vouchers':
        return 'COMPROBANTES';
      case 'stock':
        return 'INVENTARIO STOCK';
      case 'pedidos':
        return 'PEDIDOS & ENVÍOS';
      case 'catalogo':
        return 'CATÁLOGO & TIENDA';
      default:
        return 'TALLER TEXTIL';
    }
  };

  const isCatalogoActive = activeTab === 'catalogo' && clientSubTab === 'catalogo' && !isCartOpen;
  const isRastrearActive = activeTab === 'catalogo' && clientSubTab === 'mis-pedidos' && !isCartOpen;
  const isCarritoActive = !!isCartOpen;

  const handleSelectCatalogo = () => {
    if (onChangeTab) onChangeTab('catalogo');
    if (onChangeClientSubTab) onChangeClientSubTab('catalogo');
    if (onToggleCart) onToggleCart(false);
  };

  const handleSelectRastrear = () => {
    if (onChangeTab) onChangeTab('catalogo');
    if (onChangeClientSubTab) onChangeClientSubTab('mis-pedidos');
    if (onToggleCart) onToggleCart(false);
  };

  const handleSelectCarrito = () => {
    if (onChangeTab) onChangeTab('catalogo');
    if (onToggleCart) onToggleCart(!isCartOpen);
  };

  return (
    <header className="fixed top-0 inset-x-0 z-40 bg-[#fff7ff]/95 backdrop-blur-xl border-b border-[#f6e9ff] shadow-[0_1px_12px_rgba(97,0,164,0.06)] pt-safe">
      <div className="max-w-md md:max-w-3xl lg:max-w-5xl mx-auto h-16 px-2.5 sm:px-4 flex items-center justify-between gap-1.5 sm:gap-3">
        {isSubScreen ? (
          <div className="flex items-center gap-2 sm:gap-2.5 min-w-0">
            <button
              aria-label="Volver atrás"
              className="w-10 h-10 -ml-1 flex items-center justify-center rounded-full text-[#211630] hover:text-[#6100a4] hover:bg-[#f6e9ff] active:scale-95 transition-all shrink-0"
              onClick={onBack}
              type="button"
            >
              <span className="material-symbols-outlined text-[24px]">arrow_back</span>
            </button>
            <div className="relative hidden sm:flex items-center justify-center w-10 h-10 rounded-2xl bg-white shadow-2xs border border-[#ebd2ff] p-0.5 shrink-0 overflow-hidden">
              <img
                alt="Maraña Crochet"
                className="w-full h-full object-contain scale-115"
                src={BRAND_LOGO}
                referrerPolicy="no-referrer"
              />
            </div>
            <h1 className="font-rubik text-[16px] sm:text-[17px] text-[#211630] truncate leading-tight tracking-tight font-bold pl-0.5">
              {screenTitle || 'Detalle'}
            </h1>
          </div>
        ) : (
          <div className="flex items-center gap-2.5 sm:gap-3 min-w-0 shrink-0">
            <div className="relative flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white shadow-2xs border border-[#ebd2ff] p-0.5 shrink-0 overflow-hidden group hover:border-[#deb7ff] transition-all">
              <img
                alt="Maraña"
                className="w-full h-full object-contain scale-115 transform group-hover:scale-125 transition-transform"
                src={BRAND_LOGO}
                referrerPolicy="no-referrer"
              />
            </div>
            <div className="flex flex-col truncate justify-center">
              <div className="flex items-center gap-1.5">
                <span className="font-rubik text-[14px] sm:text-[16px] text-[#7b2cbf] font-extrabold tracking-tight truncate leading-tight">
                  {userRole === 'admin' ? 'Maraña Admin' : 'Maraña'}
                </span>
                {dbConnected !== undefined && (
                  <span
                    className={`inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-semibold border ${
                      dbConnected
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : 'bg-amber-50 text-amber-700 border-amber-200'
                    }`}
                    title={dbConnected ? 'Base de datos MySQL (XAMPP) conectada' : 'Modo local sin conexión MySQL'}
                  >
                    <span className={`w-1.5 h-1.5 rounded-full ${dbConnected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'}`} />
                    <span className="hidden xs:inline">{dbConnected ? 'MySQL XAMPP' : 'Offline'}</span>
                  </span>
                )}
              </div>
              <span className="text-[9.5px] sm:text-[11px] text-[#6f5092] uppercase tracking-wider truncate leading-tight font-semibold hidden sm:block">
                {getSubtitle()}
              </span>
            </div>
          </div>
        )}

        {/* Top Navigation Buttons: Catálogo, Rastrear Pedido, Carrito (Exclusivos para clientes/tienda en la parte superior) */}
        {!isSubScreen && userRole !== 'admin' && currentUser?.role !== 'admin' && (
          <nav
            aria-label="Navegación principal de tienda"
            className="flex items-center gap-0.5 sm:gap-1.5 bg-[#f6e9ff]/80 p-0.5 sm:p-1 rounded-full border border-[#f0dbff] shadow-2xs"
          >
            {/* Catálogo Button */}
            <button
              onClick={handleSelectCatalogo}
              type="button"
              className={`flex items-center gap-1 px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-full text-[11px] sm:text-[12.5px] font-bold transition-all duration-150 active:scale-95 ${
                isCatalogoActive
                  ? 'bg-[#7b2cbf] text-white shadow-xs'
                  : 'text-[#4c4353] hover:text-[#7b2cbf] hover:bg-white/70'
              }`}
              title="Explorar Catálogo de Piezas"
            >
              <span
                className="material-symbols-outlined text-[16px] sm:text-[18px]"
                style={{
                  fontVariationSettings: isCatalogoActive ? "'FILL' 1" : "'FILL' 0",
                }}
              >
                storefront
              </span>
              <span className="leading-tight">Catálogo</span>
            </button>

            {/* Rastrear Pedido Button */}
            <button
              onClick={handleSelectRastrear}
              type="button"
              className={`flex items-center gap-1 px-2 sm:px-3.5 py-1 sm:py-1.5 rounded-full text-[11px] sm:text-[12.5px] font-bold transition-all duration-150 active:scale-95 ${
                isRastrearActive
                  ? 'bg-[#7b2cbf] text-white shadow-xs'
                  : 'text-[#4c4353] hover:text-[#7b2cbf] hover:bg-white/70'
              }`}
              title="Rastrear Pedido y Envíos"
            >
              <span
                className="material-symbols-outlined text-[16px] sm:text-[18px]"
                style={{
                  fontVariationSettings: isRastrearActive ? "'FILL' 1" : "'FILL' 0",
                }}
              >
                local_shipping
              </span>
              <span className="leading-tight">
                <span className="inline md:hidden">Rastrear</span>
                <span className="hidden md:inline">Rastrear Pedido</span>
              </span>
            </button>

            {/* Carrito Button */}
            <button
              onClick={handleSelectCarrito}
              type="button"
              className={`relative flex items-center gap-1 px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-full text-[11px] sm:text-[12.5px] font-bold transition-all duration-150 active:scale-95 ${
                isCarritoActive
                  ? 'bg-[#7b2cbf] text-white shadow-xs'
                  : 'text-[#4c4353] hover:text-[#7b2cbf] hover:bg-white/70'
              }`}
              title="Abrir Bolsa / Carrito de Compras"
            >
              <div className="relative flex items-center justify-center">
                <span
                  className="material-symbols-outlined text-[16px] sm:text-[18px]"
                  style={{
                    fontVariationSettings: isCarritoActive ? "'FILL' 1" : "'FILL' 0",
                  }}
                >
                  shopping_basket
                </span>
                {cartCount > 0 && (
                  <span
                    className={`absolute -top-1.5 -right-2.5 min-w-[15px] h-3.5 px-1 rounded-full text-[9px] font-extrabold flex items-center justify-center leading-none shadow-xs ${
                      isCarritoActive ? 'bg-[#ffdf9f] text-[#211630]' : 'bg-[#7b2cbf] text-white'
                    }`}
                  >
                    {cartCount}
                  </span>
                )}
              </div>
              <span className="leading-tight">Carrito</span>
            </button>
          </nav>
        )}

        <div className="flex items-center gap-0.5 sm:gap-1 shrink-0">
          {activeScreen === 'guia-despacho' && (
            <>
              <button
                aria-label="Descargar guía"
                className="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-full text-[#7b2cbf] hover:bg-[#f6e9ff] active:scale-95 transition-all"
                onClick={onDownloadGuide}
                type="button"
              >
                <span className="material-symbols-outlined text-[19px] sm:text-[20px]">download</span>
              </button>
              <button
                aria-label="Imprimir guía"
                className="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-full text-[#211630] hover:bg-[#f6e9ff] active:scale-95 transition-all"
                onClick={onPrintGuide}
                type="button"
              >
                <span className="material-symbols-outlined text-[19px] sm:text-[20px]">print</span>
              </button>
            </>
          )}

          <button
            aria-label="Notificaciones y alertas en el navegador"
            className="relative w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-full text-[#4c4353] hover:text-[#6100a4] active:bg-[#f6e9ff] transition-colors"
            onClick={onNotificationClick}
            type="button"
            title="Centro de Notificaciones en el Navegador"
          >
            <span className="material-symbols-outlined text-[20px] sm:text-[22px]">notifications</span>
            {unreadNotificationCount > 0 ? (
              <span className="absolute -top-0.5 -right-0.5 min-w-4 h-4 px-1 rounded-full bg-[#7b2cbf] text-white text-[9px] font-extrabold flex items-center justify-center ring-2 ring-white">
                {unreadNotificationCount > 9 ? '9+' : unreadNotificationCount}
              </span>
            ) : (
              <span className="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-[#7b2cbf] ring-2 ring-[#fff7ff] animate-pulse"></span>
            )}
          </button>

          {/* Botón de Opciones para configurar datos personales */}
          {currentUser && onOpenOptions && (
            <button
              aria-label="Opciones y modificar datos personales"
              className="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-full text-[#4c4353] hover:text-[#7b2cbf] hover:bg-[#f6e9ff] active:scale-95 transition-all"
              onClick={onOpenOptions}
              type="button"
              title="Opciones: cambiar datos personales"
            >
              <span className="material-symbols-outlined text-[20px] sm:text-[22px]">tune</span>
            </button>
          )}

          <button
            aria-label="Perfil y cuentas de usuario"
            className="relative flex items-center justify-center pl-0.5 focus:outline-none group"
            onClick={onProfileClick}
            type="button"
            title={currentUser ? `Cuenta: ${currentUser.name} (${currentUser.role})` : 'Iniciar Sesión'}
          >
            {currentUser ? (
              <img
                alt={currentUser.name}
                className={`w-7 h-7 sm:w-8 sm:h-8 rounded-full object-cover ring-2 transition-transform group-hover:scale-105 ${
                  currentUser.role === 'admin' ? 'ring-[#7b2cbf]' : 'ring-[#f9bd22]'
                }`}
                src={currentUser.avatarUrl}
                onError={(e) => {
                  e.currentTarget.src = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80';
                }}
              />
            ) : (
              <div className="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-[#f6e9ff] text-[#7b2cbf] flex items-center justify-center ring-2 ring-[#deb7ff]">
                <span className="material-symbols-outlined text-[18px]">person</span>
              </div>
            )}
            <span
              className={`absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ring-2 ring-[#fff7ff] ${
                currentUser?.role === 'admin'
                  ? 'bg-[#7b2cbf]'
                  : currentUser
                  ? 'bg-[#38b000]'
                  : 'bg-[#ba1a1a]'
              }`}
            ></span>
          </button>
        </div>
      </div>
    </header>
  );
};

