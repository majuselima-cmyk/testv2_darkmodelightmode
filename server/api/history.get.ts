// API route untuk history.php - Get trading positions (GET)
export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const config = useRuntimeConfig()
  
  // Validasi token
  const token = query.token as string
  const validToken = config.apiToken || 'abc321Xyz'
  
  if (token !== validToken) {
    throw createError({
      statusCode: 401,
      message: 'Invalid token'
    })
  }

  const account = (query.account as string) || config.defaultAccount || '206943771'
  const limit = parseInt(query.limit as string) || 100

  if (!account) {
    throw createError({
      statusCode: 400,
      message: 'Account parameter is required'
    })
  }

  // Create Supabase client untuk server-side
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
    // Get positions
    const { data: positions, error } = await supabase
      .from('trading_positions')
      .select('*')
      .eq('account_number', account)
      .order('id', { ascending: false })
      .order('created_at', { ascending: false })
      .order('ticket', { ascending: false })
      .limit(limit)

    if (error) throw error

    // Get total count
    const { count, error: countError } = await supabase
      .from('trading_positions')
      .select('*', { count: 'exact', head: true })
      .eq('account_number', account)

    if (countError) throw countError

    return {
      status: 'success',
      account_number: account,
      total: count || 0,
      returned: positions?.length || 0,
      positions: positions || []
    }

  } catch (error: any) {
    throw createError({
      statusCode: 500,
      message: error.message || 'Internal server error'
    })
  }
})

