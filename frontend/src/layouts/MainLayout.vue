<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useUserStore } from '../stores/user'
import { useThemeStore } from '../stores/theme'
import { useConversationStore } from '../stores/conversation'
import { useChatStore } from '../stores/chat'
import { useFeatureStore } from '../stores/feature'

const $q = useQuasar()
const router = useRouter()
const userStore = useUserStore()
const themeStore = useThemeStore()
const conversationStore = useConversationStore()
const chatStore = useChatStore()
const featureStore = useFeatureStore()
const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

const isDarkMode = computed(() => themeStore.mode === 'dark')
const isAdmin = computed(() => userStore.isAdmin)
const conversations = computed(() => conversationStore.conversations)
const sidebarConversations = computed(() => conversations.value.slice(0, 5))
const hasMoreConversations = computed(() => conversations.value.length > 5)
const currentConversationId = computed(() => conversationStore.currentConversationId)
const features = computed(() => featureStore.features)

// Feature dialog state
const showFeatureDialog = ref(false)
const newFeatureTitle = ref('')
const creatingFeature = ref(false)

// Edit feature dialog state
const showEditFeatureDialog = ref(false)
const editingFeatureId = ref<number | null>(null)
const editFeatureTitle = ref('')
const savingFeature = ref(false)

// Right drawer - selected feature prompts
import type { Feature } from '../services/api'
const selectedFeature = ref<Feature | null>(null)
const loadingPrompts = ref(false)

onMounted(async () => {
  await userStore.init()
  // Load features for everyone (public)
  await featureStore.loadFeatures()
  // Load conversations only for logged in users
  if (userStore.isLoggedIn) {
    await conversationStore.loadConversations()
  }
})

// Reload conversations when user logs in
watch(() => userStore.isLoggedIn, async (isLoggedIn) => {
  if (isLoggedIn) {
    await conversationStore.loadConversations()
  }
})



function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function handleMenuClick(action: string) {
  switch (action) {
    case 'settings':
      router.push('/settings')
      break
    case 'new':
      startNewChat()
      break
    default:
      console.log('Menu action:', action)
  }
}

function startNewChat() {
  chatStore.clearMessages()
  chatStore.clearActivePrompt()
  conversationStore.clearCurrentConversation()
  router.push('/')
}

async function selectConversation(id: number) {
  const messages = await conversationStore.loadConversation(id)
  chatStore.clearMessages()
  messages.forEach(m => {
    chatStore.addMessage(m.role, m.content)
  })
  router.push('/')
}

async function deleteConversation(id: number, event: Event) {
  event.stopPropagation()
  const success = await conversationStore.deleteConversation(id)
  if (success) {
    $q.notify({
      type: 'positive',
      message: '会話を削除しました',
      position: 'top',
      timeout: 1500,
    })
  }
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return '今日'
  if (days === 1) return '昨日'
  if (days < 7) return `${days}日前`
  return date.toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' })
}

function handleLogin() {
  router.push('/login')
}

async function handleLogout() {
  await userStore.logout()
  chatStore.clearMessages()
  conversationStore.clearCurrentConversation()
  $q.notify({
    type: 'positive',
    message: 'ログアウトしました',
    position: 'top',
  })
}

function toggleTheme() {
  themeStore.toggleTheme()
}

// Feature management functions
function openFeatureDialog() {
  newFeatureTitle.value = ''
  showFeatureDialog.value = true
}

async function createFeature() {
  if (!newFeatureTitle.value.trim()) return
  
  creatingFeature.value = true
  try {
    const feature = await featureStore.createFeature(newFeatureTitle.value.trim())
    if (feature) {
      showFeatureDialog.value = false
      $q.notify({
        type: 'positive',
        message: '機能を作成しました',
        position: 'top',
        timeout: 1500,
      })
    }
  } finally {
    creatingFeature.value = false
  }
}

function goToPromptEdit(featureId: number) {
  router.push(`/features/${featureId}/prompts`)
}

async function deleteFeature(id: number) {
  const success = await featureStore.deleteFeature(id)
  if (success) {
    $q.notify({
      type: 'positive',
      message: '機能を削除しました',
      position: 'top',
      timeout: 1500,
    })
  }
}

