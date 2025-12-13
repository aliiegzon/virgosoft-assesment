<template>
  <div class="min-h-screen max-w-5xl mx-auto py-10 px-4 space-y-6">
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">VirgoSoft Exchange</h1>
        <p class="text-sm text-muted">Limit orders, live updates via Pusher.</p>
      </div>
      <button v-if="authed" class="text-sm text-red-500 underline" @click="handleLogout">Logout</button>
    </header>

    <LoginForm v-if="!authed" @authed="onAuthed" />

    <div v-else class="grid lg:grid-cols-3 gap-4">
      <div class="lg:col-span-1 space-y-4">
        <OrderForm @placed="onPlaced" :on-submit="handleSubmitOrder" />
      </div>
      <div class="lg:col-span-2 space-y-4">
        <OrdersOverview
          :profile="profile"
          :open-orders="openOrders"
          :open-orders-symbol="openOrdersSymbolFilter"
          :orderbook="orderbook"
          :trades="trades"
          :orderbook-symbol="orderbookSymbolFilter"
          :trades-symbol="tradesSymbolFilter"
          :open-orders-page="openOrdersPage"
          :open-orders-total-pages="openOrdersTotalPages"
          :orderbook-page="orderbookPage"
          :orderbook-total-pages="orderbookTotalPages"
          :trades-page="tradesPage"
          :trades-total-pages="tradesTotalPages"
          @refresh="refreshAll"
          @cancel="cancelOrderById"
          @open-symbol-change="changeOpenSymbol"
          @symbol-change="changeSymbol"
          @trades-symbol-change="changeTradesSymbol"
          @open-next="openNext"
          @open-prev="openPrev"
          @orderbook-next="orderbookNext"
          @orderbook-prev="orderbookPrev"
          @trades-next="tradesNext"
          @trades-prev="tradesPrev"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import LoginForm from '@/components/LoginForm.vue';
import OrderForm from '@/components/OrderForm.vue';
import OrdersOverview from '@/components/OrdersOverview.vue';
import {
    cancelOrder,
    fetchOpenOrders,
    fetchOrderHistory,
    fetchProfile,
    fetchTrades,
    logout as apiLogout,
    placeOrder as apiPlaceOrder,
    setAuthToken,
} from '@/services/api';
import {getEcho, initEcho, leaveChannel} from '@/services/echo';

const SYMBOLS = ['BTC', 'ETH', 'USDT'];

const authed = ref(false);
const profile = ref({});
const openOrders = ref([]);
const orderbook = ref([]);
const trades = ref([]);
const orderbookSymbolFilter = ref('');
const tradesSymbolFilter = ref('');
const openOrdersSymbolFilter = ref('');
const currentUserId = ref(null);
const orderbookPage = ref(1);
const orderbookTotalPages = ref(1);
const tradesPage = ref(1);
const tradesTotalPages = ref(1);
const openOrdersPage = ref(1);
const openOrdersTotalPages = ref(1);
const perPage = 15;

const subscribeToEvents = () => {
    const echo = getEcho();
    console.log('[Echo] subscribeToEvents called. userId =', currentUserId.value, 'echo =', !!echo);

    if (!echo || !currentUserId.value) {
        console.warn('[Echo] Cannot subscribe yet - echo or userId missing');
        return;
    }

    const channelName = `user.${currentUserId.value}`;
    console.log('[Echo] subscribing to private channel', channelName);

    echo.private(channelName)
        .listen('.OrderMatched', (data) => {
            console.log('[Echo] OrderMatched (private):', data);
            applyOrderMatched(data);
        })
        .listen('.OrderPlaced', (data) => {
            console.log('[Echo] OrderPlaced (private):', data);
            applyOrderPlaced(data);
        })
        .listen('.OrderCancelled', (data) => {
            console.log('[Echo] OrderCancelled (private):', data);
            applyOrderCancelled(data);
        });

    SYMBOLS.forEach((sym) => {
        const obChannel = `orderbook.${sym}`;
        const trChannel = `trades.${sym}`;
        console.log('[Echo] subscribing to public channels', obChannel, trChannel);

        echo.channel(obChannel)
            .listen('.OrderPlaced', (data) => {
                console.log('[Echo] OrderPlaced (orderbook):', sym, data);
                applyOrderPlaced(data);
            })
            .listen('.OrderCancelled', (data) => {
                console.log('[Echo] OrderCancelled (orderbook):', sym, data);
                applyOrderCancelled(data);
            })
            .listen('.OrderMatched', (data) => {
                console.log('[Echo] OrderMatched (orderbook):', sym, data);
                applyOrderMatched(data);
            });

        echo.channel(trChannel)
            .listen('.OrderMatched', (data) => {
                console.log('[Echo] OrderMatched (trades):', sym, data);
                applyOrderMatched(data);
            });
    });
};

