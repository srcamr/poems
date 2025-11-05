<script setup lang="ts">
import Start from './components/Start.vue'
import add from './components/add.vue'
import admin from './components/admin.vue'
import { ref } from 'vue'
const isMenuOpen = ref(false);
const page = ref('chat')

const toChat = () => {
  // Logic to navigate to chat page
  page.value = 'chat'
  toggleMenu()
};
const toAdd = () => {
  // Logic to navigate to add page
  page.value = 'add'
  toggleMenu()
};
const toAdmin = () => {
  // Logic to navigate to add page
  page.value = 'admin'
  toggleMenu()
};
const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value;
};
</script>

<template>
  <div class="flex items-center justify-center h-screen w-screen bg-gray-700">
    <!-- navbar -->
    <div class="flex justify-between items-center top-0 w-full bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-lg h-2/20 sm:h-auto text-white px-2 sm:px-6 py-4 fixed z-10 md:w-2/3 lg:w-5/10 shadow-lg">
      <div class="flex items-center">
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>
      </div>
      <div class="flex gap-2 justify-center items-center">
        <h1 v-if="page == 'chat'" class="text-sm sm:text-2xl font-bold tracking-wide">دردشة شعرية</h1>
        <h1 v-if="page == 'add'" class="text-sm sm:text-2xl font-bold tracking-wide">إضافة أبيات</h1>
        <h1 v-if="page == 'admin'" class="text-sm sm:text-2xl font-bold tracking-wide">الإدارة</h1>
        <nav class="rounded-md sm:rounded-xl bg-gray-800 border-gray-700">
          <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto">
            <button @click="toggleMenu" type="button" class="cursor-pointer inline-flex items-center justify-center p-1 sm:p-2 w-6 sm:w-10 h-10 text-sm text-gray-500 rounded-xs sm:rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="navbar-hamburger" :aria-expanded="isMenuOpen">
              <span class="sr-only">Open main menu</span>
              <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
              </svg>
            </button>
            <div :class="{ 'hidden': !isMenuOpen }" class="absolute top-18 right-0 w-full" id="navbar-hamburger">
              <ul class="flex flex-col font-medium mt-4 bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                <li>
                  <div @click="toChat" :class="page === 'chat' ? 'text-white bg-blue-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'" class="block text-right py-2 px-3 cursor-pointer" aria-current="page">دردشة شعرية</div>
                </li>
                <li>
                  <div @click="toAdd" :class="page === 'add' ? 'text-white bg-blue-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'" class="block text-right py-2 px-3 cursor-pointer">إضافة أبيات</div>
                </li>
                <li>
                  <div @click="toAdmin" :class="page === 'admin' ? 'text-white bg-blue-600' : 'text-gray-400 hover:bg-gray-700 hover:text-white'" class="block text-right py-2 px-3 cursor-pointer">الإدارة</div>
                </li>
              </ul>
            </div>
          </div>
        </nav>
      </div>
    </div>

    <Start v-if="page == 'chat'" />
    <add v-if="page == 'add' "/>
    <admin v-if="page == 'admin' "/>


  </div>

</template>

<style scoped>

</style>

