<script setup>
// Nuxt auto-imports ref, nextTick, useRuntimeConfig
import axios from 'axios'

const config = useRuntimeConfig();
const API_URL = config.public.apiBaseUrl;

const approveAllStatus = ref(false);
const userInput = ref('');
const lines = ref([]);

const handleKeyPress = (event) => {
  if (event.key === 'Enter') {
    addLine();
  }
};

const checkForApproveAllStatus = () => {
  approveAllStatus.value = lines.value.every(line => line[1] === 'ناجح')
}

const addLine = () => {
  if (userInput.value.trim()){
    const isDuplicate = lines.value.some(line => line[0] === userInput.value.trim());
    
    if (!isDuplicate){
      if (userInput.value.trim().includes(' * ')) {
        lines.value.push([userInput.value.trim(), 'الحالة']);
        userInput.value = ''
        approveAllStatus.value = false
      } else {
        alert('الرجاء وضع مسافة بين الشطرين باستخدام " * "')
      }
    } else {
      alert('هذا البيت موجود بالفعل في القائمة.')
    }
  }
};

const checkAll = () => {
  lines.value.forEach((line, index) => {
    line[1] = 'جاري التحقق...';
    checkOne(index)
  })
};

const checkOne = async (index) => {
  const line = lines.value[index][0];
  if (line.includes(' * ')) {
    try {
      const response = await axios.get(`${API_URL}/api/connect.php?action=checkOneLine&line=${encodeURIComponent(line)}`)
      if (response.data.exists) {
        lines.value[index][1] = 'مكرر';
      } else {
        lines.value[index][1] = 'ناجح';
        checkForApproveAllStatus()
      }
    } catch (error) {
      console.error('Error fetching poems:', error)
    }
  } else {
    lines.value[index][1] = '" * " مفقود';
  }
};

const approve = async (index, allOrSingle) => {
  const line = lines.value[index][0]
  try {
    const response = await axios.get(`${API_URL}/api/connect.php?action=approve&line=${encodeURIComponent(line)}`)
    if (response.data.success) {
      if (allOrSingle !== 'all') {
        alert('تمت إضافة البيت بنجاح.')
      }
      deleteItem(index)
    } else {
      alert('حدث خطأ أثناء الموافقة على البيت.' + lines.value[index][1])
    }
  } catch (error) {
    console.error('Error fetching poems:', error)
  }
};

const deleteItem = (index) => {
  lines.value.splice(index, 1);
  if (lines.value.length === 0) {
    approveAllStatus.value = false;
  }
};

const approveAll = () => {
  for (let i = lines.value.length - 1; i >= 0; i--) {
    lines.value[i][1] = 'جاري الإضافة...';
    approve(i, 'all')
  }
  alert('تمت إضافة الأبيات بنجاح')
};

const deleteAll = () => {
  lines.value = [];
  approveAllStatus.value = false;
};

const handleFileUpload = (event) => {
  const target = event.target;
  const file = target.files?.[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      const text = e.target?.result;
      if (typeof text === 'string') {
        const rows = text.split(/\r?\n/);
        rows.forEach(row => {
          const trimmedRow = row.trim();
          const isDuplicate = lines.value.some(line => line[0] === trimmedRow);
          if (!isDuplicate && trimmedRow) {
            lines.value.push([trimmedRow, 'الحالة'])
          }
        });
        approveAllStatus.value = false
      }
    };
    reader.readAsText(file);
  }
};

const watchLineEdit = (index) => {
  lines.value[index][1] = 'الحالة';
  approveAllStatus.value = false;
};

useHead({
  title: 'إضافة أبيات - Poetry App'
})
</script>

<template>
  <NuxtLayout>
    <div class="min-h-screen w-full bg-gradient-to-b from-gray-50 to-white shadow-2xl rounded-2xl p-8 flex flex-col gap-6 overflow-y-auto md:w-2/3 lg:w-1/2 mx-auto mt-24">
        
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 text-right">
          <label for="user-input" class="block mb-3 text-lg font-bold text-gray-800">إضافة بيت شعري</label>
          <div class="bg-blue-50 border-r-4 border-blue-500 p-4 mb-4 rounded-lg text-right">
            <p class="text-sm text-gray-700 mb-1">📝 ضع بين الشطرين: <code class="bg-blue-100 px-2 py-1 rounded">مسافة*مسافة</code></p>
            <p class="text-sm text-gray-600 italic">مثال: هل غادر الشعراء من متردم * أم هل عرفت الدار بعد توهم</p>
          </div>
          <div class="flex flex-col gap-3">
            <input 
              type="text" 
              id="user-input" 
              v-model="userInput"
              @keypress="handleKeyPress"
              placeholder="اكتب بيتاً شعرياً هنا..."
              class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-base rounded-xl focus:ring-2 focus:ring-blue-500 block w-full p-4"
            >
            <button @click="addLine" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-xl text-base font-semibold shadow-md transition-all flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
              إضافة البيت
            </button>
          </div>
        </div>

        <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-500 font-semibold">أو</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-56 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gradient-to-br from-gray-50 to-gray-100 hover:from-blue-50 hover:to-purple-50 transition-all duration-300 group">
              <div class="flex flex-col items-center justify-center pt-5 pb-6">
                  <svg class="w-12 h-12 mb-4 text-gray-400 group-hover:text-blue-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                  <p class="mb-2 text-base text-gray-600 group-hover:text-gray-700"><span class="font-bold">اضغط للرفع</span> أو اسحب وأفلت</p>
                  <p class="text-sm text-gray-500">ملف CSV, txt فقط</p>
                  <p class="text-sm text-gray-500">كل بيت سطر</p>
              </div>
              <input id="dropzone-file" type="file" accept=".csv" @change="handleFileUpload" class="hidden" />
          </label>
        </div>

        <div class="flex gap-3 justify-center">
          <button @click="approveAll" type="button" :disabled="!approveAllStatus" class="flex-1 py-3 px-6 inline-flex items-center justify-center gap-x-2 text-base font-semibold rounded-xl border-2 border-green-500 bg-green-500 text-white hover:bg-green-600 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            تأكيد الجميع
          </button>
          <button @click="checkAll" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 hover:text-black text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md transition-all flex items-center justify-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            الحالة  
          </button>
          <button @click="deleteAll" type="button" class="flex-1 py-3 px-6 inline-flex items-center justify-center gap-x-2 text-base font-semibold rounded-xl border-2 border-red-500 bg-red-500 text-white hover:bg-red-600 transition-all shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            حذف الجميع
          </button>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 flex flex-col gap-4">
          <h3 class="text-lg font-bold text-gray-800 text-right">الأبيات المضافة ({{ lines.length }})</h3>
          <div v-for="(line, index) in lines" :key="index" class="flex flex-col gap-2">
              <input type="text" v-model="lines[index][0]" @input="watchLineEdit(index)" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-base rounded-xl focus:ring-2 focus:ring-blue-500 block w-full p-4">
              <div class="flex gap-2">
                <button @click="approve(index)" :disabled="lines[index][1] !== 'ناجح'" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md transition-all flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  تأكيد
                </button>
                <button @click="checkOne(index)" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md transition-all flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  {{ lines[index][1] }}
                </button>
                <button @click="deleteItem(index)" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md transition-all flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  حذف
                </button>
              </div>
            </div>
        </div>
    </div>
  </NuxtLayout>
</template>
