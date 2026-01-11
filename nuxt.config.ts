// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  compatibilityDate: '2024-04-03',
  modules: ['@nuxtjs/tailwindcss'],
  runtimeConfig: {
    // Private keys (server-side only)
    supabaseServiceKey: process.env.SUPABASE_SERVICE_KEY || '',
    apiToken: process.env.API_TOKEN || 'abc321Xyz',
    defaultAccount: process.env.DEFAULT_ACCOUNT || '206943771',
    
    // Public keys (exposed to client)
    // Hardcoded values - tidak perlu set environment variables di Vercel
    public: {
      supabaseUrl: process.env.SUPABASE_URL || 'https://pyouwfkzmcyjhzrryvkh.supabase.co',
      supabaseAnonKey: process.env.SUPABASE_ANON_KEY || 'sb_publishable_uudkzBB90U3WkGDAES17lg_yuEworIX'
    }
  },
  css: ['~/assets/css/main.css']
})
