<template>
  <div class="dashboard">
    <div class="period-select">
      <button v-for="p in periods" :key="p.key" :class="{ active: period === p.key }" @click="period = p.key">
        {{ p.label }}
      </button>
    </div>

    <div class="totals" v-if="data">
      <div class="total-card income">
        <span>Доходы</span>
        <strong>{{ data.totals.income.toLocaleString() }} ₸</strong>
      </div>
      <div class="total-card expense">
        <span>Расходы</span>
        <strong>{{ data.totals.expense.toLocaleString() }} ₸</strong>
      </div>
      <div class="total-card balance" :class="{ neg: data.totals.balance < 0 }">
        <span>Баланс</span>
        <strong>{{ data.totals.balance.toLocaleString() }} ₸</strong>
      </div>
    </div>

    <div class="chart-section" v-if="chartData.length">
      <h3>Расходы по категориям</h3>
      <svg viewBox="0 0 100 100" class="donut">
        <circle v-for="(s, i) in chartData" :key="i" cx="50" cy="50" r="42"
          :stroke="s.color" :stroke-dasharray="s.dash" stroke-dashoffset="0"
          stroke-width="16" fill="none" :style="{ transform: 'rotate(-90deg)', transformOrigin: 'center' }"
        />
        <text x="50" y="48" text-anchor="middle" font-size="14" font-weight="bold">{{ activeType === 'expense' ? data?.totals.expense.toLocaleString() : data?.totals.income.toLocaleString() }}</text>
        <text x="50" y="58" text-anchor="middle" font-size="6" fill="#888">{{ activeType === 'expense' ? 'расходы' : 'доходы' }}</text>
      </svg>

      <div class="type-toggle">
        <button :class="{ active: activeType === 'expense' }" @click="activeType = 'expense'">Расходы</button>
        <button :class="{ active: activeType === 'income' }" @click="activeType = 'income'">Доходы</button>
      </div>

      <div class="category-list">
        <div v-for="c in categories" :key="c.category_id" class="cat-row">
          <span class="cat-dot" :style="{ background: c.category?.color || '#ccc' }">{{ c.category?.icon || '•' }}</span>
          <span class="cat-name">{{ c.category?.name || 'Без категории' }}</span>
          <span class="cat-pct">{{ c.percent }}%</span>
          <span class="cat-sum">{{ c.total.toLocaleString() }} ₸</span>
        </div>
      </div>
    </div>
    <p v-else class="empty">Нет операций за период</p>

        <router-link to="/add" class="fab">+</router-link>
      </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { api } from '../api'

const periods = [
  { key: 'day', label: 'День' },
  { key: 'week', label: 'Неделя' },
  { key: 'month', label: 'Месяц' },
  { key: 'year', label: 'Год' },
]
const period = ref('month')
const activeType = ref('expense')
const data = ref(null)
const colors = ['#6366f1','#f59e0b','#ef4444','#10b981','#8b5cf6','#ec4899','#14b8a6','#f97316','#06b6d4','#84cc16','#e11d48','#0ea5e9']

function getPeriodDates() {
  const now = new Date()
  const y = now.getFullYear(), m = now.getMonth()
  switch (period.value) {
    case 'day': return { date_from: toStr(now), date_to: toStr(now) }
    case 'week': {
      const d = new Date(now); d.setDate(d.getDate() - d.getDay())
      return { date_from: toStr(d), date_to: toStr(now) }
    }
    case 'month': return { date_from: `${y}-${pad(m+1)}-01`, date_to: toStr(now) }
    case 'year': return { date_from: `${y}-01-01`, date_to: toStr(now) }
  }
}

function pad(n) { return String(n).padStart(2, '0') }
function toStr(d) { return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}` }

const categories = computed(() => {
  if (!data.value) return []
  return data.value.categories[activeType.value] || []
})

const chartData = computed(() => {
  const cats = categories.value
  if (!cats.length) return []
  const total = cats.reduce((s, c) => s + c.percent, 0)
  let offset = 0
  return cats.map((c, i) => {
    const pct = c.percent / total * 100
    const dash = `${pct} ${100 - pct}`
    const seg = { ...c, dash, offset }
    offset = pct
    seg.color = c.category?.color || colors[i % colors.length]
    return seg
  })
})

async function load() {
  try {
    data.value = await api.getAnalytics(getPeriodDates())
  } catch { /* ignore */ }
}

watch(period, load)
onMounted(load)
</script>

<style scoped>
.dashboard { display: flex; flex-direction: column; gap: 16px; }
.period-select { display: flex; gap: 8px; }
.period-select button {
  flex: 1; padding: 8px; border: 1px solid var(--border); border-radius: 8px;
  background: var(--surface); cursor: pointer; font-size: 14px;
}
.period-select button.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.totals { display: flex; gap: 8px; }
.total-card { flex: 1; background: var(--surface); padding: 12px; border-radius: 12px; text-align: center; }
.total-card span { display: block; font-size: 12px; color: var(--text-secondary); }
.total-card strong { font-size: 16px; }
.total-card.income strong { color: #10b981; }
.total-card.expense strong { color: #ef4444; }
.total-card.balance.neg strong { color: var(--danger); }
.chart-section { background: var(--surface); padding: 20px; border-radius: 16px; text-align: center; }
.chart-section h3 { font-size: 15px; color: var(--text-secondary); margin-bottom: 12px; }
.donut { width: 200px; height: 200px; margin: 0 auto; display: block; }
.type-toggle { display: flex; gap: 8px; justify-content: center; margin: 12px 0; }
.type-toggle button { padding: 6px 16px; border: 1px solid var(--border); border-radius: 20px; background: var(--surface); cursor: pointer; font-size: 13px; }
.type-toggle button.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.category-list { display: flex; flex-direction: column; gap: 8px; text-align: left; }
.cat-row { display: flex; align-items: center; gap: 8px; padding: 8px 0; }
.cat-dot { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.cat-name { flex: 1; font-size: 14px; }
.cat-pct { font-size: 13px; color: var(--text-secondary); min-width: 40px; text-align: right; }
.cat-sum { font-size: 14px; font-weight: 600; min-width: 80px; text-align: right; }
.empty { text-align: center; color: var(--text-secondary); padding: 40px; }
.fab {
  position: fixed; bottom: 24px; right: 24px;
  width: 56px; height: 56px; border-radius: 50%;
  background: var(--primary); color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; font-weight: 300; text-decoration: none;
  box-shadow: 0 4px 12px rgba(99,102,241,.4);
  z-index: 50;
}
</style>