// Edit feature functions
function openEditFeatureDialog(id: number, title: string) {
  editingFeatureId.value = id
  editFeatureTitle.value = title
  showEditFeatureDialog.value = true
}

async function saveEditFeature() {
  if (!editingFeatureId.value || !editFeatureTitle.value.trim()) return
  
  savingFeature.value = true
  try {
    const success = await featureStore.updateFeature(editingFeatureId.value, editFeatureTitle.value.trim())
    if (success) {
      showEditFeatureDialog.value = false
      $q.notify({
        type: 'positive',
        message: '機能名を更新しました',
        position: 'top',
        timeout: 1500,
      })
    }
  } finally {
    savingFeature.value = false
  }
}

// Select feature and show prompts in right drawer
async function selectFeature(featureId: number) {
  loadingPrompts.value = true
  rightDrawerOpen.value = true
  
  try {
    const feature = await featureStore.loadFeature(featureId)
    if (feature) {
      selectedFeature.value = feature
    }
  } catch (error) {
    console.error('Failed to load feature:', error)
    $q.notify({
      type: 'negative',
      message: 'プロンプトの読み込みに失敗しました',
      position: 'top',
    })
  } finally {
    loadingPrompts.value = false
  }
}

function closeRightDrawer() {
  rightDrawerOpen.value = false
  selectedFeature.value = null
}

