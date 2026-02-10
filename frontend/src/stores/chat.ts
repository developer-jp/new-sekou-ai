import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface GroundingSource {
  title: string
  uri: string
}

export interface ChatMessage {
  id: string
  role: 'user' | 'assistant'
  content: string
  timestamp: Date
  groundingSources?: GroundingSource[]
  searchQueries?: string[]
}

export interface ActivePrompt {
  id: number
  featureId: number
  featureTitle: string
  title: string
  description: string | null
  promptContent: string
}

export const useChatStore = defineStore('chat', () => {
  const messages = ref<ChatMessage[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const activePrompt = ref<ActivePrompt | null>(null)
  const isPromptCollapsed = ref(false)
  const useGrounding = ref(false)

  const hasMessages = computed(() => messages.value.length > 0)
  const hasActivePrompt = computed(() => activePrompt.value !== null)

  function addMessage(role: 'user' | 'assistant', content: string, groundingSources?: GroundingSource[], searchQueries?: string[]) {
    const message: ChatMessage = {
      id: crypto.randomUUID(),
      role,
      content,
      timestamp: new Date(),
      groundingSources,
      searchQueries
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

  function updateLastAssistantGrounding(sources: GroundingSource[], searchQueries?: string[]) {
    const lastMessage = messages.value[messages.value.length - 1]
    if (lastMessage && lastMessage.role === 'assistant') {
      lastMessage.groundingSources = sources
      if (searchQueries) {
        lastMessage.searchQueries = searchQueries
      }
    }
  }

  function toggleGrounding() {
    useGrounding.value = !useGrounding.value
  }

  function setGrounding(val: boolean) {
    useGrounding.value = val
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

  function setActivePrompt(prompt: ActivePrompt) {
    activePrompt.value = prompt
    isPromptCollapsed.value = false
  }

  function clearActivePrompt() {
    activePrompt.value = null
    isPromptCollapsed.value = false
  }

  function togglePromptCollapsed() {
    isPromptCollapsed.value = !isPromptCollapsed.value
  }

  // Get system prompt for API calls
  function getSystemPrompt(): string | null {
    return activePrompt.value?.promptContent || null
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
    activePrompt,
    isPromptCollapsed,
    useGrounding,
    hasMessages,
    hasActivePrompt,
    addMessage,
    updateLastAssistantMessage,
    appendToLastAssistantMessage,
    updateLastAssistantGrounding,
    setLoading,
    setError,
    clearMessages,
    setActivePrompt,
    clearActivePrompt,
    togglePromptCollapsed,
    toggleGrounding,
    setGrounding,
    getSystemPrompt,
    getHistory
  }
})
