<script setup>
import { ref, computed, onMounted } from 'vue'
// 🌟 Library สำหรับออกรายงาน (ติดตั้งด้วย npm install jspdf jspdf-autotable xlsx ก่อนนะครับ)
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable' 
import * as XLSX from 'xlsx'

// --- State Management ---
const isLoggedIn = ref(false)
const isRegistering = ref(false)
const username = ref(''); const password = ref('')
const regUsername = ref(''); const regPassword = ref(''); const regFullname = ref('')

const currentUser = ref(null)
const equipmentList = ref([])
const historyList = ref([])
const activeTab = ref('home')
const isUpdating = ref(null)

// --- ตัวแปรสำหรับ Popup ต่างๆ ---
const showBorrowModal = ref(false)
const borrowForm = ref({ item: null, borrower_name: '', borrower_phone: '', quantity: 1 })
const showOverdueModal = ref(false)
const returnForm = ref({ item: null, reason: '' })
const newEquip = ref({ name: '', category: 'Laptop' })

// 🛡️ เช็คว่าเป็น Admin หรือไม่ (ป้องกันตัวพิมพ์เล็ก-ใหญ่)
const isAdmin = computed(() => {
  return currentUser.value && currentUser.value.role && currentUser.value.role.toLowerCase() === 'admin'
})

// --- ระบบจดจำผู้ใช้ (Auto-Login) ---
onMounted(() => {
  // 1. เช็คว่าเคยล็อกอินค้างไว้ไหม
  const savedUser = localStorage.getItem('etech_user');
  if (savedUser) {
    try {
      currentUser.value = JSON.parse(savedUser);
      isLoggedIn.value = true;
      fetchEquipment(); // ดึงข้อมูลทันทีเมื่อเปิดเว็บ
    } catch(e) { 
      localStorage.removeItem('etech_user'); 
    }
  }
// 2. 🌟 ระบบ Real-time Polling: ดึงข้อมูลใหม่ทุกๆ 30 วินาที
  // เพื่อให้อัปเดตสถานะ (พร้อมยืม/กำลังยืม/ส่งซ่อม) แบบเรียลไทม์
  setInterval(() => {
    if (isLoggedIn.value) {
      fetchEquipment();
      // ถ้าเปิดหน้า History หรือ Manage อยู่ ก็ให้อัปเดตประวัติด้วย
      if (activeTab.value === 'history' || activeTab.value === 'manage') {
        fetchHistory();
      }
      console.log('อัปเดตข้อมูลอัตโนมัติเรียบร้อย...'); 
    }
  }, 30000); // 30000 ms = 30 วินาที
})
// --- API Functions ---
const fetchEquipment = async () => {
  try {
    const response = await fetch('http://10.12.29.62/api/get_equipment.php');
    const data = await response.json();
    equipmentList.value = Array.isArray(data) ? data : [];
  } catch (error) { console.error(error); equipmentList.value = []; }
}

const fetchHistory = async () => {
  try {
    const response = await fetch('http://10.12.29.62/api/get_equipment.php');
    const data = await response.json();
    historyList.value = Array.isArray(data) ? data : [];
  } catch (error) { console.error(error); }
}

const handleLogin = async () => {
  try {
    const response = await fetch('http://10.12.29.62/api/db.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: username.value, password: password.value })
    });
    const data = await response.json();
    if (data.status === 'success') {
      isLoggedIn.value = true;
      currentUser.value = data.user;
      localStorage.setItem('etech_user', JSON.stringify(data.user));
      activeTab.value = 'home';
      fetchEquipment();
    } else { alert(data.message); }
  } catch (error) { alert("เชื่อมต่อ Server ไม่ได้ เช็ค XAMPP ด้วยครับ!"); }
}

