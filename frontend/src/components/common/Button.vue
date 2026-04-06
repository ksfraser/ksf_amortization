<template>
  <button
    :class="[
      'btn',
      `btn-${variantClass}`,
      `btn-${size}`,
      { 'opacity-50 cursor-not-allowed': disabled, 'animate-pulse': loading },
    ]"
    :disabled="disabled || loading"
    @click="handleClick"
    v-bind="$attrs"
  >
    <span v-if="loading" class="spinner mr-2">
      <svg
        class="animate-spin h-4 w-4"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </span>
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

/**
 * Reusable Button Component
 * 
 * Props:
 * - variant: Button style (primary, secondary, success, error, warning)
 * - size: Button size (sm, md, lg)
 * - disabled: Disable button
 * - loading: Show loading spinner
 * 
 * Usage:
 * <Button variant="primary" @click="handleClick">
 *   Click Me
 * </Button>
 */

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'success', 'error', 'warning'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['click'])

// Map error variant to danger class
const variantClass = computed(() => {
  return props.variant === 'error' ? 'danger' : props.variant
})

// Handle click - don't emit if disabled or loading
const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>
