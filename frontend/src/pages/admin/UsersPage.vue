<template>
  <q-page padding>
    <!-- Loading indicator -->
    <q-inner-loading :showing="loading">
      <q-spinner-dots size="50px" color="primary" />
    </q-inner-loading>

    <div class="row q-mb-md items-center justify-between">
      <div class="text-h5">Users Management</div>
      <q-btn color="primary" label="Add User" @click="openUserDialog()" />
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
            :color="props.row?.is_active ? 'positive' : 'negative'"
            text-color="white"
            dense
          >
            {{ props.row?.is_active ? 'Active' : 'Inactive' }}
          </q-chip>
        </q-td>
      </template>

      <!-- Roles Column -->
      <template v-slot:body-cell-roles="props">
        <q-td :props="props">
          <q-chip
            v-for="role in (props.row?.roles || [])"
            :key="role?.id"
            dense
            class="q-mr-xs"
          >
            {{ role?.name || 'Unknown Role' }}
          </q-chip>
        </q-td>
      </template>

      <!-- Actions Column -->
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-group flat>
            <q-btn 
              flat 
              round 
              :color="props.row.is_active ? 'negative' : 'positive'"
              :icon="props.row.is_active ? 'block' : 'check_circle'"
              @click="toggleUserStatus(props.row)"
              :title="props.row.is_active ? 'Deactivate User' : 'Activate User'"
            />
            <q-btn flat round color="info" icon="visibility" @click="viewUser(props.row)" />
            <q-btn flat round color="primary" icon="edit" @click="openUserDialog(props.row)" />
            <q-btn flat round color="negative" icon="delete" @click="confirmDelete(props.row)" />
          </q-btn-group>
        </q-td>
      </template>
    </q-table>

    <!-- User Dialog -->
    <q-dialog v-model="userDialog" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">{{ editingUser.id ? 'Edit User' : 'Add User' }}</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveUser" class="q-gutter-md">
            <q-input
              v-model="editingUser.name"
              label="Name"
              :rules="[val => !!val || 'Name is required']"
            />

            <q-input
              v-model="editingUser.email"
              label="Email"
              type="email"
              :rules="[
                val => !!val || 'Email is required',
                val => isValidEmail(val) || 'Invalid email format'
              ]"
            />

            <q-input
              v-model="editingUser.password"
              label="Password"
              type="password"
              :rules="[
                val => !editingUser.id || !!val || 'Password is required for new users',
                val => !val || val.length >= 8 || 'Password must be at least 8 characters'
              ]"
            />

            <q-select
              v-model="editingUser.roles"
              :options="roles"
              option-label="name"
              option-value="id"
              label="Roles"
              multiple
              use-chips
              emit-value
              map-options
              :rules="[val => val.length > 0 || 'At least one role is required']"
            >
              <template v-slot:selected-item="scope">
                <q-chip
                  removable
                  @remove="scope.removeAtIndex(scope.index)"
                  :label="scope.opt.name"
                  color="primary"
                  text-color="white"
                />
              </template>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.description }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <div class="row justify-end q-gutter-sm">
              <q-btn label="Cancel" color="negative" v-close-popup />
              <q-btn label="Save" type="submit" color="primary" />
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
    <q-dialog v-model="showDetailsDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">User Details</div>
        </q-card-section>

        <q-card-section v-if="selectedUser">
          <div class="q-gutter-y-md">
            <div>
              <strong>Name:</strong> {{ selectedUser.name }}
            </div>
            <div>
              <strong>Email:</strong> {{ selectedUser.email }}
            </div>
            <div>
              <strong>Roles:</strong>
              <q-chip
                v-for="role in selectedUser.roles"
                :key="role.id"
                color="primary"
                text-color="white"
                class="q-ml-sm"
              >
                {{ role.name }}
              </q-chip>
            </div>
            <div>
              <strong>Created:</strong> {{ new Date(selectedUser.created_at).toLocaleString() }}
            </div>
            <div>
              <strong>Last Updated:</strong> {{ new Date(selectedUser.updated_at).toLocaleString() }}
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios'

const $q = useQuasar()