const onAuthed = async ({ token, profile: profileData }) => {
    console.log('[Auth] onAuthed received:', { token, profileData });

    authed.value = true;
    setAuthToken(token);

    let effectiveProfile = profileData;

    // If login didn't send user id, fetch profile from API
    if (!effectiveProfile || !effectiveProfile.id) {
        try {
            const fetched = await fetchProfile();
            console.log('[Auth] fetched profile from API:', fetched);
            effectiveProfile = fetched;
        } catch (e) {
            console.error('[Auth] Failed to fetch profile after login', e);
            effectiveProfile = profileData || {};
        }
    }

    profile.value = effectiveProfile;
    currentUserId.value = effectiveProfile.id;
    console.log('[Auth] currentUserId set to', currentUserId.value);

    initEcho(token);
    subscribeToEvents();

    await refreshAll();
};

const refreshAll = async () => {
  profile.value = await fetchProfile();
  currentUserId.value = profile.value?.id;
  openOrdersPage.value = 1;
  orderbookPage.value = 1;
  tradesPage.value = 1;
  await Promise.allSettled([refreshOpen(), refreshOrderbook(), refreshTrades()]);
};

const refreshOrderbook = async () => {
  try {
    const res = await fetchOrderHistory(orderbookSymbolFilter.value, {
      page: orderbookPage.value,
      per_page: perPage,
    });
    const collection = res?.data ?? res;
    const meta = res?.meta ?? res?.data?.meta ?? null;
    const pagination = meta?.pagination_data ?? meta?.pagination ?? meta ?? {};

    if (Array.isArray(collection?.data)) {
      orderbook.value = collection.data;
    } else if (Array.isArray(collection)) {
      orderbook.value = collection;
    } else {
      orderbook.value = [];
    }

    orderbookTotalPages.value = pagination?.last_page || pagination?.lastPage || 1;
  } catch (e) {
    console.error('Orderbook fetch failed', e);
    orderbook.value = [];
    orderbookTotalPages.value = 1;
  }
};

const refreshOpen = async () => {
  try {
    const res = await fetchOpenOrders(openOrdersSymbolFilter.value, currentUserId.value, {
      page: openOrdersPage.value,
      per_page: perPage,
    });
    const collection = res?.data ?? res;
    const meta = res?.meta ?? res?.data?.meta ?? null;
    const pagination = meta?.pagination_data ?? meta?.pagination ?? meta ?? {};

    if (Array.isArray(collection)) {
      openOrders.value = collection;
    } else if (Array.isArray(collection?.data)) {
      openOrders.value = collection.data;
    } else {
      openOrders.value = [];
    }

    openOrdersTotalPages.value = pagination?.last_page || pagination?.lastPage || 1;
  } catch (e) {
    console.error('Open orders fetch failed', e);
    openOrders.value = [];
    openOrdersTotalPages.value = 1;
  }
};

const refreshTrades = async () => {
  try {
    const res = await fetchTrades(tradesSymbolFilter.value, {
      page: tradesPage.value,
      per_page: perPage,
    });
    const collection = res?.data ?? res;
    const meta = res?.meta ?? res?.data?.meta ?? null;
    const pagination = meta?.pagination_data ?? meta?.pagination ?? meta ?? {};

    if (Array.isArray(collection?.data)) {
      trades.value = collection.data;
    } else if (Array.isArray(collection)) {
      trades.value = collection;
    } else {
      trades.value = [];
    }

    tradesTotalPages.value = pagination?.last_page || pagination?.lastPage || 1;
  } catch (e) {
    console.error('Trades fetch failed', e);
    trades.value = [];
    tradesTotalPages.value = 1;
  }
};

const onPlaced = () => {
  // No-op: handled locally in submitOrder
};

const cancelOrderById = async (id) => {
    const order = openOrders.value.find((o) => o.id === id);
    if (!order) return;

    await cancelOrder(id);

    openOrders.value = openOrders.value.filter((o) => o.id !== id);

    orderbook.value = (orderbook.value || []).map((o) =>
        o.id === id
            ? { ...o, status: { value: 3, label: 'CANCELLED' } }
            : o
    );
};

