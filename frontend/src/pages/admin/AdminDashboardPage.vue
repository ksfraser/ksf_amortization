<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
      <p class="text-gray-600 mt-1">Manage OAuth clients, users, and system metrics</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="card">
        <div class="card-body">
          <p class="text-sm text-gray-600 font-medium">Total Clients</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">24</p>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <p class="text-sm text-gray-600 font-medium">Active Users</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">1,248</p>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <p class="text-sm text-gray-600 font-medium">API Calls (24h)</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">45.6K</p>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <p class="text-sm text-gray-600 font-medium">System Uptime</p>
          <p class="text-3xl font-bold text-success-600 mt-2">99.9%</p>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex gap-4 border-b border-gray-200 mb-8">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          activeTab === tab.id
            ? 'border-primary-600 text-primary-600'
            : 'border-transparent text-gray-600 hover:text-primary-600',
        ]"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content -->
    <div>
      <!-- Clients Tab -->
      <div v-if="activeTab === 'clients'">
        <ClientList />
      </div>

      <!-- Metrics Tab -->
      <div v-else-if="activeTab === 'metrics'">
        <MetricsDashboard />
      </div>

      <!-- Users Tab -->
      <div v-else-if="activeTab === 'users'">
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-gray-900">User Management</h3>
          </div>
          <div class="card-body">
            <p class="text-gray-600">User management features coming soon...</p>
          </div>
        </div>
      </div>

      <!-- Settings Tab -->
      <div v-else-if="activeTab === 'settings'">
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-gray-900">System Settings</h3>
          </div>
          <div class="card-body">
            <p class="text-gray-600">System settings coming soon...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ClientList from '../../components/admin/ClientList.vue'
import MetricsDashboard from '../../components/admin/MetricsDashboard.vue'

/**
 * Admin Dashboard Page
 * 
 * Central admin hub for managing OAuth clients, users, and system
 */

const activeTab = ref('clients')

const tabs = [
  { id: 'clients', label: '🔐 OAuth Clients' },
  { id: 'metrics', label: '📊 Metrics' },
  { id: 'users', label: '👥 Users' },
  { id: 'settings', label: '⚙️ Settings' },
]
</script>
