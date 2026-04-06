<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Audit Log</h1>
      <p class="text-gray-600 mt-1">System activity and user actions</p>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="card-body flex gap-4 items-center flex-wrap">
        <input
          type="text"
          placeholder="Search audit log..."
          class="flex-1 min-w-xs"
        />
        <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm">
          <option>All Actions</option>
          <option>Create</option>
          <option>Update</option>
          <option>Delete</option>
        </select>
        <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm">
          <option>All Users</option>
          <option>Admin</option>
          <option>User</option>
        </select>
      </div>
    </div>

    <!-- Audit Entries -->
    <div class="space-y-4">
      <div v-for="entry in auditLog" :key="entry.id" class="card">
        <div class="card-body">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3">
                <span :class="['w-10 h-10 rounded-lg flex items-center justify-center font-bold text-white', getActionColor(entry.action)]">
                  {{ getActionIcon(entry.action) }}
                </span>
                <div>
                  <p class="font-semibold text-gray-900">{{ entry.action }}: {{ entry.resource }}</p>
                  <p class="text-sm text-gray-600">by {{ entry.user }}</p>
                </div>
              </div>
              <p class="text-sm text-gray-600 mt-2">{{ entry.details }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500">{{ formatDate(entry.timestamp) }}</p>
              <span :class="['badge mt-2', entry.status === 'success' ? 'badge-success' : 'badge-error']">
                {{ entry.status }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex items-center justify-between">
      <p class="text-sm text-gray-600">Showing 1-10 of 243 entries</p>
      <div class="flex gap-2">
        <button class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
          ← Previous
        </button>
        <button class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">
          Next →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { formatDate } from '../../utils/helpers'

/**
 * Audit Log Page
 * 
 * System and user activity audit trail
 */

const auditLog = [
  {
    id: 1,
    action: 'Created',
    resource: 'OAuth Client',
    user: 'admin@example.com',
    details: 'New client "Mobile App" created with scopes: read, write',
    timestamp: new Date(Date.now() - 2 * 3600000),
    status: 'success',
  },
  {
    id: 2,
    action: 'Updated',
    resource: 'User',
    user: 'system',
    details: 'User profile updated: email changed',
    timestamp: new Date(Date.now() - 5 * 3600000),
    status: 'success',
  },
  {
    id: 3,
    action: 'Deleted',
    resource: 'OAuth Client',
    user: 'admin@example.com',
    details: 'Client "Legacy App" deleted',
    timestamp: new Date(Date.now() - 1 * 24 * 3600000),
    status: 'success',
  },
  {
    id: 4,
    action: 'Access',
    resource: 'API Endpoint',
    user: 'user@example.com',
    details: 'Failed authentication attempt on /api/user/me',
    timestamp: new Date(Date.now() - 2 * 24 * 3600000),
    status: 'error',
  },
]

const getActionIcon = (action) => {
  const icons = {
    'Created': '+',
    'Updated': '✎',
    'Deleted': '×',
    'Access': '→',
  }
  return icons[action] || '•'
}

const getActionColor = (action) => {
  const colors = {
    'Created': 'bg-success-600',
    'Updated': 'bg-primary-600',
    'Deleted': 'bg-error-600',
    'Access': 'bg-warning-600',
  }
  return colors[action] || 'bg-gray-600'
}
</script>
