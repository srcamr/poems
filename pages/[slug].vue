<script setup lang="ts">
// Nuxt auto-imports useRoute, useFetch, useRuntimeConfig
const route = useRoute();
const config = useRuntimeConfig();
const API_URL = config.public.apiBaseUrl;

const rawSlug = route.params.slug as string;
const slug = decodeURIComponent(rawSlug);
const poemName = slug.replace(/-/g, ' ');

const { data: response, pending, error } = await useFetch(`${API_URL}/api/connect.php?action=getPoemBySlug&slug=${encodeURIComponent(poemName)}`, {
  key: `poet-${slug}`,
  transform: (res: any) => res || { poems: [] }
});

const poems = computed(() => response.value?.poems || []);

useHead({
  title: `${poemName} - أجمل الأبيات الشعرية`,
  meta: [
    { name: 'description', content: `تصفح أجمل الأبيات الشعرية للشاعر ${poemName}` }
  ]
})
</script>

<template>
  <NuxtLayout>
    <div class="min-h-screen py-32 px-6 w-full md:w-2/3 lg:w-1/2 mx-auto">
      <div class="mb-16 animate-in fade-in slide-in-from-top-10 duration-700">
        <NuxtLink to="/" class="text-blue-400 hover:text-blue-300 font-bold mb-6 flex items-center gap-2 transition-colors">
          <span>→</span> العودة للرئيسية
        </NuxtLink>
        <h1 class="text-5xl md:text-6xl font-black text-white text-right leading-tight">
          أبيات <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">{{ poemName }}</span>
        </h1>
      </div>

      <div v-if="pending" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent"></div>
      </div>

      <div v-else-if="error" class="text-center py-20 bg-red-500/10 rounded-3xl border border-red-500/20">
        <p class="text-red-400 text-xl font-medium">حدث خطأ أثناء تحميل البيانات. يرجى المحاولة لاحقاً.</p>
      </div>

      <div v-else class="space-y-6">
        <div v-for="(poem, index) in poems" :key="index" 
          class="bg-white p-8 sm:p-10 rounded-3xl shadow-xl transition-all hover:scale-[1.01] hover:shadow-2xl animate-in fade-in slide-in-from-right-10 duration-500"
          :style="{ animationDelay: `${index * 100}ms` }">
          <p class="text-xl sm:text-2xl text-gray-800 text-right leading-relaxed font-arabic break-words whitespace-pre-wrap">
            {{ poem }}
          </p>
        </div>

        <div v-if="poems.length === 0" class="text-center py-20 bg-white/5 rounded-3xl border border-dashed border-white/20">
          <p class="text-gray-400 text-xl font-medium">لا توجد أبيات متاحة لهذا الشاعر حالياً.</p>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<style scoped>
.font-arabic {
  font-family: 'Amiri', serif;
}
</style>
