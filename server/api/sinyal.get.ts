// API route untuk sinyal.php - Generate trading signals Type B
export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const config = useRuntimeConfig()
  
  const accountNumber = query.account as string || ''
  const token = query.token as string || ''
  const validToken = config.apiToken || 'abc321Xyz'

  // Validasi token
  if (token !== validToken) {
    throw createError({
      statusCode: 401,
      message: 'Invalid token'
    })
  }

  // Konfigurasi Multi Schedule Type B (30 MENIT) - V10
  const jarakMenit = 30 // Jarak menit antara sinyal dalam satu schedule
  const jumlahEntry = 40 // Jumlah total entry per schedule

  // DEFINE SCHEDULE (V10)
  const schedules = [
    { schedule_id: 'S1', schedule_time: '01:00:00' },
    { schedule_id: 'S2', schedule_time: '01:03:00' },
    { schedule_id: 'S3', schedule_time: '01:06:00' },
    { schedule_id: 'S4', schedule_time: '01:09:00' },
    { schedule_id: 'S5', schedule_time: '01:12:00' },
    { schedule_id: 'S6', schedule_time: '01:15:00' },
    { schedule_id: 'S7', schedule_time: '01:18:00' },
    { schedule_id: 'S8', schedule_time: '01:21:00' },
    { schedule_id: 'S9', schedule_time: '01:24:00' },
    { schedule_id: 'SX', schedule_time: '01:27:00' }
  ]

  // Tanggal hari ini (UTC)
  const tanggalHariIni = new Date().toISOString().split('T')[0].replace(/-/g, '.')

  // Generate sinyal Type B untuk semua schedule
  const allEntries: any[] = []

  schedules.forEach(schedule => {
    const scheduleId = schedule.schedule_id
    const scheduleTime = schedule.schedule_time

    // Parse jam mulai dari schedule
    const [startHour, startMinute, startSecond = 0] = scheduleTime.split(':').map(Number)

    let currentHour = startHour
    let currentMinute = startMinute
    let counter = 1

    // Generate 40 entries untuk schedule ini
    for (let i = 0; i < jumlahEntry; i++) {
      // Parameter FIX untuk Type B (BuyStop/SellStop)
      const triggerbuy = 10000 // Jarak BUY STOP dari current price (DIATAS)
      const triggersell = 10000 // Jarak SELL STOP dari current price (DIBAWAH)
      const slbuy = 20000 // SL untuk BUY STOP position (dibawah entry)
      const tpbuy = 40000 // TP untuk BUY STOP position (diatas entry)
      const slsell = 20000 // SL untuk SELL STOP position (diatas entry)
      const tpsell = 40000 // TP untuk SELL STOP position (dibawah entry)

      // Format waktu
      const timeFormatted = `${String(currentHour).padStart(2, '0')}:${String(currentMinute).padStart(2, '0')}`

      // Generate sinyal Type B dengan schedule identification
      allEntries.push({
        schedule_id: scheduleId,
        schedule_time: scheduleTime,
        nomor: counter,
        tanggal: tanggalHariIni,
        time: timeFormatted,
        entry: 'B', // TYPE B: BuyStop/SellStop
        slbuy: slbuy,
        tpbuy: tpbuy,
        slsell: slsell,
        tpsell: tpsell,
        triggerbuy: triggerbuy,
        triggersell: triggersell
      })

      // Tambah waktu berdasarkan jarak menit (30 menit antar entry dalam schedule)
      const totalMinutes = (currentHour * 60) + currentMinute + jarakMenit
      currentHour = Math.floor(totalMinutes / 60) % 24
      currentMinute = totalMinutes % 60

      counter++

      // Jika sudah melewati jam 23, stop
      if (currentHour > 23) {
        break
      }
    }
  })

  // Format response sesuai format PHP (tanpa quotes pada field tertentu)
  // Untuk Nuxt, kita return JSON biasa, tapi bisa di-format di frontend jika perlu
  return {
    status: 'success',
    account: accountNumber || null,
    schedules: schedules.length,
    entries_per_schedule: jumlahEntry,
    jarak_menit: jarakMenit,
    entry_type: 'B',
    total_signals: allEntries.length,
    timezone: 'UTC',
    data: allEntries
  }
})