const handleRegister = async () => {
  if (!regUsername.value || !regPassword.value || !regFullname.value) return alert('กรุณากรอกข้อมูลให้ครบ');
  try {
    const response = await fetch('http://10.12.29.62/api/register.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: regUsername.value, password: regPassword.value, fullname: regFullname.value })
    });
    const data = await response.json();
    if (data.status === 'success') {
      alert("สมัครสำเร็จ! เข้าสู่ระบบได้เลย");
      isRegistering.value = false;
      regUsername.value = ''; regPassword.value = ''; regFullname.value = '';
    } else { alert(data.message); }
  } catch (error) { alert("เชื่อมต่อไม่ได้"); }
}

const handleLogout = () => {
  isLoggedIn.value = false; currentUser.value = null; username.value = ''; password.value = '';
  localStorage.removeItem('etech_user');
  activeTab.value = 'home';
}

// --- Borrow & Return Logic ---
const checkIsOverdue = (item) => {
  if (item.status !== 'borrowed' || !item.due_date) return false;
  return new Date() > new Date(item.due_date);
}

const openBorrowModal = (item) => {
  if (item.status === 'borrowed') {
    if (checkIsOverdue(item)) {
      returnForm.value = { item: item, reason: '' };
      showOverdueModal.value = true;
    } else {
      if (confirm(`ยืนยันการคืนอุปกรณ์: ${item.name}?`)) {
        processStatusChange(item, 'available', {});
      }
    }
  } else if (item.status === 'repairing') {
    if (confirm(`ซ่อมอุปกรณ์ ${item.name} เสร็จแล้วใช่ไหม?`)) {
      processStatusChange(item, 'available', {});
    }
  } else {
    borrowForm.value = { item: item, borrower_name: '', borrower_phone: '', quantity: 1 };
    showBorrowModal.value = true;
  }
}

const submitBorrow = () => {
  if (!borrowForm.value.borrower_name || !borrowForm.value.borrower_phone) return alert("กรุณากรอกชื่อและเบอร์โทรให้ครบ");
  processStatusChange(borrowForm.value.item, 'borrowed', {
    borrower_name: borrowForm.value.borrower_name,
    borrower_phone: borrowForm.value.borrower_phone,
    quantity: borrowForm.value.quantity
  });
  showBorrowModal.value = false;
}

const submitReturnOverdue = () => {
  if (!returnForm.value.reason) return alert("กรุณาระบุเหตุผลที่คืนล่าช้า");
  processStatusChange(returnForm.value.item, 'available', { overdue_reason: returnForm.value.reason });
  showOverdueModal.value = false;
}

const processStatusChange = async (item, newStatus, extraData) => {
  isUpdating.value = item.id;
  try {
    await fetch('http://10.12.29.62/api/update_status.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: item.id, status: newStatus, fullname: currentUser.value.fullname, ...extraData })
    });
    fetchEquipment(); 
  } finally { isUpdating.value = null; }
}

// --- Admin Actions ---
const sendToRepair = (item) => {
  if (confirm(`ยืนยันการส่งซ่อมอุปกรณ์: ${item.name}?`)) {
    processStatusChange(item, 'repairing', { overdue_reason: 'ส่งซ่อมบำรุง' });
  }
}

const addEquipment = async () => {
  if (!newEquip.value.name) return alert('ระบุชื่ออุปกรณ์ด้วยครับ');
  try {
    const response = await fetch('http://10.12.29.62/api/add_equipment.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...newEquip.value, role: currentUser.value.role })
    });
    const data = await response.json();
    if (data.status === 'success') { newEquip.value.name = ''; fetchEquipment(); alert('เพิ่มสำเร็จ!'); }
    else { alert(data.message); }
  } catch (error) { console.error(error); }
}

const deleteItem = async (id) => {
  if (!confirm('ยืนยันลบถาวร?')) return;
  try {
    const response = await fetch('http://10.12.29.62/api/delete_equipment.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, role: currentUser.value.role })
    });
    const data = await response.json();
    if (data.status === 'success') fetchEquipment(); else alert(data.message);
  } catch (error) { console.error(error); }
}

