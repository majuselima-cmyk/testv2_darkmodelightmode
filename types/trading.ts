// Type definitions untuk Trading System

export interface TradingPosition {
  id?: number
  account_number: string
  ticket: number
  symbol: string
  position_type: 'BUY' | 'SELL'
  volume: number | string
  price: number | string
  profit: number | string
  position_time: string
  comment?: string | null
  sync_time?: string
  created_at?: string
}

export interface LotSize {
  id?: number
  schedule?: string
  size: number | string
  order_index: number
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface EAControl {
  account_number: string
  status: 'ON' | 'OFF'
  schedule_s1: 'ON' | 'OFF'
  schedule_s2: 'ON' | 'OFF'
  schedule_s3: 'ON' | 'OFF'
  schedule_s4: 'ON' | 'OFF'
  schedule_s5: 'ON' | 'OFF'
  schedule_s6: 'ON' | 'OFF'
  schedule_s7: 'ON' | 'OFF'
  schedule_s8: 'ON' | 'OFF'
  schedule_s9: 'ON' | 'OFF'
  schedule_sx: 'ON' | 'OFF'
  updated_at?: string
  updated_by?: string | null
}


