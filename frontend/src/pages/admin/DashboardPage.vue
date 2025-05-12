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

      <!-- Role Distribution -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Role Distribution</div>
            <q-list>
              <q-item v-for="role in stats.role_distribution" :key="role.name">
                <q-item-section>
                  <q-item-label>{{ role.name }}</q-item-label>
                  <q-item-label caption>{{ role.count }} users</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>

      <!-- Registration Trend -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Recent Registrations</div>
            <q-list>
              <q-item v-for="trend in stats.registration_trend?.slice(-5)" :key="trend.date">
                <q-item-section>
                  <q-item-label>{{ formatDate(trend.date) }}</q-item-label>
                  <q-item-label caption>{{ trend.count }} new registrations</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>

      <!-- Login Activity -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Recent Login Activity</div>
            <q-list>
              <q-item v-for="activity in stats.login_activity?.slice(-5)" :key="activity.date">
                <q-item-section>
                  <q-item-label>{{ formatDate(activity.date) }}</q-item-label>
                  <q-item-label caption>{{ activity.count }} logins</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
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

export default {
  name: 'DashboardPage',
  setup () {
    const $q = useQuasar()
    const stats = ref({})
    const loading = ref(false)

    const fetchStats = async () => {
      try {
        loading.value = true
        const response = await api.get('/api/analytics/dashboard')
        stats.value = response.data
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
      })
    })

    return {
      stats,
      loading,
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