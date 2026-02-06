import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '../services/api'
import type { ConversationSummary, ConversationMessage } from '../services/api'

export const useConversationStore = defineStore('conversation', () => {
  const conversations = ref<ConversationSummary[]>([])
  const currentConversationId = ref<number | null>(null)
  const isLoading = ref(false)

  const currentConversation = computed(() => {
    return conversations.value.find(c => c.id === currentConversationId.value)
  })

  async function loadConversations() {
    isLoading.value = true
    try {
      const response = await api.getConversations()
      if (response.success) {
        conversations.value = response.conversations
      }
    } finally {
      isLoading.value = false
    }
  }

  async function loadConversation(id: number): Promise<ConversationMessage[]> {
    isLoading.value = true
    try {
      const response = await api.getConversation(id)
      if (response.success && response.conversation) {
        currentConversationId.value = id
        return response.conversation.messages
      }
      return []
    } finally {
      isLoading.value = false
    }
  }

  function setCurrentConversation(id: number | null) {
    currentConversationId.value = id
  }

  function addConversation(conversation: ConversationSummary) {
    // Add to the beginning of the list
    conversations.value.unshift(conversation)
    currentConversationId.value = conversation.id
  }

  async function deleteConversation(id: number) {
    const response = await api.deleteConversation(id)
    if (response.success) {
      conversations.value = conversations.value.filter(c => c.id !== id)
      if (currentConversationId.value === id) {
        currentConversationId.value = null
      }
    }
    return response.success
  }

  function clearCurrentConversation() {
    currentConversationId.value = null
  }

  return {
    conversations,
    currentConversationId,
    currentConversation,
    isLoading,
    loadConversations,
    loadConversation,
    setCurrentConversation,
    addConversation,
    deleteConversation,
    clearCurrentConversation,
  }
})
