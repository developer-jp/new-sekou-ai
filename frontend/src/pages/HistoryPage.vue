<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useConversationStore } from '../stores/conversation'
import { useChatStore } from '../stores/chat'

const $q = useQuasar()
const router = useRouter()
const conversationStore = useConversationStore()
const chatStore = useChatStore()

const searchQuery = ref('')

const conversations = computed(() => conversationStore.conversations)
const isLoading = computed(() => conversationStore.isLoading)

const filteredConversations = computed(() => {
  if (!searchQuery.value.trim()) {
    return conversations.value
  }
  const query = searchQuery.value.toLowerCase()
  return conversations.value.filter(c => 
    c.title?.toLowerCase().includes(query)
  )
})

onMounted(async () => {
  await conversationStore.loadConversations()
})

async function selectConversation(id: number) {
  const messages = await conversationStore.loadConversation(id)
  chatStore.clearMessages()
  messages.forEach(m => {
    chatStore.addMessage(m.role, m.content)
  })
  router.push('/')
}

async function deleteConversation(id: number) {
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
  return date.toLocaleDateString('ja-JP', { 
    year: 'numeric',
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatRelativeDate(dateStr: string | null): string {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return '今日'
  if (days === 1) return '昨日'
  if (days < 7) return `${days}日前`
  if (days < 30) return `${Math.floor(days / 7)}週間前`
  return date.toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' })
}
</script>

<template>
  <q-page class="history-page">
    <div class="content-container">
      <div class="page-header">
        <h1 class="page-title">
          <q-icon name="history" class="title-icon" />
          履歴
        </h1>
        <p class="page-subtitle">過去の会話を検索・閲覧できます</p>
      </div>

      <!-- Search Bar -->
      <div class="search-section">
        <q-input
          v-model="searchQuery"
          placeholder="会話を検索..."
          borderless
          class="search-input"
        >
          <template #prepend>
            <q-icon name="search" color="grey-6" />
          </template>
          <template v-if="searchQuery" #append>
            <q-icon 
              name="close" 
              class="cursor-pointer" 
              @click="searchQuery = ''" 
            />
          </template>
        </q-input>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-section">
        <q-spinner-dots size="40px" color="primary" />
      </div>

      <!-- Conversations List -->
      <div v-else-if="filteredConversations.length > 0" class="conversations-grid">
        <div
          v-for="conv in filteredConversations"
          :key="conv.id"
          class="conversation-card"
          @click="selectConversation(conv.id)"
        >
          <div class="card-content">
            <div class="card-icon">
              <q-icon name="chat_bubble_outline" size="24px" />
            </div>
            <div class="card-info">
              <h3 class="card-title">{{ conv.title || '新しいチャット' }}</h3>
              <p class="card-date">{{ formatDate(conv.last_message_at) }}</p>
            </div>
            <div class="card-actions">
              <q-chip 
                size="sm" 
                color="grey-3" 
                text-color="grey-7"
                class="date-chip"
              >
                {{ formatRelativeDate(conv.last_message_at) }}
              </q-chip>
              <q-btn
                flat
                round
                dense
                icon="delete_outline"
                class="delete-btn"
                @click.stop="deleteConversation(conv.id)"
              >
                <q-tooltip>削除</q-tooltip>
              </q-btn>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <q-icon name="chat_bubble_outline" size="64px" class="empty-icon" />
        <h3>{{ searchQuery ? '検索結果がありません' : '履歴がありません' }}</h3>
        <p>{{ searchQuery ? '別のキーワードでお試しください' : '新しい会話を始めましょう' }}</p>
        <q-btn
          v-if="!searchQuery"
          color="primary"
          label="新しいチャットを開始"
          icon="add"
          unelevated
          class="start-chat-btn"
          @click="router.push('/')"
        />
      </div>
    </div>
  </q-page>
</template>

<style lang="sass" scoped>
.history-page
  padding: 40px 20px
  min-height: calc(100vh - 50px)

.content-container
  max-width: 900px
  margin: 0 auto

.page-header
  text-align: center
  margin-bottom: 32px

.page-title
  font-size: 2rem
  font-weight: 700
  color: var(--text-primary)
  display: flex
  align-items: center
  justify-content: center
  gap: 12px
  margin: 0

.title-icon
  color: var(--accent-primary)

.page-subtitle
  color: var(--text-secondary)
  margin-top: 8px

.search-section
  margin-bottom: 24px

.search-input
  background: var(--bg-card)
  border: 1px solid var(--border-color)
  border-radius: 12px
  padding: 8px 16px

.loading-section
  display: flex
  justify-content: center
  padding: 60px 0

.conversations-grid
  display: flex
  flex-direction: column
  gap: 12px

.conversation-card
  background: var(--bg-card)
  border: 1px solid var(--border-color)
  border-radius: 12px
  padding: 16px 20px
  cursor: pointer
  transition: all 0.2s ease

  &:hover
    border-color: var(--accent-primary)
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.1)

    .delete-btn
      opacity: 1

.card-content
  display: flex
  align-items: center
  gap: 16px

.card-icon
  width: 48px
  height: 48px
  border-radius: 12px
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(99, 102, 241, 0.1))
  display: flex
  align-items: center
  justify-content: center
  color: var(--accent-primary)
  flex-shrink: 0

.card-info
  flex: 1
  min-width: 0

.card-title
  font-size: 1rem
  font-weight: 600
  color: var(--text-primary)
  margin: 0
  white-space: nowrap
  overflow: hidden
  text-overflow: ellipsis

.card-date
  font-size: 0.875rem
  color: var(--text-tertiary)
  margin: 4px 0 0

.card-actions
  display: flex
  align-items: center
  gap: 8px

.date-chip
  font-size: 0.75rem

.delete-btn
  opacity: 0
  color: var(--text-tertiary)
  transition: opacity 0.2s ease

  &:hover
    color: #ef4444

.empty-state
  text-align: center
  padding: 60px 20px

  .empty-icon
    color: var(--text-tertiary)
    opacity: 0.5

  h3
    color: var(--text-primary)
    margin: 16px 0 8px

  p
    color: var(--text-secondary)
    margin-bottom: 24px

.start-chat-btn
  border-radius: 24px
  padding: 12px 32px
</style>
