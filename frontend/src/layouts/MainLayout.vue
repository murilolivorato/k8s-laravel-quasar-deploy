<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="bg-primary text-white">
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
          Quasar App
        </q-toolbar-title>

        <q-space />

        <template v-if="authStore.isAuthenticated">
          <q-btn flat dense round icon="person" :label="authStore.user?.name" />
          <q-btn 
            flat 
            dense 
            round 
            icon="logout" 
            label="Logout"
            @click="logout"
            class="q-ml-sm"
          />
        </template>
        <template v-else>
          <q-btn flat dense round to="/auth/login" label="Login" />
          <q-btn flat dense round to="/auth/register" label="Register" />
        </template>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
    >
      <q-list>
        <q-item-label header>
          Menu
        </q-item-label>

        <!-- Add user info at the top of drawer -->
        <q-item v-if="authStore.isAuthenticated">
          <q-item-section avatar>
            <q-icon name="person" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ authStore.user?.name }}</q-item-label>
            <q-item-label caption>{{ authStore.user?.email }}</q-item-label>
          </q-item-section>
        </q-item>

        <!-- Add logout button in drawer -->
        <q-item
          v-if="authStore.isAuthenticated"
          clickable
          v-ripple
          @click="logout"
        >
          <q-item-section avatar>
            <q-icon name="logout" />
          </q-item-section>
          <q-item-section>
            Logout
          </q-item-section>
        </q-item>

        <q-separator />

        <q-item-label header>
          Essential Links
        </q-item-label>

        <EssentialLink
          v-for="link in linksList"
          :key="link.title"
          v-bind="link"
        />
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from 'vue'
import EssentialLink from 'components/EssentialLink.vue'
import { useAuthStore } from 'stores/auth'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'

const linksList = [
  {
    title: 'Docs',
    caption: 'quasar.dev',
    icon: 'school',
    link: 'https://quasar.dev'
  },
  {
    title: 'Github',
    caption: 'github.com/quasarframework',
    icon: 'code',
    link: 'https://github.com/quasarframework'
  },
  {
    title: 'Discord Chat Channel',
    caption: 'chat.quasar.dev',
    icon: 'chat',
    link: 'https://chat.quasar.dev'
  },
  {
    title: 'Forum',
    caption: 'forum.quasar.dev',
    icon: 'record_voice_over',
    link: 'https://forum.quasar.dev'
  },
  {
    title: 'Twitter',
    caption: '@quasarframework',
    icon: 'rss_feed',
    link: 'https://twitter.quasar.dev'
  },
  {
    title: 'Facebook',
    caption: '@QuasarFramework',
    icon: 'public',
    link: 'https://facebook.quasar.dev'
  },
  {
    title: 'Quasar Awesome',
    caption: 'Community Quasar projects',
    icon: 'favorite',
    link: 'https://awesome.quasar.dev'
  },
  {
    title: 'User Management',
    caption: 'Manage system users',
    icon: 'people',
    link: '/users'
  }
]

const leftDrawerOpen = ref(false)
const authStore = useAuthStore()
const router = useRouter()
const $q = useQuasar()

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

const logout = async () => {
  try {
    console.log('Starting logout process...')
    await authStore.logout()
    console.log('Logout successful, redirecting...')
    $q.notify({
      type: 'positive',
      message: 'Logged out successfully',
      position: 'top'
    })
    router.push('/auth/login')
  } catch (err) {
    console.error('Logout error:', err)
    $q.notify({
      type: 'negative',
      message: err.message || 'Logout failed',
      position: 'top'
    })
  }
}
</script>

<style scoped>
.q-drawer {
  background: #f5f5f5;
}
</style>
