import { Order, Voucher, StockProduct, UserAccount } from '../types';
import { INITIAL_ORDERS, INITIAL_VOUCHERS, INITIAL_PRODUCTS } from '../data/mockData';

// Determina dinámicamente la URL base de la API PHP en XAMPP
const getApiBaseUrl = (): string => {
  if (typeof window !== 'undefined') {
    // Si la app está abierta directamente en XAMPP (ej. http://localhost/marana-crochet/)
    const path = window.location.pathname;
    if (path.includes('/marana-crochet')) {
      return '/marana-crochet/api';
    }
    // Si está corriendo en el puerto de Vite (ej. localhost:3000) o raíz de Apache
    if (window.location.port && window.location.port !== '80') {
      return 'http://localhost/marana-crochet/api';
    }
  }
  return '/api';
};

const API_BASE = getApiBaseUrl();

export interface ApiStatus {
  connected: boolean;
  database: string;
  server: string;
  counts?: {
    productos: number;
    pedidos: number;
    comprobantes: number;
    usuarios: number;
  };
  error?: string;
}

/**
 * Verifica la conexión con MySQL en XAMPP
 */
export async function checkDatabaseStatus(): Promise<ApiStatus> {
  try {
    const res = await fetch(`${API_BASE}/status.php`, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    return {
      connected: data.success === true,
      database: data.database || 'marana_crochet',
      server: data.server || 'MySQL (XAMPP)',
      counts: data.counts,
    };
  } catch (err: any) {
    console.warn('[API XAMPP] No se pudo conectar a MySQL:', err.message);
    return {
      connected: false,
      database: 'marana_crochet',
      server: 'Desconectado',
      error: err.message,
    };
  }
}

/**
 * Obtener productos desde MySQL (con fallback a mockData)
 */
export async function getProductsFromApi(): Promise<StockProduct[]> {
  try {
    const res = await fetch(`${API_BASE}/products.php`, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (json.success && Array.isArray(json.data) && json.data.length > 0) {
      return json.data;
    }
  } catch (e) {
    console.warn('[API XAMPP] Fallback a productos locales:', e);
  }
  return INITIAL_PRODUCTS;
}

/**
 * Actualizar stock de un producto en MySQL
 */
export async function updateProductStockInApi(sku: string, currentStock: number): Promise<boolean> {
  try {
    const res = await fetch(`${API_BASE}/products.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ sku, currentStock }),
    });
    const json = await res.json();
    return json.success === true;
  } catch (e) {
    console.error('[API XAMPP] Error al actualizar stock:', e);
    return false;
  }
}

/**
 * Obtener pedidos desde MySQL (con fallback a mockData)
 */
export async function getOrdersFromApi(): Promise<Order[]> {
  try {
    const res = await fetch(`${API_BASE}/orders.php`, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (json.success && Array.isArray(json.data) && json.data.length > 0) {
      return json.data;
    }
  } catch (e) {
    console.warn('[API XAMPP] Fallback a pedidos locales:', e);
  }
  return INITIAL_ORDERS;
}

/**
 * Guardar nuevo pedido en MySQL
 */
export async function createOrderInApi(order: Order): Promise<boolean> {
  try {
    const res = await fetch(`${API_BASE}/orders.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(order),
    });
    const json = await res.json();
    return json.success === true;
  } catch (e) {
    console.error('[API XAMPP] Error al crear pedido en MySQL:', e);
    return false;
  }
}

/**
 * Actualizar pedido en MySQL
 */
export async function updateOrderInApi(order: Partial<Order> & { id: string }): Promise<boolean> {
  try {
    const res = await fetch(`${API_BASE}/orders.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(order),
    });
    const json = await res.json();
    return json.success === true;
  } catch (e) {
    console.error('[API XAMPP] Error al actualizar pedido en MySQL:', e);
    return false;
  }
}

/**
 * Obtener comprobantes desde MySQL (con fallback a mockData)
 */
export async function getVouchersFromApi(): Promise<Voucher[]> {
  try {
    const res = await fetch(`${API_BASE}/vouchers.php`, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (json.success && Array.isArray(json.data) && json.data.length > 0) {
      return json.data;
    }
  } catch (e) {
    console.warn('[API XAMPP] Fallback a comprobantes locales:', e);
  }
  return INITIAL_VOUCHERS;
}

/**
 * Crear comprobante en MySQL
 */
export async function createVoucherInApi(voucher: Voucher): Promise<boolean> {
  try {
    const res = await fetch(`${API_BASE}/vouchers.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(voucher),
    });
    const json = await res.json();
    return json.success === true;
  } catch (e) {
    console.error('[API XAMPP] Error al crear comprobante en MySQL:', e);
    return false;
  }
}

/**
 * Actualizar estado de comprobante en MySQL
 */
export async function updateVoucherStatusInApi(
  id: string,
  status: 'pendiente' | 'aprobado' | 'rechazado',
  internalNote?: string
): Promise<boolean> {
  try {
    const res = await fetch(`${API_BASE}/vouchers.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status, internalNote }),
    });
    const json = await res.json();
    return json.success === true;
  } catch (e) {
    console.error('[API XAMPP] Error al actualizar comprobante en MySQL:', e);
    return false;
  }
}

/**
 * Obtener lista de usuarios desde MySQL
 */
export async function getUsersFromApi(): Promise<UserAccount[]> {
  try {
    const res = await fetch(`${API_BASE}/users.php`, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      return json.data;
    }
  } catch (e) {
    console.warn('[API XAMPP] Error obteniendo usuarios:', e);
  }
  return [];
}
