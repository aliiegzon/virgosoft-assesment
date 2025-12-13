<template>
  <div class="space-y-4">
    <!-- Wallet -->
    <div class="card p-6 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Wallet</h2>
        <button class="text-xs text-accent underline" @click="$emit('refresh')">Refresh All</button>
      </div>
      <div class="space-y-2">
        <div class="flex justify-between text-sm">
          <span class="text-muted">USD Balance</span>
          <span class="font-semibold">{{ profile.balance_usd ?? 0 }}</span>
        </div>
        <div class="space-y-1">
          <div class="text-sm text-muted">Assets</div>
          <div v-if="profile.assets?.length" class="space-y-1">
            <div v-for="asset in profile.assets" :key="asset.symbol" class="flex justify-between text-sm">
              <span class="font-medium">{{ asset.symbol }}</span>
              <span>{{ asset.amount }} <span class="text-muted">(locked {{ asset.locked_amount }})</span></span>
            </div>
          </div>
          <div v-else class="text-sm text-muted">No assets yet.</div>
        </div>
      </div>
    </div>

    <!-- Open Orders row -->
    <div class="card p-6 space-y-2">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">Open Orders</h3>
        <div class="flex items-center gap-2 text-sm">
          <label class="text-muted">Symbol</label>
          <select v-model="openOrdersSymbolLocal" class="border rounded px-2 py-1">
            <option value="">All</option>
            <option v-for="symbol in symbols" :key="symbol" :value="symbol">{{ symbol }}</option>
          </select>
        </div>
      </div>
      <div class="flex items-center justify-between text-xs text-muted">
        <div>Page {{ openOrdersPage }} / {{ openOrdersTotalPages || 1 }}</div>
        <div class="space-x-2">
          <button class="underline disabled:text-gray-400" :disabled="openOrdersPage === 1" @click="$emit('open-prev')">Prev</button>
          <button class="underline disabled:text-gray-400" :disabled="openOrdersPage === openOrdersTotalPages" @click="$emit('open-next')">Next</button>
        </div>
      </div>
      <div class="max-h-48 overflow-auto space-y-2">
        <div
          v-if="openOrdersOpen?.length"
          v-for="order in openOrdersOpen"
          :key="order.id"
          class="flex items-center justify-between text-sm border-b pb-2"
        >
          <div>
            <div class="font-medium">{{ order.side?.toUpperCase() }} {{ order.symbol }}</div>
            <div class="text-muted text-xs">
              Amount: {{ order.amount }} | Price: {{ order.price }} | Status: {{ statusLabel(order.status) }}
            </div>
          </div>
          <button class="text-red-500 text-xs underline" @click="$emit('cancel', order.id)">
            Cancel
          </button>
        </div>
        <div v-else class="text-sm text-muted">No open orders.</div>
      </div>
    </div>

    <div class="card p-6 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Orderbook</h2>
        <div class="flex items-center gap-2 text-sm">
          <label class="text-muted">Symbol</label>
          <select v-model="orderbookSymbolLocal" class="border rounded px-2 py-1">
            <option value="">All</option>
            <option v-for="symbol in symbols" :key="symbol" :value="symbol">{{ symbol }}</option>
          </select>
        </div>
      </div>
      <div class="flex items-center justify-between text-xs text-muted">
        <div>Page {{ orderbookPage }} / {{ orderbookTotalPages || 1 }}</div>
        <div class="space-x-2">
          <button class="underline disabled:text-gray-400" :disabled="orderbookPage === 1" @click="$emit('orderbook-prev')">Prev</button>
          <button class="underline disabled:text-gray-400" :disabled="orderbookPage === orderbookTotalPages" @click="$emit('orderbook-next')">Next</button>
        </div>
      </div>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <h3 class="text-sm font-semibold text-green-600 mb-2">Buys</h3>
          <div v-if="paginatedOrderbook.buys.length" class="space-y-1">
            <div v-for="order in paginatedOrderbook.buys" :key="order.id" class="flex justify-between text-sm">
              <span>{{ order.amount }} ({{ statusLabel(order.status) }}) ({{ order.symbol }})</span>
              <span class="font-medium">{{ order.price }}</span>
            </div>
          </div>
          <div v-else class="text-sm text-muted">No buys</div>
        </div>
        <div>
          <h3 class="text-sm font-semibold text-red-600 mb-2">Sells</h3>
          <div v-if="paginatedOrderbook.sells.length" class="space-y-1">
            <div v-for="order in paginatedOrderbook.sells" :key="order.id" class="flex justify-between text-sm">
              <span>{{ order.amount }} ({{ statusLabel(order.status) }}) ({{ order.symbol }})</span>
              <span class="font-medium">{{ order.price }}</span>
            </div>
          </div>
          <div v-else class="text-sm text-muted">No sells</div>
        </div>
      </div>
    </div>

    <div class="card p-6 space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Trades</h2>
        <div class="flex items-center gap-2 text-sm">
          <label class="text-muted">Symbol</label>
          <select v-model="tradesSymbolLocal" class="border rounded px-2 py-1">
            <option value="">All</option>
            <option v-for="symbol in symbols" :key="symbol" :value="symbol">{{ symbol }}</option>
          </select>
        </div>
      </div>
      <div class="flex items-center justify-between text-xs text-muted">
        <div>Page {{ tradesPage }} / {{ tradesTotalPages || 1 }}</div>
        <div class="space-x-2">
          <button class="underline disabled:text-gray-400" :disabled="tradesPage === 1" @click="$emit('trades-prev')">Prev</button>
          <button class="underline disabled:text-gray-400" :disabled="tradesPage === tradesTotalPages" @click="$emit('trades-next')">Next</button>
        </div>
      </div>
      <div class="space-y-2 max-h-60 overflow-auto">
        <div v-if="trades?.length" v-for="trade in trades" :key="trade.id" class="text-sm border-b pb-2">
          <div class="flex justify-between">
            <span class="font-medium">{{ trade.symbol }}</span>
            <span class="text-muted text-xs">Price: {{ trade.price }}</span>
          </div>
          <div class="text-xs text-muted">Amount: {{ trade.amount }} | Volume: {{ trade.volume_usd }} | Fee: {{ trade.fee_usd }}</div>
        </div>
        <div v-else class="text-sm text-muted">No trades yet.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  profile: { type: Object, default: () => ({}) },

  trades: { type: Array, default: () => [] },
  tradesPage: { type: Number, default: 1 },
  tradesTotalPages: { type: Number, default: 1 },
  tradesSymbol: { type: String, default: '' },

  orderbook: { type: Array, default: () => [] },
  orderbookPage: { type: Number, default: 1 },
  orderbookTotalPages: { type: Number, default: 1 },
  orderbookSymbol: { type: String, default: '' },

  openOrders: { type: Array, default: () => [] },
  openOrdersSymbol: { type: String, default: '' },
  openOrdersPage: { type: Number, default: 1 },
  openOrdersTotalPages: { type: Number, default: 1 },
});

