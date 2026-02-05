<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useThemeStore } from '../stores/theme'

const $q = useQuasar()
const router = useRouter()
const themeStore = useThemeStore()

// Settings state
const selectedModel = ref('')
const selectedTheme = ref(themeStore.mode)
const selectedLanguage = ref('ja')
const systemPrompt = ref('')
const loading = ref(false)

// Available AI models (will be fetched from API)
const aiModels = ref([
  { id: 1, name: 'GPT-4o', provider: 'openai', description: 'OpenAI最新のフラッグシップモデル' },
  { id: 2, name: 'GPT-4o Mini', provider: 'openai', description: 'コスト効率の高い軽量モデル' },
  { id: 3, name: 'Claude 3.5 Sonnet', provider: 'anthropic', description: 'Anthropicの最も賢いモデル' },
  { id: 4, name: 'Claude 3.5 Haiku', provider: 'anthropic', description: '高速で効率的なモデル' },
  { id: 5, name: 'Gemini 2.0 Flash', provider: 'google', description: 'Googleの最新高速モデル' },
])

const themeOptions = [
  { value: 'dark', label: 'ダークモード', icon: 'dark_mode' },
  { value: 'light', label: 'ライトモード', icon: 'light_mode' },
]

const languageOptions = [
  { value: 'ja', label: '日本語' },
  { value: 'en', label: 'English' },
  { value: 'zh', label: '中文' },
]

// Model options for dropdown
const modelOptions = computed(() => {
  return aiModels.value.map(model => ({
    value: String(model.id),
    label: model.name,
    provider: model.provider,
    description: model.description,
  }))
})

const providerLabels: Record<string, string> = {
  openai: 'OpenAI',
  anthropic: 'Anthropic',
  google: 'Google',
}

function getProviderColor(provider: string) {
  const colors: Record<string, string> = {
    openai: '#10a37f',
    anthropic: '#d4a27f',
    google: '#4285f4',
  }
  return colors[provider] || '#8B5CF6'
}

function handleThemeChange(theme: 'light' | 'dark') {
  selectedTheme.value = theme
  themeStore.setTheme(theme)
}