// Table columns
const columns = [
  { 
    name: 'id', 
    label: 'ID', 
    field: row => row?.id || '', 
    sortable: true 
  },
  { 
    name: 'name', 
    label: 'Name', 
    field: row => row?.name || '', 
    sortable: true 
  },
  { 
    name: 'email', 
    label: 'Email', 
    field: row => row?.email || '', 
    sortable: true 
  },
  { 
    name: 'phone', 
    label: 'Phone', 
    field: row => row?.phone || '' 
  },
  { 
    name: 'roles', 
    label: 'Roles', 
    field: row => row?.roles || [] 
  },
  { 
    name: 'status', 
    label: 'Status', 
    field: row => row?.is_active ?? true 
  },
  { 
    name: 'actions', 
    label: 'Actions', 
    field: 'actions', 
    align: 'center' 
  }
]

// Initialize all refs with proper default values
const users = ref([]) // Initialize as empty array
const loading = ref(false)
const submitting = ref(false)
const search = ref('')
const roleFilter = ref(null)
const statusFilter = ref(null)
const userDialog = ref(false)
const showDeleteDialog = ref(false)
const showDetailsDialog = ref(false)
const editingUser = ref({
  name: '',
  email: '',
  password: '',
  roles: []
})
const pagination = ref({
  sortBy: 'name',
  descending: false,
  page: 1,
  rowsPerPage: 10
})
const selectedUser = ref(null)
const roles = ref([]) // Make sure this is initialized

// Status options for filter
const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

// Role options for select
const roleOptions = ref([])

// Computed filtered users with proper null checks
const filteredUsers = computed(() => {
  // Ensure users.value is an array
  if (!Array.isArray(users.value)) {
    return []
  }

  let filtered = [...users.value]

  // Apply search filter with null checks
  if (search.value) {
    const searchLower = search.value.toLowerCase()
    filtered = filtered.filter(user => {
      if (!user) return false
      
      const name = (user.name || '').toLowerCase()
      const email = (user.email || '').toLowerCase()
      const phone = (user.phone || '').toLowerCase()

      return name.includes(searchLower) ||
             email.includes(searchLower) ||
             phone.includes(searchLower)
    })
  }

  // Apply role filter with null checks
  if (roleFilter.value) {
    filtered = filtered.filter(user => {
      if (!user || !Array.isArray(user.roles)) return false
      return user.roles.some(role => role && role.id === roleFilter.value.value)
    })
  }

  // Apply status filter with null check
  if (statusFilter.value !== null) {
    filtered = filtered.filter(user => {
      if (!user) return false
      return user.is_active === statusFilter.value.value
    })
  }

  return filtered
})

// Methods
const fetchUsers = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/api/users')
    // Ensure we're setting an array
    users.value = Array.isArray(data) ? data : []
  } catch (err) {
    console.error('Error fetching users:', err)
    users.value = [] // Reset to empty array on error
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
    roles.value = data
    roleOptions.value = data.map(role => ({
      label: role.name,
      value: role.id
    }))
  } catch (err) {
    console.error('Error fetching roles:', err)
    roleOptions.value = []
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to fetch roles',
      position: 'top'
    })
  }
}

const resetForm = () => {
  editingUser.value = {
    name: '',
    email: '',
    password: '',
    roles: []
  }
}

const openUserDialog = (user = null) => {
  if (user) {
    editingUser.value = {
      ...user,
      password: '',
      roles: user.roles.map(r => r.id)
    }
  } else {
    resetForm()
  }
  userDialog.value = true
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

const toggleUserStatus = async (user) => {
  try {
    loading.value = true
    await api.put(`/api/users/${user.id}/toggle-status`)
    await fetchUsers() // Refresh the users list
    $q.notify({
      type: 'positive',
      message: `User ${user.is_active ? 'deactivated' : 'activated'} successfully`,
      position: 'top'
    })
  } catch (error) {
    console.error('Error toggling user status:', error)
    $q.notify({
      type: 'negative',
      message: 'Error updating user status',
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

const saveUser = async () => {
  submitting.value = true
  try {
    loading.value = true
    const userData = {
      ...editingUser.value,
      roles: editingUser.value.roles
    }

    if (editingUser.value.id) {
      await api.put(`/api/users/${editingUser.value.id}`, userData)
    } else {
      await api.post('/api/users', userData)
    }
    await fetchUsers()
    userDialog.value = false
    resetForm()
    $q.notify({
      type: 'positive',
      message: `User ${editingUser.value.id ? 'updated' : 'created'} successfully`,
      position: 'top'
    })
  } catch (error) {
    console.error('Error saving user:', error)
    $q.notify({
      type: 'negative',
      message: 'Error saving user',
      position: 'top'
    })
  } finally {
    loading.value = false
    submitting.value = false
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