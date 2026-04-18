<script setup lang="ts">
const isMenuOpen = ref(false);
const route = useRoute();

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value;
};

const pageTitle = computed(() => {
  if (route.path === '/chat') return 'دردشة شعرية';
  if (route.path === '/add') return 'إضافة أبيات';
  if (route.path === '/admin') return 'الإدارة';
  return 'الرئيسية';
});
</script>

<template>
  <div class="flex items-center justify-center min-h-screen w-screen bg-gray-700 font-sans">
    <!-- navbar -->
    <div class="flex justify-between items-center top-0 w-full bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-lg h-16 sm:h-auto text-white px-4 sm:px-6 py-4 fixed z-50 md:w-2/3 lg:w-1/2 shadow-lg">
      <div class="flex items-center">
        <NuxtLink to="/">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
        </NuxtLink>
      </div>
      <div class="flex gap-4 justify-center items-center">
        <h1 class="text-lg sm:text-2xl font-bold tracking-wide">{{ pageTitle }}</h1>
        <nav class="rounded-md sm:rounded-xl bg-gray-800 border-gray-700">
          <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto">
            <button @click="toggleMenu" type="button" class="cursor-pointer inline-flex items-center justify-center p-2 w-10 h-10 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="navbar-hamburger" :aria-expanded="isMenuOpen">
              <span class="sr-only">Open main menu</span>
              <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
              </svg>
            </button>
            <div :class="{ 'hidden': !isMenuOpen }" class="absolute top-16 right-0 w-full" id="navbar-hamburger">
              <ul class="flex flex-col font-medium mt-4 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 shadow-2xl">
                <li>
                  <NuxtLink @click="isMenuOpen = false" to="/chat" class="block text-right py-3 px-4 text-gray-400 hover:bg-gray-700 hover:text-white border-b border-gray-700" :class="{ 'text-white bg-blue-600': route.path === '/chat' }">دردشة شعرية</NuxtLink>
                </li>
                <li>
                  <NuxtLink @click="isMenuOpen = false" to="/add" class="block text-right py-3 px-4 text-gray-400 hover:bg-gray-700 hover:text-white border-b border-gray-700" :class="{ 'text-white bg-blue-600': route.path === '/add' }">إضافة أبيات</NuxtLink>
                </li>
                <li>
                  <NuxtLink @click="isMenuOpen = false" to="/admin" class="block text-right py-3 px-4 text-gray-400 hover:bg-gray-700 hover:text-white" :class="{ 'text-white bg-blue-600': route.path === '/admin' }">الإدارة</NuxtLink>
                </li>
              </ul>
            </div>
          </div>
        </nav>
      </div>
    </div>

    <!-- Page Content -->
    <slot />
  </div>
</template>

<style>
/* Nuxt uses @apply for Tailwind if you want, but simple CSS classes work too */
</style>
