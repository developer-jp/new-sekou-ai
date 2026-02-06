<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useFeatureStore } from '../stores/feature'
import type { FeaturePrompt } from '../services/api'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const featureStore = useFeatureStore()

const featureId = computed(() => Number(route.params.id))
const feature = computed(() => featureStore.currentFeature)
const loading = computed(() => featureStore.loading)

// New prompt form
const showAddPromptDialog = ref(false)
const newPrompt = ref({
  title: '',
  prompt_content: '',
  description: '',
})
const savingPrompt = ref(false)

// Edit prompt
const showEditPromptDialog = ref(false)
const editingPrompt = ref<FeaturePrompt | null>(null)
const editForm = ref({
  title: '',
  prompt_content: '',
  description: '',
})

onMounted(async () => {
  await featureStore.loadFeature(featureId.value)
})

watch(featureId, async (newId) => {
  if (newId) {
    await featureStore.loadFeature(newId)
  }
})

function goBack() {
  router.back()
}

function openAddPromptDialog() {
  newPrompt.value = {
    title: '',
    prompt_content: '',
    description: '',
  }
  showAddPromptDialog.value = true
}

async function saveNewPrompt() {
  if (!newPrompt.value.title.trim()) return
  
  savingPrompt.value = true
  try {
    const prompt = await featureStore.createPrompt(featureId.value, {
      title: newPrompt.value.title.trim(),
      prompt_content: newPrompt.value.prompt_content || null,
      description: newPrompt.value.description || null,
    })
    
    if (prompt) {
      showAddPromptDialog.value = false
      $q.notify({
        type: 'positive',
        message: 'プロンプトを作成しました',
        position: 'top',
        timeout: 1500,
      })
    }
  } finally {
    savingPrompt.value = false
  }
}

function openEditPromptDialog(prompt: FeaturePrompt) {
  editingPrompt.value = prompt
  editForm.value = {
    title: prompt.title,
    prompt_content: prompt.prompt_content || '',
    description: prompt.description || '',
  }
  showEditPromptDialog.value = true
}

async function saveEditPrompt() {
  if (!editingPrompt.value || !editForm.value.title.trim()) return
  
  savingPrompt.value = true
  try {
    const success = await featureStore.updatePrompt(editingPrompt.value.id, {
      title: editForm.value.title.trim(),
      prompt_content: editForm.value.prompt_content || null,
      description: editForm.value.description || null,
    })
    
    if (success) {
      showEditPromptDialog.value = false
      $q.notify({
        type: 'positive',
        message: 'プロンプトを更新しました',
        position: 'top',
        timeout: 1500,
      })
    }
  } finally {
    savingPrompt.value = false
  }
}

async function deletePrompt(id: number) {
  $q.dialog({
    title: '確認',
    message: 'このプロンプトを削除しますか？',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    const success = await featureStore.deletePrompt(id)
    if (success) {
      $q.notify({
        type: 'positive',
        message: 'プロンプトを削除しました',
        position: 'top',
        timeout: 1500,
      })
    }
  })
}
</script>

