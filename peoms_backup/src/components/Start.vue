<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import Mp1 from './mp1.vue'
import Mp2 from './mp2.vue'
import { API_URL } from '../config';


const userInput = ref('')
const messages = ref([])
const oldPoems = ref([])
const points = ref(0)
const messagesContainer = ref(null)
const reportClicked = ref(false)

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

const getoldPoems = async () => {
  try {
    const response = await axios.get(`${API_URL}/connect.php?action=getAllPoems&s=1`)
    oldPoems.value = response.data.poems
  } catch (error) {
    console.error('Error fetching poems:', error)
  }
}

const systemResponse = (errorType, char) => {
  if (errorType === 'isUsedBefore') {
    messages.value.push(['systemError', 'لقد تم استخدام هذا البيت من قبل، يرجى إدخال بيت جديد ' + char])
    scrollToBottom()
    return
  }
  let lastElement = messages.value[messages.value.length - 1]
  if (errorType === 'report') {
    lastElement = messages.value[messages.value.length - 2]
  } 
  let lastElementContent = lastElement[1]
  if (lastElementContent) {
    let lastChar = lastElementContent[lastElementContent.length - 1]
    
    if (['ى','ا', 'و', 'ي'].includes(lastChar)) {
      lastChar = lastElementContent[lastElementContent.length - 2]
    }
    if (lastChar === 'ة') {
      lastChar = 'ه'
    } else if (['أ', 'ء', 'ئ', 'ؤ', 'إ'].includes(lastChar)) {
      lastChar = 'أ'
    }

    let matchCount = 0
    let matchingIndices = []

    if (lastChar === 'أ') {
      for (let i = 0; i < oldPoems.value.length; i++) {
        if (oldPoems.value[i].startsWith('أ') || oldPoems.value[i].startsWith('إ')) {
          matchingIndices.push(i)
          matchCount++
        }
      }
    } else {
      for (let i = 0; i < oldPoems.value.length; i++) {
        if (oldPoems.value[i].startsWith(lastChar)) {
          matchingIndices.push(i)
          matchCount++
        }
      }
    }

    if (matchCount > 0) {
      const randomIdx = Math.floor(Math.random() * matchCount)
      const matchedPoem = oldPoems.value[matchingIndices[randomIdx]]
      
      const isUsedBefore = messages.value.some(msg => msg[1] === matchedPoem)
      if (isUsedBefore) {
        oldPoems.value = oldPoems.value.filter(p => p !== matchedPoem)
        systemResponse()
      } else {
        messages.value.push(['system', matchedPoem])
        scrollToBottom()
      }
    } else {
      messages.value.push(['systemAnouncement',  'عذرا، لا يمكنني المتابعة لعدم توفر أبيات إضافية بهذا الحرف في قاعدة البيانات ' + lastChar])
      scrollToBottom()
    }
  }
}

const addMessage = () => {
  if (userInput.value.trim()) {
    // ✅ تحويل العلامات إلى *
    let normalizedInput = userInput.value.trim()
      .replace(/\s*\/\s*/g, ' * ')   // تحويل / إلى *
      .replace(/\s*-\s*/g, ' * ')    // تحويل - إلى *
      .replace(/\s*_\s*/g, ' * ')    // تحويل _ إلى *
      .replace(/\s+\*\s+/g, ' * ');  // توحيد المسافات حول *
    
    if (messages.value.length !== 0) {
      if (oldPoems.value.length === 0) {
        messages.value.push(['systemAnouncement',  'عذرا، لا يمكنني المتابعة لعدم توفر أبيات إضافية في قاعدة البيانات. شكراً لمشاركتك!'])
        scrollToBottom()
        return
      }
      let lastElement = messages.value[messages.value.length - 1]
      let lastElementContent = lastElement[1]
      
      if (lastElementContent) {
        let lastChar = lastElementContent[lastElementContent.length - 1]
       
        if (['ى','ا', 'و', 'ي'].includes(lastChar)) {
          lastChar = lastElementContent[lastElementContent.length - 2]
        }
        if (lastChar === 'ة') {
          lastChar = 'ه'
        } else if (['أ', 'ء', 'ئ', 'ؤ', 'إ'].includes(lastChar)) {
          lastChar = 'أ'
        }

        let userFirstChar = normalizedInput[0]
        if (['أ', 'ء', 'ئ', 'ؤ', 'إ'].includes(userFirstChar)) {
          userFirstChar = 'أ'
        }
        
        if (userFirstChar == lastChar) {
          const isUsedBefore = messages.value.some(msg => msg[1] === normalizedInput)
          if (isUsedBefore) {
            systemResponse('isUsedBefore', lastChar)
          } else {
            messages.value.push(['user', normalizedInput])
            userInput.value = ''
            scrollToBottom()
            
            points.value += 1
            systemResponse()
          }
        } else {
          messages.value.push(['systemError', `البيت الذي أدخلته لا يلتزم بالقافية المطلوبة. يجب أن يبدأ بحرف "${lastChar}" حاول مرة أخرى ${lastChar}`])
          scrollToBottom()
        }
      }
    } else {   
      messages.value.push(['user', normalizedInput])
      userInput.value = ''
      scrollToBottom()
  
      points.value += 1
      systemResponse()      
    }
  }
}

