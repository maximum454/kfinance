import { defineStore } from 'pinia'
import { api } from '../api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token'),
    loading: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
  },

  actions: {
    async register(data) {
      this.loading = true
      try {
        const res = await api.register(data)
        this.token = res.token
        this.user = res.user
        localStorage.setItem('token', res.token)
      } finally {
        this.loading = false
      }
    },

    async login(data) {
      this.loading = true
      try {
        const res = await api.login(data)
        this.token = res.token
        this.user = res.user
        localStorage.setItem('token', res.token)
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.logout()
      } catch {
        // ignore
      }
      this.token = null
      this.user = null
      localStorage.removeItem('token')
    },

    async fetchUser() {
      if (!this.token) return
      try {
        this.user = await api.user()
      } catch {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
      }
    },
  },
})