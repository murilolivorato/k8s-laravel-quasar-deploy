<template>
  <q-page class="flex flex-center">
    <q-card class="register-card">
      <q-card-section>
        <div class="text-h6 text-center q-mb-md">Create Account</div>
        
        <q-form @submit="onSubmit" class="q-gutter-md">
          <!-- Name Field -->
          <q-input
            v-model="form.name"
            label="Full Name"
            :rules="[val => !!val || 'Name is required']"
            outlined
          >
            <template v-slot:prepend>
              <q-icon name="person" />
            </template>
          </q-input>

          <!-- Email Field -->
          <q-input
            v-model="form.email"
            label="Email"
            type="email"
            :rules="[
              val => !!val || 'Email is required',
              val => isValidEmail(val) || 'Invalid email format'
            ]"
            outlined
          >
            <template v-slot:prepend>
              <q-icon name="email" />
            </template>
          </q-input>

          <!-- Phone Field -->
          <q-input
            v-model="form.phone"
            label="Phone"
            outlined
          >
            <template v-slot:prepend>
              <q-icon name="phone" />
            </template>
          </q-input>

          <!-- Password Field -->
          <q-input
            v-model="form.password"
            label="Password"
            :type="isPwd ? 'password' : 'text'"
            :rules="[
              val => !!val || 'Password is required',
              val => val.length >= 8 || 'Password must be at least 8 characters'
            ]"
            outlined
          >
            <template v-slot:prepend>
              <q-icon name="lock" />
            </template>
            <template v-slot:append>
              <q-icon
                :name="isPwd ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="isPwd = !isPwd"
              />
            </template>
          </q-input>

          <!-- Password Confirmation Field -->
          <q-input
            v-model="form.password_confirmation"
            label="Confirm Password"
            :type="isPwdConfirm ? 'password' : 'text'"
            :rules="[
              val => !!val || 'Please confirm your password',
              val => val === form.password || 'Passwords do not match'
            ]"
            outlined
          >
            <template v-slot:prepend>
              <q-icon name="lock" />
            </template>
            <template v-slot:append>
              <q-icon
                :name="isPwdConfirm ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="isPwdConfirm = !isPwdConfirm"
              />
            </template>
          </q-input>

          <!-- Terms and Conditions -->
          <q-checkbox
            v-model="form.accept_terms"
            label="I accept the terms and conditions"
            :rules="[val => val || 'You must accept the terms and conditions']"
          />

          <!-- Submit Button -->
          <div class="row justify-center q-mt-md">
            <q-btn
              label="Register"
              type="submit"
              color="primary"
              :loading="loading"
              class="full-width"
            />
          </div>

          <!-- Login Link -->
          <div class="text-center q-mt-sm">
            Already have an account?
            <router-link to="/login" class="text-primary">Login here</router-link>
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios'

const router = useRouter()
const $q = useQuasar()

// Form state
const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  accept_terms: false
})

// UI state
const loading = ref(false)
const isPwd = ref(true)
const isPwdConfirm = ref(true)

// Validation
const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Form submission
const onSubmit = async () => {
  loading.value = true
  try {
    const { data } = await api.post('/api/register', {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation
    })

    // Store the token
    localStorage.setItem('token', data.token)

    // Show success message
    $q.notify({
      type: 'positive',
      message: 'Registration successful! Welcome aboard.',
      position: 'top'
    })

    // Redirect to dashboard
    router.push('/')
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Registration failed. Please try again.',
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-card {
  width: 100%;
  max-width: 400px;
  padding: 20px;
}

@media (max-width: 599px) {
  .register-card {
    margin: 0 16px;
  }
}
</style> 