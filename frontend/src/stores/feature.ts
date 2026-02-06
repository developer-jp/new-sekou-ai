import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { api } from '../services/api'
import type { Feature, FeaturePrompt } from '../services/api'

export const useFeatureStore = defineStore('feature', () => {
  const features = ref<Feature[]>([])
  const currentFeature = ref<Feature | null>(null)
  const loading = ref(false)

  const featureCount = computed(() => features.value.length)

  async function loadFeatures() {
    loading.value = true
    try {
      const response = await api.getFeatures()
      if (response.success) {
        features.value = response.features
      }
    } finally {
      loading.value = false
    }
  }

  async function createFeature(title: string) {
    const response = await api.createFeature(title)
    if (response.success && response.feature) {
      features.value.unshift(response.feature)
      return response.feature
    }
    return null
  }

  async function loadFeature(id: number) {
    loading.value = true
    try {
      const response = await api.getFeature(id)
      if (response.success && response.feature) {
        currentFeature.value = response.feature
        return response.feature
      }
      return null
    } finally {
      loading.value = false
    }
  }

  async function updateFeature(id: number, title: string) {
    const response = await api.updateFeature(id, title)
    if (response.success && response.feature) {
      const index = features.value.findIndex(f => f.id === id)
      if (index !== -1) {
        features.value[index] = response.feature
      }
      if (currentFeature.value?.id === id) {
        currentFeature.value = { ...currentFeature.value, ...response.feature }
      }
      return true
    }
    return false
  }

  async function deleteFeature(id: number) {
    const response = await api.deleteFeature(id)
    if (response.success) {
      features.value = features.value.filter(f => f.id !== id)
      if (currentFeature.value?.id === id) {
        currentFeature.value = null
      }
      return true
    }
    return false
  }

  async function createPrompt(featureId: number, data: Partial<FeaturePrompt>) {
    const response = await api.createPrompt(featureId, data)
    if (response.success && response.prompt) {
      if (currentFeature.value?.id === featureId) {
        currentFeature.value.prompts = [...(currentFeature.value.prompts || []), response.prompt]
      }
      return response.prompt
    }
    return null
  }

  async function updatePrompt(id: number, data: Partial<FeaturePrompt>) {
    const response = await api.updatePrompt(id, data)
    if (response.success && response.prompt && currentFeature.value) {
      const index = currentFeature.value.prompts?.findIndex(p => p.id === id) ?? -1
      if (index !== -1 && currentFeature.value.prompts) {
        currentFeature.value.prompts[index] = response.prompt
      }
      return true
    }
    return false
  }

  async function deletePrompt(id: number) {
    const response = await api.deletePrompt(id)
    if (response.success && currentFeature.value) {
      currentFeature.value.prompts = currentFeature.value.prompts?.filter(p => p.id !== id) || []
      return true
    }
    return false
  }

  function clearCurrentFeature() {
    currentFeature.value = null
  }

  return {
    features,
    currentFeature,
    loading,
    featureCount,
    loadFeatures,
    createFeature,
    loadFeature,
    updateFeature,
    deleteFeature,
    createPrompt,
    updatePrompt,
    deletePrompt,
    clearCurrentFeature,
  }
})
