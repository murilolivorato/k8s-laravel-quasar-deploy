<template>
  <q-page padding>
    <div class="row q-mb-md items-center justify-between">
      <div class="text-h5">User Management</div>
      <q-btn
        color="primary"
        icon="add"
        label="Add User"
        @click="openCreateDialog"
      />
    </div>

    <!-- Search and Filters -->
    <div class="row q-mb-md q-gutter-md">
      <q-input
        v-model="search"
        dense
        outlined
        placeholder="Search users..."
        class="col-12 col-md-4"
      >
        <template v-slot:append>
          <q-icon name="search" />
        </template>
      </q-input>

      <q-select
        v-model="roleFilter"
        :options="roleOptions"
        dense
        outlined
        label="Filter by Role"
        class="col-12 col-md-3"
        clearable
      />

      <q-select
        v-model="statusFilter"
        :options="statusOptions"
        dense
        outlined
        label="Filter by Status"
        class="col-12 col-md-3"
        clearable
      />
    </div>

    <!-- Users Table -->
    <q-table
      :rows="filteredUsers"
      :columns="columns"
      row-key="id"
      :loading="loading"
      v-model:pagination="pagination"
      :rows-per-page-options="[10, 20, 50, 0]"
      flat
      bordered
    >
      <!-- Status Column -->
      <template v-slot:body-cell-status="props">
        <q-td :props="props">
          <q-chip
            :color="props.row.is_active ? 'positive' : 'negative'"
            text-color="white"
            dense
          >
            {{ props.row.is_active ? 'Active' : 'Inactive' }}
          </q-chip>
        </q-td>
      </template>

      <!-- Roles Column -->
      <template v-slot:body-cell-roles="props">
        <q-td :props="props">
          <q-chip
            v-for="role in props.row.roles"
            :key="role.id"
            dense
            class="q-mr-xs"
          >
            {{ role.name }}
          </q-chip>
        </q-td>
      </template>

      <!-- Actions Column -->
      <template v-slot:body-cell-actions="props">
        <q-td :props="props" class="q-gutter-xs">
          <q-btn
            flat
            round
            color="primary"
            icon="visibility"
            @click="viewUser(props.row)"
          >
            <q-tooltip>View Details</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            color="primary"
            icon="edit"
            @click="editUser(props.row)"
          >
            <q-tooltip>Edit User</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            color="negative"
            icon="delete"
            @click="confirmDelete(props.row)"
          >
            <q-tooltip>Delete User</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <!-- Create/Edit Dialog -->
    <q-dialog v-model="showDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ isEditing ? 'Edit User' : 'Create User' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
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
              :rules="[
                val => !!val || 'Email is required',
                val => isValidEmail(val) || 'Invalid email format'
              ]"
              outlined
            />

            <q-input
              v-model="form.phone"
              label="Phone"
              outlined
            />

            <q-input
              v-if="!isEditing"
              v-model="form.password"
              label="Password"
              type="password"
              :rules="[val => !isEditing && (!val || val.length >= 8) || 'Password must be at least 8 characters']"
              outlined
            />

            <q-select
              v-model="form.roles"
              :options="roleOptions"
              label="Roles"
              multiple
              outlined
              :rules="[val => val.length > 0 || 'At least one role is required']"
            />

            <q-toggle
              v-model="form.is_active"
              label="Active"
            />

            <q-input
              v-model="form.notes"
              label="Notes"
              type="textarea"
              outlined
            />

            <div class="row justify-end q-mt-md">
              <q-btn
                label="Cancel"
                color="grey"
                flat
                v-close-popup
                class="q-mr-sm"
              />
              <q-btn
                :label="isEditing ? 'Update' : 'Create'"
                type="submit"
                color="primary"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Delete Confirmation Dialog -->
    <q-dialog v-model="showDeleteDialog">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white" />
          <span class="q-ml-sm">Are you sure you want to delete this user?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="primary" v-close-popup />
          <q-btn flat label="Delete" color="negative" @click="deleteUser" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- User Details Dialog -->
    <UserDetailsDialog
      v-model="showDetailsDialog"
      :user="selectedUser"
      @edit="editUser"
      @status-changed="fetchUsers"
    />
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios'
import UserDetailsDialog from 'components/UserDetailsDialog.vue'

