<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
        />

        <q-toolbar-title>
          Admin Panel
        </q-toolbar-title>

        <q-btn flat round icon="logout" @click="logout" />
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
    >
      <q-list>
        <q-item-label header>
          Navigation
        </q-item-label>

        <q-item
          v-ripple
          clickable
          to="/admin/dashboard"
          exact
        >
          <q-item-section avatar>
            <q-icon name="dashboard" />
          </q-item-section>
          <q-item-section>
            Dashboard
          </q-item-section>
        </q-item>

        <q-item
          v-ripple
          clickable
          to="/admin/users"
          exact
        >
          <q-item-section avatar>
            <q-icon name="people" />
          </q-item-section>
          <q-item-section>
            Users
          </q-item-section>
        </q-item>

        <q-item
          v-ripple
          clickable
          to="/admin/roles"
          exact
        >
          <q-item-section avatar>
            <q-icon name="security" />
          </q-item-section>
          <q-item-section>
            Roles
          </q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios'

export default {
  name: 'AdminLayout',
  setup () {
    const $q = useQuasar()
    const router = useRouter()
    const leftDrawerOpen = ref(false)

    const toggleLeftDrawer = () => {
      leftDrawerOpen.value = !leftDrawerOpen.value
    }

    const logout = async () => {
      try {
        await api.post('/api/logout')
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']
        router.push('/auth/login')
      } catch (error) {
        console.error('Logout error:', error)
        $q.notify({
          type: 'negative',
          message: 'Error during logout',
          position: 'top'
        })
      }
    }

    return {
      leftDrawerOpen,
      toggleLeftDrawer,
      logout
    }
  }
}
</script> 