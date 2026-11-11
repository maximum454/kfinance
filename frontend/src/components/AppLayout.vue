<template>
  <div class="layout">
    <header class="header" v-if="!route.meta.hideHeader">
      <button class="menu-btn" @click="menuOpen = !menuOpen">☰</button>
      <span class="title">Kfinance</span>
      <span class="balance" v-if="balance !== null">{{ balance.toLocaleString() }} ₸</span>
      <router-link to="/history" class="header-link">📋</router-link>
    </header>

    <nav class="menu" :class="{ open: menuOpen }">
      <div class="user-info" v-if="auth.user">
        <strong>{{ auth.user.name }}</strong>
        <small>{{ auth.user.email }}</small>
      </div>
      <router-link v-for="item in menuItems" :key="item.path" :to="item.path" @click="menuOpen = false">
        {{ item.icon }} {{ item.label }}
      </router-link>
      <hr />
      <a href="#" @click.prevent="handleLogout">🚪 Выйти</a>
    </nav>

    <main class="content" @click="menuOpen = false">
      <router-view />
    </main>

    <div class="overlay" v-if="menuOpen" @click="menuOpen = false" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { api } from '../api'

const route = useRoute()
const auth = useAuthStore()
const router = useRouter()
const menuOpen = ref(false)
const balance = ref(null)

const menuItems = [
  { path: '/', icon: '📊', label: 'Главная' },
  { path: '/accounts', icon: '🏦', label: 'Счета' },
  { path: '/categories', icon: '🏷️', label: 'Категории' },
  { path: '/tags', icon: '#️⃣', label: 'Теги' },
  { path: '/recurring', icon: '🔄', label: 'Регулярные' },
]

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

onMounted(async () => {
  if (!auth.user) await auth.fetchUser()
  try {
    const accounts = await api.getAccounts()
    balance.value = accounts.reduce((sum, a) => sum + (a.balance || 0), 0)
  } catch { /* ignore */ }
})
</script>

<style scoped>
.layout { min-height: 100vh; display: flex; flex-direction: column; }
.header {
  position: sticky; top: 0; z-index: 100;
  background: var(--primary); color: #fff; padding: 12px 16px;
  display: flex; align-items: center; gap: 12px;
}
.menu-btn { background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }
.title { flex: 1; font-weight: 600; font-size: 18px; }
.balance { font-weight: 700; font-size: 16px; text-align: center}
.header-link { color: #fff; text-decoration: none; font-size: 20px; padding: 4px; border-radius: 6px; }
.header-link:hover { background: rgba(255,255,255,.15); }
.menu {
  position: fixed; top: 0; left: -260px; width: 260px; height: 100vh;
  background: var(--surface); z-index: 200; transition: left .2s;
  padding: 20px; box-shadow: 2px 0 12px rgba(0,0,0,.1);
  display: flex; flex-direction: column; gap: 4px;
}
.menu.open { left: 0; }
.menu a {
  display: block; padding: 10px 12px; border-radius: 8px;
  text-decoration: none; color: var(--text); font-size: 15px;
}
.menu a:hover, .menu a.router-link-active { background: #ecfdf5; color: var(--primary); }
.menu hr { margin: 8px 0; border: none; border-top: 1px solid var(--border); }
.user-info { padding: 10px 12px; margin-bottom: 8px; }
.user-info strong { display: block; font-size: 16px; }
.user-info small { color: var(--text-secondary); font-size: 13px; }
.content { flex: 1; padding: 16px; max-width: 600px; margin: 0 auto; width: 100%; }
.overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 150;
}
</style>