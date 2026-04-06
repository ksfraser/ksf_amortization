<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Header -->
    <div class="text-center mb-6">
      <h2 class="text-2xl font-semibold text-gray-900">
        Grant Access
      </h2>
      <p class="text-gray-600 mt-2">
        You are granting this application permission to access your account
      </p>
    </div>

    <!-- Scopes by Category -->
    <div v-if="groupedScopes.length > 0" class="space-y-6">
      <div v-for="group in groupedScopes" :key="group.category" class="space-y-3">
        <!-- Category Header -->
        <h3 class="font-semibold text-gray-900">{{ group.category || 'Permissions' }}</h3>

        <!-- Scopes in Category -->
        <div class="space-y-3">
          <label v-for="scope in group.scopes" :key="scope.id" class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
            <input
              type="checkbox"
              :checked="selectedScopes.includes(scope.id)"
              :disabled="scope.required"
              @change="toggleScope(scope.id)"
              class="h-4 w-4 mt-0.5"
            />
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="font-medium text-gray-900">{{ scope.name }}</span>
                <span v-if="scope.required" class="inline-block px-2 py-1 text-xs font-semibold text-red-600 bg-red-50 rounded">
                  Required
                </span>
              </div>
              <p class="text-sm text-gray-600 mt-1">{{ scope.description }}</p>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Warning -->
    <Alert
      type="warning"
      title="Authorization Required"
      message="Only authorize this application if you trust it. It will have access to your account data."
      closable
    />

    <!-- Action Buttons -->
    <div class="flex gap-3 pt-4">
      <Button
        variant="secondary"
        class="flex-1"
        @click="handleDeny"
        :disabled="isLoading"
      >
        Deny
      </Button>
      <Button
        variant="primary"
        class="flex-1"
        @click="handleApprove"
        :loading="isLoading"
        :disabled="isLoading"
      >
        Approve
      </Button>
    </div>

    <!-- Error Message -->
    <Alert
      v-if="error"
      type="error"
      title="Error"
      :message="error"
      closable
      @close="error = ''"
    />
  </form>
</template>

<script setup>
import { ref, computed } from 'vue'
import Button from '../common/Button.vue'
import Alert from '../common/Alert.vue'

/**
 * Consent Form Component
 * 
 * Displays OAuth consent screen where user approves/denies scope access
 * 
 * Props:
 * - appName: Application name requesting access (optional)
 * - scopes: Array of scope objects with id, name, description, required, category
 * 
 * Emits:
 * - approve: When user approves consent with selected scopes
 * - deny: When user denies consent
 */

const props = defineProps({
  appName: {
    type: String,
    default: 'Application',
  },
  scopes: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['approve', 'deny'])

const isLoading = ref(false)
const error = ref('')
const selectedScopes = ref([])

// Initialize selected scopes with required scopes
const initializeScopes = () => {
  selectedScopes.value = props.scopes
    .filter((scope) => scope.required)
    .map((scope) => scope.id)
}

// Group scopes by category
const groupedScopes = computed(() => {
  const groups = {}
  
  props.scopes.forEach((scope) => {
    const category = scope.category || 'Permissions'
    if (!groups[category]) {
      groups[category] = { category, scopes: [] }
    }
    groups[category].scopes.push(scope)
  })

  return Object.values(groups)
})

const toggleScope = (scopeId) => {
  const index = selectedScopes.value.indexOf(scopeId)
  if (index > -1) {
    selectedScopes.value.splice(index, 1)
  } else {
    selectedScopes.value.push(scopeId)
  }
}

const handleApprove = async () => {
  isLoading.value = true
  try {
    emit('approve', {
      scopes: selectedScopes.value,
    })
  } catch (err) {
    error.value = err.message || 'Failed to approve consent'
  } finally {
    isLoading.value = false
  }
}

const handleDeny = async () => {
  isLoading.value = true
  try {
    emit('deny')
  } catch (err) {
    error.value = err.message || 'Failed to deny consent'
  } finally {
    isLoading.value = false
  }
}

// Initialize on mount
initializeScopes()
</script>