const switchTab = (tab) => { activeTab.value = tab; if (tab === 'history' || tab === 'manage') fetchHistory(); }

// --- Stats & Reports ---
const totalItems = computed(() => equipmentList.value?.length || 0)
const borrowedItems = computed(() => equipmentList.value?.filter(i => i.status === 'borrowed').length || 0)
const availableItems = computed(() => equipmentList.value?.filter(i => i.status === 'available').length || 0)
const monthlyBorrowed = computed(() => historyList.value.filter(log => log.action_type === 'borrow').length)
const monthlyRepaired = computed(() => equipmentList.value.filter(i => i.status === 'repairing').length)

const exportPDF = () => {
  try {
    const doc = new jsPDF()
    doc.setFontSize(18);
    doc.text("E.Tech Computer - Equipment Report", 14, 20); // หัวข้อภาษาอังกฤษ
    
    const tableData = historyList.value.map(log => [
      log.action_date.substring(0, 10),
      "Staff", // ใส่คำกลางๆ หรือดึงข้อมูลที่เป็นภาษาอังกฤษ
      log.action_type.toUpperCase(),
      `ID: ${log.equipment_id}`,
      "Member", // แทนที่ชื่อภาษาไทยด้วยคำอังกฤษ
      log.quantity || 1
    ])

    autoTable(doc, {
      startY: 45,
      head: [['Date', 'Staff', 'Action', 'Item ID', 'User', 'Qty']], // หัวตารางอังกฤษ
      body: tableData,
      // ... โค้ดส่วนอื่นคงเดิม
    })
    doc.save("ETech_Report.pdf")
  } catch (e) { /* ... */ }
}

const exportExcel = () => {
  try {
    const exportData = historyList.value.map(log => ({
      "วันที่และเวลา": log.action_date,
      "ผู้ทำรายการ (TA)": log.user_fullname,
      "ประเภทรายการ": log.action_type === 'borrow' ? 'ยืม' : (log.action_type === 'repairing' ? 'ส่งซ่อม' : 'คืน'),
      "รหัสอุปกรณ์": log.equipment_id,
      "ชื่อผู้ยืม": log.borrower_name || '-',
      "เบอร์โทร": log.borrower_phone || '-',
      "จำนวน": log.quantity || 1,
      "หมายเหตุ / เหตุผลคืนช้า": log.overdue_reason || '-'
    }))
    
    // สร้าง Sheet และ Book โดยใช้ชื่อตัวแปรให้ตรงกัน
    const worksheet = XLSX.utils.json_to_sheet(exportData)
    const workbook = XLSX.utils.book_new()
    
    // ปรับความกว้างของคอลัมน์ให้ดูสวยงามตอนเปิด Excel
    worksheet['!cols'] = [
      { wch: 20 }, { wch: 20 }, { wch: 15 }, { wch: 15 }, 
      { wch: 25 }, { wch: 15 }, { wch: 10 }, { wch: 30 }
    ]

    XLSX.utils.book_append_sheet(workbook, worksheet, "Report")
    XLSX.writeFile(workbook, "ETech_Monthly_Report.xlsx")
    
  } catch (error) {
    console.error("Excel Export Error: ", error)
    alert("เกิดข้อผิดพลาดในการโหลด Excel ลองกดปุ่มรีเฟรชหน้าเว็บ (F5) 1 ครั้งแล้วกดใหม่นะครับ")
  }
}
</script>

