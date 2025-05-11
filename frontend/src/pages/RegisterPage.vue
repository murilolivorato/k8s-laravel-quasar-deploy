<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="register-card q-pa-md">
      <q-card-section class="text-center">
        <div class="text-h5 q-mb-md">Register</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-input
            v-model="form.name"
            label="Name"
            :rules="[val => !!val || 'Name is required']"
            outlined
          />

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

          <q-input
            v-model="form.password_confirmation"
            label="Confirm Password"
            type="password"
            :rules="[
              val => !!val || 'Please confirm your password',
              val => val === form.password || 'Passwords do not match'
            ]"
            outlined
          />

          <div>
            <q-btn 
              label="Register" 
              type="submit" 
              color="primary" 
              class="full-width"
              size="lg"
            />
          </div>

          <div class="text-center q-mt-sm">
            <router-link to="/login" class="text-primary">Already have an account? Login</router-link>
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
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const onSubmit = async () => {
  try {
    await authStore.register(form.value)
    $q.notify({
      color: 'positive',
      message: 'Registered successfully',
    })
    router.push('/')
  } catch (error) {
    $q.notify({
      color: 'negative',
      message: error.message || 'Registration failed',
    })
  }
}
</script>

<style scoped>
.register-card {
  width: 100%;
  max-width: 400px;
}

.bg-grey-2 {
  background: #f5f5f5;
}
</style> 