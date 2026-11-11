<template>
  <div class="dashboard">
    <div class="rates" v-if="rates.length">
      <div class="rate" v-for="r in rates" :key="r.code">
        <span class="rate-code">{{ r.code }}</span>
        <span class="rate-value">
          <span class="rate-direction" :class="'rate-direction--' + r.direction">{{ r.direction === 'up' ? '▲' : r.direction === 'down' ? '▼' : '–' }}</span>
          {{ r.value.toFixed(2) }} ₸
        </span>
      </div>
    </div>
    <div class="type-toggle">
      <button :class="{ active: activeType === 'expense' }" @click="activeType = 'expense'">Расходы</button>
      <button :class="{ active: activeType === 'income' }" @click="activeType = 'income'">Доходы</button>
    </div>

    <div class="chart-section">
      <div class="period-select">
        <button v-for="p in periods" :key="p.key" :class="{ active: period === p.key }" @click="period = p.key">
          {{ p.label }}
        </button>
      </div>
      <div class="period-select-date">
        <button class="period-select-date__arrow period-select-date__arrow-prev" @click="prevPeriod" aria-label="Предыдущий период">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <span>{{ periodDateLabel }}</span>
        <button class="period-select-date__arrow period-select-date__arrow-next" :class="{ 'is-disabled': periodOffset === 0 }" @click="nextPeriod" aria-label="Следующий период">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </button>
      </div>
      <svg viewBox="0 0 100 100" class="donut">
        <circle v-for="(s, i) in chartData" :key="i" cx="50" cy="50" r="42"
                :stroke="s.color" :stroke-dasharray="s.dash"
                :stroke-dashoffset="s.offset"
                stroke-width="16" fill="none" :style="{ transform: 'rotate(-90deg)', transformOrigin: 'center' }"
        />
        <text x="50" y="54" text-anchor="middle" font-size="14" font-weight="bold">
          {{ activeType === 'expense' ? data?.totals.expense.toLocaleString() : data?.totals.income.toLocaleString() }}
        </text>
      </svg>
    </div>
    <div v-if="chartData.length" class="category-list">
      <div v-for="c in categories" :key="c.category_id" class="cat-row">
        <span class="cat-dot" :style="{ background: c.category?.color || '#ccc' }">{{ c.category?.icon || '•' }}</span>
        <span class="cat-name">{{ c.category?.name || 'Без категории' }}</span>
        <span class="cat-pct">{{ c.percent }}%</span>
        <span class="cat-sum">{{ c.total.toLocaleString() }} ₸</span>
      </div>
    </div>

    <p v-else class="empty">Нет операций за период</p>

    <router-link :to="{ path: '/add', query: { type: activeType } }" class="fab">+</router-link>
  </div>
</template>

<script setup>
import {ref, computed, watch, onMounted} from 'vue'
import {api} from '../api'

const periods = [
  {key: 'day', label: 'День'},
  {key: 'week', label: 'Неделя'},
  {key: 'month', label: 'Месяц'},
  {key: 'year', label: 'Год'},
]
const period = ref('month')
const activeType = ref('expense')
const data = ref(null)
const periodOffset = ref(0)
const rates = ref([])

const anchorDate = computed(() => {
  const d = new Date()
  const n = periodOffset.value
  switch (period.value) {
    case 'day':
      d.setDate(d.getDate() + n)
      break
    case 'week':
      d.setDate(d.getDate() + n * 7)
      break
    case 'month':
      d.setMonth(d.getMonth() + n)
      break
    case 'year':
      d.setFullYear(d.getFullYear() + n)
      break
  }
  return d
})

function prevPeriod() {
  periodOffset.value--
}

function nextPeriod() {
  if (periodOffset.value < 0) periodOffset.value++
}

