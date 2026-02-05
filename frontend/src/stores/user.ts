import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api, type User } from '../services/api'

export const useUserStore = defineStore('user', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isLoggedIn = computed(() => !!user.value)

  async function login(email: string, password: string) {
    loading.value = true
    error.value = null

    try {
      const response = await api.login({ email, password })
      if (response.user) {
        user.value = response.user
      }
      return response
    } catch (e: unknown) {
      const err = e as { message?: string; errors?: Record<string, string[]> }
      error.value = err.message || 'ログインに失敗しました'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string) {
    loading.value = true
    error.value = null

    try {
      const response = await api.register({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
      if (response.user) {
        user.value = response.user
      }
      return response
    } catch (e: unknown) {
      const err = e as { message?: string; errors?: Record<string, string[]> }
      error.value = err.message || '登録に失敗しました'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    loading.value = true
    error.value = null

    try {
      await api.logout()
      user.value = null
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'ログアウトに失敗しました'
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!api.getToken()) return

    loading.value = true
    error.value = null

    try {
      const response = await api.me()
      if (response.user) {
        user.value = response.user
      }
    } catch {
      user.value = null
      api.setToken(null)
    } finally {
      loading.value = false
    }
  }

  // 初期化時にトークンがあればユーザー情報を取得
  function init() {
    if (api.getToken()) {
      fetchUser()
    }
  }

  return {
    user,
    loading,
    error,
    isLoggedIn,
    login,
    register,
    logout,
    fetchUser,
    init,
  }
})
