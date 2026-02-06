import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface ChatMessage {
  id: string
  role: 'user' | 'assistant'
  content: string
  timestamp: Date
}

export const useChatStore = defineStore('chat', () => {
  const messages = ref<ChatMessage[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const hasMessages = computed(() => messages.value.length > 0)

  function addMessage(role: 'user' | 'assistant', content: string) {
    const message: ChatMessage = {
      id: crypto.randomUUID(),
      role,
      content,
      timestamp: new Date()
    }
    messages.value.push(message)
    return message
  }

  function updateLastAssistantMessage(content: string) {
    const lastMessage = messages.value[messages.value.length - 1]
    if (lastMessage && lastMessage.role === 'assistant') {
      lastMessage.content = content
    }
  }

  function appendToLastAssistantMessage(chunk: string) {
    const lastMessage = messages.value[messages.value.length - 1]
    if (lastMessage && lastMessage.role === 'assistant') {
      lastMessage.content += chunk
    }
  }

  function setLoading(loading: boolean) {
    isLoading.value = loading
  }

  function setError(err: string | null) {
    error.value = err
  }

  function clearMessages() {
    messages.value = []
    error.value = null
  }

  // Get history for API calls (excluding the last user message if it's pending)
  function getHistory(): { role: string; content: string }[] {
    return messages.value.map(m => ({
      role: m.role,
      content: m.content
    }))
  }

  return {
    messages,
    isLoading,
    error,
    hasMessages,
    addMessage,
    updateLastAssistantMessage,
    appendToLastAssistantMessage,
    setLoading,
    setError,
    clearMessages,
    getHistory
  }
})