<template>
  <q-page class="prompt-edit-page">
    <div class="page-container">
      <!-- Header -->
      <div class="page-header">
        <q-btn
          flat
          round
          icon="arrow_back"
          @click="goBack"
          class="back-btn"
        />
        <div class="header-content">
          <h1 class="page-title">{{ feature?.title || 'プロンプト編集' }}</h1>
          <p class="page-subtitle">この機能のプロンプトを管理します</p>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-container">
        <q-spinner-dots size="40px" color="primary" />
      </div>

      <!-- Content -->
      <div v-else-if="feature" class="content-area">
        <!-- Add Prompt Button -->
        <q-btn
          unelevated
          color="primary"
          icon="add"
          label="新規プロンプト追加"
          class="add-prompt-btn q-mb-lg"
          @click="openAddPromptDialog"
        />

        <!-- Prompts List -->
        <div class="prompts-section">
          <div v-if="feature.prompts && feature.prompts.length > 0" class="prompts-list">
            <q-card
              v-for="prompt in feature.prompts"
              :key="prompt.id"
              class="prompt-card q-mb-md"
            >
              <q-card-section>
                <div class="prompt-header">
                  <div class="prompt-title">{{ prompt.title }}</div>
                  <div class="prompt-actions">
                    <q-btn
                      flat
                      round
                      dense
                      icon="edit"
                      color="primary"
                      @click="openEditPromptDialog(prompt)"
                    >
                      <q-tooltip>編集</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      round
                      dense
                      icon="delete"
                      color="negative"
                      @click="deletePrompt(prompt.id)"
                    >
                      <q-tooltip>削除</q-tooltip>
                    </q-btn>
                  </div>
                </div>
                <div v-if="prompt.description" class="prompt-description">
                  <strong>使用説明:</strong> {{ prompt.description }}
                </div>
                <div v-if="prompt.prompt_content" class="prompt-content">
                  <strong>プロンプト:</strong>
                  <pre class="prompt-text">{{ prompt.prompt_content }}</pre>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div v-else class="no-prompts">
            <q-icon name="description" size="48px" color="grey-5" />
            <p>プロンプトがありません</p>
            <p class="text-caption text-grey">「新規プロンプト追加」ボタンをクリックして追加してください</p>
          </div>
        </div>
      </div>

      <!-- Not Found -->
      <div v-else class="not-found">
        <p>機能が見つかりません</p>
        <q-btn flat color="primary" label="戻る" @click="goBack" />
      </div>
    </div>

    <!-- Add Prompt Dialog -->
    <q-dialog v-model="showAddPromptDialog">
      <q-card style="min-width: 500px; max-width: 90vw">
        <q-card-section>
          <div class="text-h6">新規プロンプト追加</div>
        </q-card-section>

        <q-card-section class="q-pt-none q-gutter-md">
          <q-input
            v-model="newPrompt.title"
            label="タイトル *"
            outlined
            dense
            autofocus
          />
          <q-input
            v-model="newPrompt.description"
            label="使用説明"
            outlined
            dense
            type="textarea"
            rows="2"
          />
          <q-input
            v-model="newPrompt.prompt_content"
            label="プロンプト内容"
            outlined
            dense
            type="textarea"
            rows="5"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="キャンセル" v-close-popup />
          <q-btn
            unelevated
            color="primary"
            label="作成"
            :loading="savingPrompt"
            :disable="!newPrompt.title.trim()"
            @click="saveNewPrompt"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Edit Prompt Dialog -->
    <q-dialog v-model="showEditPromptDialog">
      <q-card style="min-width: 500px; max-width: 90vw">
        <q-card-section>
          <div class="text-h6">プロンプト編集</div>
        </q-card-section>

        <q-card-section class="q-pt-none q-gutter-md">
          <q-input
            v-model="editForm.title"
            label="タイトル *"
            outlined
            dense
            autofocus
          />
          <q-input
            v-model="editForm.description"
            label="使用説明"
            outlined
            dense
            type="textarea"
            rows="2"
          />
          <q-input
            v-model="editForm.prompt_content"
            label="プロンプト内容"
            outlined
            dense
            type="textarea"
            rows="5"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="キャンセル" v-close-popup />
          <q-btn
            unelevated
            color="primary"
            label="保存"
            :loading="savingPrompt"
            :disable="!editForm.title.trim()"
            @click="saveEditPrompt"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<style lang="sass" scoped>
.prompt-edit-page
  min-height: 100vh
  background: transparent

.page-container
  max-width: 900px
  margin: 0 auto
  padding: 24px

.page-header
  display: flex
  align-items: flex-start
  gap: 16px
  margin-bottom: 32px

.back-btn
  color: var(--text-secondary)

.header-content
  flex: 1

.page-title
  font-size: 1.75rem
  font-weight: 600
  margin: 0
  color: var(--text-primary)

.page-subtitle
  font-size: 0.875rem
  color: var(--text-tertiary)
  margin: 4px 0 0 0

.loading-container
  display: flex
  justify-content: center
  align-items: center
  min-height: 200px

.add-prompt-btn
  border-radius: 12px

.prompts-section
  margin-top: 16px

.prompt-card
  background: var(--bg-card)
  border-radius: 12px
  border: 1px solid var(--border-color)

.prompt-header
  display: flex
  justify-content: space-between
  align-items: center
  margin-bottom: 12px

.prompt-title
  font-size: 1.125rem
  font-weight: 600
  color: var(--text-primary)

.prompt-actions
  display: flex
  gap: 4px

.prompt-description
  font-size: 0.875rem
  color: var(--text-secondary)
  margin-bottom: 12px

.prompt-content
  font-size: 0.875rem
  color: var(--text-secondary)

.prompt-text
  background: var(--bg-hover)
  padding: 12px
  border-radius: 8px
  margin-top: 8px
  white-space: pre-wrap
  word-wrap: break-word
  font-family: 'Fira Code', 'Monaco', monospace
  font-size: 0.8125rem

.no-prompts
  text-align: center
  padding: 48px 24px
  color: var(--text-tertiary)
  
  p
    margin: 12px 0 0 0

.not-found
  text-align: center
  padding: 48px 24px
  color: var(--text-tertiary)
</style>
