<template>
  <q-page padding>
    <div class="row q-mb-md items-center justify-between">
      <div class="text-h5">Roles Management</div>
      <q-btn color="primary" label="Add Role" @click="openRoleDialog()" />
    </div>

    <q-table
      :rows="roles"
      :columns="columns"
      row-key="id"
      :loading="loading"
      v-model:pagination="pagination"
    >
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-group flat>
            <q-btn flat round color="primary" icon="edit" @click="openRoleDialog(props.row)" />
            <q-btn flat round color="negative" icon="delete" @click="confirmDelete(props.row)" />
          </q-btn-group>
        </q-td>
      </template>
    </q-table>

    <!-- Role Dialog -->
    <q-dialog v-model="roleDialog" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">{{ editingRole.id ? 'Edit Role' : 'Add Role' }}</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveRole" class="q-gutter-md">
            <q-input
              v-model="editingRole.name"
              label="Name"
              :rules="[val => !!val || 'Name is required']"
            />

            <q-input
              v-model="editingRole.slug"
              label="Slug"
              :rules="[val => !!val || 'Slug is required']"
            />

            <q-input
              v-model="editingRole.description"
              label="Description"
              type="textarea"
            />

            <div class="row justify-end q-gutter-sm">
              <q-btn label="Cancel" color="negative" v-close-popup />
              <q-btn label="Save" type="submit" color="primary" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios'

export default {
  name: 'RolesPage',
  setup () {
    const $q = useQuasar()
    const roles = ref([])
    const loading = ref(false)
    const roleDialog = ref(false)
    const editingRole = ref({
      name: '',
      slug: '',
      description: ''
    })

    const columns = [
      { name: 'id', label: 'ID', field: 'id', sortable: true },
      { name: 'name', label: 'Name', field: 'name', sortable: true },
      { name: 'slug', label: 'Slug', field: 'slug', sortable: true },
      { name: 'description', label: 'Description', field: 'description' },
      { name: 'actions', label: 'Actions', field: 'actions', align: 'center' }
    ]

    const pagination = ref({
      sortBy: 'name',
      descending: false,
      page: 1,
      rowsPerPage: 10
    })

    const fetchRoles = async () => {
      try {
        loading.value = true
        const response = await api.get('/api/roles')
        roles.value = response.data
      } catch (error) {
        console.error('Error fetching roles:', error)
        $q.notify({
          type: 'negative',
          message: 'Error loading roles',
          position: 'top'
        })
      } finally {
        loading.value = false
      }
    }

    const openRoleDialog = (role = null) => {
      if (role) {
        editingRole.value = { ...role }
      } else {
        editingRole.value = {
          name: '',
          slug: '',
          description: ''
        }
      }
      roleDialog.value = true
    }

    const saveRole = async () => {
      try {
        loading.value = true
        if (editingRole.value.id) {
          await api.put(`/api/roles/${editingRole.value.id}`, editingRole.value)
        } else {
          await api.post('/api/roles', editingRole.value)
        }
        await fetchRoles()
        roleDialog.value = false
        $q.notify({
          type: 'positive',
          message: `Role ${editingRole.value.id ? 'updated' : 'created'} successfully`,
          position: 'top'
        })
      } catch (error) {
        console.error('Error saving role:', error)
        $q.notify({
          type: 'negative',
          message: 'Error saving role',
          position: 'top'
        })
      } finally {
        loading.value = false
      }
    }

    const confirmDelete = (role) => {
      $q.dialog({
        title: 'Confirm',
        message: `Are you sure you want to delete role ${role.name}?`,
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          loading.value = true
          await api.delete(`/api/roles/${role.id}`)
          await fetchRoles()
          $q.notify({
            type: 'positive',
            message: 'Role deleted successfully',
            position: 'top'
          })
        } catch (error) {
          console.error('Error deleting role:', error)
          $q.notify({
            type: 'negative',
            message: 'Error deleting role',
            position: 'top'
          })
        } finally {
          loading.value = false
        }
      })
    }

    onMounted(() => {
      fetchRoles()
    })

    return {
      roles,
      loading,
      columns,
      pagination,
      roleDialog,
      editingRole,
      openRoleDialog,
      saveRole,
      confirmDelete
    }
  }
}
</script>

<style scoped>
.q-table__card {
  box-shadow: none;
}
</style> 