const colors = ['#059669', '#f59e0b', '#ef4444', '#10b981', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16', '#e11d48', '#0ea5e9']

function getPeriodDates() {
  const now = anchorDate.value
  const y = now.getFullYear(), m = now.getMonth()
  switch (period.value) {
    case 'day':
      return {date_from: toStr(now), date_to: toStr(now)}
    case 'week': {
      const d = new Date(now);
      d.setDate(d.getDate() - d.getDay())
      return {date_from: toStr(d), date_to: toStr(now)}
    }
    case 'month':
      return {date_from: `${y}-${pad(m + 1)}-01`, date_to: toStr(now)}
    case 'year':
      return {date_from: `${y}-01-01`, date_to: toStr(now)}
  }
}

function pad(n) {
  return String(n).padStart(2, '0')
}

const monthShort = ['янв.', 'февр.', 'мар.', 'апр.', 'мая', 'июн.', 'июл.', 'авг.', 'сент.', 'окт.', 'нояб.', 'дек.']
const monthFull = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь']

function fmtShort(d) {
  return `${d.getDate()} ${monthShort[d.getMonth()]}`
}

const periodDateLabel = computed(() => {
  const now = anchorDate.value
  switch (period.value) {
    case 'day':
      return fmtShort(now)
    case 'week': {
      const d = new Date(now)
      d.setDate(d.getDate() - 7)
      return `${fmtShort(d)} - ${fmtShort(now)}`
    }
    case 'month':
      return `${monthFull[now.getMonth()]} ${now.getFullYear()}`
    default:
      return String(now.getFullYear())
  }
})

function toStr(d) {
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const categories = computed(() => {
  if (!data.value) return []
  return data.value.categories[activeType.value] || []
})

const chartData = computed(() => {
  const cats = categories.value
  if (!cats.length) return []
  const total = cats.reduce((s, c) => s + c.percent, 0)
  if (total === 0) return []
  const circumference = 2 * Math.PI * 42
  let cumulative = 0
  return cats.map((c, i) => {
    const pct = c.percent / total
    const len = pct * circumference
    const gap = circumference - len
    const dash = `${len} ${gap}`
    const seg = {...c, dash, offset: -cumulative}
    cumulative += len
    seg.color = c.category?.color || colors[i % colors.length]
    return seg
  })
})

async function load() {
  try {
    data.value = await api.getAnalytics(getPeriodDates())
  } catch { /* ignore */
  }
}

async function loadRates() {
  try {
    const r = await api.getRates()
    const wanted = ['USD', 'EUR', 'RUB', 'CNY']
    rates.value = wanted
      .map(c => (r?.rates || []).find(x => x.code === c))
      .filter(x => x && x.value != null)
  } catch { /* блок скрыт при ошибке */
  }
}

watch(period, () => {
  periodOffset.value = 0
  load()
})
watch(periodOffset, load)
onMounted(() => {
  load()
  loadRates()
})
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.rates {
  display: flex;
  gap: 8px;
  background: var(--surface);
  border-radius: 16px;
  padding: 12px 8px;
  box-shadow:
      0 4px 6px -1px rgba(0, 0, 0, 0.05),
      0 10px 15px -3px rgba(0, 0, 0, 0.08);
}

.rate {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  border-right: 1px solid var(--border);
}

.rate:last-child {
  border-right: none;
}

.rate-code {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-secondary);
}

.rate-value {
  font-size: 14px;
  font-weight: 600;
}

.rate-direction {
  font-size: 11px;
  margin-right: 4px;
}

.rate-direction--up {
  color: #059669;
}

.rate-direction--down {
  color: #dc2626;
}

.rate-direction--flat {
  color: var(--text-secondary);
}

.period-select {
  display: flex;
  gap: 8px;
  margin-bottom: 30px;
}

.period-select button {
  flex: 1;
  padding: 6px 0;
  background: var(--surface);
  cursor: pointer;
  font-size: 18px;
  border: none;
}

.period-select button.active {
  background: #fff;
  color: var(--primary);
  border-color: var(--primary);
  border-bottom: 2px solid;
}

.period-select-date {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 20px;
  font-size: 15px;
  color: var(--text-secondary);
}

.period-select-date__arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: var(--primary);
  cursor: pointer;
  transition: background .15s ease, opacity .15s ease, transform .1s ease;
}

.period-select-date__arrow svg {
  width: 18px;
  height: 18px;
}

.period-select-date__arrow:hover:not(.is-disabled) {
  background: rgba(5, 150, 105, .12);
}

.period-select-date__arrow:active:not(.is-disabled) {
  transform: scale(.9);
}

.period-select-date__arrow.is-disabled {
  opacity: .3;
  cursor: default;
  pointer-events: none;
}

.chart-section {
  background: var(--surface);
  padding: 20px;
  margin-top: -80px;
  border-radius: 16px;
  text-align: center;
  box-shadow:
      0 4px 6px -1px rgba(0, 0, 0, 0.05),
      0 10px 15px -3px rgba(0, 0, 0, 0.08);
}

.donut {
  width: 200px;
  height: 200px;
  margin: 0 auto;
  display: block;
}

.type-toggle {
  display: flex;
  gap: 8px;
  justify-content: space-around;
  margin: -16px;
  padding: 20px 0 100px;
  background-color: var(--primary);
  border-radius: 0 0 30px 30px;
}

.type-toggle button {
  padding: 6px 16px;
  border: none;
  cursor: pointer;
  font-size: 20px;
  opacity: .6;
  background-color: transparent;
  color: #fff;
  text-transform: uppercase;
}

.type-toggle button.active {
  color: #fff;
  border-color: var(--primary);
  opacity: 1;
  border-bottom: 2px solid var(--border);
}

.category-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  text-align: left;
  padding-bottom: 50px;
}

.cat-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px;
  background-color: #fff;
  border-radius: 16px;
  box-shadow:
      0 4px 6px -1px rgba(0, 0, 0, 0.05),
      0 10px 15px -3px rgba(0, 0, 0, 0.08);
}

.cat-dot {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.cat-name {
  flex: 1;
  font-size: 14px;
}

.cat-pct {
  font-size: 13px;
  color: var(--text-secondary);
  min-width: 40px;
  text-align: right;
}

.cat-sum {
  font-size: 14px;
  font-weight: 600;
  min-width: 80px;
  text-align: right;
}

.empty {
  text-align: center;
  color: var(--text-secondary);
  padding: 40px;
}

.fab {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--primary);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 300;
  text-decoration: none;
  box-shadow:
      0 4px 6px -1px rgba(0, 0, 0, 0.05),
      0 10px 15px -3px rgba(0, 0, 0, 0.08);
  z-index: 50;
}
</style>