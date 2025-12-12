// Tipos principais do SafeNode

export interface User {
  id: number
  email: string
  name: string
  created_at: string
  is_active: boolean
}

export interface Site {
  id: number
  user_id: number
  domain: string
  api_key: string
  security_level: 'low' | 'medium' | 'high' | 'under_attack'
  is_active: boolean
  created_at: string
}

export interface SecurityLog {
  id: number
  site_id: number
  ip_address: string
  action_taken: 'allowed' | 'blocked' | 'challenged'
  threat_type: string
  request_uri: string
  user_agent: string
  response_time: number
  created_at: string
}

export interface DashboardStats {
  total_requests_24h: number
  threats_blocked_24h: number
  avg_latency: number | null
  total_requests_all: number
  threats_blocked_all: number
  uptime_percent: number
}

export interface IntegrationStats {
  total_sites: number
  total_users: number
  total_api_keys: number
  active_sites: number
}

export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  error?: string
  message?: string
}





