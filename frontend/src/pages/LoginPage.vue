<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="login-card q-pa-md">
      <q-card-section>
        <div class="text-h6 text-center q-mb-md">Login to your account</div>
        
        <q-form
          ref="formRef"
          @submit.prevent="onSubmit"
          class="q-gutter-md"
        >
          <!-- Email Field -->
          <q-input
            v-model="formData.email"
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

          <!-- Password Field -->
          <q-input
            v-model="formData.password"
            label="Password"
            :type="isPwd ? 'password' : 'text'"
            :rules="[val => !!val || 'Password is required']"
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

          <!-- Remember Me -->
          <q-checkbox
            v-model="formData.remember"
            label="Remember me"
          />

          <!-- Submit Button -->
          <div class="row justify-center q-mt-md">
            <q-btn
              label="Login"
              type="submit"
              color="primary"
              :loading="loading"
              class="full-width"
            />
          </div>

          <!-- Register Link -->
          <div class="text-center q-mt-sm">
            Don't have an account?
            <router-link to="/auth/register" class="text-primary">Register here</router-link>
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios'

export default {
  name: 'LoginPage',
  setup () {
    const $q = useQuasar()
    const router = useRouter()
    const formRef = ref(null)
    const loading = ref(false)
    const isPwd = ref(true)

    const formData = reactive({
      email: '',
      password: '',
      remember: false
    })

    const isValidEmail = (email) => {
      const emailPattern = /^(?=[a-zA-Z0-9@._%+-]{6,254}$)[a-zA-Z0-9._%+-]{1,64}@(?:[a-zA-Z0-9-]{1,63}\.){1,8}[a-zA-Z]{2,63}$/
      return emailPattern.test(email)
    }

    const onSubmit = async () => {
      if (loading.value) return

      try {
        const valid = await formRef.value.validate()
        if (!valid) return

        loading.value = true

        // Clear any existing token
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']

        // Make the login request
        const response = await api.post('/api/login', {
          email: formData.email,
          password: formData.password,
          remember: formData.remember
        })

        console.log('Login response:', {
          token: response.data.token ? 'present' : 'missing',
          user: response.data.user,
          roles: response.data.user?.roles || []
        })

        if (!response.data.token) {
          throw new Error('No token received')
        }

        // Store the token
        const token = response.data.token
        localStorage.setItem('token', token)
        
        // Set the token in axios headers
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`

        // Show success message
        $q.notify({
          type: 'positive',
          message: 'Login successful',
          position: 'top',
          timeout: 2000
        })

        // Let the router handle the auth check and redirection
        console.log('Redirecting to dashboard...')
        await router.push('/admin/dashboard')

      } catch (error) {
        console.error('Login error:', {
          message: error.message,
          response: error.response?.data,
          status: error.response?.status
        })
        
        let message = 'An error occurred during login'
        
        if (error.response?.data?.message) {
          message = error.response.data.message
        } else if (error.response) {
          message = 'Invalid credentials'
        } else if (error.request) {
          message = 'Unable to connect to the server. Please check your connection.'
        }
        
        $q.notify({
          type: 'negative',
          message: message,
          position: 'top',
          timeout: 5000
        })

        // Clear token on error
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']
      } finally {
        loading.value = false
      }
    }

    return {
      formRef,
      formData,
      loading,
      onSubmit,
      isPwd,
      isValidEmail
    }
  }
}
</script>

<style scoped>
.login-card {
  width: 100%;
  max-width: 400px;
}

@media (max-width: 599px) {
  .login-card {
    margin: 0 16px;
  }
}
</style> 