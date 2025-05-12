import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    getUser: (state) => state.user,
  },

  actions: {
    async initialize() {
      const token = localStorage.getItem('token')
      if (token) {
        try {
          const { data } = await api.get('/api/user')
          this.token = token
          this.user = data
        } catch (err) {
          console.error('Failed to initialize auth:', err)
          this.token = null
          this.user = null
          localStorage.removeItem('token')
        }
      }
    },

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
        if (!this.token) {
          throw new Error('No token found')
        }

        const config = {
          headers: {
            Authorization: `Bearer ${this.token}`
          }
        }

        await api.post('/api/logout', {}, config)
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
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

    async forceLogout() {
      this.token = null
      this.user = null
      localStorage.removeItem('token')
    },
  },
}) 