<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios'
import { API_URL } from '../config';

const isAuthenticated = ref(false);
const password = ref('');
const showPasswordPrompt = ref(true);
const currentView = ref('addPoems'); // 'addPoems', 'reports', 'addedPoems', 'statistics'


onMounted(async () => {
  try {
    console.log(`${API_URL}/connect.php?action=check`);
    const response = await axios.get(`${API_URL}/connect.php?action=check`, {
      withCredentials: true
    });
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
    const response = await axios.post(`${API_URL}/connect.php?action=login`, formData, {
      withCredentials: true
    });
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

// إضافة التحقق لجميع الطلبات
axios.defaults.withCredentials = true;

// معالجة الأخطاء 401
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


// وظائف المنيو
const showAddPoemsForm = () => {
  currentView.value = 'addPoems';
};


const logout = async () => {
  try {
    await axios.get(`${API_URL}/connect.php?action=logout`);
    isAuthenticated.value = false;
    showPasswordPrompt.value = true;
    password.value = '';
  } catch (error) {
    console.error('خطأ في تسجيل الخروج:', error);
  }
};


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
    // البحث في المصفوفة ثنائية الأبعاد
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
  // Add your logic here
  lines.value.forEach((line, index) => {
    line[1] = 'جاري التحقق...';
    checkOne(index)
  })
};

const checkOne = async (index) => {
  const line = lines.value[index][0];
  if (line.includes(' * ')) {
    try {
      const response = await axios.get(`${API_URL}/connect.php?action=checkOneLine&line=` + encodeURIComponent(line))
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
    const response = await axios.get(`${API_URL}/connect.php?action=approve&type=admin&line=` + encodeURIComponent(line))
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



//  ======= addedPoems view functions ======
const addedPoemsLines = ref([]);
const approveAllStatusAdded = ref(false);
const getaddedPoems = async () => {
  try {
    const response = await axios.get(`${API_URL}/connect.php?action=getAllPoems&s=0&id=1`)
    addedPoemsLines.value = response.data.poems.map(poem => {
      return [poem.content, 'ناجح', poem.ID]; 
    });
    if (addedPoemsLines.value.length > 0) {
      approveAllStatusAdded.value = true;
    } else {
      approveAllStatusAdded.value = false;
    }
  } catch (error) {
    console.error('Error fetching poems:', error)
  }
}

const checkForApproveAllStatusAdded = () => {
  approveAllStatusAdded.value = addedPoemsLines.value.every(line => line[1] === 'ناجح')
}


const checkAllAdded = () => {
  // Add your logic here
  addedPoemsLines.value.forEach((line, index) => {
    line[1] = 'جاري التحقق...';
    checkOneAdded(index)
  })
};

const checkOneAdded = async (index) => {
  const line = addedPoemsLines.value[index][0];
  if (line.includes(' * ')) {
    try {
      const response = await axios.get(`${API_URL}/connect.php?action=checkOneLine&line=` + encodeURIComponent(line))
      if (response.data.exists) {
        addedPoemsLines.value[index][1] = 'مكرر';
      } else {
        addedPoemsLines.value[index][1] = 'ناجح';
        checkForApproveAllStatusAdded()
      }
    } catch (error) {
      console.error('Error fetching poems:', error)
    }
  } else {
    addedPoemsLines.value[index][1] = '" * " مفقود';
  }
};

const approveAdded = async (index, allOrSingle) => {
  const line = addedPoemsLines.value[index][0];
  const lineID = addedPoemsLines.value[index][2];
  
  try {
    const response = await axios.get(
      `${API_URL}/connect.php?action=approveAdded&id=${lineID}&content=${encodeURIComponent(line)}`
    );
    
    if (response.data.success) {
      if (allOrSingle !== 'all') {
        alert(`✅ تم تأكيد البيت بنجاح:\n${line}`);
      }
      addedPoemsLines.value.splice(index, 1);
      
      if (addedPoemsLines.value.length === 0) {
        approveAllStatusAdded.value = false;
      }
    } else {
      alert(`❌ حدث خطأ:\n${line}`);
    }
  } catch (error) {
    console.error('❌ Error:', error);
    alert('❌ فشل الاتصال');
  }
};

const deleteItemAdded = async (index, allOrSingle) => {
  let confirmDelete = true;
  if (allOrSingle !== 'all') { 
    confirmDelete = confirm('هل أنت متأكد من حذف هذا البيت؟');
  }

  if (!confirmDelete) {
    return;
  } else {
    const lineID = addedPoemsLines.value[index][2]
    try {
      const response = await axios.get(`${API_URL}/connect.php?action=deleteAdded&id=` + lineID)
      if (response.data.success) {
        if (allOrSingle !== 'all') {
          alert(' تمت حذف البيت بنجاح. ' + addedPoemsLines.value[index][0])
        }
        // remove from array
        addedPoemsLines.value.splice(index, 1);
        if (addedPoemsLines.value.length === 0) {
          approveAllStatusAdded.value = false;
        }
      } else {
        alert('حدث خطأ أثناء الموافقة على البيت.' + addedPoemsLines.value[index][0])
      }
    } catch (error) {
      console.error('Error fetching poems:', error)
    }
  }
};

const approveAllAdded = () => {
  for (let i = addedPoemsLines.value.length - 1; i >= 0; i--) {
    addedPoemsLines.value[i][1] = 'جاري الإضافة...';
    approveAdded(i, 'all')
  }
  alert('تمت إضافة الأبيات بنجاح')
};

const deleteAllAdded = () => {
  let confirmDelete = true;
    confirmDelete = confirm('هل أنت متأكد من حذف جميع الأبيات؟');
  if (!confirmDelete) {
    return;
  } else {
    for (let i = addedPoemsLines.value.length - 1; i >= 0; i--) {
      addedPoemsLines.value[i][1] = 'جاري الحذف...';
      deleteItemAdded(i, 'all')
    }
    alert('تم حذف جميع الأبيات بنجاح')
  }
  
};

const showAddedPoems = () => {
  currentView.value = 'addedPoems';
  getaddedPoems();
};

const watchLineEdit = (index) => {
  addedPoemsLines.value[index][1] = 'الحالة';
  approveAllStatusAdded.value = false;
};






//  ======= Report view functions ======
const currentReportView = ref('Syntax');
const reportedLines = ref([]);
const getReportedLines = async () => {
  try {
    const response = await axios.get(`${API_URL}/connect.php?action=getReportedLines&type=` + currentReportView.value)
    if (response.data.success) {
      reportedLines.value = response.data.reportedLines.map(item => {
        return [item.content, item.error_id, item.poem_id, 'الحالة']; 
      });
    } else {
      reportedLines.value = [];
      alert('لا توجد أبيات معلنة في هذا القسم.')
    }
  } catch (error) {
    console.error('Error fetching reported lines:', error)
  }
};

const IgnoreOne = (index, allOrSingle) => {
  // ignore report one
  let confirmDelete = true;
  if (allOrSingle !== 'all') { 
    confirmDelete = confirm('هل أنت متأكد من تجاهل هذا البيت؟' + reportedLines.value[index][0]);
  }

  if (!confirmDelete) {
    return;
  } else {
    const lineID = reportedLines.value[index][1];
    axios.get(`${API_URL}/connect.php?action=ReportActions&subAction=Ignore&id=` + lineID)
      .then(response => {
        if (response.data.success) {
          if (allOrSingle !== 'all') {
            alert('تم تجاهل البلاغ بنجاح.')
          }
          reportedLines.value.splice(index, 1);
        } else {
          if (allOrSingle !== 'all') {
            alert('حدث خطأ أثناء تجاهل البلاغ.')
          }
        }
      })
      .catch(error => {
        console.error('Error ignoring report:', error)
      });
    }
};

const deleteReportOne = (index, allOrSingle) => {
  // delete report one
  let confirmDelete = true;
  if (allOrSingle !== 'all') { 
    confirmDelete = confirm('هل أنت متأكد من حذف هذا البيت؟');
  }

  if (!confirmDelete) {
    return;
  } else {
    let confirmDelete = true;
    if (allOrSingle !== 'all') { 
      confirmDelete = confirm('هل أنت متأكد من حذف هذا البيت؟' + reportedLines.value[index][0]);
    }

    if (!confirmDelete) {
      return;
    } else {
      const lineID = reportedLines.value[index][1];
      axios.get(`${API_URL}/connect.php?action=ReportActions&subAction=delete&id=` + lineID)
        .then(response => {
          if (response.data.success) {
            if (allOrSingle !== 'all') {
              alert('تم حذف البيت بنجاح.')
            }
            reportedLines.value.splice(index, 1);
          } else {
            if (allOrSingle !== 'all') {
              alert('حدث خطأ أثناء حذف البيت.')
            }
          }
        })
        .catch(error => {
          console.error('Error ignoring report:', error)
        });
      }
    }
}; 

const checkReportOne = (index, allOrSingle) => {
  // check report one
  const lineID = reportedLines.value[index][1];
  axios.get(`${API_URL}/connect.php?action=ReportActions&subAction=check&id=` + lineID + '&content=' + encodeURIComponent(reportedLines.value[index][0]))
    .then(response => {
      if (response.data.exists) {
        if (allOrSingle !== 'all') {
          alert('تم التحقق من البلاغ بنجاح.')
        }
        reportedLines.value[index][3] = 'مكرر';
      } else {
        if (allOrSingle !== 'all') {
          alert('حدث خطأ أثناء التحقق من البلاغ.')
        }
      }
    })
    .catch(error => {
      console.error('Error ignoring report:', error)
    });
    
}; 

const RelodOne = (index, allOrSingle) => {
  // ignore report one
  let confirmDelete = true;
  if (allOrSingle !== 'all') { 
    confirmDelete = confirm('هل أنت متأكد من تحديث هذا البيت؟ ' + reportedLines.value[index][0]);
  }

  if (!confirmDelete) {
    return;
  } else {
    const lineID = reportedLines.value[index][1];
    axios.get(`${API_URL}/connect.php?action=ReportActions&subAction=reload&id=` + lineID + '&content=' + encodeURIComponent(reportedLines.value[index][0]))
      .then(response => {
        if (response.data.success) {
          if (allOrSingle !== 'all') {
            alert('تم تحديث النص بنجاح.')
          }
          reportedLines.value.splice(index, 1);
        } else {
          if (allOrSingle !== 'all') {
            alert('حدث خطأ أثناء تحديث النص.')
          }
        }
      })
      .catch(error => {
        console.error('Error ignoring report:', error)
      });
    }
}; 

const relodAll = () => {
  let confirmDelete = true;
    confirmDelete = confirm('هل أنت متأكد من تحديث جميع الأبيات؟');
  if (!confirmDelete) {
    return;
  } else {
    for (let i = reportedLines.value.length - 1; i >= 0; i--) {
      reportedLines.value[i][3] = 'جاري التحديث...';
      RelodOne(i, 'all')
    }
    alert('تم تجاهل جميع الأبيات بنجاح')
  }
}          
const checkReportAll = () => {
  for (let i = reportedLines.value.length - 1; i >= 0; i--) {
    reportedLines.value[index][3] = 'جاري التحقق...';
    checkReportOne(i, 'all')
  }
}     

const IgnoreAll = () => {
  let confirmDelete = true;
    confirmDelete = confirm('هل أنت متأكد من تجاهل جميع الأبيات؟');
  if (!confirmDelete) {
    return;
  } else {
    for (let i = reportedLines.value.length - 1; i >= 0; i--) {
      reportedLines.value[i][3] = 'جاري التجاهل...';
      IgnoreOne(i, 'all')
    }
    alert('تم تجاهل جميع الأبيات بنجاح')
  }
} 

const showSyntaxReports = () => {
  currentReportView.value = 'Syntax';
  getReportedLines();
};
const showDublicatedbReports = () => {
  currentReportView.value = 'Dublicated';
  getReportedLines();
};
const showReports = () => {
  currentView.value = 'reports';
  getReportedLines();
};













//  ======= statistics view functions ======
const allLinesCount = ref(0);
const ApprovalLinesCount = ref(0);
const UnApprovalLinesCount = ref(0);
const ReportedLinesCount = ref(0);
const DuplicatedLinesCount = ref(0);
const SyntaxErrorsCount = ref(0);
const showStatistics = async () => {
  currentView.value = 'statistics';
  // fetch statistics data
  // total poems
  try {
    const response = await axios.get(`${API_URL}/connect.php?action=staticesView&type=totalPoems`)
    if (response.data.success) {
      allLinesCount.value = response.data.total;
    } else {
      allLinesCount.value = -1;
    }
  } catch (error) {
    console.error('Error fetching poems:', error)
  }
  
  // approved poems
  try {
    const response = await axios.get(`${API_URL}/connect.php?action=staticesView&type=totalApproved`)
    if (response.data.success) {
      ApprovalLinesCount.value = response.data.total;
    } else {
      ApprovalLinesCount.value = -1;;
    }
  } catch (error) {
    console.error('Error fetching poems:', error)
  }

  // unapproved poems
  UnApprovalLinesCount.value = allLinesCount.value - ApprovalLinesCount.value;
  
  
  // reported poems
  try {
    const response = await axios.get(`${API_URL}/connect.php?action=staticesView&type=totalReported`)
    if (response.data.success) {
      ReportedLinesCount.value = response.data.total;
    } else {
      ReportedLinesCount.value = -1;;
    }
  } catch (error) {
    console.error('Error fetching poems:', error)
  }

  // duplicated poems
  try {
    const response = await axios.get(`${API_URL}/connect.php?action=staticesView&type=DuplicateReported`)
    if (response.data.success) {
      DuplicatedLinesCount.value = response.data.total;
    } else {
      DuplicatedLinesCount.value = -1;
    }
  } catch (error) {
    console.error('Error fetching poems:', error)
  }
  
  // syntax errors poems
  SyntaxErrorsCount.value = ReportedLinesCount.value - DuplicatedLinesCount.value;
};

</script>



<template>
  <!-- Password Prompt Modal -->
  <div v-if="showPasswordPrompt" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-5">
    <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full">
      <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">🔒 لوحة التحكم</h2>
      <form @submit.prevent="login" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">كلمة المرور</label>
          <input 
            type="password" 
            v-model="password"
            class="text-black w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="أدخل كلمة المرور"
            required
          >
        </div>
        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 rounded-lg transition-all">
          دخول
        </button>
      </form>
    </div>
  </div>


  <!-- Main Content with improved spacing -->
  <div v-if="isAuthenticated" class="h-screen w-full bg-gradient-to-b from-gray-50 to-white shadow-2xl rounded-2xl p-8 flex flex-col gap-6 overflow-y-auto md:w-2/3 lg:w-5/10 pt-24">
      

    <!-- admin menu  -->
    <div class="flex flex-row-reverse gap-3 justify-center flex-wrap">

      <button 
        @click="showReports"
        :class="currentView === 'reports' ? 'ring-4 ring-purple-300' : ''"
        class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        البلاغات
      </button>

      <button 
        @click="showAddedPoems"
        :class="currentView === 'addedPoems' ? 'ring-4 ring-blue-300' : ''"
        class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        الأبيات
      </button>

      <button 
        @click="showAddPoemsForm"
        :class="currentView === 'addPoems' ? 'ring-4 ring-green-300' : ''"
        class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        إضافة
      </button>

      <button 
        @click="showStatistics"
        :class="currentView === 'statistics' ? 'ring-4 ring-orange-300' : ''"
        class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        إحصائيات
      </button>

      <button 
        @click="logout"
        class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        خروج
      </button>

    </div>


    <!-- add View -->
      <!-- Single line input section with improved styling -->
      <template v-if="currentView === 'addPoems'">
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
              class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-base rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-4 transition-all duration-200 hover:border-gray-400"
            >
            <button @click="addLine" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 
                text-white px-6 py-3 rounded-xl text-base font-semibold shadow-md hover:shadow-lg transition-all duration-200 
                flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
              </svg>
              إضافة البيت
            </button>
          </div>
        </div>

        <!-- Divider -->
        <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-500 font-semibold">أو</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>

        <!-- File upload with improved design -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-56 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gradient-to-br from-gray-50 to-gray-100 hover:from-blue-50 hover:to-purple-50 transition-all duration-300 group">
              <div class="flex flex-col items-center justify-center pt-5 pb-6">
                  <svg class="w-12 h-12 mb-4 text-gray-400 group-hover:text-blue-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                  </svg>
                  <p class="mb-2 text-base text-gray-600 group-hover:text-gray-700"><span class="font-bold">اضغط للرفع</span> أو اسحب وأفلت</p>
                  <p class="text-sm text-gray-500">ملف CSV, txt فقط</p>
                  <p class="text-sm text-gray-500">كل بيت سطر</p>
              </div>
              <input id="dropzone-file" type="file" accept=".csv" @change="handleFileUpload" class="hidden" />
          </label>
        </div>

        <!-- Action buttons with better styling -->
        <div class="flex gap-3 justify-center">
          <button @click="approveAll" type="button" 
            :disabled="approveAllStatus === false"
            class="flex-1 py-3 px-6 inline-flex items-center justify-center gap-x-2 
              text-base font-semibold rounded-xl border-2 border-green-500 bg-green-500 text-white hover:bg-green-600 hover:border-green-600 
              focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg 
              disabled:opacity-50 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            تأكيد الجميع
          </button>
          <button @click="checkAll" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 hover:from-yellow-400 
                hover:to-yellow-400 hover:text-black text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                duration-200 flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
              الحالة  
          </button>
          <button @click="deleteAll" type="button" class="flex-1 py-3 px-6 inline-flex items-center justify-center 
              gap-x-2 text-base font-semibold rounded-xl border-2 border-red-500 bg-red-500 text-white hover:bg-red-600 
              hover:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all 
              duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            حذف الجميع
          </button>
        </div>

        <!-- Individual action input -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 flex flex-col gap-4">
          <h3 class="text-lg font-bold text-gray-800 text-right">الأبيات المضافة ({{ lines.length }})</h3>
          <div v-for="(line, index) in lines" :key="index" class="flex flex-col gap-2">
              <input 
                type="text" 
                v-model="lines[index][0]"
                class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-base rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-4 transition-all duration-200"
              >
              <div class="flex gap-2">
                <button 
                  @click="approve(index)" 
                  :disabled="lines[index][1] !== 'ناجح'"
                  class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 
                    hover:to-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  تأكيد
                </button>
                <button @click="checkOne(index)" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 hover:from-yellow-400 
                    hover:to-yellow-400 hover:text-black text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                  </svg>
                  {{ lines[index][1] }}
                </button>
                <button @click="deleteItem(index)" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 
                    hover:to-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  حذف
                </button>
              </div>
            </div>
        </div>
      </template>

      <!-- addedPoems View -->
       <template v-if="currentView === 'addedPoems'">
        <div class="text-center text-gray-600 font-semibold">
          <div class="flex gap-3 justify-center">
          <button @click="approveAllAdded" type="button" 
            :disabled="approveAllStatusAdded === false"
            class="flex-1 py-3 px-6 inline-flex items-center justify-center gap-x-2 
              text-base font-semibold rounded-xl border-2 border-green-500 bg-green-500 text-white hover:bg-green-600 hover:border-green-600 
              focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg 
              disabled:opacity-50 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            تأكيد الجميع
          </button>
          <button @click="checkAllAdded" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 hover:from-yellow-400 
                hover:to-yellow-400 hover:text-black text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                duration-200 flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
              الحالة  
          </button>
          <button @click="deleteAllAdded" type="button" class="flex-1 py-3 px-6 inline-flex items-center justify-center 
              gap-x-2 text-base font-semibold rounded-xl border-2 border-red-500 bg-red-500 text-white hover:bg-red-600 
              hover:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all 
              duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            حذف الجميع
          </button>
        </div>

        <!-- Individual action input -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 flex flex-col gap-4">
          <h3 class="text-lg font-bold text-gray-800 text-right">الأبيات المضافة ({{ addedPoemsLines.length }})</h3>
          <div v-for="(line, index) in addedPoemsLines" :key="index" class="flex flex-col gap-2">
              <input 
                type="text" 
                v-model="addedPoemsLines[index][0]"
                @input="watchLineEdit(index)"
                class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-base rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-4 transition-all duration-200"
              >
              <div class="flex gap-2">
                <button 
                  @click="approveAdded(index)" 
                  :disabled="addedPoemsLines[index][1] !== 'ناجح'"
                  class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 
                    hover:to-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  تأكيد
                </button>
                <button @click="checkOneAdded(index)" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 hover:from-yellow-400 
                    hover:to-yellow-400 hover:text-black text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                  </svg>
                  {{ addedPoemsLines[index][1] }}
                </button>
                <button @click="deleteItemAdded(index)" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 
                    hover:to-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  حذف
                </button>
              </div>
            </div>
        </div>
        </div>
      </template>






<!-- reports View -->
       <template v-if="currentView === 'reports'">
        <div class="flex flex-row-reverse gap-3 justify-center flex-wrap">
          <button 
            @click="showDublicatedbReports"
            :class="currentReportView === 'Dublicated' ? 'ring-4 ring-purple-300' : ''"
            class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            بلاغات تكرار
          </button>
          <button 
            @click="showSyntaxReports"
            :class="currentReportView === 'Syntax' ? 'ring-4 ring-purple-300' : ''"
            class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            بلاغات خطأ في البيت
          </button>   
        </div>
<!-- Duplicated Reports -->
        <template v-if="currentReportView === 'Dublicated'">

        <div class="text-center text-gray-600 font-semibold">
          <div class="flex gap-3 justify-center">
          <button @click="relodAll" type="button"
            :disabled="reportedLines.length === 0"   
            class="flex-1 py-3 px-6 inline-flex items-center justify-center gap-x-2 
              text-base font-semibold rounded-xl border-2 border-green-500 bg-green-500 text-white hover:bg-green-600 hover:border-green-600 
              focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg 
              disabled:opacity-50 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            تحديث الجميع
          </button>
          <button 
            :disabled="reportedLines.length === 0"
            @click="checkReportAll" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 hover:from-yellow-400 
                hover:to-yellow-400 hover:text-black text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                duration-200 flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
              الحالة  
          </button>
          <button 
            :disabled="reportedLines.length === 0"
            @click="IgnoreAll" type="button" class="flex-1 py-3 px-6 inline-flex items-center justify-center 
              gap-x-2 text-base font-semibold rounded-xl border-2 border-red-500 bg-red-500 text-white hover:bg-red-600 
              hover:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all 
              duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            تجاهل الجميع
          </button>
        </div>

        <!-- Individual action input -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 flex flex-col gap-4">
          <h3 class="text-lg font-bold text-gray-800 text-right">الأبيات المبلغ عن تكررها ({{ reportedLines.length }})</h3>
          <div v-for="(line, index) in reportedLines" :key="index" class="flex flex-col gap-2">
              <input 
                type="text" 
                v-model="reportedLines[index][0]"
                class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-base rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-4 transition-all duration-200"
              >
              <div class="flex gap-2">
                <button 
                  @click="RelodOne(index)" 
                  class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 
                    hover:to-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  تحديث
                </button>
                <button @click="checkReportOne(index)" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 hover:from-yellow-400 
                    hover:to-yellow-400 hover:text-black text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                  </svg>
                  {{ reportedLines[index][3] }}
                </button>
                <button @click="deleteReportOne(index)" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 
                    hover:to-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  حذف
                </button>
                <button @click="IgnoreOne(index)" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 
                    hover:to-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  تجاهل
                </button>
              </div>
            </div>
        </div>
        </div>
        </template>

<!-- Syntax Reports -->
        <template v-if="currentReportView === 'Syntax'">

        <div class="text-center text-gray-600 font-semibold">
          <div class="flex gap-3 justify-center">
          <button 
            :disabled="reportedLines.length === 0"
            @click="relodAll" type="button" 
            class="flex-1 py-3 px-6 inline-flex items-center justify-center gap-x-2 
              text-base font-semibold rounded-xl border-2 border-green-500 bg-green-500 text-white hover:bg-green-600 hover:border-green-600 
              focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg 
              disabled:opacity-50 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            تحديث الجميع
          </button>
          <button 
            :disabled="reportedLines.length === 0"
            @click="checkReportAll" class="flex-1 bg-gradient-to-r from-orange-500 to-yellow-600 hover:from-yellow-400 
                hover:to-yellow-400 hover:text-black text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                duration-200 flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
              الحالة  
          </button>
          <button 
            :disabled="reportedLines.length === 0"
            @click="IgnoreAll" type="button" class="flex-1 py-3 px-6 inline-flex items-center justify-center 
              gap-x-2 text-base font-semibold rounded-xl border-2 border-red-500 bg-red-500 text-white hover:bg-red-600 
              hover:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all 
              duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            تجاهل الجميع
          </button>
        </div>

        <!-- Individual action input -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 flex flex-col gap-4">
          <h3 class="text-lg font-bold text-gray-800 text-right">الأبيات المبلغ على خطأ نصي فيها ({{ reportedLines.length }})</h3>
          <div v-for="(line, index) in reportedLines" :key="index" class="flex flex-col gap-2">
              <input 
                type="text" 
                v-model="reportedLines[index][0]"
                class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-right text-base rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-4 transition-all duration-200"
              >
              <div class="flex gap-2">
                <button 
                  @click="RelodOne(index)" 
                  class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 
                    hover:to-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  تحديث
                </button>
                <button @click="deleteReportOne(index)" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 
                    hover:to-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  حذف
                </button>
                <button @click="IgnoreOne(index)" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 
                    hover:to-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all 
                    duration-200 flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  تجاهل
                </button>
              </div>
            </div>
        </div>
        </div>
        </template>
      </template>







      <!-- statistics View -->
      <template v-if="currentView === 'statistics'">
        <div class="space-y-6">
    
        <!-- إحصائيات الأبيات -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <h3 class="text-xl font-bold text-gray-800 text-right mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            إحصائيات الأبيات الشعرية
          </h3>
          
          <div class="grid grid-row-1 md:grid-row-3 gap-4">
            <!-- عدد الأبيات الكلي -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-5 rounded-lg border-r-4 border-blue-500 hover:shadow-lg transition-all duration-200">
              <div class="flex items-center justify-between">
                <div class="text-left">
                  <p class="text-sm text-gray-600 font-medium mb-1">عدد الأبيات الكلي</p>
                  <p class="text-3xl font-bold text-blue-600">{{ allLinesCount }}</p>
                </div>
                <div class="bg-blue-500 p-3 rounded-full">
                  <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                  </svg>
                </div>
              </div>
            </div>

            <!-- عدد الأبيات الموثقة -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-lg border-r-4 border-green-500 hover:shadow-lg transition-all duration-200">
              <div class="flex items-center justify-between">
                <div class="text-left">
                  <p class="text-sm text-gray-600 font-medium mb-1">عدد الأبيات الموثقة</p>
                  <p class="text-3xl font-bold text-green-600">{{ ApprovalLinesCount }}</p>
                </div>
                <div class="bg-green-500 p-3 rounded-full">
                  <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
              </div>
            </div>

            <!-- عدد الأبيات الغير موثقة -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-5 rounded-lg border-r-4 border-orange-500 hover:shadow-lg transition-all duration-200">
              <div class="flex items-center justify-between">
                <div class="text-left">
                  <p class="text-sm text-gray-600 font-medium mb-1">عدد الأبيات الغير موثقة</p>
                  <p class="text-3xl font-bold text-orange-600">{{ UnApprovalLinesCount }}</p>
                </div>
                <div class="bg-orange-500 p-3 rounded-full">
                  <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- إحصائيات البلاغات -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <h3 class="text-xl font-bold text-gray-800 text-right mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            إحصائيات البلاغات
          </h3>
          
          <div class="grid grid-row-1 md:grid-row-3 gap-4">
            <!-- عدد البلاغات الكلي -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-5 rounded-lg border-r-4 border-purple-500 hover:shadow-lg transition-all duration-200">
              <div class="flex items-center justify-between">
                <div class="text-left">
                  <p class="text-sm text-gray-600 font-medium mb-1">عدد البلاغات</p>
                  <p class="text-3xl font-bold text-purple-600">{{ ReportedLinesCount }}</p>
                </div>
                <div class="bg-purple-500 p-3 rounded-full">
                  <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                  </svg>
                </div>
              </div>
            </div>

            <!-- عدد بلاغات التكرار -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 p-5 rounded-lg border-r-4 border-red-500 hover:shadow-lg transition-all duration-200">
              <div class="flex items-center justify-between">
                <div class="text-left">
                  <p class="text-sm text-gray-600 font-medium mb-1">عدد بلاغات التكرار</p>
                  <p class="text-3xl font-bold text-red-600">{{ DuplicatedLinesCount }}</p>
                </div>
                <div class="bg-red-500 p-3 rounded-full">
                  <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </div>
              </div>
            </div>

            <!-- عدد بلاغات أخطاء نصية -->
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-5 rounded-lg border-r-4 border-yellow-500 hover:shadow-lg transition-all duration-200">
              <div class="flex items-center justify-between">
                <div class="text-left">
                  <p class="text-sm text-gray-600 font-medium mb-1">عدد بلاغات أخطاء نصية</p>
                  <p class="text-3xl font-bold text-yellow-600">{{ SyntaxErrorsCount }}</p>
                </div>
                <div class="bg-yellow-500 p-3 rounded-full">
                  <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      </template>
  </div>
</template>