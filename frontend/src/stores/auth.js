import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    getUser: (state) => state.user,
  },

  actions: {
    async login(credentials) {
      try {
        const { data } = await api.post('/api/login', credentials)
        this.token = data.token
        this.user = data.user
        localStorage.setItem('token', data.token)
        return data
      } catch (error) {
        throw error.response?.data || error
      }
    },

    async register(userData) {
      try {
        const { data } = await api.post('/api/register', userData)
        this.token = data.token
        this.user = data.user
        localStorage.setItem('token', data.token)
        return data
      } catch (error) {
        throw error.response?.data || error
      }
    },

    async logout() {
      try {
        await api.post('/api/logout')
        this.token = null
        this.user = null
        localStorage.removeItem('token')
      } catch (error) {
        console.error('Logout error:', error)
      }
    },

    async fetchUser() {
      try {
        const { data } = await api.get('/api/user')
        this.user = data
        return data
      } catch (error) {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
        throw error
      }
    },
  },
}) 