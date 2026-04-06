<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="$emit('close')" />

    <!-- Modal -->
    <div class="relative bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg sticky top-0">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ isEditing ? 'Edit Client' : 'Create New Client' }}
        </h3>
      </div>

      <form @submit.prevent="handleSubmit" class="px-6 py-4 space-y-4">
        <!-- Client Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Client Name
          </label>
          <input
            v-model="form.name"
            type="text"
            placeholder="My Application"
            class="w-full"
            required
            :disabled="isSubmitting"
          />
          <p v-if="fieldErrors.name" class="text-error-600 text-sm mt-1">
            {{ fieldErrors.name }}
          </p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea
            v-model="form.description"
            placeholder="Brief description of your application"
            rows="3"
            class="w-full"
            :disabled="isSubmitting"
          />
        </div>

        <!-- Redirect URIs -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Redirect URIs (one per line)
          </label>
          <textarea
            v-model="form.redirectUris"
            placeholder="https://example.com/callback&#10;https://example.com/auth"
            rows="4"
            class="w-full font-mono text-sm"
            required
            :disabled="isSubmitting"
          />
          <p class="text-gray-600 text-xs mt-1">
            Comma-separated or one per line
          </p>
          <p v-if="fieldErrors.redirectUris" class="text-error-600 text-sm mt-1">
            {{ fieldErrors.redirectUris }}
          </p>
        </div>

        <!-- Scopes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Granted Scopes
          </label>
          <div class="space-y-2">
            <label v-for="scope in availableScopes" :key="scope" class="flex items-center gap-2">
              <input
                type="checkbox"
                :value="scope"
                :checked="form.scopes.includes(scope)"
                @change="toggleScope(scope)"
                :disabled="isSubmitting"
              />
              <span class="text-sm text-gray-700">{{ scope }}</span>
            </label>
          </div>
          <p v-if="fieldErrors.scopes" class="text-error-600 text-sm mt-1">
            {{ fieldErrors.scopes }}
          </p>
        </div>

        <!-- Error Message -->
        <Alert
          v-if="generalError"
          type="error"
          title="Error"
          :message="generalError"
          @close="generalError = ''"
        />

        <!-- Actions -->
        <div class="flex gap-3 pt-4 border-t border-gray-200">
          <Button
            variant="secondary"
            class="flex-1"
            @click="$emit('close')"
          >
            Cancel
          </Button>
          <Button
            variant="primary"
            class="flex-1"
            type="submit"
            :loading="isSubmitting"
            :disabled="isSubmitting"
          >
            {{ isEditing ? 'Update Client' : 'Create Client' }}
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { getValidationErrors } from '../../utils/helpers'
import Button from '../common/Button.vue'
import Alert from '../common/Alert.vue'

/**
 * Client Form Component
 * 
 * Form for creating or editing OAuth clients
 * 
 * Props:
 * - client: (Optional) Client to edit
 * 
 * Emits:
 * - save: When form submitted
 * - close: When modal closed
 */

const props = defineProps({
  client: Object,
})

const emit = defineEmits(['save', 'close'])

const availableScopes = ['read', 'write', 'profile', 'email', 'offline_access']

const form = reactive({
  name: props.client?.name || '',
  description: props.client?.description || '',
  redirectUris: props.client?.redirect_uris?.join('\n') || '',
  scopes: props.client?.scopes || [],
})

const isSubmitting = ref(false)
const generalError = ref('')
const fieldErrors = reactive({
  name: '',
  redirectUris: '',
  scopes: '',
})

const isEditing = !!props.client

const toggleScope = (scope) => {
  const index = form.scopes.indexOf(scope)
  if (index > -1) {
    form.scopes.splice(index, 1)
  } else {
    form.scopes.push(scope)
  }
}

const handleSubmit = async () => {
  // Validate
  Object.assign(fieldErrors, { name: '', redirectUris: '', scopes: '' })
  generalError.value = ''

  if (!form.name) {
    fieldErrors.name = 'Client name is required'
    return
  }

  if (!form.redirectUris) {
    fieldErrors.redirectUris = 'At least one redirect URI is required'
    return
  }

  if (form.scopes.length === 0) {
    fieldErrors.scopes = 'At least one scope must be selected'
    return
  }

  isSubmitting.value = true

  try {
    const redirectUris = form.redirectUris
      .split('\n')
      .map((uri) => uri.trim())
      .filter((uri) => uri.length > 0)

    emit('save', {
      name: form.name,
      description: form.description,
      redirect_uris: redirectUris,
      scopes: form.scopes,
    })
  } catch (error) {
    const validationErrors = getValidationErrors(error)
    if (Object.keys(validationErrors).length > 0) {
      Object.assign(fieldErrors, validationErrors)
    } else {
      generalError.value = 'Failed to save client'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>
