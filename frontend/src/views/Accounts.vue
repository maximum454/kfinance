<template>
  <div class="mgmt">
    <PageHeader title="Счета" />
    <form @submit.prevent="addAc" class="add-form">
      <input v-model="newName" placeholder="Название" required />
      <select v-model="newCurr"><option value="KZT">KZT</option><option value="USD">USD</option><option value="RUB">RUB</option></select>
      <button>+</button>
    </form>
    <div v-for="a in accs" :key="a.id" class="item">
      <span>{{ a.icon || '🏦' }} {{ a.name }}</span>
      <span class="bal">{{ Number(a.balance).toLocaleString() }} {{ a.currency }}</span>
      <button class="del" @click="del(a.id)">✕</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import PageHeader from '../components/PageHeader.vue'
const accs = ref([])
const newName = ref('')
const newCurr = ref('KZT')
async function load() { accs.value = await api.getAccounts() }
async function addAc() { await api.createAccount({ name: newName.value, currency: newCurr.value }); newName.value = ''; load() }
async function del(id) { await api.deleteAccount(id); load() }
onMounted(load)
</script>
<style scoped>
.mgmt { display: flex; flex-direction: column; gap: 8px; }
.add-form { display: flex; gap: 8px; }
.add-form input, .add-form select { flex: 1; padding: 8px; border: 1px solid var(--border); border-radius: 8px; }
.add-form button { padding: 8px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 18px; }
.item { display: flex; align-items: center; gap: 8px; background: var(--surface); padding: 10px 12px; border-radius: 10px; }
.bal { margin-left: auto; font-weight: 600; color: var(--primary); }
.del { background: none; border: none; color: var(--danger); cursor: pointer; font-size: 16px; }
</style>