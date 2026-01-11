<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="text-center">
      <h1 class="text-2xl font-bold text-gray-900 mb-4">Redirecting to Dashboard...</h1>
    </div>
  </div>
</template>

<script setup>
// Redirect to dashboard
onMounted(() => {
  navigateTo('/dashboard')
})
</script>