<template>
  <div class="min-h-screen font-sans text-slate-900">
    
    <div v-if="showBorrowModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-[100] p-6 animate-fade-in">
      <div class="bg-white rounded-[2.5rem] shadow-2xl p-10 max-w-md w-full text-center">
        <h3 class="text-3xl font-black mb-2 text-slate-800">ยืมอุปกรณ์</h3>
        <p class="text-[11px] font-bold text-blue-500 mb-8 uppercase tracking-widest bg-blue-50 py-2 rounded-xl">กำหนดคืนภายใน 7 วัน</p>
        <div class="space-y-4 text-left">
          <input v-model="borrowForm.borrower_name" type="text" placeholder="ชื่อคนยืม" class="w-full bg-white border border-slate-300 rounded-2xl px-6 py-4 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 font-medium transition-all">
          <input v-model="borrowForm.borrower_phone" type="tel" placeholder="เบอร์โทรติดต่อ" class="w-full bg-white border border-slate-300 rounded-2xl px-6 py-4 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 font-medium transition-all">
          <div class="flex items-center gap-4 bg-white border border-slate-300 rounded-2xl px-6 py-4 focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-500/10">
            <span class="text-slate-400 font-medium whitespace-nowrap">จำนวน :</span>
            <input v-model="borrowForm.quantity" type="number" min="1" class="w-full outline-none font-medium text-slate-800 bg-transparent">
          </div>
        </div>
        <div class="flex gap-4 mt-10">
          <button @click="showBorrowModal = false" class="flex-1 py-4 font-bold text-slate-400 hover:bg-slate-100 rounded-2xl">ยกเลิก</button>
          <button @click="submitBorrow" class="flex-1 py-4 bg-[#1e3a8a] text-white rounded-2xl font-black uppercase tracking-widest shadow-xl hover:bg-blue-800 transition-all">ยืนยันยืม</button>
        </div>
      </div>
    </div>

    <div v-if="showOverdueModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-[110] p-6 animate-fade-in">
      <div class="bg-white rounded-[2.5rem] shadow-2xl p-10 max-w-md w-full text-center">
        <div class="text-amber-500 text-5xl mb-4">⚠️</div>
        <h3 class="text-2xl font-black mb-2 text-slate-800">คืนเกินกำหนด!</h3>
        <p class="text-sm text-slate-500 mb-6">กรุณาระบุเหตุผลที่คืนล่าช้าเพื่อบันทึกในระบบ</p>
        <textarea v-model="returnForm.reason" class="w-full bg-white border border-slate-300 rounded-2xl p-5 outline-none focus:ring-4 focus:ring-amber-500/10 transition-all" rows="4" placeholder="ระบุเหตุผล..."></textarea>
        <div class="flex gap-4 mt-8">
          <button @click="showOverdueModal = false" class="flex-1 py-4 font-bold text-slate-400">ยกเลิก</button>
          <button @click="submitReturnOverdue" class="flex-1 py-4 bg-amber-500 text-white rounded-2xl font-black shadow-lg">ยืนยันการคืน</button>
        </div>
      </div>
    </div>

    <div v-if="!isLoggedIn" class="min-h-screen flex items-center justify-center p-6 animate-fade-in">
      <div class="max-w-md w-full bg-white rounded-[2rem] shadow-2xl border border-slate-100 overflow-hidden">
        <div class="p-12 bg-[#1e3a8a] text-white text-center">
          <div class="w-20 h-20 bg-white/10 rounded-[1.5rem] flex items-center justify-center text-3xl font-bold mx-auto mb-6 border border-white/20 shadow-inner">ET</div>
          <h2 class="text-2xl font-black tracking-tight">{{ isRegistering ? 'Create Account' : 'E.Tech System' }}</h2>
        </div>
        <form v-if="!isRegistering" @submit.prevent="handleLogin" class="p-10 space-y-6 animate-fade-in">
          <input v-model="username" type="text" placeholder="Username" class="w-full px-6 py-4 rounded-2xl border border-slate-200 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all">
          <input v-model="password" type="password" placeholder="Password" class="w-full px-6 py-4 rounded-2xl border border-slate-200 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all">
          <button type="submit" class="w-full bg-[#1e3a8a] text-white py-4 rounded-2xl font-black shadow-xl transition-all uppercase">Sign In</button>
          <p class="text-center text-sm text-slate-400">ยังไม่มีบัญชีใช่ไหม? <a href="#" @click.prevent="isRegistering = true" class="text-blue-600 font-bold hover:underline">ลงทะเบียนที่นี่</a></p>
        </form>
        <form v-else @submit.prevent="handleRegister" class="p-10 space-y-5 animate-fade-in">
          <input v-model="regFullname" type="text" placeholder="ชื่อ-นามสกุล" class="w-full px-6 py-4 rounded-2xl border border-slate-200 outline-none focus:ring-4 focus:ring-emerald-500/10 transition-all">
          <input v-model="regUsername" type="text" placeholder="Username" class="w-full px-6 py-4 rounded-2xl border border-slate-200 outline-none focus:ring-4 focus:ring-emerald-500/10 transition-all">
          <input v-model="regPassword" type="password" placeholder="Password" class="w-full px-6 py-4 rounded-2xl border border-slate-200 outline-none focus:ring-4 focus:ring-emerald-500/10 transition-all">
          <button type="submit" class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-black shadow-xl uppercase">Register</button>
          <p class="text-center text-sm text-slate-400">มีบัญชีอยู่แล้ว? <a href="#" @click.prevent="isRegistering = false" class="text-blue-600 font-bold hover:underline">กลับไปล็อกอิน</a></p>
        </form>
      </div>
    </div>

    <div v-else class="animate-fade-in">
      <nav class="bg-white border-b border-slate-100 px-10 py-6 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-5">
          <div class="w-12 h-12 bg-blue-900 rounded-2xl flex items-center justify-center text-white font-bold">ET</div>
          <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tighter">E.Tech Computer</h1>
            <p class="text-[10px] font-bold uppercase tracking-widest" :class="isAdmin ? 'text-blue-600' : 'text-slate-400'">
              {{ isAdmin ? '🛡️ ADMIN' : '👤 ASSISTANT' }} : {{ currentUser.fullname }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-10 text-[11px] font-black tracking-widest uppercase">
          <button @click="switchTab('home')" :class="activeTab === 'home' ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-400'">Home</button>
          <button @click="switchTab('history')" :class="activeTab === 'history' ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-400'">History</button>
          <button v-if="isAdmin" @click="switchTab('manage')" :class="activeTab === 'manage' ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-400'">Manage</button>
          <button @click="handleLogout" class="bg-red-50 text-red-500 px-5 py-2 rounded-xl">Logout</button>
        </div>
      </nav>

      <main class="max-w-7xl mx-auto p-10">
        <div v-if="activeTab === 'home'" class="animate-fade-in">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="bg-white p-8 rounded-[2rem] border-l-[8px] border-l-blue-600 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase">Total Items</p><p class="text-5xl font-black mt-3">{{ totalItems }}</p></div>
            <div class="bg-white p-8 rounded-[2rem] border-l-[8px] border-l-amber-500 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase">On Loan</p><p class="text-5xl font-black mt-3 text-amber-500">{{ borrowedItems }}</p></div>
            <div class="bg-white p-8 rounded-[2rem] border-l-[8px] border-l-emerald-500 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase">Available</p><p class="text-5xl font-black mt-3 text-emerald-600">{{ availableItems }}</p></div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div v-for="item in equipmentList" :key="item.id" class="bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm relative overflow-hidden group flex flex-col">
              <div v-if="checkIsOverdue(item)" class="absolute top-0 left-0 right-0 h-1 bg-red-500 animate-pulse"></div>
              <div class="flex justify-between items-start mb-8 text-5xl">
                <span>{{ item.category === 'Laptop' ? '💻' : item.category === 'Monitor' ? '🖥️' : '📦' }}</span>
                <span :class="checkIsOverdue(item) ? 'bg-red-50 text-red-600' : (item.status === 'available' ? 'bg-emerald-50 text-emerald-600' : (item.status === 'repairing' ? 'bg-slate-100 text-slate-500' : 'bg-amber-50 text-amber-600'))" class="text-[9px] font-black px-3 py-1 rounded-full uppercase border">
                  {{ checkIsOverdue(item) ? 'Overdue!' : item.status }}
                </span>
              </div>
              <h4 class="font-bold text-xl mb-2 h-14 overflow-hidden text-slate-800">{{ item.name }}</h4>
              <p v-if="item.due_date && item.status === 'borrowed'" class="text-[10px] font-bold text-slate-400 mb-6">กำหนดคืน: {{ item.due_date.substring(0, 10) }}</p>
              <div class="flex gap-2 mt-auto">
                <button @click="openBorrowModal(item)" class="flex-1 py-4 rounded-2xl font-black text-[10px] uppercase bg-[#1e3a8a] text-white shadow-lg">
                  {{ item.status === 'available' ? 'Borrow' : (item.status === 'repairing' ? 'Fixed' : 'Return') }}
                </button>
                <button v-if="item.status === 'available'" @click="sendToRepair(item)" class="w-14 bg-red-50 text-red-500 rounded-2xl font-black text-lg hover:bg-red-100">🔧</button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'manage' && isAdmin" class="animate-fade-in">
          <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-200 mb-10 flex flex-wrap items-center justify-between gap-6">
            <div><h3 class="text-xl font-black text-slate-800">📊 Monthly Summary Report</h3>
              <p class="text-sm font-bold text-slate-500 mt-2">Borrowed: {{ monthlyBorrowed }} times | Repairing: {{ monthlyRepaired }} items</p>
            </div>
            <div class="flex gap-4">
              <button @click="exportPDF" class="bg-red-50 text-red-600 px-6 py-4 rounded-2xl font-black text-[10px] uppercase flex items-center gap-2">📄 Export PDF</button>
              <button @click="exportExcel" class="bg-emerald-50 text-emerald-600 px-6 py-4 rounded-2xl font-black text-[10px] uppercase flex items-center gap-2">📊 Export Excel</button>
            </div>
          </div>
          <div class="bg-blue-900 p-10 rounded-[3rem] shadow-xl text-white mb-10">
            <h3 class="text-2xl font-black mb-8">Add New Equipment</h3>
            <div class="flex flex-wrap gap-6 items-end">
              <div class="flex-1 min-w-[300px]"><label class="text-[10px] font-bold opacity-50 block mb-3">Name</label><input v-model="newEquip.name" type="text" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 outline-none"></div>
              <div class="w-56"><label class="text-[10px] font-bold opacity-50 block mb-3">Category</label><select v-model="newEquip.category" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 outline-none text-slate-900"><option value="Laptop">Laptop</option><option value="Monitor">Monitor</option><option value="Peripheral">Peripheral</option></select></div>
              <button @click="addEquipment" class="bg-white text-blue-900 px-10 py-4 rounded-2xl font-black uppercase">Add Item</button>
            </div>
          </div>
          <div class="bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden shadow-xl"><table class="w-full text-left"><thead class="bg-slate-50 border-b"><tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><th class="p-8">ID</th><th class="p-8">Name</th><th class="p-8">Category</th><th class="p-8">Actions</th></tr></thead><tbody class="divide-y divide-slate-50"><tr v-for="item in equipmentList" :key="item.id" class="hover:bg-slate-50 transition-colors"><td class="p-8 font-mono text-xs">#EQU-{{ item.id }}</td><td class="p-8 font-bold text-slate-800">{{ item.name }}</td><td class="p-8 text-xs font-bold uppercase">{{ item.category }}</td><td class="p-8"><button @click="deleteItem(item.id)" class="text-red-500 font-black text-[10px] uppercase px-4 py-2 bg-red-50 rounded-xl">Delete</button></td></tr></tbody></table></div>
        </div>

        <div v-if="activeTab === 'history'" class="animate-fade-in">
          <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden"><table class="w-full text-left"><thead class="bg-slate-50 border-b"><tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><th class="p-8">Date</th><th class="p-8">Staff</th><th class="p-8">Borrower</th><th class="p-8">Details</th><th class="p-8">Action</th></tr></thead><tbody class="divide-y divide-slate-50"><tr v-for="log in historyList" :key="log.id" class="hover:bg-slate-50 transition-colors"><td class="p-8 text-sm text-slate-400">{{ log.action_date }}</td><td class="p-8 font-bold text-slate-700">{{ log.user_fullname }}</td><td class="p-8"><div><p class="font-bold text-sm text-slate-800">{{ log.borrower_name || '-' }}</p><p class="text-xs text-slate-500">📞 {{ log.borrower_phone || '-' }} | Qty: {{ log.quantity || 1 }}</p></div></td><td class="p-8"><p v-if="log.overdue_reason" class="text-xs text-red-500 italic bg-red-50 p-2 rounded-lg border border-red-100">"{{ log.overdue_reason }}"</p><p v-else class="text-xs text-slate-300">-</p></td><td class="p-8"><span :class="log.action_type === 'borrow' ? 'text-blue-600 bg-blue-50' : 'text-emerald-600 bg-emerald-50'" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">{{ log.action_type }}</span></td></tr></tbody></table></div>
        </div>
      </main>
    </div>
  </div>
</template>

<style>
/* 1. ส่วนกำหนดฟอนต์เดิมของคุณ (ปล่อยไว้เหมือนเดิม) */
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700;800&family=Inter:wght@400;700;900&display=swap');

body {
  font-family: 'Sarabun', 'Inter', sans-serif;
  -webkit-font-smoothing: antialiased;
  
  /* ========================================== */
  /* 🌟 2. ส่วนเปลี่ยนพื้นหลัง (ทับส่วน body เดิม) */
  /* ========================================== */
  
  /* 🌟 แก้ไขตรงนี้ครับ: ใช้คำว่า @/assets หรือ ../assets ขึ้นอยู่กับตำแหน่งไฟล์ */
  /* แต่สำหรับ Vite แนะนำให้ใส่แบบนี้ครับ */
 background-image: url('./assets/ETECH-4.jpg');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  background-repeat: no-repeat;
  margin: 0;
  padding: 0;
}

/* 3. 🌟 ส่วน Overlay (สร้างเลเยอร์สีใสๆ บังหน้าทับรูป เพื่อให้อ่านตัวหนังสือออก) */
body::before {
  content: "";
  position: fixed; /* เต็มหน้าจอ */
  top: 0; left: 0; right: 0; bottom: 0;
  
  /* ใส่สีพื้นขาวแบบใส (Opacity 80%) เพื่อให้รูปดูซอฟต์ลง */
  background-color: rgba(255, 255, 255, 0.85); 
  
  /* เพิ่มเอฟเฟกต์เบลอที่รูปภาพ (ถ้าอยากให้เบลอกว่านี้ให้เพิ่มตัวเลข) */
  backdrop-filter: blur(2px); 
  
  z-index: -1; /* วางไว้หลังเนื้อหาทั้งหมด */
}

/* ========================================== */
/* 4. ส่วน CSS อื่นๆ (ปล่อยไว้เหมือนเดิม) */
/* ========================================== */
h1, h2, h3, h4, button, .font-black {
  font-family: 'Inter', 'Sarabun', sans-serif;
  letter-spacing: -0.01em;
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

table th {
  font-size: 12px !important;
  font-weight: 700 !important;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b !important;
  padding: 1.5rem !important;
}

table td {
  font-size: 15px;
  padding: 1.5rem !important;
}

input, select, textarea {
  font-family: 'Sarabun', sans-serif !important;
  font-size: 15px !important;
}

@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>