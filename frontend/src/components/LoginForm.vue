<template>
  <div class="card p-6 space-y-4">
    <h2 class="text-lg font-semibold">Sign In</h2>
    <form @submit.prevent="submit">
      <div class="space-y-2">
        <label class="text-sm text-gray-600">Email</label>
        <input v-model="email" type="email" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-accent/50" />
      </div>
      <div class="space-y-2">
        <label class="text-sm text-gray-600">Password</label>
        <input v-model="password" type="password" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-accent/50" />
      </div>
      <button type="submit" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary/90 transition">
        {{ loading ? 'Signing in...' : 'Login' }}
      </button>
      <p v-if="error" class="text-sm text-red-500 mt-2">{{ error }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { login, setAuthToken, fetchProfile } from '@/services/api';
import { initEcho } from '@/services/echo';

const emit = defineEmits(['authed']);

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

const submit = async () => {
  error.value = '';
  loading.value = true;
  try {
    const response = await login({ email: email.value, password: password.value });
    const token = response?.auth?.access_token ?? response?.access_token;
    const profile = await fetchProfile();
    initEcho(token);
    emit('authed', { token, profile });
  } catch (e) {
    error.value = e?.response?.data?.message || 'Login failed';
  } finally {
    loading.value = false;
  }
};
</script>
