<template>
  <div class="card p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">Place Order</h2>
      <span class="text-xs text-muted">Fee: 1.5%</span>
    </div>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="text-sm text-gray-600">Symbol</label>
        <select v-model="form.symbol" class="mt-1 w-full border rounded-lg px-3 py-2">
          <option v-for="symbol in symbols" :key="symbol" :value="symbol">{{ symbol }}</option>
        </select>
      </div>
      <div>
        <label class="text-sm text-gray-600">Side</label>
        <div class="mt-1 grid grid-cols-2 gap-2">
          <button
            type="button"
            :class="['py-2 rounded-lg border', form.side === 'buy' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-white']"
            @click="form.side = 'buy'"
          >
            Buy
          </button>
          <button
            type="button"
            :class="['py-2 rounded-lg border', form.side === 'sell' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-white']"
            @click="form.side = 'sell'"
          >
            Sell
          </button>
        </div>
      </div>
      <div>
        <label class="text-sm text-gray-600">Amount</label>
        <input v-model.number="form.amount" type="number" step="0.00000001" min="0" required class="mt-1 w-full border rounded-lg px-3 py-2" />
      </div>
      <div class="text-sm text-gray-600">
        Price (fixed): <span class="font-semibold">{{ currentPrice }}</span>
      </div>
      <div class="text-sm text-gray-600">
        Est. Cost (incl. fee): <span class="font-semibold">{{ estimatedCost }}</span>
      </div>
      <button type="submit" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary/90 transition" :disabled="loading">
        {{ loading ? 'Placing...' : 'Place Order' }}
      </button>
      <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
      <p v-if="success" class="text-sm text-green-600">{{ success }}</p>
    </form>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
  onSubmit: {
    type: Function,
    required: true,
  },
});

const emit = defineEmits(['placed']);

const symbols = ['BTC', 'ETH', 'USDT'];
const prices = {
  BTC: '90370.24',
  ETH: '3091.86',
  USDT: '1.00',
};

const form = reactive({
  symbol: 'BTC',
  side: 'buy',
  amount: 0.001,
});

const loading = ref(false);
const error = ref('');
const success = ref('');

const currentPrice = computed(() => prices[form.symbol]);
const estimatedCost = computed(() => {
  const volume = Number(form.amount || 0) * Number(currentPrice.value || 0);
  const fee = volume * 0.015;
  return (volume + fee).toFixed(2);
});

watch(() => form.symbol, () => {
  error.value = '';
  success.value = '';
});

const submit = async () => {
  error.value = '';
  success.value = '';
  loading.value = true;
  try {
    const payload = {
      symbol: form.symbol,
      side: form.side,
      amount: form.amount.toString(),
    };
    const res = await props.onSubmit(payload);
    success.value = 'Order placed!';
    emit('placed', res);
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to place order';
  } finally {
    loading.value = false;
  }
};
</script>
