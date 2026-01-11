// API route untuk history-report - Get trading positions dengan filter & pagination
export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const config = useRuntimeConfig()
  
  const account = (query.account as string) || config.defaultAccount || '270787386'
  const filterDate = (query.date as string) || new Date().toISOString().split('T')[0]
  const filterSchedule = (query.schedule as string) || ''
  const page = Math.max(1, parseInt(query.page as string) || 1)
  const perPage = 15

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
    // Build query conditions
    const dateStartUTC = `${filterDate} 00:00:00`
    const dateEndUTC = `${filterDate} 23:59:59`

    // Build base query for count
    let countQuery = supabase
      .from('trading_positions')
      .select('*', { count: 'exact', head: true })
      .eq('account_number', account)
      .gte('position_time', dateStartUTC)
      .lte('position_time', dateEndUTC)

    // Filter by schedule jika ada
    if (filterSchedule && ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX'].includes(filterSchedule)) {
      countQuery = countQuery.ilike('comment', `%_${filterSchedule}_%`)
    }

    // Get total count for filtered data
    const { count: totalCount, error: countError } = await countQuery

    if (countError) throw countError

    const totalPages = Math.max(1, Math.ceil((totalCount || 0) / perPage))
    const currentPage = Math.min(page, totalPages)
    const offset = (currentPage - 1) * perPage

    // Get paginated data
    let dataQuery = supabase
      .from('trading_positions')
      .select('*')
      .eq('account_number', account)
      .gte('position_time', dateStartUTC)
      .lte('position_time', dateEndUTC)
      .order('id', { ascending: false })
      .order('created_at', { ascending: false })
      .order('ticket', { ascending: false })
      .range(offset, offset + perPage - 1)

    if (filterSchedule && ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX'].includes(filterSchedule)) {
      dataQuery = dataQuery.ilike('comment', `%_${filterSchedule}_%`)
    }

    const { data: positions, error: dataError } = await dataQuery

    if (dataError) throw dataError

    // Get total count all positions for account (for reference)
    const { count: totalAll, error: totalAllError } = await supabase
      .from('trading_positions')
      .select('*', { count: 'exact', head: true })
      .eq('account_number', account)

    if (totalAllError) throw totalAllError

    // Calculate statistics from filtered data (all matching positions, not just paginated)
    // For better performance, we calculate from paginated data, but note that statistics are per page
    const totalProfit = positions?.reduce((sum, pos) => sum + parseFloat(pos.profit?.toString() || '0'), 0) || 0
    const buyCount = positions?.filter(pos => pos.position_type?.toUpperCase() === 'BUY').length || 0
    const sellCount = positions?.filter(pos => pos.position_type?.toUpperCase() === 'SELL').length || 0

    return {
      status: 'success',
      account_number: account,
      filter_date: filterDate,
      filter_schedule: filterSchedule || null,
      pagination: {
        page: currentPage,
        per_page: perPage,
        total_pages: totalPages,
        total_count: totalCount || 0,
        total_all: totalAll || 0
      },
      statistics: {
        total_profit: totalProfit,
        buy_count: buyCount,
        sell_count: sellCount,
        returned: positions?.length || 0
      },
      positions: positions || []
    }

  } catch (error: any) {
    throw createError({
      statusCode: 500,
      message: error.message || 'Internal server error'
    })
  }
})

