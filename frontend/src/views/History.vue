<template>
  <div class="history">
    <PageHeader title="История"/>
    <div class="filters">
      <input v-model="dateFrom" type="date" />
      <input v-model="dateTo" type="date" />
      <select v-model="fType"><option value="">Все</option><option value="expense">Расход</option><option value="income">Доход</option></select>
    </div>
    <div v-for="txn in transactions" :key="txn.id" class="txn-row">
      <div class="txn-left">
        <strong>{{ txn.category?.name || '—' }}</strong>
        <small>{{ txn.account?.name }} · {{ txn.date }}</small>
      </div>
      <div class="txn-right" :class="txn.type">
        {{ txn.type === 'income' ? '+' : '-' }}{{ Number(txn.amount).toLocaleString() }} ₸
      </div>
    </div>
    <p v-if="!transactions.length" class="empty">Нет транзакций</p>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { api } from '../api'
import PageHeader from "../components/PageHeader.vue";

const transactions = ref([])
const dateFrom = ref('')
const dateTo = ref('')
const fType = ref('')

async function load() {
  const params = {}
  if (dateFrom.value) params.date_from = dateFrom.value
  if (dateTo.value) params.date_to = dateTo.value
  if (fType.value) params.type = fType.value
  const res = await api.getTransactions(params)
  transactions.value = res.data || res
}

watch([dateFrom, dateTo, fType], load)
onMounted(load)
</script>

<style scoped>
.history { display: flex; flex-direction: column; gap: 8px; }
.filters { display: flex; gap: 8px; flex-wrap: wrap; }
.filters input, .filters select { padding: 8px; border: 1px solid var(--border); border-radius: 8px; flex: 1; min-width: 120px; }
.txn-row { display: flex; justify-content: space-between; align-items: center; background: var(--surface); padding: 12px; border-radius: 12px; }
.txn-left strong { display: block; font-size: 15px; }
.txn-left small { font-size: 12px; color: var(--text-secondary); }
.txn-right { font-weight: 700; font-size: 16px; }
.txn-right.income { color: #10b981; }
.txn-right.expense { color: #ef4444; }
.empty { text-align: center; color: var(--text-secondary); padding: 40px; }
</style>