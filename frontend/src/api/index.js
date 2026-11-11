const API_BASE = '/api'

function helper(tok) {
  return 'Bearer ' + tok
}

async function request(path, options = {}) {
  const token = localStorage.getItem('token')
  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: helper(token) } : {}),
    ...options.headers,
  }

  const res = await fetch(API_BASE + path, {
    ...options,
    headers,
  })

  if (res.status === 204) return null
  const data = await res.json()

  if (!res.ok) {
    const msg = data?.message || 'Ошибка запроса'
    if (res.status === 401) {
      localStorage.removeItem('token')
      window.location.hash = '#/login'
    }
    throw new Error(msg)
  }

  return data
}

export const api = {
  register: (d) => request('/register', { method: 'POST', body: JSON.stringify(d) }),
  login: (d) => request('/login', { method: 'POST', body: JSON.stringify(d) }),
  logout: () => request('/logout', { method: 'POST' }),
  user: () => request('/user'),
  getCategories: () => request('/categories'),
  createCategory: (d) => request('/categories', { method: 'POST', body: JSON.stringify(d) }),
  updateCategory: (id, d) => request('/categories/' + id, { method: 'PUT', body: JSON.stringify(d) }),
  deleteCategory: (id) => request('/categories/' + id, { method: 'DELETE' }),
  getAccounts: () => request('/accounts'),
  createAccount: (d) => request('/accounts', { method: 'POST', body: JSON.stringify(d) }),
  updateAccount: (id, d) => request('/accounts/' + id, { method: 'PUT', body: JSON.stringify(d) }),
  deleteAccount: (id) => request('/accounts/' + id, { method: 'DELETE' }),
  getTransactions: (p) => { const q = new URLSearchParams(p).toString(); return request('/transactions' + (q ? '?' + q : '')) },
  createTransaction: (d) => request('/transactions', { method: 'POST', body: JSON.stringify(d) }),
  updateTransaction: (id, d) => request('/transactions/' + id, { method: 'PUT', body: JSON.stringify(d) }),
  deleteTransaction: (id) => request('/transactions/' + id, { method: 'DELETE' }),
  getAnalytics: (p) => { const q = new URLSearchParams(p).toString(); return request('/analytics' + (q ? '?' + q : '')) },
  getRates: () => request('/rates'),
  getTags: () => request('/tags'),
  createTag: (d) => request('/tags', { method: 'POST', body: JSON.stringify(d) }),
  deleteTag: (id) => request('/tags/' + id, { method: 'DELETE' }),
  getRecurringPayments: () => request('/recurring-payments'),
  createRecurringPayment: (d) => request('/recurring-payments', { method: 'POST', body: JSON.stringify(d) }),
  updateRecurringPayment: (id, d) => request('/recurring-payments/' + id, { method: 'PUT', body: JSON.stringify(d) }),
  deleteRecurringPayment: (id) => request('/recurring-payments/' + id, { method: 'DELETE' }),
}