const emit = defineEmits([
  'refresh',
  'cancel',
  'symbol-change',
  'open-symbol-change',
  'trades-symbol-change',
  'orderbook-next',
  'orderbook-prev',
  'trades-next',
  'trades-prev',
  'open-next',
  'open-prev',
]);
const symbols = ['BTC', 'ETH', 'USDT'];
const orderbookSymbolLocal = ref(props.orderbookSymbol || '');
const tradesSymbolLocal = ref(props.tradesSymbol || '');
const openOrdersSymbolLocal = ref(props.openOrdersSymbol || '');

const openOrdersOpen = computed(() =>
  (props.openOrders || []).filter((o) => Number(o?.status?.value ?? o?.status) === 1)
);

const bookSplit = computed(() => {
  const buys = [];
  const sells = [];
  (props.orderbook || []).forEach((o) => {
    if ((o.side || '').toLowerCase() === 'buy') buys.push(o);
    else sells.push(o);
  });
  return {
    buys,
    sells,
  };
});

const paginatedOrderbook = computed(() => bookSplit.value);

watch(
  () => props.orderbookSymbol,
  (val) => {
    orderbookSymbolLocal.value = val || '';
  }
);

watch(
  () => props.tradesSymbol,
  (val) => {
    tradesSymbolLocal.value = val || '';
  }
);

watch(
  () => props.openOrdersSymbol,
  (val) => {
    openOrdersSymbolLocal.value = val || '';
  }
);

watch(orderbookSymbolLocal, (val) => {
  emit('symbol-change', val);
});

watch(tradesSymbolLocal, (val) => {
  emit('trades-symbol-change', val);
});

watch(openOrdersSymbolLocal, (val) => {
  emit('open-symbol-change', val);
});

const statusLabel = (status) => {
  if (typeof status === 'object' && status?.label) return status.label;
  const map = { 1: 'OPEN', 2: 'FILLED', 3: 'CANCELLED' };
  return map[Number(status)] || status || '';
};

const valueOf = (order) => {
  const amount = Number(order.amount || 0);
  const price = Number(order.price || 0);
  return (amount * price).toFixed(8);
};
</script>