const changeSymbol = async (symbol) => {
  orderbookSymbolFilter.value = symbol;
  orderbookPage.value = 1;
  openOrdersPage.value = 1;
  await Promise.all([refreshOrderbook(), refreshOpen()]);
};

const changeTradesSymbol = async (symbol) => {
  tradesSymbolFilter.value = symbol;
  tradesPage.value = 1;
  await refreshTrades();
};

const changeOpenSymbol = async (symbol) => {
  openOrdersSymbolFilter.value = symbol;
  openOrdersPage.value = 1;
  await refreshOpen();
};

const handleLogout = async () => {
  await apiLogout();
  setAuthToken(null);
  if (currentUserId.value) {
    leaveChannel(`user.${currentUserId.value}`);
  }
  authed.value = false;
  profile.value = {};
  openOrders.value = [];
  orderbook.value = [];
  trades.value = [];
};

onMounted(() => {
  // Optionally restore token from storage here
});

const placeOrder = async (payload) => {
    return await apiPlaceOrder(payload);
};

const handleSubmitOrder = async (payload) => {
  return placeOrder(payload);
};

const applyOrderMatched = (data) => {
    const payload = data?.payload ?? data;
    if (!payload) return;

    // --- 1. Sync wallet balances for buyer/seller if it's us ---
    const updateUser = (userPayload) => {
        if (!userPayload || String(userPayload.id) !== String(currentUserId.value)) return;
        if (!profile.value) return;

        profile.value.balance_usd = userPayload.balance ?? profile.value.balance_usd;

        if (userPayload.asset) {
            const existing = (profile.value.assets || []).find(
                (a) => a.symbol === userPayload.asset.symbol,
            );

            if (existing) {
                existing.amount = userPayload.asset.amount;
                existing.locked_amount = userPayload.asset.locked_amount;
            } else {
                profile.value.assets = [
                    ...(profile.value.assets || []),
                    {
                        symbol: userPayload.asset.symbol,
                        amount: userPayload.asset.amount,
                        locked_amount: userPayload.asset.locked_amount,
                    },
                ];
            }
        }
    };

    updateUser(payload.buyer);
    updateUser(payload.seller);

    const updatedOrders = [payload.buy_order, payload.sell_order].filter(Boolean);
    if (!updatedOrders.length) return;

    // --- 2. Remove matched orders from *open* orders ---
    updatedOrders.forEach((updated) => {
        const idx = openOrders.value.findIndex(
            (o) => String(o.id) === String(updated.id),
        );
        if (idx !== -1) {
            openOrders.value.splice(idx, 1);
        }
    });

    // --- 3. Mark them FILLED (or update data) inside orderbook ---
    const updatesById = new Map(
        updatedOrders.map((u) => [String(u.id), u]),
    );

    // Update if the order already exists in the current page
    orderbook.value = (orderbook.value || []).map((o) => {
        const updated = updatesById.get(String(o.id));
        if (!updated) return o;

        return {
            ...o,
            ...updated,
            status: updated.status ?? o.status,
            locked_value: updated.locked_value ?? o.locked_value,
        };
    });

    // If an updated order is NOT in the current list, prepend it
    updatedOrders.forEach((u) => {
        const exists = orderbook.value.some((o) => String(o.id) === String(u.id));
        const matchesFilter =
            !orderbookSymbolFilter.value || orderbookSymbolFilter.value === u.symbol;

        if (!exists && matchesFilter) {
            orderbook.value = [
                {
                    id: u.id,
                    symbol: u.symbol,
                    side: u.side,
                    price: u.price,
                    amount: u.amount,
                    status: u.status,
                    locked_value: u.locked_value,
                },
                ...orderbook.value,
            ];
        }
    });

    // --- 4. Trades list (unchanged) ---
    if (payload.trade) {
        const exists = trades.value.some(
            (t) => String(t.id) === String(payload.trade.id),
        );
        if (!exists) {
            const matchesTradeFilter =
                !tradesSymbolFilter.value || tradesSymbolFilter.value === payload.trade.symbol;

            if (matchesTradeFilter) {
                trades.value = [payload.trade, ...trades.value];
            }
        }
    }
};

const orderbookNext = async () => {
  if (orderbookPage.value < orderbookTotalPages.value) {
    orderbookPage.value += 1;
    await refreshOrderbook();
  }
};

