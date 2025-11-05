<script setup lang="ts">
defineProps<{
    msg: string
}>()
</script>

<template>
    <div class="flex justify-end animate-fade-in">
        <div class="text-right bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 max-w-[85%] sm:max-w-3/4 shadow-md hover:shadow-lg transition-all duration-200">
            <p class="text-xs sm:text-sm md:text-base leading-relaxed break-words">{{ msg }}</p>
        </div>
    </div>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}
</style>