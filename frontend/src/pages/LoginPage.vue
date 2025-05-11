<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="login-card q-pa-md">
      <q-card-section class="text-center">
        <div class="text-h5 q-mb-md">Login</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-input
            v-model="form.email"
            label="Email"
            type="email"
            :rules="[val => !!val || 'Email is required']"
            outlined
          />

          <q-input
            v-model="form.password"
            label="Password"
            type="password"
            :rules="[val => !!val || 'Password is required']"
            outlined
          />

          <div>
            <q-btn 
              label="Login" 
              type="submit" 
              color="primary" 
              class="full-width"
              size="lg"
            />
          </div>

          <div class="text-center q-mt-sm">
            <router-link to="/register" class="text-primary">Don't have an account? Register</router-link>
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'stores/auth'
import { useQuasar } from 'quasar'

const router = useRouter()
const authStore = useAuthStore()
const $q = useQuasar()

const form = ref({
  email: '',
  password: '',
})

const onSubmit = async () => {
  try {
    await authStore.login(form.value)
    $q.notify({
      color: 'positive',
      message: 'Logged in successfully',
    })
    router.push('/')
  } catch (error) {
    $q.notify({
      color: 'negative',
      message: error.message || 'Login failed',
    })
  }
}
</script>

<style scoped>
.login-card {
  width: 100%;
  max-width: 400px;
}

.bg-grey-2 {
  background: #f5f5f5;
}
</style> 