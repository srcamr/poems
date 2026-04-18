<script setup>
// Nuxt auto-imports ref, onMounted, nextTick, useRuntimeConfig
import Mp1 from '~/components/Mp1.vue'
import Mp2 from '~/components/Mp2.vue'
import axios from 'axios'

const config = useRuntimeConfig();
const API_URL = config.public.apiBaseUrl;

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
    const response = await axios.get(`${API_URL}/api/connect.php?action=getAllPoems&s=1`)
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
    let normalizedInput = userInput.value.trim()
      .replace(/\s*\/\s*/g, ' * ')
      .replace(/\s*-\s*/g, ' * ')
      .replace(/\s*_\s*/g, ' * ')
      .replace(/\s+\*\s+/g, ' * ');
    
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
    while (i < messages.value.length && errorElement && errorElement[0] != 'system') {
      i++;
      errorElement = messages.value[messages.value.length - i]
    }
    
    if (errorElement && errorElement[1]) {
      let errorElementContent = errorElement[1]
      
      const formData = new FormData();
      formData.append('action', 'reportError');
      formData.append('type', type);
      formData.append('content', errorElementContent);

      axios.post(`${API_URL}/api/connect.php`, formData)
      
      reportClicked.value = false
      alert('تم إرسال تقرير الخطأ. شكراً لمساعدتك في تحسين النظام.')
      systemResponse('report')
    } else {
      reportClicked.value = false
      alert('لم يتم العثور على رسالة نظام للإبلاغ عنها')
    }
  }
}

onMounted(() => {
  getoldPoems()
})

useHead({
  title: 'دردشة شعرية - ابدأ المساجلة'
})
</script>

<template>
  <NuxtLayout>
    <div class="absolute inset-0 bg-gradient-to-b from-gray-50 to-white shadow-2xl rounded-2xl p-4 sm:p-6 flex flex-col gap-4 overflow-y-auto md:w-2/3 lg:w-1/2 mx-auto py-24">
      <p class="fixed top-24 left-8 sm:absolute sm:top-28 sm:left-13 bg-blue-900 text-white p-2 rounded-xl z-20 font-semibold shadow-md">
        النقاط: <span class="text-yellow-300">{{ points }}</span>
      </p>

      <div ref="messagesContainer" class="flex-1 space-y-4 pb-20 flex flex-col gap-4 overflow-y-auto mt-10">
        <template v-for="(msg, index) in messages" :key="index">
          <Mp1 :msg="msg[1]" v-if="msg[0] == 'user'" />
          <Mp2 :msg="msg[1]" :status="1" v-if="msg[0] == 'system'" />
          <Mp2 :msg="msg[1]" :status="0" v-if="msg[0] == 'systemError'" />
          <Mp2 :msg="msg[1]" :status="2" v-if="msg[0] == 'systemAnouncement'" />
        </template>
      </div>
      
      <div class="fixed bottom-0 left-0 right-0 bg-white border-t-2 border-gray-200 p-4 z-40 shadow-lg md:absolute md:rounded-b-2xl md:w-full">
         <div class="absolute text-xs sm:text-base bottom-24 right-4 z-10 flex flex-row gap-2">
           <button @click="reportError('expand')" v-if="!reportClicked" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl z-10 font-semibold shadow-lg transition-all flex items-center gap-2">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
             إبلاغ عن خطأ
           </button>

           <button @click="reportError('syntax')" v-if="reportClicked" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl z-10 font-semibold shadow-lg flex items-center gap-2"> النص خطأ </button>
           <button @click="reportError('duplicate')" v-if="reportClicked" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl z-10 font-semibold shadow-lg flex items-center gap-2"> البيت تكرر </button>
           <button @click="reportError('expand')" v-if="reportClicked" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-xl z-10 font-semibold shadow-lg transition-all"> تراجع </button>
         </div>

        <div class="relative">
          <div class="flex gap-3 items-stretch">
            <button @click="addMessage" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 rounded-xl font-bold shadow-lg transition-all flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
            <input type="text" v-model="userInput" @keypress="handleKeyPress" placeholder="اكتب بيتاً شعرياً هنا..." class="flex-1 bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-base rounded-xl focus:ring-2 focus:ring-blue-500 p-4">
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>
