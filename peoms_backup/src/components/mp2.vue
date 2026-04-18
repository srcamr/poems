<script setup lang="ts">
defineProps<{
    msg: string,
    status: number
}>()
</script>

<template>
    <div class="flex justify-start animate-fade-in">
      <!-- رسالة عادية -->
        <div v-if="status == 1" class="text-right bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 rounded-xl px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 max-w-[85%] sm:max-w-3/4 shadow-md border border-gray-300 hover:shadow-lg transition-all duration-200">
            <p class="text-xs sm:text-sm md:text-base leading-relaxed break-words">{{ msg }}</p>
        </div>
        
        <!-- رسالة خطأ -->
        <div v-if="status == 0" class="text-right bg-gradient-to-r from-red-100 to-red-200 text-red-800 rounded-xl px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 max-w-[85%] sm:max-w-3/4 shadow-md border-2 border-red-300 hover:shadow-lg transition-all duration-200">
            <div class="flex items-start gap-1 sm:gap-2">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p class="text-xs sm:text-sm md:text-base leading-relaxed break-words">{{ msg }}</p>
            </div>
        </div>
        
        <!-- إعلان النظام -->
        <div v-if="status == 2" class="text-right bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800 rounded-xl px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 max-w-[85%] sm:max-w-3/4 shadow-md border-2 border-yellow-300 hover:shadow-lg transition-all duration-200">
            <div class="flex items-start gap-1 sm:gap-2">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p class="text-xs sm:text-sm md:text-base leading-relaxed break-words">{{ msg }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}
</style>