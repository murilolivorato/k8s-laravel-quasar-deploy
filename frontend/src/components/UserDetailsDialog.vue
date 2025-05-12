<template>
  <q-dialog v-model="show" persistent>
    <q-card style="min-width: 600px">
      <q-card-section class="row items-center">
        <div class="text-h6">User Details</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section v-if="user">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <q-item>
              <q-item-section>
                <q-item-label caption>Name</q-item-label>
                <q-item-label>{{ user.name }}</q-item-label>
              </q-item-section>
            </q-item>
          </div>

          <div class="col-12 col-md-6">
            <q-item>
              <q-item-section>
                <q-item-label caption>Email</q-item-label>
                <q-item-label>{{ user.email }}</q-item-label>
              </q-item-section>
            </q-item>
          </div>

          <div class="col-12 col-md-6">
            <q-item>
              <q-item-section>
                <q-item-label caption>Phone</q-item-label>
                <q-item-label>{{ user.phone || 'Not provided' }}</q-item-label>
              </q-item-section>
            </q-item>
          </div>

          <div class="col-12 col-md-6">
            <q-item>
              <q-item-section>
                <q-item-label caption>Status</q-item-label>
                <q-item-label>
                  <q-chip
                    :color="user.is_active ? 'positive' : 'negative'"
                    text-color="white"
                    dense
                  >
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </q-chip>
                </q-item-label>
              </q-item-section>
            </q-item>
          </div>

          <div class="col-12">
            <q-item>
              <q-item-section>
                <q-item-label caption>Roles</q-item-label>
                <q-item-label>
                  <q-chip
                    v-for="role in user.roles"
                    :key="role.id"
                    dense
                    class="q-mr-xs"
                  >
                    {{ role.name }}
                  </q-chip>
                </q-item-label>
              </q-item-section>
            </q-item>
          </div>

          <div class="col-12" v-if="user.notes">
            <q-item>
              <q-item-section>
                <q-item-label caption>Notes</q-item-label>
                <q-item-label>{{ user.notes }}</q-item-label>
              </q-item-section>
            </q-item>
          </div>

          <div class="col-12">
            <q-item>
              <q-item-section>
                <q-item-label caption>Last Login</q-item-label>
                <q-item-label>
                  {{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </div>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Close" color="primary" v-close-popup />
        <q-btn
          flat
          :label="user?.is_active ? 'Deactivate' : 'Activate'"
          :color="user?.is_active ? 'negative' : 'positive'"
          @click="toggleStatus"
        />
        <q-btn flat label="Edit" color="primary" @click="editUser" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios'

const props = defineProps({
  modelValue: Boolean,
  user: Object
})

const emit = defineEmits(['update:modelValue', 'edit', 'status-changed'])

const $q = useQuasar()
const show = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  show.value = val
})

watch(show, (val) => {
  emit('update:modelValue', val)
})

const toggleStatus = async () => {
  try {
    await api.patch(`/api/users/${props.user.id}/status`)
    emit('status-changed')
    $q.notify({
      type: 'positive',
      message: `User ${props.user.is_active ? 'deactivated' : 'activated'} successfully`
    })
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to update user status'
    })
  }
}

const editUser = () => {
  emit('edit', props.user)
  show.value = false
}
</script> 