<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useUserStore } from '../stores/user'
import { useThemeStore } from '../stores/theme'

const $q = useQuasar()
const router = useRouter()
const userStore = useUserStore()
const themeStore = useThemeStore()

const isDarkMode = computed(() => themeStore.mode === 'dark')

onMounted(() => {
  themeStore.init()
})

const isLoginMode = ref(true)
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const name = ref('')
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

function toggleMode() {
  isLoginMode.value = !isLoginMode.value
  resetForm()
}

function resetForm() {
  email.value = ''
  password.value = ''
  confirmPassword.value = ''
  name.value = ''
  errorMessage.value = ''
}

async function handleSubmit() {
  loading.value = true
  errorMessage.value = ''

  try {
    if (isLoginMode.value) {
      await userStore.login(email.value, password.value)
      $q.notify({
        type: 'positive',
        message: 'ログインしました',
        position: 'top',
      })
    } else {
      await userStore.register(
        name.value,
        email.value,
        password.value,
        confirmPassword.value
      )
      $q.notify({
        type: 'positive',
        message: '登録が完了しました',
        position: 'top',
      })
    }

    router.push('/')
  } catch (e: unknown) {
    const err = e as { message?: string; errors?: Record<string, string[]> }
    if (err.errors) {
      const firstError = Object.values(err.errors)[0]
      if (firstError) {
        errorMessage.value = Array.isArray(firstError)
          ? (firstError[0] ?? 'エラーが発生しました')
          : String(firstError)
      } else {
        errorMessage.value = 'エラーが発生しました'
      }
    } else {
      errorMessage.value = err.message || 'エラーが発生しました'
    }
  } finally {
    loading.value = false
  }
}

function handleGoogleLogin() {
  $q.notify({
    type: 'info',
    message: 'Google ログインは準備中です',
    position: 'top',
  })
}

function handleGithubLogin() {
  $q.notify({
    type: 'info',
    message: 'Github ログインは準備中です',
    position: 'top',
  })
}

function toggleTheme() {
  themeStore.toggleTheme()
}
</script>

<template>
  <div class="login-page">
    <!-- Theme Toggle -->
    <q-btn
      flat
      round
      dense
      :icon="isDarkMode ? 'light_mode' : 'dark_mode'"
      class="theme-toggle-btn"
      @click="toggleTheme"
    >
      <q-tooltip>{{ isDarkMode ? 'ライトモード' : 'ダークモード' }}</q-tooltip>
    </q-btn>

    <!-- Background decoration -->
    <div class="bg-decoration">
      <div class="circle circle-1"></div>
      <div class="circle circle-2"></div>
      <div class="circle circle-3"></div>
    </div>

    <div class="login-container">
      <!-- Logo Section -->
      <div class="logo-section">
        <span class="logo-text">SekouAI</span>
        <p class="logo-subtitle">AI アシスタント</p>
      </div>

      <!-- Login Card -->
      <q-card class="login-card">
        <q-card-section class="card-header">
          <h2 class="dialog-title">{{ isLoginMode ? 'おかえりなさい' : 'アカウント作成' }}</h2>
          <p class="dialog-subtitle">
            {{ isLoginMode ? 'SekouAI を続けるにはログインしてください' : 'SekouAI を始めるには登録してください' }}
          </p>
        </q-card-section>

        <q-card-section class="card-body">
          <!-- Error Message -->
          <q-banner v-if="errorMessage" class="error-banner q-mb-md" rounded>
            <template #avatar>
              <q-icon name="error" color="negative" />
            </template>
            {{ errorMessage }}
          </q-banner>

          <!-- Social Login Buttons -->
          <div class="social-buttons">
            <q-btn
              outline
              class="social-btn"
              @click="handleGoogleLogin"
            >
              <img src="https://www.google.com/favicon.ico" class="social-icon" />
              <span>Google でログイン</span>
            </q-btn>
            <q-btn
              outline
              class="social-btn"
              @click="handleGithubLogin"
            >
              <q-icon name="mdi-github" size="20px" class="q-mr-sm" />
              <span>Github でログイン</span>
            </q-btn>
          </div>

          <div class="divider">
            <span>または</span>
          </div>

          <!-- Email Form -->
          <q-form @submit.prevent="handleSubmit" class="login-form">
            <q-input
              v-if="!isLoginMode"
              v-model="name"
              label="ユーザー名"
              outlined
              dense
              class="form-input"
              :rules="[val => !!val || 'ユーザー名を入力してください']"
            >
              <template #prepend>
                <q-icon name="person" />
              </template>
            </q-input>

            <q-input
              v-model="email"
              label="メールアドレス"
              type="email"
              outlined
              dense
              class="form-input"
              :rules="[val => !!val || 'メールアドレスを入力してください', val => /.+@.+\..+/.test(val) || '有効なメールアドレスを入力してください']"
            >
              <template #prepend>
                <q-icon name="email" />
              </template>
            </q-input>

            <q-input
              v-model="password"
              label="パスワード"
              :type="showPassword ? 'text' : 'password'"
              outlined
              dense
              class="form-input"
              :rules="[val => !!val || 'パスワードを入力してください', val => val.length >= 6 || 'パスワードは6文字以上必要です']"
            >
              <template #prepend>
                <q-icon name="lock" />
              </template>
              <template #append>
                <q-icon
                  :name="showPassword ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="showPassword = !showPassword"
                />
              </template>
            </q-input>

            <q-input
              v-if="!isLoginMode"
              v-model="confirmPassword"
              label="パスワード確認"
              :type="showPassword ? 'text' : 'password'"
              outlined
              dense
              class="form-input"
              :rules="[val => !!val || 'パスワードを確認してください', val => val === password || 'パスワードが一致しません']"
            >
              <template #prepend>
                <q-icon name="lock" />
              </template>
            </q-input>

            <q-btn
              type="submit"
              color="primary"
              class="submit-btn"
              :loading="loading"
              :label="isLoginMode ? 'ログイン' : '登録'"
            />
          </q-form>
        </q-card-section>

        <q-card-section class="card-footer">
          <p class="toggle-text">
            {{ isLoginMode ? 'アカウントをお持ちでないですか？' : 'すでにアカウントをお持ちですか？' }}
            <a href="#" class="toggle-link" @click.prevent="toggleMode">
              {{ isLoginMode ? '新規登録' : 'ログイン' }}
            </a>
          </p>
        </q-card-section>
      </q-card>

      <!-- Back to Home -->
      <router-link to="/" class="back-link">
        <q-icon name="arrow_back" class="q-mr-xs" />
        ホームに戻る
      </router-link>
    </div>
  </div>
