// Server-side utility functions untuk trading
// Bisa dipakai di API routes

import type { TradingPosition, LotSize } from '~/types/trading'

export function getSchedulePatterns(schedule: string): string[] {
  return [
    `%_${schedule}_%`,
    `${schedule}_%`,
    `%_${schedule}%`
  ]
}

export function filterTradesBySchedule(trades: TradingPosition[], schedule: string): TradingPosition[] {
  return trades.filter(trade => {
    const comment = trade.comment || ''
    const pattern = new RegExp(`[^0-9]${schedule.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?![0-9])|^${schedule.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?![0-9])`)
    return pattern.test(comment)
  })
}

export async function calculateLotIndexFromLast15Trades(
  supabase: any,
  account: string,
  schedule: string,
  lotSizes: LotSize[]
): Promise<number> {
  const patterns = getSchedulePatterns(schedule)
  
  let trades: TradingPosition[] = []
  
  // Try each pattern
  for (const pattern of patterns) {
    const { data, error } = await supabase
      .from('trading_positions')
      .select('id, ticket, profit, comment, created_at, volume')
      .eq('account_number', account)
      .ilike('comment', pattern)
      .order('id', { ascending: false })
      .order('created_at', { ascending: false })
      .order('ticket', { ascending: false })
      .limit(25)

    if (!error && data && data.length > 0) {
      const filtered = filterTradesBySchedule(data as TradingPosition[], schedule)
      if (filtered.length > 0) {
        trades = filtered.slice(0, 15)
        break
      }
    }
  }

  if (trades.length === 0) {
    return 0
  }

  let lotIndex = 0
  const tradesReversed = [...trades].reverse()

  // Hitung lot index dari trade terlama ke terbaru
  for (const trade of tradesReversed) {
    const profit = parseFloat(trade.profit?.toString() || '0')
    const volume = trade.volume ? parseFloat(trade.volume.toString()) : null

    if (profit < 0) {
      // Loss: increment lot index
      lotIndex = Math.min(lotIndex + 1, lotSizes.length - 1)
    } else if (profit > 0) {
      // Profit: reset ke index 0
      lotIndex = 0
    }
  }

  // Validasi final dengan trade terakhir
  if (trades.length > 0) {
    const lastTrade = trades[0]
    const lastProfit = parseFloat(lastTrade.profit?.toString() || '0')
    const lastVolume = lastTrade.volume ? parseFloat(lastTrade.volume.toString()) : null

    // Jika trade terakhir profit, lot_index HARUS 0
    if (lastProfit > 0) {
      return 0
    }

    // Jika trade terakhir loss, validasi berdasarkan volume
    if (lastProfit < 0 && lastVolume !== null) {
      const usedLotIndex = lotSizes.findIndex(
        (lot) => Math.abs(parseFloat(lot.size.toString()) - lastVolume) < 0.001
      )

      if (trades.length > 1) {
        const prevTrade = trades[1]
        const prevProfit = parseFloat(prevTrade.profit?.toString() || '0')
        const prevVolume = prevTrade.volume ? parseFloat(prevTrade.volume.toString()) : null

        if (prevProfit > 0) {
          lotIndex = Math.max(1, lotIndex)
        } else if (prevProfit < 0 && prevVolume !== null) {
          const prevLotIndex = lotSizes.findIndex(
            (lot) => Math.abs(parseFloat(lot.size.toString()) - prevVolume) < 0.001
          )

          if (prevLotIndex >= 0) {
            const minRequiredIndex = Math.min(prevLotIndex + 1, lotSizes.length - 1)
            if (lotIndex <= prevLotIndex) {
              lotIndex = minRequiredIndex
            } else {
              lotIndex = Math.max(lotIndex, minRequiredIndex)
            }
          }
        }
      }
    }
  }

  return lotIndex
}

// Helper function untuk mendapatkan lot sizes per schedule
export async function getLotSizesBySchedule(
  supabase: any,
  schedule: string
): Promise<LotSize[]> {
  const { data, error } = await supabase
    .from('lot_sizes')
    .select('*')
    .eq('schedule', schedule)
    .eq('is_active', true)
    .order('order_index', { ascending: true })

  if (error || !data || data.length === 0) {
    // Fallback ke default lot sizes
    return [
      { id: 0, size: 0.03, order_index: 1, is_active: true, schedule },
      { id: 0, size: 0.06, order_index: 2, is_active: true, schedule },
      { id: 0, size: 0.10, order_index: 3, is_active: true, schedule },
      { id: 0, size: 0.15, order_index: 4, is_active: true, schedule },
      { id: 0, size: 0.24, order_index: 5, is_active: true, schedule },
      { id: 0, size: 0.29, order_index: 6, is_active: true, schedule },
      { id: 0, size: 0.39, order_index: 7, is_active: true, schedule },
      { id: 0, size: 0.51, order_index: 8, is_active: true, schedule },
      { id: 0, size: 0.67, order_index: 9, is_active: true, schedule },
      { id: 0, size: 0.86, order_index: 10, is_active: true, schedule },
      { id: 0, size: 1.10, order_index: 11, is_active: true, schedule },
      { id: 0, size: 1.35, order_index: 12, is_active: true, schedule },
      { id: 0, size: 1.76, order_index: 13, is_active: true, schedule },
      { id: 0, size: 2.22, order_index: 14, is_active: true, schedule },
      { id: 0, size: 2.80, order_index: 15, is_active: true, schedule },
      { id: 0, size: 3.52, order_index: 16, is_active: true, schedule }
    ]
  }

  return data.map((item: any) => ({
    ...item,
    size: parseFloat(item.size.toString())
  }))
}

export async function getActiveLotsPerSchedule(
  supabase: any,
  account: string
): Promise<Record<string, any>> {
  const schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX']
  const activeLots: Record<string, any> = {}

  for (const schedule of schedules) {
    // Get lot sizes untuk schedule ini
    const lotSizes = await getLotSizesBySchedule(supabase, schedule)
    const patterns = getSchedulePatterns(schedule)

    let lastTrade: TradingPosition | null = null
    let last15Trades: TradingPosition[] = []
    let usedPattern = ''

    // Get last 15 trades untuk schedule ini
    for (const pattern of patterns) {
      const { data, error } = await supabase
        .from('trading_positions')
        .select('id, ticket, profit, comment, created_at, volume')
        .eq('account_number', account)
        .ilike('comment', pattern)
        .order('id', { ascending: false })
        .order('created_at', { ascending: false })
        .order('ticket', { ascending: false })
        .limit(25)

      if (!error && data && data.length > 0) {
        const filtered = filterTradesBySchedule(data as TradingPosition[], schedule)
        if (filtered.length > 0) {
          lastTrade = filtered[0]
          usedPattern = pattern
          last15Trades = filtered.slice(0, 15)
          break
        }
      }
    }

    // Calculate lot index
    const calculatedLotIndex = await calculateLotIndexFromLast15Trades(supabase, account, schedule, lotSizes)

    activeLots[schedule] = {
      schedule,
      lot_index: calculatedLotIndex,
      active_lot: lotSizes[calculatedLotIndex]?.size || 0.01,
      last_profit: lastTrade?.profit || null,
      last_ticket: lastTrade?.ticket || null,
      last_comment: lastTrade?.comment || null,
      total_trades: last15Trades.length,
      last_15_trades: last15Trades.map(t => ({
        ...t,
        lot_entry: t.volume || null
      })),
      pattern_used: usedPattern,
      account_used: account
    }
  }

  return activeLots
}


