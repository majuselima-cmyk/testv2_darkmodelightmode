export const useDarkMode = () => {
  const isDark = ref(true) // Default dark mode

  const applyTheme = (dark: boolean) => {
    if (process.client) {
      const html = document.documentElement
      if (dark) {
        html.classList.add('dark')
      } else {
        html.classList.remove('dark')
      }
    }
  }

  // Initialize from localStorage or default to dark
  if (process.client) {
    const savedTheme = localStorage.getItem('theme')
    if (savedTheme !== null) {
      isDark.value = savedTheme === 'dark'
    } else {
      isDark.value = true // Default dark mode
      localStorage.setItem('theme', 'dark')
    }
    applyTheme(isDark.value)
  }

  const toggle = () => {
    isDark.value = !isDark.value
    if (process.client) {
      localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
      applyTheme(isDark.value)
    }
  }

  const setDark = () => {
    isDark.value = true
    if (process.client) {
      localStorage.setItem('theme', 'dark')
      applyTheme(true)
    }
  }

  const setLight = () => {
    isDark.value = false
    if (process.client) {
      localStorage.setItem('theme', 'light')
      applyTheme(false)
    }
  }

  // Watch for changes and apply theme
  watch(isDark, (newValue) => {
    if (process.client) {
      applyTheme(newValue)
    }
  })

  // Apply theme on mount
  onMounted(() => {
    applyTheme(isDark.value)
  })

  return {
    isDark: readonly(isDark),
    toggle,
    setDark,
    setLight
  }
}
