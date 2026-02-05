import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export type ThemeMode = 'light' | 'dark'

export const useThemeStore = defineStore('theme', () => {
  // Initialize from localStorage or default to 'dark'
  const savedTheme = localStorage.getItem('theme') as ThemeMode | null
  const mode = ref<ThemeMode>(savedTheme || 'dark')

  // Watch for changes and persist to localStorage
  watch(mode, (newMode) => {
    localStorage.setItem('theme', newMode)
    applyTheme(newMode)
  })

  function applyTheme(theme: ThemeMode) {
    document.documentElement.setAttribute('data-theme', theme)
    // Also update body class for easier CSS targeting
    document.body.classList.remove('theme-light', 'theme-dark')
    document.body.classList.add(`theme-${theme}`)
  }

  function toggleTheme() {
    mode.value = mode.value === 'dark' ? 'light' : 'dark'
  }

  function setTheme(theme: ThemeMode) {
    mode.value = theme
  }

  // Initialize theme on store creation
  function init() {
    applyTheme(mode.value)
  }

  return {
    mode,
    toggleTheme,
    setTheme,
    init,
  }
})
