const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost.sekouAI.com'

interface ApiResponse<T = unknown> {
  success: boolean
  message?: string
  user?: T
  token?: string
  errors?: Record<string, string[]>
}

interface LoginData {
  email: string
  password: string
}

interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
}

interface User {
  id: number
  name: string
  email: string
  avatar?: string
}

class ApiService {
  private token: string | null = null

  constructor() {
    this.token = localStorage.getItem('auth_token')
  }

  private getHeaders(): HeadersInit {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    }

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`
    }

    return headers
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const url = `${API_BASE_URL}/api${endpoint}`

    try {
      const response = await fetch(url, {
        ...options,
        headers: this.getHeaders(),
      })

      const data = await response.json()

      if (!response.ok) {
        throw {
          status: response.status,
          ...data,
        }
      }

      return data
    } catch (error: unknown) {
      if (error && typeof error === 'object' && 'status' in error) {
        throw error
      }
      throw {
        success: false,
        message: 'ネットワークエラーが発生しました',
      }
    }
  }

  setToken(token: string | null) {
    this.token = token
    if (token) {
      localStorage.setItem('auth_token', token)
    } else {
      localStorage.removeItem('auth_token')
    }
  }

  getToken(): string | null {
    return this.token
  }

  async login(data: LoginData): Promise<ApiResponse<User>> {
    const response = await this.request<User>('/login', {
      method: 'POST',
      body: JSON.stringify(data),
    })

    if (response.token) {
      this.setToken(response.token)
    }

    return response
  }

  async register(data: RegisterData): Promise<ApiResponse<User>> {
    const response = await this.request<User>('/register', {
      method: 'POST',
      body: JSON.stringify(data),
    })

    if (response.token) {
      this.setToken(response.token)
    }

    return response
  }

  async logout(): Promise<ApiResponse> {
    const response = await this.request('/logout', {
      method: 'POST',
    })

    this.setToken(null)

    return response
  }

  async me(): Promise<ApiResponse<User>> {
    return this.request<User>('/me')
  }
}

export const api = new ApiService()
export type { User, LoginData, RegisterData, ApiResponse }
