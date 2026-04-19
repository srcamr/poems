export default defineNuxtConfig({
  ssr: true,
  modules: ['@nuxtjs/tailwindcss'],
  hooks: {
    async 'prerender:routes'(ctx) {
      const baseUrl = process.env.API_BASE_URL || 'https://poem.aallem.com/public';
      try {
        const response = await fetch(`${baseUrl}/api/connect.php?action=getSlugs`);
        const records = await response.json();
        if (Array.isArray(records)) {
          records.forEach((name: string) => {
            const slug = encodeURIComponent(name.trim().replace(/\s+/g, '-'));
            ctx.routes.add(`/${slug}`);
          });
        }
      } catch (e) {
        console.error("Error fetching slugs for prerender:", e);
      }
    }
  },
  nitro: {
    prerender: {
      crawlLinks: true
    }
  },
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.API_BASE_URL || 'https://poem.aallem.com/public'
    }
  },
  devtools: { enabled: true }
})
