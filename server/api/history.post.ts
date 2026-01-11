// API route untuk history.php - Sync trading positions (POST)
export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const query = getQuery(event)
  const config = useRuntimeConfig()
  
  // Validasi token
  const token = (query.token as string) || (body.token as string)
  const validToken = config.apiToken || 'abc321Xyz'
  
  if (token !== validToken) {
    throw createError({
      statusCode: 401,
      message: 'Invalid token'
    })
  }

  if (!body.account || !body.positions || !Array.isArray(body.positions)) {
    throw createError({
      statusCode: 400,
      message: 'Missing required fields: account and positions array'
    })
  }

  const accountNumber = body.account
  const positions = body.positions

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

  // Get IP address untuk tracking
  const ipAddress = getHeader(event, 'x-forwarded-for') || 
                    getHeader(event, 'x-real-ip') || 
                    'unknown'

  let successCount = 0
  let errorCount = 0
  const errors: string[] = []

  try {
    // Prepare positions untuk insert/update
    const positionsToUpsert = positions.map((position: any) => {
      // Validasi required fields
      if (!position.ticket || !position.symbol || !position.type || 
          !position.volume || !position.price || !position.time) {
        errorCount++
        errors.push(`Missing required fields for ticket ${position.ticket || 'unknown'}`)
        return null
      }

      // Parse position_time
      let positionTime: Date
      try {
        positionTime = new Date(position.time)
        if (isNaN(positionTime.getTime())) {
          positionTime = new Date()
        }
      } catch {
        positionTime = new Date()
      }

      return {
        account_number: accountNumber,
        ticket: parseInt(position.ticket.toString()),
        symbol: position.symbol.toString(),
        position_type: position.type.toString().toUpperCase(),
        volume: parseFloat(position.volume.toString()),
        price: parseFloat(position.price.toString()),
        profit: position.profit ? parseFloat(position.profit.toString()) : 0.00,
        position_time: positionTime.toISOString(),
        comment: position.comment || '',
        sync_time: new Date().toISOString()
      }
    }).filter(p => p !== null)

    // Upsert positions (insert or update on conflict)
    if (positionsToUpsert.length > 0) {
      const { error: upsertError } = await supabase
        .from('trading_positions')
        .upsert(positionsToUpsert, {
          onConflict: 'account_number,ticket',
          ignoreDuplicates: false
        })

      if (upsertError) {
        throw upsertError
      }

      successCount = positionsToUpsert.length
      errorCount = positions.length - successCount
    }

    const totalCount = positions.length
    const syncType = totalCount > 50 ? 'bulk' : 'normal'

    return {
      status: 'success',
      message: 'Positions synced successfully',
      data: {
        total_received: totalCount,
        successful: successCount,
        failed: errorCount,
        sync_type: syncType
      },
      errors: errors.length > 0 ? errors : undefined
    }

  } catch (error: any) {
    throw createError({
      statusCode: 500,
      message: error.message || 'Internal server error'
    })
  }
})

