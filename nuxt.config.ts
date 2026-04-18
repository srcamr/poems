export default defineNuxtConfig({
  ssr: true,
  modules: ['@nuxtjs/tailwindcss'],
  nitro: {
    prerender: {
      crawlLinks: true,
      routes: async () => {
        // Fetch slugs at build time to generate static pages
        const baseUrl = process.env.API_BASE_URL || 'http://localhost/public';
        try {
          const response = await fetch(`${baseUrl}/api/connect.php?action=getSlugs`);
          const records = await response.json();
          return Array.isArray(records) ? records.map((name) => `/${name}`) : [];
        } catch (e) {
          console.error("Error fetching slugs for prerender:", e);
          return [];
        }
      }
    }
  },
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.API_BASE_URL || 'https://poem.aallem.com/public'
    }
  },
  devtools: { enabled: true }
})
