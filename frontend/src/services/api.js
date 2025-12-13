import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || 'http://localhost/api',
});

let authToken = null;

export function setAuthToken(token) {
  authToken = token;
  if (token) {
    api.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    delete api.defaults.headers.common.Authorization;
  }
}

export async function login(credentials) {
  const { data } = await api.post('/login', credentials);
  const token = data?.auth?.access_token ?? data?.access_token ?? null;
  if (token) {
    setAuthToken(token);
  }
  return data;
}

export async function logout() {
  await api.post('/logout');
  setAuthToken(null);
}

export async function fetchProfile() {
  const { data } = await api.get('/profile');
  return data?.data ?? data;
}

export async function fetchOpenOrders(symbol, userId, extraParams = {}) {
  const params = {
    'filter[status]': 1,
    ...(symbol ? { 'filter[symbol]': symbol } : {}),
    ...(userId ? { 'filter[user_id]': userId } : {}),
    ...extraParams,
  };
  const { data } = await api.get('/orders', { params });
  return data;
}

export async function fetchOrderHistory(symbol, extraParams = {}) {
  const params = {
    ...(symbol ? { 'filter[symbol]': symbol } : {}),
    ...extraParams,
  };
  const { data } = await api.get('/orders', { params });
  return data;
}

export async function fetchTrades(symbol, extraParams = {}) {
    const params = {
        ...(symbol ? { 'filter[symbol]': symbol } : {}),
        ...extraParams,
    };
    const { data } = await api.get('/trades', { params });
    return data;
}

export async function placeOrder(payload) {
  const { data } = await api.post('/orders', payload);
  return data?.data ?? data;
}

export async function cancelOrder(id) {
  const { data } = await api.post(`/orders/${id}/cancel`);
  return data?.data ?? data;
}

export default api;
