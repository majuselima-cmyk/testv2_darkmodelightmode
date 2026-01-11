<template>
  <button
    @click="toggle"
    :class="[
      'relative inline-flex h-10 w-10 items-center justify-center rounded-lg transition-all duration-200',
      'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600',
      'text-gray-800 dark:text-gray-200',
      'shadow-sm hover:shadow-md'
    ]"
    :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
  >
    <i v-if="isDark" class="fas fa-sun text-lg transition-transform duration-200 hover:rotate-180"></i>
    <i v-else class="fas fa-moon text-lg transition-transform duration-200 hover:rotate-12"></i>
  </button>
</template>

<script setup lang="ts">
const { isDark, toggle } = useDarkMode()
</script>


