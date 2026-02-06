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
  is_admin?: boolean
}

interface ChatResponse {
  success: boolean
  message?: string
  error?: string
}

interface ChatHistory {
  role: 'user' | 'assistant'
  content: string
}

interface AiModel {
  id: number
  name: string
  provider: string
  model_id: string
  description: string | null
  max_tokens: number
  context_window: number
  supports_vision: boolean
  supports_streaming: boolean
}

interface AiModelsResponse {
  success: boolean
  models: AiModel[]
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

  async getAiModels(): Promise<AiModelsResponse> {
    const url = `${API_BASE_URL}/api/ai-models`
    
    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: this.getHeaders(),
      })

      const data = await response.json()

      if (!response.ok) {
        return {
          success: false,
          models: [],
        }
      }

      return data
    } catch (error) {
      return {
        success: false,
        models: [],
      }
    }
  }

  async chat(message: string, history: ChatHistory[] = []): Promise<ChatResponse> {
    const url = `${API_BASE_URL}/api/chat`
    
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify({ message, history }),
      })

      const data = await response.json()

      if (!response.ok) {
        return {
          success: false,
          error: data.error || 'チャットリクエストに失敗しました',
        }
      }

      return {
        success: true,
        message: data.message,
      }
    } catch (error) {
      return {
        success: false,
        error: 'ネットワークエラーが発生しました',
      }
    }
  }

  // Conversation API methods
  async getConversations(): Promise<ConversationsResponse> {
    const url = `${API_BASE_URL}/api/conversations`
    
    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: this.getHeaders(),
      })

      const data = await response.json()
      return data
    } catch (error) {
      return {
        success: false,
        conversations: [],
      }
    }
  }

  async getConversation(id: number): Promise<ConversationDetailResponse> {
    const url = `${API_BASE_URL}/api/conversations/${id}`
    
    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: this.getHeaders(),
      })

      const data = await response.json()
      return data
    } catch (error) {
      return {
        success: false,
        conversation: null,
      }
    }
  }

  async deleteConversation(id: number): Promise<{ success: boolean }> {
    const url = `${API_BASE_URL}/api/conversations/${id}`
    
    try {
      const response = await fetch(url, {
        method: 'DELETE',
        headers: this.getHeaders(),
      })

      return await response.json()
    } catch (error) {
      return { success: false }
    }
  }

  // Feature API methods
  async getFeatures(): Promise<FeaturesResponse> {
    const url = `${API_BASE_URL}/api/features`
    
    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: this.getHeaders(),
      })

      const data = await response.json()
      return data
    } catch (error) {
      return { success: false, features: [] }
    }
  }

  async createFeature(title: string): Promise<FeatureCreateResponse> {
    const url = `${API_BASE_URL}/api/features`
    
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify({ title }),
      })

      return await response.json()
    } catch (error) {
      return { success: false }
    }
  }

  async getFeature(id: number): Promise<FeatureDetailResponse> {
    const url = `${API_BASE_URL}/api/features/${id}`
    
    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: this.getHeaders(),
      })

      return await response.json()
    } catch (error) {
      return { success: false, feature: null }
    }
  }

  async updateFeature(id: number, title: string): Promise<FeatureCreateResponse> {
    const url = `${API_BASE_URL}/api/features/${id}`
    
    try {
      const response = await fetch(url, {
        method: 'PUT',
        headers: this.getHeaders(),
        body: JSON.stringify({ title }),
      })

      return await response.json()
    } catch (error) {
      return { success: false }
    }
  }

  async deleteFeature(id: number): Promise<{ success: boolean }> {
    const url = `${API_BASE_URL}/api/features/${id}`
    
    try {
      const response = await fetch(url, {
        method: 'DELETE',
        headers: this.getHeaders(),
      })

      return await response.json()
    } catch (error) {
      return { success: false }
    }
  }

  // Feature Prompt API methods
  async createPrompt(featureId: number, data: Partial<FeaturePrompt>): Promise<PromptCreateResponse> {
    const url = `${API_BASE_URL}/api/features/${featureId}/prompts`
    
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify(data),
      })

      return await response.json()
    } catch (error) {
      return { success: false }
    }
  }

  async updatePrompt(id: number, data: Partial<FeaturePrompt>): Promise<PromptCreateResponse> {
    const url = `${API_BASE_URL}/api/prompts/${id}`
    
    try {
      const response = await fetch(url, {
        method: 'PUT',
        headers: this.getHeaders(),
        body: JSON.stringify(data),
      })

      return await response.json()
    } catch (error) {
      return { success: false }
    }
  }

  async deletePrompt(id: number): Promise<{ success: boolean }> {
    const url = `${API_BASE_URL}/api/prompts/${id}`
    
    try {
      const response = await fetch(url, {
        method: 'DELETE',
        headers: this.getHeaders(),
      })

      return await response.json()
    } catch (error) {
      return { success: false }
    }
  }
}

interface ConversationSummary {
  id: number
  title: string | null
  last_message_at: string | null
  created_at: string
}

interface ConversationMessage {
  id: number
  conversation_id: number
  role: 'user' | 'assistant'
  content: string
  created_at: string
}

interface ConversationDetail {
  id: number
  title: string | null
  messages: ConversationMessage[]
}

interface ConversationsResponse {
  success: boolean
  conversations: ConversationSummary[]
}

interface ConversationDetailResponse {
  success: boolean
  conversation: ConversationDetail | null
}

// Feature interfaces
interface Feature {
  id: number
  user_id: number
  title: string
  prompts_count?: number
  prompts?: FeaturePrompt[]
  created_at: string
  updated_at: string
}

interface FeaturePrompt {
  id: number
  feature_id: number
  title: string
  prompt_content: string | null
  description: string | null
  created_at: string
  updated_at: string
}

interface FeaturesResponse {
  success: boolean
  features: Feature[]
}

interface FeatureCreateResponse {
  success: boolean
  feature?: Feature
}

interface FeatureDetailResponse {
  success: boolean
  feature: Feature | null
}

interface PromptCreateResponse {
  success: boolean
  prompt?: FeaturePrompt
}

export const api = new ApiService()
export type { 
  User, 
  LoginData, 
  RegisterData, 
  ApiResponse, 
  ChatHistory, 
  ChatResponse, 
  AiModel, 
  AiModelsResponse,
  ConversationSummary,
  ConversationMessage,
  ConversationDetail,
  ConversationsResponse,
  ConversationDetailResponse,
  Feature,
  FeaturePrompt,
  FeaturesResponse,
  FeatureCreateResponse,
  FeatureDetailResponse,
  PromptCreateResponse
}