const $q = useQuasar()

// Table columns
const columns = [
  { name: 'id', label: 'ID', field: 'id', sortable: true },
  { name: 'name', label: 'Name', field: 'name', sortable: true },
  { name: 'email', label: 'Email', field: 'email', sortable: true },
  { name: 'phone', label: 'Phone', field: 'phone' },
  { name: 'roles', label: 'Roles', field: 'roles' },
  { name: 'status', label: 'Status', field: 'is_active' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' }
]

// State
const users = ref([])
const loading = ref(false)
const search = ref('')
const roleFilter = ref(null)
const statusFilter = ref(null)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const isEditing = ref(false)
const selectedUser = ref(null)
const pagination = ref({
  sortBy: 'name',
  descending: false,
  page: 1,
  rowsPerPage: 10
})
const showDetailsDialog = ref(false)

// Form data
const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  roles: [],
  is_active: true,
  notes: ''
})

// Role options for select
const roleOptions = ref([])

// Status options for filter
const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

// Computed filtered users
const filteredUsers = computed(() => {
  let filtered = [...users.value]

  // Apply search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase()
    filtered = filtered.filter(user => 
      user.name.toLowerCase().includes(searchLower) ||
      user.email.toLowerCase().includes(searchLower) ||
      user.phone?.toLowerCase().includes(searchLower)
    )
  }

  // Apply role filter
  if (roleFilter.value) {
    filtered = filtered.filter(user => 
      user.roles.some(role => role.id === roleFilter.value.value)
    )
  }

  // Apply status filter
  if (statusFilter.value !== null) {
    filtered = filtered.filter(user => user.is_active === statusFilter.value.value)
  }

  return filtered
})

// Methods
const fetchUsers = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/api/users')
    users.value = data
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to fetch users',
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

const fetchRoles = async () => {
  try {
    const { data } = await api.get('/api/roles')
    roleOptions.value = data.map(role => ({
      label: role.name,
      value: role.id
    }))
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to fetch roles',
      position: 'top'
    })
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    phone: '',
    password: '',
    roles: [],
    is_active: true,
    notes: ''
  }
}

const openCreateDialog = () => {
  isEditing.value = false
  resetForm()
  showDialog.value = true
}

const editUser = (user) => {
  isEditing.value = true
  selectedUser.value = user
  form.value = {
    ...user,
    roles: user.roles.map(role => role.id)
  }
  showDialog.value = true
}

const viewUser = (user) => {
  selectedUser.value = user
  showDetailsDialog.value = true
}

const confirmDelete = (user) => {
  selectedUser.value = user
  showDeleteDialog.value = true
}

const deleteUser = async () => {
  if (!selectedUser.value) return

  try {
    await api.delete(`/api/users/${selectedUser.value.id}`)
    await fetchUsers()
    $q.notify({
      type: 'positive',
      message: 'User deleted successfully',
      position: 'top'
    })
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to delete user',
      position: 'top'
    })
  }
}

const onSubmit = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/api/users/${selectedUser.value.id}`, form.value)
      $q.notify({
        type: 'positive',
        message: 'User updated successfully',
        position: 'top'
      })
    } else {
      await api.post('/api/users', form.value)
      $q.notify({
        type: 'positive',
        message: 'User created successfully',
        position: 'top'
      })
    }
    showDialog.value = false
    await fetchUsers()
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Operation failed',
      position: 'top'
    })
  }
}

const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Lifecycle hooks
onMounted(async () => {
  await Promise.all([fetchUsers(), fetchRoles()])
})
</script>

<style scoped>
.q-table__card {
  box-shadow: none;
}
</style> 