// Select a prompt and set it as active for chat
function selectPrompt(prompt: { id: number; title: string; description: string | null; prompt_content: string | null }) {
  if (!selectedFeature.value || !prompt.prompt_content) return
  
  chatStore.setActivePrompt({
    id: prompt.id,
    featureId: selectedFeature.value.id,
    featureTitle: selectedFeature.value.title,
    title: prompt.title,
    description: prompt.description,
    promptContent: prompt.prompt_content,
  })
  
  // Clear current messages to start fresh with the new prompt
  chatStore.clearMessages()
  
  // Close right drawer and navigate to home
  closeRightDrawer()
  router.push('/')
  
  $q.notify({
    type: 'positive',
    message: `「${prompt.title}」を選択しました`,
    position: 'top',
    timeout: 1500,
  })
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

          <!-- 会话历史列表 -->
          <div v-if="userStore.isLoggedIn" class="conversation-section">
            <div class="section-title">履歴</div>
            <q-list v-if="sidebarConversations.length > 0" class="conversation-list">
              <q-item
                v-for="conv in sidebarConversations"
                :key="conv.id"
                clickable
                v-ripple
                class="conversation-item"
                :class="{ active: conv.id === currentConversationId }"
                @click="selectConversation(conv.id)"
              >
                <q-item-section avatar>
                  <q-icon name="chat_bubble_outline" size="20px" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="conversation-title" lines="1">
                    {{ conv.title || '新しいチャット' }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ formatDate(conv.last_message_at) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-btn
                    flat
                    round
                    dense
                    size="sm"
                    icon="delete_outline"
                    class="delete-btn"
                    @click="deleteConversation(conv.id, $event)"
                  >
                    <q-tooltip>削除</q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
            </q-list>
            <!-- View All Link -->
            <q-item
              v-if="hasMoreConversations || sidebarConversations.length > 0"
              clickable
              v-ripple
              class="view-all-item"
              @click="$router.push('/history')"
            >
              <q-item-section avatar>
                <q-icon name="history" size="20px" />
              </q-item-section>
              <q-item-section>
                {{ hasMoreConversations ? 'すべての履歴を表示' : '履歴を管理' }}
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" size="20px" />
              </q-item-section>
            </q-item>
            <div v-if="sidebarConversations.length === 0" class="no-conversations">
              履歴がありません
            </div>
          </div>

          <!-- 機能リスト -->
          <div class="feature-section">
            <div class="section-header">
              <div class="section-title">機能一覧</div>
              <q-btn
                v-if="isAdmin"
                flat
                round
                dense
                size="sm"
                icon="add"
                color="primary"
                @click="openFeatureDialog"
              >
                <q-tooltip>功能追加</q-tooltip>
              </q-btn>
            </div>
            <q-list v-if="features.length > 0" class="feature-list">
              <q-item
                v-for="feature in features"
                :key="feature.id"
                clickable
                v-ripple
                class="feature-item"
                @click="selectFeature(feature.id)"
              >
                <q-item-section>
                  <q-item-label class="feature-title text-ellipsis" lines="1">
                    {{ feature.title }}
                    <q-tooltip>{{ feature.title }}</q-tooltip>
                  </q-item-label>
                  <q-item-label caption>
                    {{ feature.prompts_count || 0 }} プロンプト
                  </q-item-label>
                </q-item-section>
                <q-item-section v-if="isAdmin" side>
                  <q-btn
                    flat
                    round
                    dense
                    size="sm"
                    icon="more_vert"
                    class="more-btn"
                  >
                    <q-menu anchor="bottom right" self="top right">
                      <q-list style="min-width: 150px">
                        <q-item clickable v-close-popup @click="openEditFeatureDialog(feature.id, feature.title)">
                          <q-item-section avatar>
                            <q-icon name="edit_note" size="20px" />
                          </q-item-section>
                          <q-item-section>機能名編集</q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup @click="goToPromptEdit(feature.id)">
                          <q-item-section avatar>
                            <q-icon name="edit" size="20px" />
                          </q-item-section>
                          <q-item-section>Prompt編集</q-item-section>
                        </q-item>
                        <q-separator />
                        <q-item clickable v-close-popup @click="deleteFeature(feature.id)">
                          <q-item-section avatar>
                            <q-icon name="delete" size="20px" color="negative" />
                          </q-item-section>
                          <q-item-section class="text-negative">削除</q-item-section>
                        </q-item>
                      </q-list>
                    </q-menu>
                  </q-btn>
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="no-features">
              機能がありません
            </div>
          </div>
        </div>
      </q-scroll-area>

      <!-- 底部設定ボタン（固定） -->
      <div class="bottom-menu-fixed">
        <q-separator />
        <q-item
          clickable
          v-ripple
          class="menu-item"
          @click="handleMenuClick('settings')"
        >
          <q-item-section avatar>
            <q-icon name="settings" />
          </q-item-section>
          <q-item-section>設定</q-item-section>
        </q-item>
      </div>
    </q-drawer>

    <!-- Right Drawer - Prompts Display -->
    <q-drawer
      v-model="rightDrawerOpen"
      side="right"
      :width="350"
      :breakpoint="768"
      class="right-drawer"
      overlay
    >
      <q-scroll-area class="fit">
        <div class="drawer-content">
          <!-- Header -->
          <div class="drawer-header">
            <q-btn
              flat
              round
              dense
              icon="close"
              @click="closeRightDrawer"
            />
            <div class="drawer-title">{{ selectedFeature?.title || 'プロンプト一覧' }}</div>
          </div>

          <!-- Loading State -->
          <div v-if="loadingPrompts" class="loading-container">
            <q-spinner-dots size="40px" color="primary" />
          </div>

          <!-- Prompts List -->
          <q-list v-else-if="selectedFeature?.prompts && selectedFeature.prompts.length > 0" class="prompt-list" separator>
            <q-item
              v-for="prompt in selectedFeature.prompts"
              :key="prompt.id"
              clickable
              v-ripple
              class="prompt-item"
              @click="selectPrompt(prompt)"
            >
              <q-item-section>
                <q-item-label class="prompt-title">
                  {{ prompt.title }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" size="20px" color="grey" />
              </q-item-section>
            </q-item>
          </q-list>

          <!-- Empty State -->
          <div v-else class="no-prompts">
            プロンプトがありません
          </div>
        </div>
      </q-scroll-area>
    </q-drawer>

    <!-- Page Container -->
    <q-page-container>
      <router-view />
    </q-page-container>

    <!-- Feature Create Dialog -->
    <q-dialog v-model="showFeatureDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">功能追加</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="newFeatureTitle"
            autofocus
            label="機能タイトル"
            outlined
            dense
            @keyup.enter="createFeature"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="キャンセル" v-close-popup />
          <q-btn
            unelevated
            color="primary"
            label="作成"
            :loading="creatingFeature"
            :disable="!newFeatureTitle.trim()"
            @click="createFeature"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Feature Edit Dialog -->
    <q-dialog v-model="showEditFeatureDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">機能名編集</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="editFeatureTitle"
            autofocus
            label="機能タイトル"
            outlined
            dense
            @keyup.enter="saveEditFeature"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="キャンセル" v-close-popup />
          <q-btn
            unelevated
            color="primary"
            label="保存"
            :loading="savingFeature"
            :disable="!editFeatureTitle.trim()"
            @click="saveEditFeature"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
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

.conversation-section
  flex: 1
  overflow-y: auto
  margin-top: 16px

.section-title
  font-size: 0.75rem
  font-weight: 600
  color: var(--text-tertiary)
  text-transform: uppercase
  letter-spacing: 0.05em
  padding: 0 8px
  margin-bottom: 8px

.conversation-list
  padding: 0

.conversation-item
  border-radius: 8px
  margin-bottom: 2px
  padding: 8px 12px
  min-height: 48px
  color: var(--text-secondary)
  transition: all 0.2s ease

  &:hover
    background: var(--bg-hover)
    color: var(--text-primary)

    .delete-btn
      opacity: 1

  &.active
    background: var(--bg-accent, rgba(139, 92, 246, 0.1))
    color: var(--accent-primary)

.conversation-title
  font-size: 0.875rem
  font-weight: 500

.delete-btn
  opacity: 0
  color: var(--text-tertiary)
  transition: opacity 0.2s ease

  &:hover
    color: #ef4444

.no-conversations
  color: var(--text-tertiary)
  font-size: 0.875rem
  text-align: center
  padding: 24px 16px

.view-all-item
  border-radius: 8px
  margin-top: 8px
  color: var(--accent-primary)
  font-size: 0.875rem
  transition: all 0.2s ease

  &:hover
    background: var(--bg-hover)

// Feature section styles
.feature-section
  margin-top: 16px

.section-header
  display: flex
  align-items: center
  justify-content: space-between
  padding: 0 8px
  margin-bottom: 8px

.feature-list
  padding: 0

.feature-item
  border-radius: 8px
  margin-bottom: 2px
  padding: 8px 12px
  min-height: 48px
  color: var(--text-secondary)
  transition: all 0.2s ease

  &:hover
    background: var(--bg-hover)
    color: var(--text-primary)

    .more-btn
      opacity: 1

.feature-title
  font-size: 0.875rem
  font-weight: 500
  max-width: 180px

.text-ellipsis
  white-space: nowrap
  overflow: hidden
  text-overflow: ellipsis
  display: block

.more-btn
  opacity: 0.5
  color: var(--text-tertiary)
  transition: opacity 0.2s ease

  &:hover
    color: var(--text-primary)

.no-features
  color: var(--text-tertiary)
  font-size: 0.875rem
  text-align: center
  padding: 16px

.bottom-menu-fixed
  position: absolute
  bottom: 0
  left: 0
  right: 0
  background: var(--drawer-bg, var(--bg-card-solid))
  padding: 8px 16px

  .menu-item
    border-radius: 12px
    color: var(--text-secondary)
    transition: background 0.3s ease, color 0.3s ease

    &:hover
      background: var(--bg-hover)
      color: var(--text-primary)

// Right drawer styles
.right-drawer
  background: var(--drawer-bg, var(--bg-card-solid))

.drawer-header
  display: flex
  align-items: center
  gap: 8px
  padding: 12px 16px
  border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.1))

.drawer-title
  font-size: 1rem
  font-weight: 600
  color: var(--text-primary)

.loading-container
  display: flex
  justify-content: center
  align-items: center
  padding: 40px

.prompt-list
  padding: 8px

.prompt-item
  border-radius: 8px
  margin-bottom: 4px
  padding: 12px 16px
  transition: all 0.2s ease

  &:hover
    background: var(--bg-hover)

.prompt-title
  font-size: 0.9rem
  font-weight: 500
  color: var(--text-primary)

.no-prompts
  color: var(--text-tertiary)
  font-size: 0.875rem
  text-align: center
  padding: 32px 16px
</style>

