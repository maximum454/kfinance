<template>
  <div class="auth-page">
    <div class="auth-card">
      <h1>Kfinance</h1>
      <p class="subtitle">Вход</p>
      <form @submit.prevent="submit">
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="password" type="password" placeholder="Пароль" required />
        <p class="error" v-if="error">{{ error }}</p>
        <button type="submit" :disabled="loading">{{ loading ? '...' : 'Войти' }}</button>
      </form>
      <p class="link">Нет аккаунта? <router-link to="/register">Регистрация</router-link></p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login({ email: email.value, password: password.value })
    router.push('/')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
.auth-card { background: var(--surface); padding: 32px; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.08); width: 100%; max-width: 360px; }
.auth-card h1 { font-size: 28px; color: var(--primary); margin-bottom: 4px; }
.subtitle { color: var(--text-secondary); margin-bottom: 20px; }
form { display: flex; flex-direction: column; gap: 12px; }
input { padding: 12px; border: 1px solid var(--border); border-radius: 10px; outline: none; }
input:focus { border-color: var(--primary); }
button { padding: 12px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
button:disabled { opacity: .6; }
.error { color: var(--danger); font-size: 14px; }
.link { margin-top: 12px; font-size: 14px; color: var(--text-secondary); text-align: center; }
.link a { color: var(--primary); text-decoration: none; }
</style>