import { createApp, watch } from 'vue'
import { createPinia } from 'pinia'
import { Quasar, Notify, Dialog, Dark } from 'quasar'
import router from './router'

// Import icon libraries
import '@quasar/extras/material-icons/material-icons.css'
import '@quasar/extras/mdi-v7/mdi-v7.css'

// Import Quasar css
import 'quasar/src/css/index.sass'

import App from './App.vue'
import { useThemeStore } from './stores/theme'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(Quasar, {
  plugins: {
    Notify,
    Dialog,
    Dark
  },
  config: {
    dark: 'auto'
  }
})

// Initialize theme store and sync with Quasar Dark mode
const themeStore = useThemeStore()

// Watch theme changes and sync with Quasar
watch(() => themeStore.mode, (newMode) => {
  Dark.set(newMode === 'dark')
}, { immediate: true })

// Initialize theme on startup
themeStore.init()

app.mount('#app')
