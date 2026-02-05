<script setup lang="ts">
import { onMounted } from 'vue'
import { useThemeStore } from './stores/theme'

const themeStore = useThemeStore()

onMounted(() => {
  themeStore.init()
})
</script>

<template>
  <router-view />
</template>

<style>
/* CSS Variables for themes */
:root {
  /* Dark theme (default) */
  --bg-primary: #0f0f23;
  --bg-secondary: #1a1a2e;
  --bg-tertiary: #16213e;
  --bg-card: rgba(26, 26, 46, 0.9);
  --bg-card-solid: #1a1a2e;
  --bg-hover: rgba(139, 92, 246, 0.15);
  --bg-input: rgba(255, 255, 255, 0.08);
  --bg-input-focus: rgba(255, 255, 255, 0.12);
  
  --text-primary: #ffffff;
  --text-secondary: rgba(255, 255, 255, 0.8);
  --text-tertiary: rgba(255, 255, 255, 0.6);
  --text-muted: rgba(255, 255, 255, 0.4);
  
  --border-color: rgba(255, 255, 255, 0.1);
  --border-hover: rgba(255, 255, 255, 0.2);
  --border-focus: rgba(139, 92, 246, 0.5);
  
  --accent-primary: #8B5CF6;
  --accent-secondary: #EC4899;
  --accent-tertiary: #6366F1;
  
  --shadow-color: rgba(0, 0, 0, 0.3);
  --scrollbar-track: rgba(255, 255, 255, 0.05);
  --scrollbar-thumb: rgba(139, 92, 246, 0.3);
  --scrollbar-thumb-hover: rgba(139, 92, 246, 0.5);
  
  /* Dark theme specific */
  --drawer-bg: rgba(26, 26, 46, 0.95);
  --header-bg: rgba(26, 26, 46, 0.9);
  --main-content-bg: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
}

[data-theme="light"] {
  --bg-primary: #f1f5f9;
  --bg-secondary: #e2e8f0;
  --bg-tertiary: #f8fafc;
  --bg-card: rgba(255, 255, 255, 0.98);
  --bg-card-solid: #ffffff;
  --bg-hover: rgba(139, 92, 246, 0.08);
  --bg-input: #ffffff;
  --bg-input-focus: #ffffff;
  
  --text-primary: #1e293b;
  --text-secondary: #334155;
  --text-tertiary: #64748b;
  --text-muted: #94a3b8;
  
  --border-color: #e2e8f0;
  --border-hover: #cbd5e1;
  --border-focus: #8B5CF6;
  
  --accent-primary: #8B5CF6;
  --accent-secondary: #EC4899;
  --accent-tertiary: #6366F1;
  
  --shadow-color: rgba(0, 0, 0, 0.08);
  --scrollbar-track: rgba(0, 0, 0, 0.03);
  --scrollbar-thumb: rgba(139, 92, 246, 0.25);
  --scrollbar-thumb-hover: rgba(139, 92, 246, 0.4);
  
  /* Light theme specific */
  --drawer-bg: #ffffff;
  --header-bg: rgba(255, 255, 255, 0.95);
  --main-content-bg: #f1f5f9;
  --input-border: #e2e8f0;
  --input-border-focus: #8B5CF6;
}

html, body, #app {
  margin: 0;
  padding: 0;
  min-height: 100vh;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: var(--bg-primary);
  color: var(--text-primary);
  transition: background-color 0.3s ease, color 0.3s ease;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: var(--scrollbar-track);
}

::-webkit-scrollbar-thumb {
  background: var(--scrollbar-thumb);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--scrollbar-thumb-hover);
}
</style>