const orderbookPrev = async () => {
  if (orderbookPage.value > 1) {
    orderbookPage.value -= 1;
    await refreshOrderbook();
  }
};

const tradesNext = async () => {
  if (tradesPage.value < tradesTotalPages.value) {
    tradesPage.value += 1;
    await refreshTrades();
  }
};

const tradesPrev = async () => {
  if (tradesPage.value > 1) {
    tradesPage.value -= 1;
    await refreshTrades();
  }
};

const openNext = async () => {
  if (openOrdersPage.value < openOrdersTotalPages.value) {
    openOrdersPage.value += 1;
    await refreshOpen();
  }
};

const openPrev = async () => {
  if (openOrdersPage.value > 1) {
    openOrdersPage.value -= 1;
    await refreshOpen();
  }
};

// Handle OrderPlaced payload
const applyOrderPlaced = (data) => {
  const payload = data?.payload ?? data;
  if (!payload?.order) return;
  const order = payload.order;
  const userPayload = payload.user;

  // Wallet update if current user
  if (userPayload?.id && String(userPayload.id) === String(currentUserId.value) && profile.value) {
    profile.value.balance_usd = userPayload.balance ?? profile.value.balance_usd;
    if (userPayload.asset) {
      const existing = (profile.value.assets || []).find((a) => a.symbol === userPayload.asset.symbol);
      if (existing) {
        existing.amount = userPayload.asset.amount;
        existing.locked_amount = userPayload.asset.locked_amount;
      } else {
        profile.value.assets = [...(profile.value.assets || []), userPayload.asset];
      }
    }
  }

  // Open orders (only if this user's order and open)
  const statusVal = order?.status?.value ?? order?.status;
  if (statusVal === 1 && String(order.user_id ?? userPayload?.id ?? '') === String(currentUserId.value)) {
    const exists = openOrders.value.some((o) => String(o.id) === String(order.id));
    const matchesFilter = !openOrdersSymbolFilter.value || openOrdersSymbolFilter.value === order.symbol;
    if (!exists && matchesFilter) {
      openOrders.value = [order, ...openOrders.value];
    }
  }

  // Orderbook list
  const existsOb = orderbook.value.some((o) => String(o.id) === String(order.id));
  const matchesObFilter = !orderbookSymbolFilter.value || orderbookSymbolFilter.value === order.symbol;
  if (!existsOb && matchesObFilter) {
    orderbook.value = [order, ...orderbook.value];
  }
};

// Handle OrderCancelled payload
const applyOrderCancelled = (data) => {
    const payload = data?.payload ?? data;
    if (!payload?.order) return;

    const order = payload.order;
    const userPayload = payload.user;

    // 1) Wallet update if current user
    if (userPayload?.id && String(userPayload.id) === String(currentUserId.value) && profile.value) {
        profile.value.balance_usd = userPayload.balance ?? profile.value.balance_usd;

        if (userPayload.asset) {
            const existing = (profile.value.assets || []).find(
                (a) => a.symbol === userPayload.asset.symbol,
            );
            if (existing) {
                existing.amount = userPayload.asset.amount;
                existing.locked_amount = userPayload.asset.locked_amount;
            }
        }
    }

    // 2) Remove from OPEN orders (that list should only show status=OPEN)
    openOrders.value = (openOrders.value || []).filter(
        (o) => String(o.id) !== String(order.id),
    );

    // 3) In the ORDERBOOK we want to KEEP it and mark as CANCELLED
    let found = false;

    orderbook.value = (orderbook.value || []).map((o) => {
        if (String(o.id) !== String(order.id)) return o;
        found = true;

        return {
            ...o,
            ...order,
            status: order.status ?? { value: 3, label: 'CANCELLED' },
            locked_value: order.locked_value ?? o.locked_value,
        };
    });

    // If it wasn't on the current page but matches the symbol filter, prepend it
    if (!found) {
        const matchesFilter =
            !orderbookSymbolFilter.value || orderbookSymbolFilter.value === order.symbol;

        if (matchesFilter) {
            orderbook.value = [
                {
                    id: order.id,
                    symbol: order.symbol,
                    side: order.side,
                    price: order.price,
                    amount: order.amount,
                    status: order.status ?? { value: 3, label: 'CANCELLED' },
                    locked_value: order.locked_value,
                },
                ...orderbook.value,
            ];
        }
    }
};
</script>
