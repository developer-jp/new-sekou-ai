<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useChatStore } from '../stores/chat'
import { useConversationStore } from '../stores/conversation'
import { useUserStore } from '../stores/user'
import type { ChatHistory } from '../services/api'

const router = useRouter()
const chatStore = useChatStore()
const conversationStore = useConversationStore()
const userStore = useUserStore()
const isLoggedIn = computed(() => userStore.isLoggedIn)

const inputMessage = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const uploadedFiles = ref<File[]>([])

const ACCEPTED_FILE_TYPES = '.pdf,.docx,.doc,.xlsx,.xls,.pptx,.ppt,.jpg,.jpeg,.png,.gif,.webp'

const suggestions = [
  { icon: 'edit', text: 'メールを書いて', color: '#3B82F6' },
  { icon: 'code', text: 'コードを説明して', color: '#6366F1' },
  { icon: 'lightbulb', text: 'アイデアを出して', color: '#8B5CF6' },
  { icon: 'translate', text: '翻訳して', color: '#10B981' },
]

const isLoading = computed(() => chatStore.isLoading)
const hasMessages = computed(() => chatStore.hasMessages)
const messages = computed(() => chatStore.messages)
const activePrompt = computed(() => chatStore.activePrompt)
const isPromptCollapsed = computed(() => chatStore.isPromptCollapsed)
const hasActivePrompt = computed(() => chatStore.hasActivePrompt)

async function handleSend() {
  if (!isLoggedIn.value) return
  const message = inputMessage.value.trim()
  if ((!message && uploadedFiles.value.length === 0) || isLoading.value) return

  inputMessage.value = ''
  const filesToSend = [...uploadedFiles.value]
  uploadedFiles.value = []
  
  // Build display message with file info
  let displayMessage = message
  if (filesToSend.length > 0) {
    displayMessage += '\n\n📎 ' + filesToSend.map(f => f.name).join(', ')
  }
  
  // Add user message
  chatStore.addMessage('user', displayMessage)
  
  // Prepare history (exclude the message we just added)
  const history: ChatHistory[] = chatStore.messages
    .slice(0, -1)
    .map(m => ({ role: m.role, content: m.content }))

  // Add placeholder for assistant message
  chatStore.addMessage('assistant', '')
  chatStore.setLoading(true)
  chatStore.setError(null)

  await nextTick()
  scrollToBottom()

  try {
    const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost.sekouAI.com'
    const token = localStorage.getItem('auth_token')
    const conversationId = conversationStore.currentConversationId
    
    let response: Response
    
    if (filesToSend.length > 0) {
      // Use FormData for file uploads
      const formData = new FormData()
      formData.append('message', message || 'ファイルを分析してください')
      formData.append('history', JSON.stringify(history))
      if (conversationId) formData.append('conversation_id', String(conversationId))
      const systemPrompt = chatStore.getSystemPrompt()
      if (systemPrompt) formData.append('system_prompt', systemPrompt)
      filesToSend.forEach(file => formData.append('files[]', file))
      
      response = await fetch(`${API_BASE_URL}/api/chat/stream-with-files`, {
        method: 'POST',
        headers: {
          'Accept': 'text/event-stream',
          'Authorization': `Bearer ${token}`,
        },
        body: formData,
      })
    } else {
      response = await fetch(`${API_BASE_URL}/api/chat/stream`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'text/event-stream',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ 
          message, 
          history,
          conversation_id: conversationId,
          system_prompt: chatStore.getSystemPrompt()
        }),
      })
    }

    if (!response.ok) {
      throw new Error('Stream request failed')
    }

    const reader = response.body?.getReader()
    const decoder = new TextDecoder()
    let accumulatedContent = ''

    if (reader) {
      while (true) {
        const { done, value } = await reader.read()
        if (done) break

        const chunk = decoder.decode(value, { stream: true })
        const lines = chunk.split('\n')

        for (const line of lines) {
          if (line.startsWith('data: ')) {
            const data = line.slice(6)
            if (data === '[DONE]') {
              break
            }
            try {
              const parsed = JSON.parse(data)
              // Handle conversation_id from first SSE message
              if (parsed.conversation_id && !conversationStore.currentConversationId) {
                conversationStore.addConversation({
                  id: parsed.conversation_id,
                  title: message.substring(0, 50),
                  last_message_at: new Date().toISOString(),
                  created_at: new Date().toISOString(),
                })
              }
              if (parsed.content) {
                accumulatedContent += parsed.content
                chatStore.updateLastAssistantMessage(accumulatedContent)
                await nextTick()
                scrollToBottom()
              } else if (parsed.error) {
                chatStore.setError(parsed.error)
              }
            } catch (e) {
              // Skip invalid JSON
            }
          }
        }
      }
    }

    if (!accumulatedContent) {
      chatStore.updateLastAssistantMessage('申し訳ありませんが、応答を取得できませんでした。')
    }
  } catch (error) {
    console.error('Chat error:', error)
    chatStore.updateLastAssistantMessage('申し訳ありませんが、エラーが発生しました。もう一度お試しください。')
    chatStore.setError('Network error')
  } finally {
    chatStore.setLoading(false)
    await nextTick()
    scrollToBottom()
  }
}

