<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useUserStore } from '../stores/user'
import { useThemeStore } from '../stores/theme'

const $q = useQuasar()
const router = useRouter()
const userStore = useUserStore()
const themeStore = useThemeStore()
const leftDrawerOpen = ref(false)

const isDarkMode = computed(() => themeStore.mode === 'dark')

onMounted(() => {
  userStore.init()
})

const menuItems = [
  { icon: 'add_circle', label: '新しいチャット', action: 'new' },
  { icon: 'history', label: '履歴', action: 'history' },
  { icon: 'star', label: 'お気に入り', action: 'favorites' },
]

const bottomMenuItems = [
  { icon: 'settings', label: '設定', action: 'settings' },
  { icon: 'help', label: 'ヘルプ', action: 'help' },
]

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function handleMenuClick(action: string) {
  console.log('Menu action:', action)
}

function handleLogin() {
  router.push('/login')
}

async function handleLogout() {
  await userStore.logout()
  $q.notify({
    type: 'positive',
    message: 'ログアウトしました',
    position: 'top',
  })
}

function toggleTheme() {
  themeStore.toggleTheme()
}
</script>

<template>
  <q-layout view="lHh LpR lFf" class="main-layout">
    <!-- Header -->
    <q-header class="header-bar">
      <q-toolbar class="q-px-md">
        <q-btn
          flat
          dense
          round
          icon="menu"
          class="menu-btn"
          @click="toggleLeftDrawer"
        />

        <q-toolbar-title class="logo-title">

          <span class="logo-text">SekouAI</span>
        </q-toolbar-title>

        <q-space />

        <!-- Theme Toggle Button -->
        <q-btn
          flat
          round
          dense
          :icon="isDarkMode ? 'light_mode' : 'dark_mode'"
          class="theme-toggle-btn q-mr-sm"
          @click="toggleTheme"
        >
          <q-tooltip>{{ isDarkMode ? 'ライトモード' : 'ダークモード' }}</q-tooltip>
        </q-btn>

        <!-- 右上角登录/用户区域 -->
        <template v-if="userStore.isLoggedIn">
          <q-btn flat round>
            <q-avatar size="36px" color="primary" text-color="white">
              <img v-if="userStore.user?.avatar" :src="userStore.user.avatar" />
              <span v-else>{{ userStore.user?.name?.charAt(0) || 'U' }}</span>
            </q-avatar>
            <q-menu>
              <q-list style="min-width: 200px">
                <q-item>
                  <q-item-section>
                    <q-item-label class="text-weight-bold">{{ userStore.user?.name }}</q-item-label>
                    <q-item-label caption>{{ userStore.user?.email }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-separator />
                <q-item clickable v-close-popup @click="handleLogout">
                  <q-item-section avatar>
                    <q-icon name="logout" />
                  </q-item-section>
                  <q-item-section>ログアウト</q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>
        </template>
        <template v-else>
          <q-btn
            unelevated
            color="primary"
            label="ログイン"
            class="login-btn"
            @click="handleLogin"
          />
        </template>
      </q-toolbar>
    </q-header>

    <!-- Left Drawer -->
    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      :width="280"
      :breakpoint="768"
      class="left-drawer"
    >
      <q-scroll-area class="fit">
        <div class="drawer-content">
          <!-- 新建对话按钮 -->
          <q-btn
            unelevated
            color="primary"
            icon="add"
            label="新しいチャット"
            class="new-chat-btn q-mb-md"
            @click="handleMenuClick('new')"
          />

          <!-- 主菜单 -->
          <q-list class="menu-list">
            <q-item
              v-for="item in menuItems.slice(1)"
              :key="item.action"
              clickable
              v-ripple
              class="menu-item"
              @click="handleMenuClick(item.action)"
            >
              <q-item-section avatar>
                <q-icon :name="item.icon" />
              </q-item-section>
              <q-item-section>{{ item.label }}</q-item-section>
            </q-item>
          </q-list>

          <q-space />

          <!-- 底部菜单 -->
          <q-list class="menu-list bottom-menu">
            <q-separator class="q-my-sm" />
            <q-item
              v-for="item in bottomMenuItems"
              :key="item.action"
              clickable
              v-ripple
              class="menu-item"
              @click="handleMenuClick(item.action)"
            >
              <q-item-section avatar>
                <q-icon :name="item.icon" />
              </q-item-section>
              <q-item-section>{{ item.label }}</q-item-section>
            </q-item>
          </q-list>
        </div>
      </q-scroll-area>
    </q-drawer>

    <!-- Page Container -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<style lang="sass" scoped>
.main-layout
  background: var(--main-content-bg, linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 50%, var(--bg-tertiary) 100%))
  min-height: 100vh
  transition: background 0.3s ease

.header-bar
  background: var(--header-bg, var(--bg-card))
  backdrop-filter: blur(10px)
  border-bottom: 1px solid var(--border-color)
  box-shadow: 0 1px 3px var(--shadow-color)
  transition: background 0.3s ease, border-color 0.3s ease

.menu-btn
  color: var(--text-secondary)
  &:hover
    color: var(--text-primary)

.logo-title
  display: flex
  align-items: center
  gap: 8px
  font-size: 1.25rem
  font-weight: 600

.logo-icon
  font-size: 1.5rem

.logo-text
  background: linear-gradient(90deg, #8B5CF6, #EC4899)
  -webkit-background-clip: text
  -webkit-text-fill-color: transparent
  background-clip: text

.theme-toggle-btn
  color: var(--text-secondary)
  transition: color 0.3s ease
  &:hover
    color: var(--accent-primary)

.login-btn
  border-radius: 20px
  padding: 6px 24px
  font-weight: 500

.left-drawer
  background: var(--drawer-bg, var(--bg-card-solid))
  backdrop-filter: blur(10px)
  border-right: 1px solid var(--border-color)
  transition: background 0.3s ease, border-color 0.3s ease

.drawer-content
  display: flex
  flex-direction: column
  height: 100%
  padding: 16px

.new-chat-btn
  border-radius: 24px
  padding: 12px 20px
  font-weight: 500
  width: 100%

.menu-list
  .menu-item
    border-radius: 12px
    margin-bottom: 4px
    color: var(--text-secondary)
    transition: background 0.3s ease, color 0.3s ease
    &:hover
      background: var(--bg-hover)
      color: var(--text-primary)

.bottom-menu
  margin-top: auto
</style>
