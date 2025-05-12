<template>
  <q-page padding>
    <div class="text-h5 q-mb-md">Admin Dashboard</div>
    
    <div class="row q-col-gutter-md">
      <!-- Stats Cards -->
      <div class="col-12 col-md-4">
        <q-card class="bg-primary text-white">
          <q-card-section>
            <div class="text-h6">Total Users</div>
            <div class="text-h4">{{ stats.total_users }}</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="bg-positive text-white">
          <q-card-section>
            <div class="text-h6">Active Users (30d)</div>
            <div class="text-h4">{{ stats.active_users }}</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="bg-info text-white">
          <q-card-section>
            <div class="text-h6">New Users (30d)</div>
            <div class="text-h4">{{ stats.new_users }}</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Charts -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Role Distribution</div>
            <div class="q-pa-md">
              <canvas ref="roleChart"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">User Registration Trend</div>
            <div class="q-pa-md">
              <canvas ref="registrationChart"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Login Activity</div>
            <div class="q-pa-md">
              <canvas ref="loginChart"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- System Health -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">System Health</div>
            <q-list>
              <q-item>
                <q-item-section>
                  <q-item-label>Database Size</q-item-label>
                  <q-item-label caption>{{ formatBytes(stats.system_health?.database_size) }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label>Active Sessions</q-item-label>
                  <q-item-label caption>{{ stats.system_health?.active_sessions }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label>Last Backup</q-item-label>
                  <q-item-label caption>{{ formatDate(stats.system_health?.last_backup) }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label>Server Load</q-item-label>
                  <q-item-label caption>
                    <q-linear-progress
                      :value="stats.system_health?.server_load / 100"
                      :color="getLoadColor(stats.system_health?.server_load)"
                    />
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios'
import { Chart, registerables } from 'chart.js'

// Register all Chart.js components
Chart.register(...registerables)

export default {
  name: 'DashboardPage',
  setup () {
    const $q = useQuasar()
    const stats = ref({})
    const loading = ref(false)
    const roleChart = ref(null)
    const registrationChart = ref(null)
    const loginChart = ref(null)

    const fetchStats = async () => {
      try {
        loading.value = true
        const response = await api.get('/api/analytics/dashboard')
        stats.value = response.data
        updateCharts()
      } catch (error) {
        console.error('Error fetching stats:', error)
        $q.notify({
          type: 'negative',
          message: 'Error loading dashboard data',
          position: 'top'
        })
      } finally {
        loading.value = false
      }
    }

    const updateCharts = () => {
      // Destroy existing charts before creating new ones
      if (roleChart.value) {
        const existingChart = Chart.getChart(roleChart.value)
        if (existingChart) {
          existingChart.destroy()
        }
      }
      if (registrationChart.value) {
        const existingChart = Chart.getChart(registrationChart.value)
        if (existingChart) {
          existingChart.destroy()
        }
      }
      if (loginChart.value) {
        const existingChart = Chart.getChart(loginChart.value)
        if (existingChart) {
          existingChart.destroy()
        }
      }

      // Create new charts
      if (roleChart.value) {
        const roleCtx = roleChart.value.getContext('2d')
        new Chart(roleCtx, {
          type: 'pie',
          data: {
            labels: stats.value.role_distribution?.map(r => r.name) || [],
            datasets: [{
              data: stats.value.role_distribution?.map(r => r.count) || [],
              backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
            }]
          }
        })
      }

      if (registrationChart.value) {
        const regCtx = registrationChart.value.getContext('2d')
        new Chart(regCtx, {
          type: 'line',
          data: {
            labels: stats.value.registration_trend?.map(t => t.date) || [],
            datasets: [{
              label: 'New Registrations',
              data: stats.value.registration_trend?.map(t => t.count) || [],
              borderColor: '#36A2EB',
              tension: 0.1
            }]
          }
        })
      }

      if (loginChart.value) {
        const loginCtx = loginChart.value.getContext('2d')
        new Chart(loginCtx, {
          type: 'line',
          data: {
            labels: stats.value.login_activity?.map(a => a.date) || [],
            datasets: [{
              label: 'Logins',
              data: stats.value.login_activity?.map(a => a.count) || [],
              borderColor: '#4BC0C0',
              tension: 0.1
            }]
          }
        })
      }
    }

    const formatBytes = (bytes) => {
      if (!bytes) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }

    const formatDate = (date) => {
      if (!date) return 'N/A'
      return new Date(date).toLocaleString()
    }

    const getLoadColor = (load) => {
      if (!load) return 'grey'
      if (load < 50) return 'positive'
      if (load < 80) return 'warning'
      return 'negative'
    }

    onMounted(() => {
      fetchStats()
      // Refresh stats every 5 minutes
      const interval = setInterval(fetchStats, 300000)
      onUnmounted(() => {
        clearInterval(interval)
        // Clean up charts when component is unmounted
        if (roleChart.value) {
          const chart = Chart.getChart(roleChart.value)
          if (chart) chart.destroy()
        }
        if (registrationChart.value) {
          const chart = Chart.getChart(registrationChart.value)
          if (chart) chart.destroy()
        }
        if (loginChart.value) {
          const chart = Chart.getChart(loginChart.value)
          if (chart) chart.destroy()
        }
      })
    })

    return {
      stats,
      loading,
      roleChart,
      registrationChart,
      loginChart,
      formatBytes,
      formatDate,
      getLoadColor
    }
  }
}
</script>

<style scoped>
.q-card {
  height: 100%;
}
</style> 