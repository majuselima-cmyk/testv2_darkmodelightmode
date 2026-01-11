// Composable untuk trading functions (lot calculation, etc)
import type { TradingPosition, LotSize } from '~/types/trading'

export const useTrading = () => {
  const { supabase } = useSupabase()
  const config = useRuntimeConfig()

  // Get schedule patterns untuk query
  const getSchedulePatterns = (schedule: string): string[] => {
    return [
      `%_${schedule}_%`,
      `${schedule}_%`,
      `%_${schedule}%`
    ]
  }

  // Filter trades berdasarkan schedule
  const filterTradesBySchedule = (trades: TradingPosition[], schedule: string): TradingPosition[] => {
    return trades.filter(trade => {
      const comment = trade.comment || ''
      const pattern = new RegExp(`[^0-9]${schedule.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?![0-9])|^${schedule.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?![0-9])`)
      return pattern.test(comment)
    })
  }

  // Calculate lot index dari 15 trade terakhir
  const calculateLotIndexFromLast15Trades = async (
    account: string,
    schedule: string,
    lotSizes: LotSize[]
  ): Promise<number> => {
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
          (lot, i) => Math.abs(parseFloat(lot.size.toString()) - lastVolume) < 0.001
        )

        if (trades.length > 1) {
          const prevTrade = trades[1]
          const prevProfit = parseFloat(prevTrade.profit?.toString() || '0')
          const prevVolume = prevTrade.volume ? parseFloat(prevTrade.volume.toString()) : null

          if (prevProfit > 0) {
            lotIndex = Math.max(1, lotIndex)
          } else if (prevProfit < 0 && prevVolume !== null) {
            const prevLotIndex = lotSizes.findIndex(
              (lot, i) => Math.abs(parseFloat(lot.size.toString()) - prevVolume) < 0.001
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

  // Get active lots per schedule
  const getActiveLotsPerSchedule = async (
    account: string,
    lotSizes: LotSize[]
  ): Promise<Record<string, any>> => {
    const schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX']
    const activeLots: Record<string, any> = {}

    for (const schedule of schedules) {
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
      const calculatedLotIndex = await calculateLotIndexFromLast15Trades(account, schedule, lotSizes)

      activeLots[schedule] = {
        schedule,
        lot_index: calculatedLotIndex,
        active_lot: lotSizes[calculatedLotIndex]?.size || '0.01',
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

  return {
    getSchedulePatterns,
    filterTradesBySchedule,
    calculateLotIndexFromLast15Trades,
    getActiveLotsPerSchedule
  }
}