async function saveSettings() {
  loading.value = true
  try {
    // TODO: Save to API
    await new Promise(resolve => setTimeout(resolve, 500))
    
    $q.notify({
      type: 'positive',
      message: '設定を保存しました',
      position: 'top',
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: '設定の保存に失敗しました',
      position: 'top',
    })
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push('/')
}

onMounted(() => {
  selectedModel.value = '1' // Default to first model
})
</script>

<template>
  <q-page class="settings-page">
    <div class="settings-container">
      <!-- Header -->
      <div class="settings-header">
        <q-btn
          flat
          round
          icon="arrow_back"
          class="back-btn"
          @click="goBack"
        />
        <h1 class="settings-title">設定</h1>
      </div>

      <!-- Settings Sections -->
      <div class="settings-content">
        <!-- Model Selection -->
        <section class="settings-section">
          <h2 class="section-title">
            <q-icon name="smart_toy" class="q-mr-sm" />
            AIモデル
          </h2>
          <p class="section-description">デフォルトで使用するAIモデルを選択してください</p>
          
          <q-select
            v-model="selectedModel"
            :options="modelOptions"
            emit-value
            map-options
            outlined
            class="model-select"
            popup-content-class="model-select-popup"
          >
            <template #selected-item="scope">
              <div class="selected-model" v-if="scope.opt">
                <span 
                  class="provider-dot" 
                  :style="{ backgroundColor: getProviderColor(scope.opt.provider) }"
                ></span>
                <span class="model-name">{{ scope.opt.label }}</span>
                <span class="provider-label">{{ providerLabels[scope.opt.provider] }}</span>
              </div>
            </template>
            <template #option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section avatar>
                  <span 
                    class="provider-dot" 
                    :style="{ backgroundColor: getProviderColor(scope.opt.provider) }"
                  ></span>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ scope.opt.label }}</q-item-label>
                  <q-item-label caption>{{ scope.opt.description }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge :label="providerLabels[scope.opt.provider]" :style="{ backgroundColor: getProviderColor(scope.opt.provider) }" />
                </q-item-section>
              </q-item>
            </template>
          </q-select>
        </section>

        <!-- Theme Selection -->
        <section class="settings-section">
          <h2 class="section-title">
            <q-icon name="palette" class="q-mr-sm" />
            テーマ
          </h2>
          <p class="section-description">アプリケーションの外観を選択してください</p>
          
          <div class="theme-options">
            <q-card
              v-for="theme in themeOptions"
              :key="theme.value"
              flat
              class="theme-card"
              :class="{ 'theme-card--selected': selectedTheme === theme.value }"
              @click="handleThemeChange(theme.value as 'light' | 'dark')"
            >
              <q-card-section class="row items-center">
                <q-icon :name="theme.icon" size="24px" class="q-mr-md" />
                <span>{{ theme.label }}</span>
                <q-space />
                <q-icon
                  v-if="selectedTheme === theme.value"
                  name="check_circle"
                  color="primary"
                  size="20px"
                />
              </q-card-section>
            </q-card>
          </div>
        </section>

        <!-- Language Selection -->
        <section class="settings-section">
          <h2 class="section-title">
            <q-icon name="language" class="q-mr-sm" />
            言語
          </h2>
          <p class="section-description">表示言語を選択してください</p>
          
          <q-select
            v-model="selectedLanguage"
            :options="languageOptions"
            emit-value
            map-options
            outlined
            class="language-select"
          />
        </section>

        <!-- System Prompt -->
        <section class="settings-section">
          <h2 class="section-title">
            <q-icon name="code" class="q-mr-sm" />
            システムプロンプト
          </h2>
          <p class="section-description">すべての会話で使用されるデフォルトのシステムプロンプト</p>
          
          <q-input
            v-model="systemPrompt"
            type="textarea"
            outlined
            placeholder="例: あなたは親切なアシスタントです。簡潔で分かりやすい回答を心がけてください。"
            class="system-prompt-input"
            :rows="4"
          />
        </section>

        <!-- Save Button -->
        <div class="settings-actions">
          <q-btn
            unelevated
            color="primary"
            label="設定を保存"
            class="save-btn"
            :loading="loading"
            @click="saveSettings"
          />
        </div>
      </div>
    </div>
  </q-page>
</template>

<style lang="sass" scoped>
.settings-page
  min-height: 100vh
  padding: 24px

.settings-container
  max-width: 800px
  margin: 0 auto

.settings-header
  display: flex
  align-items: center
  gap: 16px
  margin-bottom: 32px

.back-btn
  color: var(--text-secondary)
  &:hover
    color: var(--text-primary)

.settings-title
  margin: 0
  font-size: 1.75rem
  font-weight: 600
  color: var(--text-primary)

.settings-content
  display: flex
  flex-direction: column
  gap: 32px

.settings-section
  background: var(--bg-card)
  border: 1px solid var(--border-color)
  border-radius: 16px
  padding: 24px
  transition: background 0.3s ease, border-color 0.3s ease

.section-title
  margin: 0 0 8px 0
  font-size: 1.1rem
  font-weight: 600
  color: var(--text-primary)
  display: flex
  align-items: center

.section-description
  margin: 0 0 20px 0
  color: var(--text-tertiary)
  font-size: 0.9rem

.model-select
  max-width: 100%
  
  :deep(.q-field__control)
    border-radius: 12px
    background: var(--bg-input)

  :deep(.q-field__native)
    color: var(--text-primary)

.selected-model
  display: flex
  align-items: center
  gap: 10px

.provider-dot
  width: 10px
  height: 10px
  border-radius: 50%
  flex-shrink: 0

.model-name
  font-weight: 600
  color: var(--text-primary)

.provider-label
  font-size: 0.8rem
  color: var(--text-tertiary)
  margin-left: auto

.theme-options
  display: grid
  grid-template-columns: repeat(2, 1fr)
  gap: 12px

  @media (max-width: 500px)
    grid-template-columns: 1fr

.theme-card
  background: var(--bg-input)
  border: 2px solid var(--border-color)
  border-radius: 12px
  cursor: pointer
  transition: all 0.3s ease
  color: var(--text-primary)

  &:hover
    border-color: var(--border-hover)

  &--selected
    border-color: var(--accent-primary)
    background: var(--bg-hover)

.language-select
  max-width: 300px
  
  :deep(.q-field__control)
    border-radius: 12px
    background: var(--bg-input)

  :deep(.q-field__native)
    color: var(--text-primary)

.system-prompt-input
  :deep(.q-field__control)
    border-radius: 12px
    background: var(--bg-input)

  :deep(textarea)
    color: var(--text-primary)

.settings-actions
  display: flex
  justify-content: flex-end
  padding-top: 16px

.save-btn
  padding: 12px 32px
  border-radius: 12px
  font-weight: 600
</style>
