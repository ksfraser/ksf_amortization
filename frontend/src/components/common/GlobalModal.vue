<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="close" />

    <!-- Modal -->
    <div class="relative bg-white rounded-lg shadow-lg max-w-md w-full mx-4 fade-in">
      <!-- Header -->
      <div v-if="title" class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h2 class="text-xl font-semibold text-gray-900">{{ title }}</h2>
      </div>

      <!-- Body -->
      <div class="px-6 py-4 text-gray-700">
        <p>{{ message }}</p>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg flex gap-3 justify-end">
        <Button
          v-if="type !== 'success' && type !== 'error'"
          variant="secondary"
          @click="cancel"
        >
          {{ cancelText }}
        </Button>
        <Button
          :variant="isDestructive ? 'danger' : 'primary'"
          @click="confirm"
        >
          {{ confirmText }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useModalStore } from '../../stores/ui'
import Button from './Button.vue'

/**
 * Global Modal Component
 * 
 * Displays modals with title, message, and action buttons
 * Uses modal store for reactive state
 */

const modalStore = useModalStore()

const isOpen = computed(() => modalStore.isOpen)
const type = computed(() => modalStore.type)
const title = computed(() => modalStore.title)
const message = computed(() => modalStore.message)
const confirmText = computed(() => modalStore.confirmText)
const cancelText = computed(() => modalStore.cancelText)
const isDestructive = computed(() => modalStore.isDestructive)

const confirm = () => modalStore.confirm()
const cancel = () => modalStore.cancel()
const close = () => modalStore.close()
</script>
