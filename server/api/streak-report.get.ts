// API route untuk streak-report - Analisis streak loss per schedule
export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const config = useRuntimeConfig()
  
  const account = (query.account as string) || config.defaultAccount || '206943771'
  const filterDate = (query.date as string) || new Date().toISOString().split('T')[0]
  const minStreak = 1
  const maxStreak = 15

  // Create Supabase client
  const { createClient } = await import('@supabase/supabase-js')
  const supabaseUrl = config.public.supabaseUrl
  const supabaseAnonKey = config.public.supabaseAnonKey
  
  if (!supabaseUrl || !supabaseAnonKey) {
    throw createError({
      statusCode: 500,
      message: 'Missing Supabase configuration'
    })
  }

  const supabase = createClient(supabaseUrl, supabaseAnonKey)

  try {
    // Get positions for the date
    const dateStartUTC = `${filterDate} 00:00:00`
    const dateEndUTC = `${filterDate} 23:59:59`

    const { data: positions, error } = await supabase
      .from('trading_positions')
      .select('id, account_number, profit, comment, position_time')
      .eq('account_number', account)
      .gte('position_time', dateStartUTC)
      .lte('position_time', dateEndUTC)
      .order('position_time', { ascending: true })
      .order('id', { ascending: true })

    if (error) throw error

    // Helper function to get schedule from comment
    const getScheduleV10 = (comment: string | null): string => {
      if (!comment) return '-'
      if (comment.includes('_SX_')) return 'SX'
      if (comment.includes('_S1_')) return 'S1'
      if (comment.includes('_S2_')) return 'S2'
      if (comment.includes('_S3_')) return 'S3'
      if (comment.includes('_S4_')) return 'S4'
      if (comment.includes('_S5_')) return 'S5'
      if (comment.includes('_S6_')) return 'S6'
      if (comment.includes('_S7_')) return 'S7'
      if (comment.includes('_S8_')) return 'S8'
      if (comment.includes('_S9_')) return 'S9'
      return '-'
    }

    // Group positions by schedule
    const bySchedule: Record<string, any[]> = {}
    const schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX']
    
    schedules.forEach(s => {
      bySchedule[s] = []
    })

    positions?.forEach((pos: any) => {
      const schedule = getScheduleV10(pos.comment)
      if (schedule !== '-' && bySchedule[schedule]) {
        bySchedule[schedule].push(pos)
      }
    })

    // Calculate streak stats per schedule
    const streakStats: Record<string, any> = {}
    
    schedules.forEach(s => {
      streakStats[s] = {
        total_trades: bySchedule[s].length,
        streak_counts: {}
      }
      
      // Initialize streak counts
      for (let n = minStreak; n <= maxStreak; n++) {
        streakStats[s].streak_counts[n] = 0
      }

      // Calculate streaks - count all streaks from 1 to 15
      let currentLossStreak = 0
      bySchedule[s].forEach((pos: any) => {
        const profit = parseFloat(pos.profit?.toString() || '0')
        if (profit < 0) {
          currentLossStreak++
        } else {
          // Record streak if >= minStreak (1)
          if (currentLossStreak >= minStreak) {
            let len = currentLossStreak
            if (len > maxStreak) {
              len = maxStreak
            }
            streakStats[s].streak_counts[len] = (streakStats[s].streak_counts[len] || 0) + 1
          }
          currentLossStreak = 0
        }
      })

      // Close streak at end of data
      if (currentLossStreak >= minStreak) {
        let len = currentLossStreak
        if (len > maxStreak) {
          len = maxStreak
        }
        streakStats[s].streak_counts[len] = (streakStats[s].streak_counts[len] || 0) + 1
      }
    })

    // Calculate total streak per length (all schedules)
    const totalStreakPerLength: Record<number, number> = {}
    for (let n = minStreak; n <= maxStreak; n++) {
      totalStreakPerLength[n] = 0
      schedules.forEach(s => {
        totalStreakPerLength[n] += streakStats[s].streak_counts[n] || 0
      })
    }

    // Count active schedules
    const activeSchedules = schedules.filter(s => streakStats[s].total_trades > 0).length

    // Count total blocks
    const totalBlocks = Object.values(totalStreakPerLength).reduce((sum, val) => sum + val, 0)

    return {
      status: 'success',
      account_number: account,
      filter_date: filterDate,
      min_streak: minStreak,
      max_streak: maxStreak,
      statistics: {
        total_positions: positions?.length || 0,
        active_schedules: activeSchedules,
        total_blocks: totalBlocks
      },
      streak_stats: streakStats,
      total_streak_per_length: totalStreakPerLength
    }

  } catch (error: any) {
    throw createError({
      statusCode: 500,
      message: error.message || 'Internal server error'
    })
  }
})