function handleSuggestionClick(text: string) {
  inputMessage.value = text
}

function handleKeyDown(event: KeyboardEvent) {
  if (event.key === 'Enter') {
    // Command+Enter (Mac) or Ctrl+Enter (Windows/Linux) = new line
    if (event.metaKey || event.ctrlKey) {
      event.preventDefault()
      inputMessage.value += '\n'
    } 
    // Enter alone = send message (Shift+Enter still allows newline by default)
    else if (!event.shiftKey) {
      event.preventDefault()
      handleSend()
    }
  }
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

function formatMessage(content: string): string {
  // Enhanced markdown formatting
  let formatted = content
    // Code blocks first (preserve content inside)
    .replace(/```(\w*)\n([\s\S]*?)```/g, '<pre class="code-block"><code>$2</code></pre>')
    // Inline code
    .replace(/`([^`]+)`/g, '<code class="inline-code">$1</code>')
    // Headers (must be at start of line)
    .replace(/^#### (.+)$/gm, '<h4 class="md-h4">$1</h4>')
    .replace(/^### (.+)$/gm, '<h3 class="md-h3">$1</h3>')
    .replace(/^## (.+)$/gm, '<h2 class="md-h2">$1</h2>')
    .replace(/^# (.+)$/gm, '<h1 class="md-h1">$1</h1>')
    // Horizontal rule
    .replace(/^---$/gm, '<hr class="md-hr">')
    // Bold
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    // Italic
    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
    // Ordered list items
    .replace(/^(\d+)\. (.+)$/gm, '<li class="md-ol-item" data-num="$1">$2</li>')
    // Unordered list items
    .replace(/^[-*] (.+)$/gm, '<li class="md-ul-item">$1</li>')
  
  // Wrap consecutive list items
  formatted = formatted
    .replace(/((?:<li class="md-ol-item"[^>]*>[^<]*<\/li>\s*)+)/g, '<ol class="md-ol">$1</ol>')
    .replace(/((?:<li class="md-ul-item">[^<]*<\/li>\s*)+)/g, '<ul class="md-ul">$1</ul>')
  
  // Convert remaining newlines to <br>, but not after block elements
  formatted = formatted
    .replace(/(<\/h[1-4]>|<\/li>|<\/ol>|<\/ul>|<\/pre>|<hr[^>]*>)\n/g, '$1')
    .replace(/\n/g, '<br>')
  
  return formatted
}

const $q = useQuasar()

function copyToClipboard(content: string) {
  navigator.clipboard.writeText(content).then(() => {
    $q.notify({
      type: 'positive',
      message: 'コピーしました',
      position: 'top',
      timeout: 1500,
    })
  }).catch(() => {
    $q.notify({
      type: 'negative',
      message: 'コピーに失敗しました',
      position: 'top',
    })
  })
}

// File upload handlers
function handleAttachClick() {
  fileInput.value?.click()
}

function handleFileSelected(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files) {
    const newFiles = Array.from(target.files)
    uploadedFiles.value = [...uploadedFiles.value, ...newFiles]
    target.value = '' // Reset to allow selecting same file again
  }
}

function removeFile(index: number) {
  uploadedFiles.value.splice(index, 1)
}

function getFileIcon(file: File): string {
  const ext = file.name.split('.').pop()?.toLowerCase()
  switch (ext) {
    case 'pdf': return 'picture_as_pdf'
    case 'docx': case 'doc': return 'description'
    case 'xlsx': case 'xls': return 'table_chart'
    case 'pptx': case 'ppt': return 'slideshow'
    case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': return 'image'
    default: return 'insert_drive_file'
  }
}
</script>

<template>
  <q-page class="home-page">
    <div class="content-container" :class="{ 'has-messages': hasMessages }">
      <!-- Welcome Section (only shown when no messages) -->
      <div v-if="!hasMessages" class="welcome-section">
        <h1 class="welcome-title">
          <span class="gradient-text">こんにちは、</span>
          <span class="subtitle">何かお手伝いできますか？</span>
        </h1>
      </div>

      <!-- Suggestions Grid (only shown when no messages and logged in) -->
      <div v-if="!hasMessages && isLoggedIn" class="suggestions-grid">
        <q-card
          v-for="(suggestion, index) in suggestions"
          :key="index"
          flat
          class="suggestion-card"
          @click="handleSuggestionClick(suggestion.text)"
        >
          <q-card-section class="row items-center q-pa-md">
            <q-icon
              :name="suggestion.icon"
              size="24px"
              :style="{ color: suggestion.color }"
              class="q-mr-md"
            />
            <span class="suggestion-text">{{ suggestion.text }}</span>
          </q-card-section>
        </q-card>
      </div>

      <!-- Messages Area -->
      <div v-if="hasMessages" ref="messagesContainer" class="messages-container">
        <div
          v-for="message in messages"
          :key="message.id"
          class="message"
          :class="[`message--${message.role}`]"
        >
          <div v-if="message.role === 'assistant'" class="message-avatar">
            <q-icon name="smart_toy" size="20px" />
          </div>
          <div class="message-content">
            <div
              v-if="message.content"
              class="message-text"
              v-html="formatMessage(message.content)"
            ></div>
            <div v-else class="message-loading">
              <q-spinner-dots size="24px" color="primary" />
            </div>
            <!-- Copy button for assistant messages -->
            <div v-if="message.role === 'assistant' && message.content" class="message-actions">
              <q-btn
                flat
                dense
                size="sm"
                icon="content_copy"
                class="copy-btn"
                @click="copyToClipboard(message.content)"
              >
                <q-tooltip>コピー</q-tooltip>
              </q-btn>
            </div>
          </div>
        </div>
      </div>

      <!-- Input Section -->
      <div class="input-section">
        <!-- Login Required Notice -->
        <div v-if="!isLoggedIn" class="login-required-section">
          <div class="login-required-content">
            <q-icon name="lock" size="20px" color="grey-6" />
            <span class="login-required-text">チャットを利用するにはログインが必要です</span>
            <q-btn
              unelevated
              color="primary"
              label="ログイン"
              size="sm"
              class="login-required-btn"
              @click="router.push('/login')"
            />
          </div>
        </div>

        <!-- Chat Input (only for logged in users) -->
        <template v-else>
          <!-- Active Prompt Display -->
          <div v-if="hasActivePrompt" class="active-prompt-section">
            <div class="active-prompt-header">
              <div class="active-prompt-info">
                <span class="prompt-icon">💡</span>
                <span v-if="isPromptCollapsed" class="active-prompt-feature">{{ activePrompt?.featureTitle }}</span>
                <span v-else class="active-prompt-title">{{ activePrompt?.title }}</span>
              </div>
              <div class="active-prompt-actions">
                <q-btn
                  flat
                  round
                  dense
                  size="sm"
                  :icon="isPromptCollapsed ? 'expand_more' : 'expand_less'"
                  @click="chatStore.togglePromptCollapsed()"
                >
                  <q-tooltip>{{ isPromptCollapsed ? '利用説明を表示' : '簡略化' }}</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  dense
                  size="sm"
                  icon="close"
                  @click="chatStore.clearActivePrompt()"
                >
                  <q-tooltip>プロンプトを解除</q-tooltip>
                </q-btn>
              </div>
            </div>
            <div v-if="!isPromptCollapsed && activePrompt?.description" class="active-prompt-content">
              <div class="usage-label">
                <q-icon name="info" size="14px" color="grey" />
                <span>利用説明</span>
              </div>
              <div class="active-prompt-description">
                {{ activePrompt.description }}
              </div>
            </div>
          </div>

          <!-- File Preview Section -->
          <div v-if="uploadedFiles.length > 0" class="file-preview-section">
            <div
              v-for="(file, index) in uploadedFiles"
              :key="index"
              class="file-chip"
            >
              <q-icon :name="getFileIcon(file)" size="16px" />
              <span class="file-name">{{ file.name }}</span>
              <q-btn
                flat
                round
                dense
                size="xs"
                icon="close"
                class="remove-file-btn"
                @click="removeFile(index)"
              />
            </div>
          </div>

          <!-- Hidden File Input -->
          <input
            ref="fileInput"
            type="file"
            :accept="ACCEPTED_FILE_TYPES"
            multiple
            hidden
            @change="handleFileSelected"
          />

          <div class="input-wrapper">
            <q-input
              v-model="inputMessage"
              placeholder="質問を入力してください..."
              borderless
              type="textarea"
              autogrow
              class="chat-input"
              :disable="isLoading"
              @keydown="handleKeyDown"
            >
              <template #prepend>
                <q-icon name="auto_awesome" color="primary" />
              </template>
              <template #append>
                <q-btn
                  flat
                  round
                  dense
                  icon="attach_file"
                  class="action-btn"
                  :disable="isLoading"
                  @click="handleAttachClick"
                >
                  <q-tooltip>ファイルを添付 (PDF, Word, Excel, 画像)</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  dense
                  icon="mic"
                  class="action-btn"
                  :disable="isLoading"
                />
                <q-btn
                  round
                  dense
                  icon="send"
                  color="primary"
                  class="send-btn"
                  :disable="(!inputMessage.trim() && uploadedFiles.length === 0) || isLoading"
                  :loading="isLoading"
                  @click="handleSend"
                />
              </template>
            </q-input>
          </div>
          <p class="disclaimer">
            SekouAI は間違いを起こす可能性があります。重要な情報はご確認ください。
          </p>
        </template>
      </div>
    </div>
  </q-page>
</template>

<style lang="sass" scoped>
.home-page
  display: flex
  justify-content: center
  min-height: calc(100vh - 50px)
  padding: 20px

.content-container
  width: 100%
  max-width: 1040px
  display: flex
  flex-direction: column
  gap: 40px

  &.has-messages
    justify-content: flex-end
    gap: 0

.welcome-section
  text-align: center
  margin-bottom: 20px
  margin-top: auto

.welcome-title
  font-size: 2.5rem
  font-weight: 600
  margin: 0
  line-height: 1.3

.gradient-text
  background: linear-gradient(90deg, #3B82F6, #8B5CF6, #6366F1)
  -webkit-background-clip: text
  -webkit-text-fill-color: transparent
  background-clip: text
  font-size: 3rem

.subtitle
  display: block
  color: var(--text-tertiary)
  font-size: 1.5rem
  font-weight: 400
  margin-top: 8px

.suggestions-grid
  display: grid
  grid-template-columns: repeat(2, 1fr)
  gap: 16px

  @media (max-width: 600px)
    grid-template-columns: 1fr

.suggestion-card
  background: var(--bg-input)
  border: 1px solid var(--border-color)
  border-radius: 16px
  cursor: pointer
  transition: all 0.3s ease
  box-shadow: 0 2px 8px var(--shadow-color)

  &:hover
    background: var(--bg-hover)
    border-color: var(--border-focus)
    transform: translateY(-2px)
    box-shadow: 0 4px 16px var(--shadow-color)

.suggestion-text
  color: var(--text-secondary)
  font-size: 0.95rem

// Messages Area
.messages-container
  flex: 1
  padding: 20px 0
  display: flex
  flex-direction: column
  gap: 16px

.message
  display: flex
  gap: 12px
  animation: fadeIn 0.3s ease
  max-width: 80%

  &--user
    flex-direction: row-reverse
    align-self: flex-end

    .message-avatar
      background: linear-gradient(135deg, #8B5CF6, #6366F1)
      color: white

    .message-content
      background: linear-gradient(135deg, #8B5CF6, #6366F1)
      color: white
      border-radius: 20px 20px 4px 20px

    .message-role
      text-align: right
      color: rgba(255, 255, 255, 0.8)

    .message-text
      color: white

  &--assistant
    flex-direction: row
    align-self: flex-start

    .message-avatar
      background: linear-gradient(135deg, #10B981, #059669)
      color: white

    .message-content
      background: transparent
      border: none
      padding-left: 0

.message-avatar
  width: 32px
  height: 32px
  border-radius: 50%
  display: flex
  align-items: center
  justify-content: center
  flex-shrink: 0

.message-content
  flex: 1
  border-radius: 16px
  padding: 12px 16px
  max-width: 100%

.message-actions
  display: flex
  gap: 8px
  margin-top: 8px

.copy-btn
  color: var(--text-tertiary)
  opacity: 0.6
  transition: all 0.2s ease
  
  &:hover
    opacity: 1
    color: var(--accent-primary)

.message-role
  font-size: 0.8rem
  font-weight: 600
  color: var(--text-tertiary)
  margin-bottom: 4px

.message-text
  color: var(--text-primary)
  line-height: 1.6
  word-wrap: break-word

  :deep(.code-block)
    background: var(--bg-card)
    border: 1px solid var(--border-color)
    border-radius: 8px
    padding: 12px
    overflow-x: auto
    margin: 8px 0
    font-family: 'Fira Code', monospace
    font-size: 0.9rem

  :deep(.inline-code)
    background: var(--bg-card)
    padding: 2px 6px
    border-radius: 4px
    font-family: 'Fira Code', monospace
    font-size: 0.9rem

  // Markdown headers
  :deep(.md-h1)
    font-size: 1.5rem
    font-weight: 700
    margin: 16px 0 8px 0
    color: var(--text-primary)
    border-bottom: 1px solid var(--border-color)
    padding-bottom: 4px

  :deep(.md-h2)
    font-size: 1.3rem
    font-weight: 600
    margin: 14px 0 6px 0
    color: var(--text-primary)

  :deep(.md-h3)
    font-size: 1.1rem
    font-weight: 600
    margin: 12px 0 4px 0
    color: var(--text-primary)

  :deep(.md-h4)
    font-size: 1rem
    font-weight: 600
    margin: 10px 0 4px 0
    color: var(--text-secondary)

  // Markdown lists
  :deep(.md-ol),
  :deep(.md-ul)
    margin: 8px 0
    padding-left: 24px

  :deep(.md-ol-item),
  :deep(.md-ul-item)
    margin: 4px 0
    line-height: 1.6

  :deep(.md-ul)
    list-style-type: disc

  :deep(.md-ol)
    list-style-type: decimal

  // Horizontal rule
  :deep(.md-hr)
    border: none
    border-top: 1px solid var(--border-color)
    margin: 16px 0

.message-loading
  padding: 8px 0

@keyframes fadeIn
  from
    opacity: 0
    transform: translateY(10px)
  to
    opacity: 1
    transform: translateY(0)

.input-section
  width: 100%
  margin-top: auto
  padding-top: 16px

.input-wrapper
  background: var(--bg-input)
  border: 1px solid var(--border-color)
  border-radius: 28px
  padding: 8px 16px
  transition: all 0.3s ease
  box-shadow: 0 2px 12px var(--shadow-color)

  &:focus-within
    background: var(--bg-input-focus)
    border-color: var(--border-focus)
    box-shadow: 0 4px 20px rgba(139, 92, 246, 0.15)

.chat-input
  :deep(.q-field__control)
    color: var(--text-primary)

  :deep(input)
    color: var(--text-primary)
    &::placeholder
      color: var(--text-muted)

.action-btn
  color: var(--text-tertiary)
  &:hover
    color: var(--text-primary)

.send-btn
  margin-left: 8px
  background: linear-gradient(135deg, #3B82F6, #6366F1)
  opacity: 0.9
  transition: opacity 0.3s ease
  
  &:hover
    opacity: 1

.disclaimer
  text-align: center
  color: var(--text-muted)
  font-size: 0.75rem
  margin-top: 16px

// Login Required Section
.login-required-section
  width: 100%

.login-required-content
  display: flex
  align-items: center
  justify-content: center
  gap: 10px
  background: var(--bg-input)
  border: 1px solid var(--border-color)
  border-radius: 28px
  padding: 14px 24px

.login-required-text
  color: var(--text-secondary)
  font-size: 0.9rem

.login-required-btn
  border-radius: 20px
  padding: 4px 20px
  font-weight: 500
  background: linear-gradient(135deg, #3B82F6, #6366F1)

// Active Prompt Section
.active-prompt-section
  background: var(--bg-input)
  border: 1px solid var(--border-color)
  border-radius: 16px
  padding: 12px 16px
  margin-bottom: 12px

.active-prompt-header
  display: flex
  justify-content: space-between
  align-items: center

.active-prompt-info
  display: flex
  align-items: center
  gap: 8px

.active-prompt-feature
  font-size: 0.875rem
  font-weight: 600
  color: var(--text-primary)

.active-prompt-title
  font-size: 0.875rem
  font-weight: 500
  color: var(--text-primary)

.active-prompt-actions
  display: flex
  gap: 4px

.active-prompt-content
  margin-top: 12px
  padding-top: 12px
  border-top: 1px solid var(--border-color)

.prompt-icon
  font-size: 18px

.usage-label
  display: flex
  align-items: center
  gap: 4px
  font-size: 0.75rem
  color: var(--text-tertiary)
  margin-bottom: 6px

.active-prompt-description
  font-size: 0.85rem
  color: var(--text-secondary)
  line-height: 1.5
  background: var(--bg-card)
  padding: 10px 12px
  border-radius: 8px

// File Upload Preview
.file-preview-section
  display: flex
  flex-wrap: wrap
  gap: 8px
  margin-bottom: 12px

.file-chip
  display: flex
  align-items: center
  gap: 6px
  background: var(--bg-input)
  border: 1px solid var(--border-color)
  border-radius: 20px
  padding: 6px 8px 6px 12px
  font-size: 0.8rem
  color: var(--text-secondary)
  max-width: 200px

.file-name
  white-space: nowrap
  overflow: hidden
  text-overflow: ellipsis
  flex: 1

.remove-file-btn
  color: var(--text-tertiary)
  &:hover
    color: var(--text-primary)
</style>


