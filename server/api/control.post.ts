// API route untuk control.php - Set EA status (POST)
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

  const account = (query.account as string) || (body.account as string) || config.defaultAccount || '206943771'
  const action = (query.action as string) || (body.action as string) || 'get'
  const schedule = ((query.schedule as string) || (body.schedule as string) || '').toUpperCase()
  const statusParam = ((query.status as string) || (body.status as string) || 'ON').toUpperCase()

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

  // Get IP address untuk tracking (server-side)
  const ipAddress = getHeader(event, 'x-forwarded-for') || 
                    getHeader(event, 'x-real-ip') || 
                    'unknown'

  try {
    let updateData: any = {
      updated_by: ipAddress,
      updated_at: new Date().toISOString()
    }

    // Handle different actions
    if (action === 'set' || action === 'on' || action === 'off') {
      const newStatus = action === 'on' ? 'ON' : action === 'off' ? 'OFF' : statusParam
      if (newStatus !== 'ON' && newStatus !== 'OFF') {
        throw createError({
          statusCode: 400,
          message: 'Invalid status. Must be ON or OFF'
        })
      }
      updateData.status = newStatus

    } else if (action.match(/^s([1-9]|x)_(on|off)$/i)) {
      // Handle schedule-specific actions (s1_on, s2_off, etc)
      const match = action.match(/^s([1-9]|x)_(on|off)$/i)
      if (!match) {
        throw createError({
          statusCode: 400,
          message: 'Invalid schedule action format'
        })
      }
      const scheduleId = 'S' + match[1].toUpperCase()
      const newStatus = match[2].toUpperCase() === 'ON' ? 'ON' : 'OFF'
      updateData[`schedule_${scheduleId.toLowerCase()}`] = newStatus

    } else if (action === 'all_on' || action === 'all_off') {
      const newStatus = action === 'all_on' ? 'ON' : 'OFF'
      updateData.schedule_s1 = newStatus
      updateData.schedule_s2 = newStatus
      updateData.schedule_s3 = newStatus
      updateData.schedule_s4 = newStatus
      updateData.schedule_s5 = newStatus
      updateData.schedule_s6 = newStatus
      updateData.schedule_s7 = newStatus
      updateData.schedule_s8 = newStatus
      updateData.schedule_s9 = newStatus
      updateData.schedule_sx = newStatus

    } else {
      throw createError({
        statusCode: 400,
        message: 'Invalid action'
      })
    }

    // Update database
    const { data: updatedControl, error } = await supabase
      .from('ea_control')
      .upsert({
        account_number: account,
        ...updateData
      }, {
        onConflict: 'account_number'
      })
      .select()
      .single()

    if (error) throw error

    return {
      status: 'success',
      message: 'EA status updated successfully',
      account_number: updatedControl.account_number,
      ea_status: updatedControl.status || 'ON',
      schedule_s1: updatedControl.schedule_s1 || 'ON',
      schedule_s2: updatedControl.schedule_s2 || 'ON',
      schedule_s3: updatedControl.schedule_s3 || 'ON',
      schedule_s4: updatedControl.schedule_s4 || 'ON',
      schedule_s5: updatedControl.schedule_s5 || 'ON',
      schedule_s6: updatedControl.schedule_s6 || 'ON',
      schedule_s7: updatedControl.schedule_s7 || 'ON',
      schedule_s8: updatedControl.schedule_s8 || 'ON',
      schedule_s9: updatedControl.schedule_s9 || 'ON',
      schedule_sx: updatedControl.schedule_sx || 'ON',
      updated_at: updatedControl.updated_at,
      updated_by: updatedControl.updated_by || 'system'
    }

  } catch (error: any) {
    throw createError({
      statusCode: error.statusCode || 500,
      message: error.message || 'Internal server error'
    })
  }
})

