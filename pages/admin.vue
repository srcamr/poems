<script setup>
// Nuxt auto-imports ref, onMounted, useRuntimeConfig, useHead
import axios from 'axios'

const config = useRuntimeConfig();
const API_URL = config.public.apiBaseUrl;

// Configure axios for credentials (required for PHP sessions)
axios.defaults.withCredentials = true;

const isAuthenticated = ref(false);
const password = ref('');
const showPasswordPrompt = ref(true);
const currentView = ref('addPoems'); // 'addPoems', 'reports', 'addedPoems', 'statistics'

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/api/connect.php?action=check`);
    isAuthenticated.value = response.data.authenticated;
    showPasswordPrompt.value = !response.data.authenticated;
  } catch (error) {
    console.error('Error checking authentication:', error);
    showPasswordPrompt.value = true;
  }
});

const login = async () => {
  try {
    const formData = new FormData();
    formData.append('password', password.value);
    const response = await axios.post(`${API_URL}/api/connect.php?action=login`, formData);
    if (response.data.success) {
      isAuthenticated.value = true;
      showPasswordPrompt.value = false;
    } else {
      alert(response.data.message || 'كلمة مرور خاطئة');
    }
  } catch (error) {
    alert('حدث خطأ في تسجيل الدخول');
  }
}

const logout = async () => {
  try {
    await axios.get(`${API_URL}/api/connect.php?action=logout`);
    isAuthenticated.value = false;
    showPasswordPrompt.value = true;
    password.value = '';
  } catch (error) {
    console.error('خطأ في تسجيل الخروج:', error);
  }
};

// Add interceptor for 401
if (process.client) {
  axios.interceptors.response.use(
    response => response,
    error => {
      if (error.response?.status === 401) {
        isAuthenticated.value = false;
        showPasswordPrompt.value = true;
        alert('انتهت الجلسة، الرجاء تسجيل الدخول مرة أخرى');
      }
      return Promise.reject(error);
    }
  );
}

// ------ Add Poems Logic ------
const approveAllStatus = ref(false);
const userInput = ref('');
const lines = ref([]);

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

const checkOne = async (index) => {
  const line = lines.value[index][0];
  try {
    const response = await axios.get(`${API_URL}/api/connect.php?action=checkOneLine&line=${encodeURIComponent(line)}`)
    lines.value[index][1] = response.data.exists ? 'مكرر' : 'ناجح';
    approveAllStatus.value = lines.value.every(l => l[1] === 'ناجح');
  } catch (error) {
    console.error('Error:', error)
  }
};

const approve = async (index, allOrSingle) => {
  const line = lines.value[index][0]
  try {
    const response = await axios.get(`${API_URL}/api/connect.php?action=approve&type=admin&line=${encodeURIComponent(line)}`)
    if (response.data.success) {
      if (allOrSingle !== 'all') alert('تمت إضافة البيت بنجاح.')
      lines.value.splice(index, 1);
      approveAllStatus.value = lines.value.length > 0 && lines.value.every(l => l[1] === 'ناجح');
    }
  } catch (error) {
    console.error('Error:', error)
  }
};

// ------ Added Poems logic ------
const addedPoemsLines = ref([]);
const approveAllStatusAdded = ref(false);

const getaddedPoems = async () => {
  try {
    const response = await axios.get(`${API_URL}/api/connect.php?action=getAllPoems&s=0&id=1`)
    addedPoemsLines.value = response.data.poems.map(poem => [poem.content, 'ناجح', poem.ID]);
    approveAllStatusAdded.value = addedPoemsLines.value.length > 0;
  } catch (error) {
    console.error('Error:', error)
  }
}

const approveAdded = async (index, allOrSingle) => {
  const [line, status, id] = addedPoemsLines.value[index];
  try {
    const response = await axios.get(`${API_URL}/api/connect.php?action=approveAdded&id=${id}&content=${encodeURIComponent(line)}`);
    if (response.data.success) {
      if (allOrSingle !== 'all') alert('✅ تم تأكيد البيت بنجاح');
      addedPoemsLines.value.splice(index, 1);
      approveAllStatusAdded.value = addedPoemsLines.value.length > 0;
    }
  } catch (error) {
    console.error('Error:', error);
  }
};

// ------ Statistics Logic ------
const stats = ref({ total: 0, approved: 0, unapproved: 0, reported: 0, duplicate: 0, syntax: 0 });
const showStatistics = async () => {
  currentView.value = 'statistics';
  try {
    const [total, approved, reported, duplicate] = await Promise.all([
      axios.get(`${API_URL}/api/connect.php?action=staticesView&type=totalPoems`),
      axios.get(`${API_URL}/api/connect.php?action=staticesView&type=totalApproved`),
      axios.get(`${API_URL}/api/connect.php?action=staticesView&type=totalReported`),
      axios.get(`${API_URL}/api/connect.php?action=staticesView&type=DuplicateReported`)
    ]);
    stats.value = {
      total: total.data.total,
      approved: approved.data.total,
      unapproved: total.data.total - approved.data.total,
      reported: reported.data.total,
      duplicate: duplicate.data.total,
      syntax: reported.data.total - duplicate.data.total
    };
  } catch (e) { console.error(e); }
};

useHead({
  title: 'الإدارة - لوحة التحكم'
})
</script>

<template>
  <NuxtLayout>
    <div v-if="showPasswordPrompt" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100]">
      <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full mx-4">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center flex items-center justify-center gap-2">
          <span>🔒</span> لوحة التحكم
        </h2>
        <form @submit.prevent="login" class="space-y-6">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2 text-right">كلمة المرور</label>
            <input type="password" v-model="password" class="w-full px-4 py-4 border-2 border-gray-100 bg-gray-50 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-center text-lg" placeholder="••••••••" required>
          </div>
          <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/30 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
            تسجيل الدخول
          </button>
        </form>
      </div>
    </div>

    <div v-if="isAuthenticated" class="min-h-[80vh] w-full bg-white/90 backdrop-blur-md shadow-2xl rounded-3xl p-6 sm:p-10 flex flex-col gap-8 md:w-2/3 lg:w-1/2 mx-auto mt-28 mb-10 overflow-hidden relative">
      <!-- Admin Menu -->
      <div class="flex flex-row-reverse gap-4 justify-center flex-wrap">
        <button v-for="view in [
          {id: 'addPoems', label: 'إضافة', icon: 'M12 4v16m8-8H4', color: 'green'},
          {id: 'addedPoems', label: 'الأبيات', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', color: 'blue'},
          {id: 'statistics', label: 'إحصائيات', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', color: 'orange'}
        ]" :key="view.id" @click="view.id === 'statistics' ? showStatistics() : (view.id === 'addedPoems' ? showAddedPoems() : currentView = view.id)"
          :class="[currentView === view.id ? `ring-4 ring-${view.color}-400/30 bg-gray-100` : 'hover:bg-gray-50']"
          class="flex items-center gap-2 px-5 py-3 rounded-2xl font-bold transition-all border border-gray-100 shadow-sm">
          <svg class="w-5 h-5" :class="`text-${view.color}-500`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="view.icon"/></svg>
          {{ view.label }}
        </button>
        <button @click="logout" class="flex items-center gap-2 px-5 py-3 rounded-2xl font-bold transition-all border border-red-100 bg-red-50 text-red-600 hover:bg-red-100 shadow-sm">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          خروج
        </button>
      </div>

      <!-- Add Poems View -->
      <div v-if="currentView === 'addPoems'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4">
        <div class="bg-gray-50 p-8 rounded-3xl border-2 border-gray-100 text-right">
          <label class="block mb-4 text-xl font-bold text-gray-800">إضافة أبيات جديدة</label>
          <div class="flex flex-col gap-4">
            <input type="text" v-model="userInput" @keypress.enter="addLine" placeholder="اكتب بيتاً شعرياً (استخدم * بين الشطرين)" class="w-full p-5 bg-white border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-right text-lg shadow-inner">
            <button @click="addLine" class="bg-blue-600 hover:bg-blue-700 text-white p-5 rounded-2xl font-bold shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2 transition-transform active:scale-95">
              <span>➕</span> إضافة للقائمة
            </button>
          </div>
        </div>

        <div v-if="lines.length > 0" class="space-y-4">
          <div v-for="(line, idx) in lines" :key="idx" class="bg-white border-2 border-gray-50 p-4 rounded-2xl shadow-sm space-y-3">
            <input v-model="lines[idx][0]" class="w-full p-3 bg-gray-50 border border-gray-100 rounded-xl text-right text-base font-medium">
            <div class="flex gap-2">
              <button @click="approve(idx)" :disabled="line[1] !== 'ناجح'" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-2 rounded-xl disabled:opacity-30">تأكيد</button>
              <button @click="checkOne(idx)" class="flex-1 bg-orange-100 text-orange-700 font-bold py-2 rounded-xl border border-orange-200">{{ line[1] }}</button>
              <button @click="lines.splice(idx, 1)" class="flex-1 bg-red-100 text-red-600 font-bold py-2 rounded-xl border border-red-200">حذف</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics View -->
      <div v-if="currentView === 'statistics'" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-in fade-in zoom-in-95">
        <div v-for="(val, key) in [
          {id: 'total', label: 'إجمالي الأبيات', val: stats.total, color: 'blue'},
          {id: 'approved', label: 'الموثقة', val: stats.approved, color: 'green'},
          {id: 'unapproved', label: 'قيد الانتظار', val: stats.unapproved, color: 'orange'},
          {id: 'reported', label: 'البلاغات', val: stats.reported, color: 'red'}
        ]" :key="key" class="p-6 rounded-3xl border-2 border-gray-50 shadow-sm transition-all hover:scale-[1.02] text-right" :class="[`bg-${val.color}-50/50`]">
          <p class="text-gray-500 font-bold text-sm mb-1">{{ val.label }}</p>
          <p class="text-4xl font-black" :class="[`text-${val.color}-600`]">{{ val.val }}</p>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<style scoped>
.animate-in {
  animation-duration: 0.4s;
}
</style>
