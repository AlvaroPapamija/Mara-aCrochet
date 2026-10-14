import React, { useState, useEffect } from 'react';
import { TabType, ScreenType, Voucher, StockProduct, Order, UserRole, CartItem, UserAccount } from './types';
import { INITIAL_ORDERS, INITIAL_VOUCHERS, INITIAL_PRODUCTS } from './data/mockData';
import { Header } from './components/Header';
import { BottomNav } from './components/BottomNav';
import { Toast, ToastData } from './components/Toast';
import { ScannerModal } from './components/ScannerModal';
import { AuthUserModal, PRESET_ACCOUNTS } from './components/AuthUserModal';
import { NotificationCenterModal } from './components/NotificationCenterModal';
import {
  initNotificationService,
  getStoredNotifications,
  notifyOrderStatus,
} from './services/notificationService';
import { PanelView } from './views/PanelView';
import { VouchersView } from './views/VouchersView';
import { DetalleComprobanteView } from './views/DetalleComprobanteView';
import { StockView } from './views/StockView';
import { EditarStockView } from './views/EditarStockView';
import { PedidosView } from './views/PedidosView';
import { GuiaDespachoView } from './views/GuiaDespachoView';
import { CatalogoView } from './views/CatalogoView';
import {
  checkDatabaseStatus,
  getProductsFromApi,
  getOrdersFromApi,
  getVouchersFromApi,
  updateProductStockInApi,
  createOrderInApi,
  createVoucherInApi,
  updateVoucherStatusInApi,
  updateOrderInApi,
} from './services/apiService';

