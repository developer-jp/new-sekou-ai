<script setup lang="ts">
import { ref } from 'vue'

const inputMessage = ref('')
const suggestions = [
  { icon: 'edit', text: 'メールを書いて', color: '#8B5CF6' },
  { icon: 'code', text: 'コードを説明して', color: '#6366F1' },
  { icon: 'lightbulb', text: 'アイデアを出して', color: '#EC4899' },
  { icon: 'translate', text: '翻訳して', color: '#10B981' },
]

function handleSend() {
  if (inputMessage.value.trim()) {
    console.log('Sending:', inputMessage.value)
    inputMessage.value = ''
  }
}

function handleSuggestionClick(text: string) {
  inputMessage.value = text
}
</script>

<template>
  <q-page class="home-page">
    <div class="content-container">
      <!-- Welcome Section -->
      <div class="welcome-section">
        <h1 class="welcome-title">
          <span class="gradient-text">こんにちは、</span>
          <span class="subtitle">何かお手伝いできますか？</span>
        </h1>
      </div>

      <!-- Suggestions Grid -->
      <div class="suggestions-grid">
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

      <!-- Input Section -->
      <div class="input-section">
        <div class="input-wrapper">
          <q-input
            v-model="inputMessage"
            placeholder="質問を入力してください..."
            borderless
            class="chat-input"
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
              />
              <q-btn
                flat
                round
                dense
                icon="mic"
                class="action-btn"
              />
              <q-btn
                round
                dense
                icon="send"
                color="primary"
                class="send-btn"
                :disable="!inputMessage.trim()"
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
  align-items: center
  min-height: calc(100vh - 50px)
  padding: 20px

.content-container
  width: 100%
  max-width: 800px
  display: flex
  flex-direction: column
  gap: 40px

.welcome-section
  text-align: center
  margin-bottom: 20px

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

.input-section
  width: 100%

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
