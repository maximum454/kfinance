<template>
  <div class="mgmt">
    <PageHeader title="Регулярные" />
    <form @submit.prevent="addR" class="add-form">
      <select v-model="acct"><option value="">Счёт</option><option v-for="a in accs" :key="a.id" :value="a.id">{{ a.name }}</option></select>
      <input v-model="amount" type="number" step="0.01" placeholder="Сумма" required />
      <select v-model="freq"><option value="monthly">Ежемесячно</option><option value="weekly">Еженедельно</option><option value="yearly">Ежегодно</option></select>
      <input v-model="nextDate" type="date" required />
      <button>+</button>
    </form>
    <div v-for="r in rps" :key="r.id" class="item">
      <span>{{ r.category?.icon || '🔄' }} {{ Number(r.amount).toLocaleString() }} ₸ · {{ r.frequency }}</span>
      <small>{{ r.next_date }}</small>
      <button class="del" @click="del(r.id)">✕</button>
    </div>
    <p v-if="!rps.length" class="empty">Нет регулярных платежей</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import PageHeader from '../components/PageHeader.vue'
const rps = ref([])
const accs = ref([])
const acct = ref('')
const amount = ref('')
const freq = ref('monthly')
const nextDate = ref('')
async function load() { rps.value = await api.getRecurringPayments(); accs.value = await api.getAccounts(); }
async function addR() { await api.createRecurringPayment({ account_id: acct.value, amount: amount.value, type: 'expense', frequency: freq.value, next_date: nextDate.value }); amount.value = ''; nextDate.value = ''; load() }
async function del(id) { await api.deleteRecurringPayment(id); load() }
onMounted(load)
</script>
<style scoped>
.mgmt { display: flex; flex-direction: column; gap: 8px; }
.add-form { display: flex; gap: 8px; flex-wrap: wrap; }
.add-form input, .add-form select { padding: 8px; border: 1px solid var(--border); border-radius: 8px; min-width: 80px; flex: 1; }
.add-form button { padding: 8px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 18px; }
.item { display: flex; align-items: center; gap: 8px; background: var(--surface); padding: 10px 12px; border-radius: 10px; }
.item small { color: var(--text-secondary); font-size: 12px; }
.del { background: none; border: none; color: var(--danger); cursor: pointer; font-size: 16px; margin-left: auto; }
.empty { text-align: center; color: var(--text-secondary); padding: 20px; }
</style>