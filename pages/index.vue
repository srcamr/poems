<script setup lang="ts">
// Nuxt auto-imports useFetch, useRuntimeConfig
const config = useRuntimeConfig();
const API_URL = config.public.apiBaseUrl;

const { data: poets, pending } = await useFetch(`${API_URL}/api/connect.php?action=getPoemNames`, {
  transform: (res: any) => res || []
});

useHead({
  title: 'المساجلة الشعرية - الرئيسية',
  meta: [
    { name: 'description', content: 'استكشف القصائد وشارك في المساجلة الشعرية' }
  ]
})
</script>

<template>
  <NuxtLayout>
    <div class="min-h-screen py-32 px-6 w-full md:w-2/3 lg:w-1/2 mx-auto">
      <div class="text-center mb-16 space-y-6 animate-in fade-in slide-in-from-top-10 duration-700">
        <h1 class="text-5xl md:text-7xl font-black text-white tracking-tight leading-tight">
          دردشة <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">شعرية</span>
        </h1>
        <p class="text-xl text-gray-300 font-medium max-w-xl mx-auto leading-relaxed">
          انضم إلى أكبر تجمع لمحبي الشعر العربي، ساجل، أضف، واستمتع بجمال اللغة.
        </p>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-20 animate-in fade-in slide-in-from-bottom-5 duration-700">
        <NuxtLink to="/chat" class="group relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-700 p-8 rounded-3xl shadow-xl transition-all hover:scale-[1.03] active:scale-95">
          <div class="absolute -right-10 -bottom-10 opacity-10 group-hover:scale-110 transition-transform duration-500">
            <svg class="w-40 h-40 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/></svg>
          </div>
          <h2 class="text-2xl font-black text-white mb-2 text-right">ابدأ المساجلة</h2>
          <p class="text-blue-100 text-right">تحدى النظام في مساجلة شعرية فورية</p>
        </NuxtLink>

        <NuxtLink to="/add" class="group relative overflow-hidden bg-white p-8 rounded-3xl shadow-xl transition-all hover:scale-[1.03] active:scale-95 border-b-8 border-gray-100">
          <div class="absolute -right-10 -bottom-10 opacity-5 group-hover:scale-110 transition-transform duration-500">
            <svg class="w-40 h-40 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
          </div>
          <h2 class="text-2xl font-black text-gray-800 mb-2 text-right">إضافة أبيات</h2>
          <p class="text-gray-500 text-right">ساهم في إثراء قاعدة البيانات لدينا</p>
        </NuxtLink>
      </div>

      <!-- Poets List -->
      <div class="space-y-8 animate-in fade-in duration-1000 delay-300">
        <h3 class="text-3xl font-black text-white text-right mb-10 flex items-center justify-end gap-3">
          <span>✨</span> الشعراء والمجموعات
        </h3>
        
        <div v-if="pending" class="flex justify-center py-20">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent"></div>
        </div>

        <div v-else class="grid grid-cols-2 lg:grid-cols-3 gap-6">
          <NuxtLink v-for="poet in poets" :key="poet.ID" :to="`/${poet.name.trim().replace(/\s+/g, '-')}`" 
            class="bg-white/10 backdrop-blur-md border border-white/10 p-6 rounded-2xl text-right transition-all hover:bg-white/20 hover:border-white/30 hover:scale-105 active:scale-95 group">
            <h4 class="text-xl font-bold text-white group-hover:text-blue-300 transition-colors">{{ poet.name }}</h4>
            <div class="mt-4 flex justify-end">
              <span class="text-xs font-bold uppercase tracking-widest text-blue-400 group-hover:text-blue-200">عرض القصائد ←</span>
            </div>
          </NuxtLink>
        </div>

        <div v-if="!pending && poets.length === 0" class="text-center py-20 bg-white/5 rounded-3xl border border-dashed border-white/20">
          <p class="text-gray-400 text-xl font-medium">لا يوجد شعراء حالياً في قاعدة البيانات.</p>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<style scoped>
/* Specific animations if needed */
</style>