export default function App() {
  // Default to Client account so the initial experience is purely the public boutique catalog
  const [currentUser, setCurrentUser] = useState<UserAccount | null>(PRESET_ACCOUNTS[1]);
  const [userRole, setUserRole] = useState<UserRole>('client');
  const [activeTab, setActiveTab] = useState<TabType>('catalogo');
  const [activeScreen, setActiveScreen] = useState<ScreenType>('main');

  const [orders, setOrders] = useState<Order[]>(INITIAL_ORDERS);
  const [vouchers, setVouchers] = useState<Voucher[]>(INITIAL_VOUCHERS);
  const [products, setProducts] = useState<StockProduct[]>(INITIAL_PRODUCTS);

  const [cart, setCart] = useState<CartItem[]>([
    {
      product: INITIAL_PRODUCTS[0],
      quantity: 1,
      size: '22 cm',
    },
  ]);

  const [selectedVoucher, setSelectedVoucher] = useState<Voucher>(INITIAL_VOUCHERS[0]);
  const [selectedProduct, setSelectedProduct] = useState<StockProduct>(INITIAL_PRODUCTS[0]);
  const [selectedOrder, setSelectedOrder] = useState<Order>(INITIAL_ORDERS[0]);

  const [isScannerOpen, setIsScannerOpen] = useState(false);
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const [isNotificationCenterOpen, setIsNotificationCenterOpen] = useState(false);
  const [unreadNotificationsCount, setUnreadNotificationsCount] = useState(0);
  const [profileModalTab, setProfileModalTab] = useState<'access' | 'options' | 'login' | 'register'>('access');
  const [clientSubTab, setClientSubTab] = useState<'catalogo' | 'mis-pedidos'>('catalogo');
  const [isClientCartOpen, setIsClientCartOpen] = useState(false);

  // Inicializar Service Worker y sincronizar notificaciones no leídas
  useEffect(() => {
    initNotificationService();
    const updateUnread = () => {
      const items = getStoredNotifications();
      setUnreadNotificationsCount(items.filter((n) => !n.read).length);
    };
    updateUnread();

    const handleNotifUpdate = () => {
      updateUnread();
    };

    const handleNotifClicked = (e: Event) => {
      const customEvent = e as CustomEvent<{ orderId?: string }>;
      setActiveTab('catalogo');
      setClientSubTab('mis-pedidos');
      setActiveScreen('main');
      if (customEvent.detail?.orderId) {
        showToast(
          'Pedido Abierto',
          `Mostrando estado en tiempo real de #${customEvent.detail.orderId}`,
          'notifications_active'
        );
      }
    };

    window.addEventListener('marana:notifications-updated', handleNotifUpdate);
    window.addEventListener('marana:notification-clicked', handleNotifClicked);

    return () => {
      window.removeEventListener('marana:notifications-updated', handleNotifUpdate);
      window.removeEventListener('marana:notification-clicked', handleNotifClicked);
    };
  }, []);

  const handleOpenProfile = (tab: 'access' | 'options' = 'access') => {
    setProfileModalTab(tab);
    setIsProfileOpen(true);
  };

  const handleUpdateUser = (updatedAccount: UserAccount) => {
    setCurrentUser(updatedAccount);
  };

  const [toast, setToast] = useState<ToastData>({
    title: '',
    message: '',
    icon: '',
    visible: false,
  });

  const showToast = (title: string, message: string, icon = 'check_circle') => {
    setToast({ title, message, icon, visible: true });
    setTimeout(() => {
      setToast((prev) => ({ ...prev, visible: false }));
    }, 3500);
  };

  const [dbConnected, setDbConnected] = useState<boolean>(false);

  // Sincronizar datos iniciales con la base de datos MySQL en XAMPP
  useEffect(() => {
    async function syncDatabase() {
      const status = await checkDatabaseStatus();
      setDbConnected(status.connected);
      if (status.connected) {
        showToast(
          'MySQL (XAMPP) Conectado',
          `Base de datos '${status.database}' lista en localhost.`,
          'database'
        );
        const [apiProds, apiOrds, apiVouchs] = await Promise.all([
          getProductsFromApi(),
          getOrdersFromApi(),
          getVouchersFromApi(),
        ]);
        if (apiProds && apiProds.length > 0) setProducts(apiProds);
        if (apiOrds && apiOrds.length > 0) setOrders(apiOrds);
        if (apiVouchs && apiVouchs.length > 0) setVouchers(apiVouchs);
      }
    }
    syncDatabase();
  }, []);

  // Authentication handlers
  const handleLogin = (account: UserAccount) => {
    setCurrentUser(account);
    setUserRole(account.role);
    setActiveScreen('main');
    if (account.role === 'admin') {
      setActiveTab('panel');
      showToast(
        'Modo Taller Administrador',
        `Bienvenida, ${account.name}. Panel de control habilitado.`,
        'admin_panel_settings'
      );
    } else {
      setActiveTab('catalogo');
      showToast(
        'Bienvenida a Maraña',
        `Hola ${account.name}, explorando el catálogo artesanal.`,
        'storefront'
      );
    }
  };

  const handleLogout = () => {
    setCurrentUser(null);
    setUserRole('client');
    setActiveTab('catalogo');
    setActiveScreen('main');
  };

  // Switch roles between Admin and Client (Admin only)
  const handleToggleRole = (newRole: UserRole) => {
    if (newRole === 'admin' && currentUser?.role !== 'admin') {
      setIsProfileOpen(true);
      showToast(
        'Acceso Restringido a Taller',
        'Inicia sesión con cuenta de Administradora para ingresar.',
        'lock'
      );
      return;
    }

    setUserRole(newRole);
    setActiveScreen('main');
    if (newRole === 'client') {
      setActiveTab('catalogo');
    } else {
      setActiveTab('panel');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Switch tabs
  const handleTabChange = (tab: TabType) => {
    setActiveTab(tab);
    setActiveScreen('main');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Cart operations
  const handleAddToCart = (product: StockProduct, quantity = 1) => {
    setCart((prev) => {
      const existing = prev.find((item) => item.product.sku === product.sku);
      if (existing) {
        return prev.map((item) =>
          item.product.sku === product.sku
            ? { ...item, quantity: item.quantity + quantity }
            : item
        );
      }
      return [...prev, { product, quantity, size: product.size }];
    });
    showToast(
      'Pieza Añadida a la Bolsa',
      `${product.name} lista para compra.`,
      'shopping_bag'
    );
  };

  const handleUpdateCartQty = (sku: string, delta: number) => {
    setCart((prev) =>
      prev
        .map((item) => {
          if (item.product.sku === sku) {
            const newQty = item.quantity + delta;
            return newQty > 0 ? { ...item, quantity: newQty } : null;
          }
          return item;
        })
        .filter((item): item is CartItem => item !== null)
    );
  };

  const handleRemoveFromCart = (sku: string) => {
    setCart((prev) => prev.filter((item) => item.product.sku !== sku));
    showToast('Pieza Removida', 'Elemento eliminado de la bolsa de compras', 'delete');
  };

  // Order placement from catalog
  const handlePlaceOrder = (orderData: {
    customerName: string;
    customerPhone: string;
    customerAddress: string;
    customerLocation: string;
    specialNote: string;
    shippingMethod: string;
    shippingCost: number;
    paymentMethod: string;
    receiptUrl: string;
    items: {
      name: string;
      specs: string;
      quantity: number;
      price: number;
      imageUrl: string;
    }[];
    totalAmount: number;
  }) => {
    const orderNum = 885 + orders.length;
    const newOrderId = `MC-2024-${orderNum}`;
    const newVoucherId = `VCH-00${vouchers.length + 1}`;
    const newApprovalCode = `94${Math.floor(1000 + Math.random() * 9000)}`;

    const initials = orderData.customerName
      .split(' ')
      .map((n) => n[0])
      .join('')
      .substring(0, 2)
      .toUpperCase();

    // Create new order
    const newOrder: Order = {
      id: newOrderId,
      customerName: orderData.customerName,
      customerInitials: initials,
      customerLocation: orderData.customerLocation,
      customerAddress: orderData.customerAddress,
      customerPhone: orderData.customerPhone,
      date: 'Hoy · Recién creado',
      status: 'preparacion',
      statusLabel: 'En Taller · Esperando Validación',
      carrier: 'Servientrega Nacional',
      shippingCost: orderData.shippingCost,
      trackingNumber: `9482-0198-${Math.floor(1000 + Math.random() * 9000)}`,
      paymentVerified: false,
      readyTime: '2-3 días hábiles',
      specialNote: orderData.specialNote || 'Dedicatoria caligrafiada en papel de fibra con sello artesanal',
      items: orderData.items,
    };

    // Create new voucher for admin validation queue
    const newVoucher: Voucher = {
      id: newVoucherId,
      orderId: newOrderId,
      timeAgo: 'Hace unos instantes',
      customerName: orderData.customerName,
      customerPhone: orderData.customerPhone,
      customerAddress: orderData.customerAddress,
      shippingMethod: orderData.shippingMethod,
      totalAmount: orderData.totalAmount,
      expectedAmount: orderData.totalAmount,
      paymentMethod: orderData.paymentMethod,
      bankAccount: 'Ahorros Bancolombia 912-384729-10 (Maraña Crochet)',
      approvalCode: newApprovalCode,
      bankTimestamp: 'Hoy · Recién transferido',
      status: 'pendiente',
      receiptThumbUrl: orderData.receiptUrl,
      receiptFullUrl: orderData.receiptUrl,
      itemsSummary: orderData.items.map((it) => ({
        name: `${it.quantity}x ${it.name}`,
        tag: it.specs,
      })),
      itemsDetail: orderData.items,
      internalNote: `Comprobante ingresado por tienda digital. Total transferido: $${orderData.totalAmount.toLocaleString('es-CO')} COP.`,
    };

    setOrders([newOrder, ...orders]);
    setVouchers([newVoucher, ...vouchers]);
    setCart([]);

    // Persistir nuevo pedido y comprobante en MySQL (XAMPP)
    createOrderInApi(newOrder);
    createVoucherInApi(newVoucher);

    // Notificar al navegador de la clienta
    notifyOrderStatus(
      newOrder,
      '¡Recibimos tu pedido! En breve validaremos tu comprobante y comenzaremos el tejido en Neiva.'
    );
  };

  const handleUpdateOrder = (updatedOrder: Order) => {
    setOrders((prev) => prev.map((o) => (o.id === updatedOrder.id ? updatedOrder : o)));
    if (selectedOrder.id === updatedOrder.id) {
      setSelectedOrder(updatedOrder);
    }
    updateOrderInApi(updatedOrder);
  };

  // Open sub-screens
  const handleOpenVoucherDetail = (voucher: Voucher) => {
    setSelectedVoucher(voucher);
    setActiveScreen('detalle-comprobante');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleOpenProductEdit = (product: StockProduct) => {
    setSelectedProduct(product);
    setActiveScreen('editar-stock');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleOpenShippingGuide = (order: Order) => {
    setSelectedOrder(order);
    setActiveScreen('guia-despacho');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Actions
  const handleQuickApprove = (voucherId: string) => {
    setVouchers((prev) =>
      prev.map((v) => (v.id === voucherId ? { ...v, status: 'aprobado' } : v))
    );
    const v = vouchers.find((item) => item.id === voucherId);
    if (v) {
      setOrders((prev) =>
        prev.map((o) => {
          if (o.id === v.orderId) {
            const updated: Order = {
              ...o,
              status: 'listo',
              paymentVerified: true,
              statusLabel: 'Listo Despacho · Empacado',
            };
            notifyOrderStatus(updated);
            return updated;
          }
          return o;
        })
      );
    }
    showToast(
      'Comprobante Validado',
      'Pago registrado con éxito. Pedido listo para preparación.',
      'verified'
    );
    // Sincronizar en base de datos MySQL (XAMPP)
    updateVoucherStatusInApi(voucherId, 'aprobado');
  };

  const handleApproveVoucher = (voucherId: string) => {
    handleQuickApprove(voucherId);
  };

  const handleRejectVoucher = (voucherId: string, _reason: string) => {
    setVouchers((prev) =>
      prev.map((v) => (v.id === voucherId ? { ...v, status: 'rechazado' } : v))
    );
    updateVoucherStatusInApi(voucherId, 'rechazado', _reason);
  };

  const handleUpdateStockQty = (sku: string, newQty: number) => {
    setProducts((prev) =>
      prev.map((p) => {
        if (p.sku === sku) {
          const category =
            newQty === 0
              ? 'agotado'
              : newQty <= p.minStockAlert
              ? 'critico'
              : 'optimo';
          return {
            ...p,
            currentStock: newQty,
            category,
            statusLabel:
              newQty === 0
                ? 'Agotado'
                : newQty <= p.minStockAlert
                ? `Stock Mínimo alcanzado: ${newQty} / ${p.minStockAlert}`
                : `Stock Óptimo (${newQty} unidades)`,
          };
        }
        return p;
      })
    );
  };

  const handleSaveStockFromEditor = (sku: string, newStock: number) => {
    handleUpdateStockQty(sku, newStock);
    // Sincronizar stock en MySQL (XAMPP)
    updateProductStockInApi(sku, newStock);
  };

  const handleMarkDispatched = (orderId: string) => {
    setOrders((prev) =>
      prev.map((o) => {
        if (o.id === orderId) {
          const updated: Order = {
            ...o,
            status: 'transito',
            statusLabel: 'En Tránsito',
            trackingStatus: 'En camino a centro de distribución (Recolectado por Servientrega)',
          };
          notifyOrderStatus(updated);
          return updated;
        }
        return o;
      })
    );
  };

  const handleScanResult = (code: string) => {
    const matchOrder = orders.find(
      (o) =>
        o.id.toLowerCase() === code.toLowerCase() ||
        (o.trackingNumber && o.trackingNumber.includes(code))
    );
    if (matchOrder) {
      handleOpenShippingGuide(matchOrder);
      showToast('Pedido Localizado', `Se abrió la guía para #${matchOrder.id}`, 'qr_code_2');
      return;
    }

    const matchVoucher = vouchers.find(
      (v) =>
        v.orderId.toLowerCase() === code.toLowerCase() ||
        v.approvalCode.toLowerCase() === code.toLowerCase()
    );
    if (matchVoucher) {
      handleOpenVoucherDetail(matchVoucher);
      showToast('Comprobante Localizado', `Referencia #${matchVoucher.approvalCode}`, 'receipt');
      return;
    }

    showToast('Código Escaneado', `Identificador: ${code} registrado en sistema`, 'qr_code');
  };

  // Counts for Badges
  const pendingVouchersCount = vouchers.filter((v) => v.status === 'pendiente').length;
  const criticalStockCount = products.filter(
    (p) => p.category === 'critico' || p.category === 'agotado'
  ).length;
  const cartTotalCount = cart.reduce((sum, item) => sum + item.quantity, 0);

  const getScreenTitle = () => {
    switch (activeScreen) {
      case 'detalle-comprobante':
        return 'Detalle Comprobante';
      case 'editar-stock':
        return 'Editar Pieza Stock';
      case 'guia-despacho':
        return 'Guía y Etiqueta De Despacho';
      default:
        return undefined;
    }
  };

  return (
    <div className="min-h-screen bg-[#fff7ff] text-[#211630] flex flex-col items-center">
      {/* Universal Toast feedback */}
      <Toast toast={toast} />

      {/* Barcode & QR Scanner Modal */}
      <ScannerModal
        isOpen={isScannerOpen}
        onClose={() => setIsScannerOpen(false)}
        onScanResult={handleScanResult}
      />

      {/* Top Header with Navigation Buttons and User Account */}
      <Header
        activeTab={activeTab}
        activeScreen={activeScreen}
        userRole={userRole}
        currentUser={currentUser}
        dbConnected={dbConnected}
        onToggleRole={handleToggleRole}
        screenTitle={getScreenTitle()}
        onBack={() => setActiveScreen('main')}
        clientSubTab={clientSubTab}
        onChangeClientSubTab={(sub) => {
          setActiveTab('catalogo');
          setClientSubTab(sub);
          setIsClientCartOpen(false);
        }}
        isCartOpen={isClientCartOpen}
        onToggleCart={setIsClientCartOpen}
        cartCount={cartTotalCount}
        onChangeTab={handleTabChange}
        unreadNotificationCount={unreadNotificationsCount}
        onNotificationClick={() => setIsNotificationCenterOpen(true)}
        onProfileClick={() => handleOpenProfile('access')}
        onOpenOptions={() => handleOpenProfile('options')}
        onDownloadGuide={() =>
          showToast(
            'Descarga Iniciada',
            `Guía Servientrega #${selectedOrder.trackingNumber || '9482-0198-4820'} guardada en PDF.`,
            'download'
          )
        }
        onPrintGuide={() =>
          showToast(
            'Imprimiendo Etiqueta',
            'Enviado a impresora térmica de taller (Zebra ZD421).',
            'print'
          )
        }
      />

      {/* Main Container */}
      <main className="w-full pt-16 flex-1 flex flex-col items-center">
        {/* Sub-screens */}
        {activeScreen === 'detalle-comprobante' && (
          <DetalleComprobanteView
            voucher={selectedVoucher}
            onBack={() => setActiveScreen('main')}
            onApprove={handleApproveVoucher}
            onReject={handleRejectVoucher}
            onShowToast={showToast}
          />
        )}

        {activeScreen === 'editar-stock' && (
          <EditarStockView
            product={selectedProduct}
            onBack={() => setActiveScreen('main')}
            onSaveStock={handleSaveStockFromEditor}
            onShowToast={showToast}
          />
        )}

        {activeScreen === 'guia-despacho' && (
          <GuiaDespachoView
            order={selectedOrder}
            onBack={() => setActiveScreen('main')}
            onMarkDispatched={handleMarkDispatched}
            onShowToast={showToast}
          />
        )}

        {/* Main Screens */}
        {activeScreen === 'main' && (
          <>
            {activeTab === 'panel' && (
              <PanelView
                onNavigateTab={handleTabChange}
                onOpenProductEdit={handleOpenProductEdit}
                onShowToast={showToast}
                products={products}
              />
            )}

            {activeTab === 'vouchers' && (
              <VouchersView
                vouchers={vouchers}
                onOpenVoucherDetail={handleOpenVoucherDetail}
                onQuickApprove={handleQuickApprove}
                onShowToast={showToast}
              />
            )}

            {activeTab === 'stock' && (
              <StockView
                products={products}
                onOpenProductEdit={handleOpenProductEdit}
                onUpdateStockQty={handleUpdateStockQty}
                onShowToast={showToast}
              />
            )}

            {activeTab === 'pedidos' && (
              <PedidosView
                orders={orders}
                onOpenShippingGuide={handleOpenShippingGuide}
                onOpenScanner={() => setIsScannerOpen(true)}
                onShowToast={showToast}
                onUpdateOrder={handleUpdateOrder}
              />
            )}

            {activeTab === 'catalogo' && (
              <CatalogoView
                products={products}
                cart={cart}
                orders={orders}
                currentUser={currentUser}
                activeClientTab={clientSubTab}
                onChangeClientTab={setClientSubTab}
                isCartOpen={isClientCartOpen}
                onToggleCart={setIsClientCartOpen}
                onAddToCart={handleAddToCart}
                onUpdateCartQty={handleUpdateCartQty}
                onRemoveFromCart={handleRemoveFromCart}
                onPlaceOrder={handlePlaceOrder}
                onSwitchToAdmin={() => handleToggleRole('admin')}
                onShowToast={showToast}
                onOpenNotificationsModal={() => setIsNotificationCenterOpen(true)}
                onUpdateOrder={handleUpdateOrder}
              />
            )}
          </>
        )}
      </main>

      {/* Bottom Navigation Bar (Solo visible en administración de taller; clientes usan navegación superior) */}
      {activeScreen === 'main' && userRole === 'admin' && currentUser?.role === 'admin' && (
        <BottomNav
          activeTab={activeTab}
          onChangeTab={handleTabChange}
          userRole={userRole}
          currentUser={currentUser}
          clientSubTab={clientSubTab}
          onChangeClientSubTab={(sub) => {
            setActiveTab('catalogo');
            setClientSubTab(sub);
            setIsClientCartOpen(false);
          }}
          isCartOpen={isClientCartOpen}
          onToggleCart={setIsClientCartOpen}
          onToggleRole={handleToggleRole}
          pendingVouchersCount={pendingVouchersCount}
          criticalStockCount={criticalStockCount}
          cartCount={cartTotalCount}
          onOpenCart={() => {
            setActiveTab('catalogo');
            setIsClientCartOpen(true);
          }}
          onOpenClientOrders={() => {
            setActiveTab('catalogo');
            setClientSubTab('mis-pedidos');
            setIsClientCartOpen(false);
          }}
        />
      )}

      {/* Users and Authentication Modal (Top Right Avatar) */}
      <AuthUserModal
        isOpen={isProfileOpen}
        currentUser={currentUser}
        initialTab={profileModalTab}
        onClose={() => setIsProfileOpen(false)}
        onLogin={handleLogin}
        onLogout={handleLogout}
        onUpdateUser={handleUpdateUser}
        onShowToast={showToast}
      />

      {/* Browser Notification Center Modal */}
      <NotificationCenterModal
        isOpen={isNotificationCenterOpen}
        onClose={() => setIsNotificationCenterOpen(false)}
        orders={orders}
        onSelectOrder={() => {
          setActiveTab('catalogo');
          setClientSubTab('mis-pedidos');
          setActiveScreen('main');
        }}
        onShowToast={showToast}
        onUpdateOrder={handleUpdateOrder}
      />
    </div>
  );
}
