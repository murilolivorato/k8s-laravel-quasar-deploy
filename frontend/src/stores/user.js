import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [],
    roles: [],
    loading: false,
    error: null
  }),

  getters: {
    getActiveUsers: (state) => state.users.filter(user => user.is_active),
    getInactiveUsers: (state) => state.users.filter(user => !user.is_active),
    getUserById: (state) => (id) => state.users.find(user => user.id === id)
  },

  actions: {
    async fetchUsers() {
      this.loading = true
      try {
        const { data } = await api.get('/api/users')
        this.users = data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch users'
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchRoles() {
      try {
        const { data } = await api.get('/api/roles')
        this.roles = data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch roles'
        throw err
      }
    },

    async createUser(userData) {
      try {
        const { data } = await api.post('/api/users', userData)
        this.users.push(data)
        return data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to create user'
        throw err
      }
    },

    async updateUser(id, userData) {
      try {
        const { data } = await api.put(`/api/users/${id}`, userData)
        const index = this.users.findIndex(user => user.id === id)
        if (index !== -1) {
          this.users[index] = data
        }
        return data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to update user'
        throw err
      }
    },

    async deleteUser(id) {
      try {
        await api.delete(`/api/users/${id}`)
        this.users = this.users.filter(user => user.id !== id)
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to delete user'
        throw err
      }
    },

    async updateUserStatus(id) {
      try {
        const { data } = await api.patch(`/api/users/${id}/status`)
        const index = this.users.findIndex(user => user.id === id)
        if (index !== -1) {
          this.users[index] = data
        }
        return data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to update user status'
        throw err
      }
    }
  }
}) 