const handleKeyPress = (event) => {
  if (event.key === 'Enter') {
    addMessage()
  }
}

const reportError = (type) => {
  if (type == 'expand') {
    reportClicked.value = !reportClicked.value
  } else {
    let i = 1;
    let errorElement = messages.value[messages.value.length - i]
    
    // ✅ إصلاح: استخدام && بدلاً من || + إضافة فحص وجود العنصر
    while (i < messages.value.length && errorElement && errorElement[0] != 'system') {
      i++;
      errorElement = messages.value[messages.value.length - i]
    }
    
    // ✅ التحقق من وجود العنصر قبل الوصول له
    if (errorElement && errorElement[1]) {
      let errorElementContent = errorElement[1]
      
      if (type == 'syntax') {
        const formData = new FormData();
        formData.append('action', 'reportError');
        formData.append('type', 'syntax');
        formData.append('content', errorElementContent);

        axios.post(`${API_URL}/connect.php`, formData)
        
      } else if (type == 'duplicate') {
        const formData = new FormData();
        formData.append('action', 'reportError');
        formData.append('type', 'duplicate');
        formData.append('content', errorElementContent);

        axios.post(`${API_URL}/connect.php`, formData)
      }
      reportClicked.value = false
      alert('تم إرسال تقرير الخطأ. شكراً لمساعدتك في تحسين النظام.')
      systemResponse('report')
    } else {
      // ✅ في حالة عدم وجود رسالة نظام
      reportClicked.value = false
      alert('لم يتم العثور على رسالة نظام للإبلاغ عنها')
    }
  }
}

onMounted(() => {
  getoldPoems()
})
</script>

<template>
  
  <!-- Main Content -->
  <div class="absolute h-screen w-full bg-gradient-to-b from-gray-50 to-white shadow-2xl rounded-2xl p-1 sm:p-6 flex flex-col gap-4 overflow-y-auto md:w-2/3 lg:w-5/10 py-24">
    <!-- Fixed Header with gradient -->
    <p class="fixed top-2.5 left-8 sm:absolute sm:top-4 sm:left-13 bg-blue-900 text-white p-2 rounded-xl z-20 font-semibold">النقاط: <span class="text-yellow-300">{{ points }}</span></p>

    <!-- Messages Container -->
    <div ref="messagesContainer" class="flex-1 space-y-4 pb-20 flex flex-col gap-4 overflow-y-auto">
      <template v-for="(msg, index) in messages" :key="index">
        <Mp1 :msg="msg[1]" v-if="msg[0] == 'user'" />
        <Mp2 :msg="msg[1]" :status="1" v-if="msg[0] == 'system'" />
        <Mp2 :msg="msg[1]" :status="0" v-if="msg[0] == 'systemError'" />
        <Mp2 :msg="msg[1]" :status="2" v-if="msg[0] == 'systemAnouncement'" />
      </template>
    </div>
    
    <!-- Input Section -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t-2 border-gray-200 p-2 sm:p-4 z-40 shadow-lg md:absolute md:rounded-b-2xl md:w-full safe-area-bottom">
      <!-- زر الإبلاغ الثابت -->
       <div class="absolute text-xs sm:text-base bottom-15 sm:bottom-25 right-4 z-10 flex flex-row gap-2">
         <button 
           @click="reportError('expand')"
           v-if="!reportClicked"
           class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl z-10 font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
           </svg>
           إبلاغ عن خطأ
         </button>

         <button 
           @click="reportError('syntax')"
           v-if="reportClicked"
           class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl z-10 font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
           </svg>
           النص خطأ
         </button>
         <button 
           @click="reportError('duplicate')"
           v-if="reportClicked"
           class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl z-10 font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
           </svg>
           البيت تكرر
         </button>
         <button 
           @click="reportError('expand')"
           v-if="reportClicked"
           class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl z-10 font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
           تراجع
         </button>
       </div>

      <div class="relative">
        <div class="flex gap-3 items-stretch">
          <!-- زر الإرسال - نص على الشاشات الكبيرة، أيقونة على الموبايل -->
          <button 
            @click="addMessage"
            class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-2 sm:px-4 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
          </button>

          <!-- Input -->
          <input 
            type="text" 
            id="user-input" 
            v-model="userInput"
            @keypress="handleKeyPress"
            placeholder="اكتب بيتاً شعرياً... هنا (استخدم * أو / أو - بين الشطرين)"
            class="flex-1 bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-xs sm:text-base sm:text-lg rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 sm:p-4 transition-all duration-200 hover:border-gray-400"
          >
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>