</template>

<style lang="sass" scoped>
.login-page
  min-height: 100vh
  display: flex
  justify-content: center
  align-items: center
  background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 50%, var(--bg-tertiary) 100%)
  position: relative
  overflow: hidden
  padding: 20px
  transition: background 0.3s ease

.theme-toggle-btn
  position: absolute
  top: 20px
  right: 20px
  color: var(--text-secondary)
  z-index: 10
  transition: color 0.3s ease
  &:hover
    color: var(--accent-primary)

.bg-decoration
  position: absolute
  top: 0
  left: 0
  width: 100%
  height: 100%
  pointer-events: none
  overflow: hidden

.circle
  position: absolute
  border-radius: 50%
  opacity: 0.1

.circle-1
  width: 600px
  height: 600px
  background: linear-gradient(135deg, #8B5CF6, #EC4899)
  top: -200px
  right: -100px
  animation: float 20s ease-in-out infinite

.circle-2
  width: 400px
  height: 400px
  background: linear-gradient(135deg, #6366F1, #8B5CF6)
  bottom: -100px
  left: -100px
  animation: float 15s ease-in-out infinite reverse

.circle-3
  width: 200px
  height: 200px
  background: linear-gradient(135deg, #EC4899, #F472B6)
  top: 50%
  left: 10%
  animation: float 10s ease-in-out infinite

@keyframes float
  0%, 100%
    transform: translateY(0) rotate(0deg)
  50%
    transform: translateY(-30px) rotate(5deg)

.login-container
  position: relative
  z-index: 1
  display: flex
  flex-direction: column
  align-items: center
  width: 100%
  max-width: 440px

.logo-section
  text-align: center
  margin-bottom: 32px

.logo-text
  font-size: 2.5rem
  font-weight: 700
  background: linear-gradient(90deg, #8B5CF6, #EC4899)
  -webkit-background-clip: text
  -webkit-text-fill-color: transparent
  background-clip: text

.logo-subtitle
  color: var(--text-tertiary)
  margin: 8px 0 0 0
  font-size: 1rem

.login-card
  width: 100%
  background: var(--bg-card)
  backdrop-filter: blur(20px)
  border: 1px solid var(--border-color)
  border-radius: 24px
  position: relative
  overflow: hidden
  transition: background 0.3s ease, border-color 0.3s ease

  &::before
    content: ''
    position: absolute
    top: -50%
    left: -50%
    width: 200%
    height: 200%
    background: radial-gradient(circle, rgba(139, 92, 246, 0.08) 0%, transparent 50%)
    pointer-events: none

.card-header
  text-align: center
  padding-top: 32px

.dialog-title
  margin: 0 0 8px 0
  font-size: 1.5rem
  font-weight: 600
  color: var(--text-primary)

.dialog-subtitle
  margin: 0
  color: var(--text-tertiary)
  font-size: 0.9rem

.card-body
  padding: 24px 32px

.error-banner
  background: rgba(239, 68, 68, 0.15)
  border: 1px solid rgba(239, 68, 68, 0.3)
  color: var(--text-primary)

.social-buttons
  display: flex
  flex-direction: column
  gap: 12px

.social-btn
  border-color: var(--border-color)
  color: var(--text-secondary)
  border-radius: 12px
  padding: 12px 16px
  text-transform: none
  font-weight: 500
  transition: background 0.3s ease, border-color 0.3s ease

  &:hover
    background: var(--bg-hover)
    border-color: var(--border-hover)

.social-icon
  width: 20px
  height: 20px
  margin-right: 12px

.divider
  display: flex
  align-items: center
  margin: 24px 0
  color: var(--text-muted)

  &::before,
  &::after
    content: ''
    flex: 1
    height: 1px
    background: var(--border-color)

  span
    padding: 0 16px
    font-size: 0.85rem

.login-form
  display: flex
  flex-direction: column
  gap: 16px

.form-input
  :deep(.q-field__control)
    border-radius: 12px

  :deep(.q-field__label)
    color: var(--text-tertiary)

  :deep(input)
    color: var(--text-primary)

  :deep(.q-icon)
    color: var(--text-muted)

.submit-btn
  margin-top: 8px
  border-radius: 12px
  padding: 14px
  font-weight: 600
  font-size: 1rem

.card-footer
  text-align: center
  padding-bottom: 32px

.toggle-text
  color: var(--text-tertiary)
  margin: 0

.toggle-link
  color: var(--accent-primary)
  text-decoration: none
  font-weight: 500

  &:hover
    text-decoration: underline

.back-link
  margin-top: 24px
  color: var(--text-tertiary)
  text-decoration: none
  display: flex
  align-items: center
  font-size: 0.9rem
  transition: color 0.3s ease

  &:hover
    color: var(--text-primary)
</style>
