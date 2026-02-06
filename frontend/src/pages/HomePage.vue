<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useChatStore } from '../stores/chat'
import type { ChatHistory } from '../services/api'

const chatStore = useChatStore()

const inputMessage = ref('')
const messagesContainer = ref<HTMLElement | null>(null)

const suggestions = [
  { icon: 'edit', text: 'メールを書いて', color: '#8B5CF6' },
  { icon: 'code', text: 'コードを説明して', color: '#6366F1' },
  { icon: 'lightbulb', text: 'アイデアを出して', color: '#EC4899' },
  { icon: 'translate', text: '翻訳して', color: '#10B981' },
]

const isLoading = computed(() => chatStore.isLoading)
const hasMessages = computed(() => chatStore.hasMessages)
const messages = computed(() => chatStore.messages)

async function handleSend() {
  const message = inputMessage.value.trim()
  if (!message || isLoading.value) return

  inputMessage.value = ''
  
  // Add user message
  chatStore.addMessage('user', message)
  
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
    
    const response = await fetch(`${API_BASE_URL}/api/chat/stream`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'text/event-stream',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({ message, history }),
    })

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

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

function formatMessage(content: string): string {
  // Simple markdown-like formatting
  return content
    .replace(/```(\w*)\n([\s\S]*?)```/g, '<pre class="code-block"><code>$2</code></pre>')
    .replace(/`([^`]+)`/g, '<code class="inline-code">$1</code>')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>')
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

      <!-- Suggestions Grid (only shown when no messages) -->
      <div v-if="!hasMessages" class="suggestions-grid">
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
          <div class="message-avatar">
            <q-icon
              :name="message.role === 'user' ? 'person' : 'smart_toy'"
              size="20px"
            />
          </div>
          <div class="message-content">
            <div class="message-role">
              {{ message.role === 'user' ? 'あなた' : 'SekouAI' }}
            </div>
            <div
              v-if="message.content"
              class="message-text"
              v-html="formatMessage(message.content)"
            ></div>
            <div v-else class="message-loading">
              <q-spinner-dots size="24px" color="primary" />
            </div>
          </div>
        </div>
      </div>

      <!-- Input Section -->
      <div class="input-section">
        <div class="input-wrapper">
          <q-input
            v-model="inputMessage"
            placeholder="質問を入力してください..."
            borderless
            class="chat-input"
            :disable="isLoading"
            @keyup.enter="handleSend"
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
              />
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
                :disable="!inputMessage.trim() || isLoading"
                :loading="isLoading"
                @click="handleSend"
              />
            </template>
          </q-input>
        </div>
        <p class="disclaimer">
          SekouAI は間違いを起こす可能性があります。重要な情報はご確認ください。
        </p>
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
  max-width: 800px
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
  background: linear-gradient(90deg, #8B5CF6, #EC4899, #6366F1)
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
  gap: 24px

.message
  display: flex
  gap: 16px
  animation: fadeIn 0.3s ease

  &--user
    .message-avatar
      background: linear-gradient(135deg, #8B5CF6, #6366F1)
      color: white

    .message-content
      background: var(--bg-input)

  &--assistant
    .message-avatar
      background: linear-gradient(135deg, #10B981, #059669)
      color: white

    .message-content
      background: transparent

.message-avatar
  width: 36px
  height: 36px
  border-radius: 50%
  display: flex
  align-items: center
  justify-content: center
  flex-shrink: 0

.message-content
  flex: 1
  border-radius: 16px
  padding: 12px 16px

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

.disclaimer
  text-align: center
  color: var(--text-muted)
  font-size: 0.75rem
  margin-top: 16px
</style>
