<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AlertModal from '../../components/AlertModal.vue';

// --- 0. 綁定自訂的 Alert 元件 ---
const alertModal = ref<InstanceType<typeof AlertModal> | null>(null);

// --- 1. 地點與月曆基礎狀態 ---
const locations = ['圖書館', '活動中心']; // 這裡的地點可以由後端傳入
const selectedLocation = ref(locations[0]);

const currentDate = ref(new Date()); 
const selectedDates = ref<string[]>([]);

// 表頭星期 (改為從週日開始)
const weekdays = ['日', '一', '二', '三', '四', '五', '六'];

// --- 2. 模擬從資料庫抓取的當月狀態 ---
// 資料結構設計為：{ '2026-07-23': { isAvailable: true, assignee: null }, ... }
const monthStatusData = ref<Record<string, { isAvailable: boolean; assignee: string | null }>>({});

const displayYear = computed(() => currentDate.value.getFullYear());
const displayMonth = computed(() => currentDate.value.getMonth());
const monthTitle = computed(() => `${displayYear.value} 年 ${displayMonth.value + 1} 月`);

// 向後端請求該月份與該地點的排班狀態
const fetchMonthData = async () => {
    // 這裡替換成你真實的 API 路徑
    // const response = await axios.get(`/api/duty-status`, { 
    //     params: { year: displayYear.value, month: displayMonth.value + 1, location: selectedLocation.value } 
    // });
    // monthStatusData.value = response.data;

    /* 👇 這裡是為了讓你看效果的假資料產生器 (Mock Data) 👇 */
    const mockData: any = {};
    const daysInMonth = new Date(displayYear.value, displayMonth.value + 1, 0).getDate();
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${displayYear.value}-${String(displayMonth.value + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        // 隨機產生狀態：可能不能排班、可能已經有人
        const randomVal = Math.random();
        mockData[dateStr] = {
            isAvailable: randomVal > 0.3, // 70% 的日子開放排班
            assignee: randomVal > 0.85 ? '林大明' : null // 15% 的日子已經有人了
        };
    }
    monthStatusData.value = mockData;
    /* 👆 假資料結束 👆 */
    
    // 切換月份或地點時，清空當前選擇
    selectedDates.value = [];
};

// 監聽月份或地點改變時，重新抓資料
watch([currentDate, selectedLocation], () => {
    fetchMonthData();
});
onMounted(() => fetchMonthData());

// --- 3. 計算月曆資料 (整合後端狀態) ---
const calendarDays = computed(() => {
    const year = displayYear.value;
    const month = displayMonth.value;
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    // 因為從週日開始，JavaScript getDay() 週日剛好是 0，完美對應！
    const startPadding = firstDay.getDay(); 
    const days = [];
    
    // 填補月初空白
    for (let i = 0; i < startPadding; i++) days.push({ empty: true });
    
    // 填入實際日期與後端狀態
    for (let d = 1; d <= lastDay.getDate(); d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const status = monthStatusData.value[dateStr] || { isAvailable: false, assignee: null };
        
        days.push({
            empty: false,
            date: d,
            dateString: dateStr,
            isAvailable: status.isAvailable,
            assignee: status.assignee,
            isToday: dateStr === new Date().toISOString().split('T')[0]
        });
    }
    return days;
});

// --- 4. 互動邏輯 ---
const prevMonth = () => currentDate.value = new Date(displayYear.value, displayMonth.value - 1, 1);
const nextMonth = () => currentDate.value = new Date(displayYear.value, displayMonth.value + 1, 1);

const toggleDate = (day: any) => {
    if (day.empty) return;
    
    // 防呆：不可值勤或已經有人，不給點擊並跳出客製化提示
    if (!day.isAvailable) {
        alertModal.value?.show('這天沒有開放值勤喔！', '無法選擇');
        return;
    }
    if (day.assignee) {
        alertModal.value?.show(`這天已經由 ${day.assignee} 值勤了！`, '無法選擇');
        return;
    }

    const index = selectedDates.value.indexOf(day.dateString);
    if (index > -1) {
        selectedDates.value.splice(index, 1);
    } else {
        selectedDates.value.push(day.dateString);
    }
};

const isSelected = (dateStr?: string) => selectedDates.value.includes(dateStr!);

const handleSubmit = () => {
    if (selectedDates.value.length === 0) {
        // 使用客製化 Alert
        alertModal.value?.show('請至少選擇一天進行排班！', '操作提示');
        return;
    }
    const sortedDates = [...selectedDates.value].sort();
    console.log(`準備送出地點: ${selectedLocation.value}, 日期:`, sortedDates);
    // router.post(route('duty.store'), { location: selectedLocation.value, dates: sortedDates });
};
</script>

<template>
    <!-- 引入客製化彈窗元件 -->
    <AlertModal ref="alertModal" />

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow border-0 rounded-4 mt-4">
                <div class="card-body p-4 p-md-5">
                    
                    <h4 class="fw-bold text-center mb-4 text-primary">
                        <i class="bi bi-calendar-check me-2"></i>值勤時間填寫
                    </h4>

                    <!-- 地點切換按鈕 (Radio Button Style) -->
                    <div class="d-flex justify-content-center mb-5">
                        <div class="btn-group shadow-sm" role="group">
                            <input type="radio" class="btn-check" name="btnradio" id="loc1" autocomplete="off" 
                                :value="locations[0]" v-model="selectedLocation">
                            <label class="btn btn-outline-primary px-4 fw-bold" for="loc1">{{ locations[0] }}</label>

                            <input type="radio" class="btn-check" name="btnradio" id="loc2" autocomplete="off" 
                                :value="locations[1]" v-model="selectedLocation">
                            <label class="btn btn-outline-primary px-4 fw-bold" for="loc2">{{ locations[1] }}</label>
                        </div>
                    </div>

                    <!-- 月份控制列 -->
                    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                        <button class="btn btn-light rounded-pill px-3 shadow-sm border" @click="prevMonth">
                            <i class="bi bi-chevron-left me-1"></i>上個月
                        </button>
                        <h5 class="fw-bold mb-0 text-dark">{{ monthTitle }}</h5>
                        <button class="btn btn-light rounded-pill px-3 shadow-sm border" @click="nextMonth">
                            下個月<i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>

                    <!-- 月曆區塊 -->
                    <div class="calendar-wrapper bg-light p-3 p-md-4 rounded-4 border">
                        <div class="calendar-grid fw-bold text-muted mb-2 text-center">
                            <!-- 特別把週日標紅 -->
                            <div v-for="day in weekdays" :key="day" class="py-2" :class="{'text-danger': day === '日' || day === '六'}">
                                {{ day }}
                            </div>
                        </div>

                        <div class="calendar-grid text-center">
                            <div 
                                v-for="(day, index) in calendarDays" 
                                :key="index"
                                class="calendar-cell d-flex flex-column justify-content-center align-items-center position-relative"
                                :class="{
                                    'is-empty': day.empty,
                                    'is-selected': isSelected(day.dateString),
                                    'is-today': day.isToday && !isSelected(day.dateString),
                                    'is-disabled': !day.empty && !day.isAvailable,
                                    'is-occupied': !day.empty && day.assignee
                                }"
                                @click="toggleDate(day)"
                            >
                                <template v-if="!day.empty">
                                    <span class="date-number fw-bold">{{ day.date }}</span>
                                    
                                    <!-- 狀態提示小標 -->
                                    <span v-if="!day.isAvailable" class="status-badge text-danger">不開放</span>
                                    <span v-else-if="day.assignee" class="status-badge text-muted">{{ day.assignee }}</span>
                                    <span v-else class="status-badge text-success">可選</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- 底部送出 -->
                    <div class="mt-4 pt-4 d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            目前選擇：<span class="fw-bold text-primary">{{ selectedLocation }}</span>，
                            共 <span class="fw-bold text-primary fs-5">{{ selectedDates.length }}</span> 天
                        </div>
                        <button class="btn btn-primary btn-lg px-5 rounded-3 fw-bold shadow-sm" @click="handleSubmit">
                            送出排班
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
}

.calendar-cell {
    aspect-ratio: 1 / 1; 
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.2rem;
    background-color: #ffffff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
}

.calendar-cell.is-empty {
    cursor: default;
    background-color: transparent;
    box-shadow: none;
}

/* 日期下方的文字狀態 */
.status-badge {
    font-size: 0.7rem;
    margin-top: 4px;
    font-weight: 600;
}

/* 正常可選的 hover 狀態 */
.calendar-cell:not(.is-empty):not(.is-disabled):not(.is-occupied):hover {
    background-color: #f1f8ff;
    border: 2px solid #0d6efd;
}

/* 被選中的狀態 */
.calendar-cell.is-selected {
    background-color: #0d6efd;
    color: white;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
    transform: scale(1.05);
}
.calendar-cell.is-selected .status-badge {
    color: #e0f0ff !important;
}

/* 不開放排班 (灰色背景，刪除線) */
.calendar-cell.is-disabled {
    background-color: #f8f9fa;
    color: #adb5bd;
    cursor: not-allowed;
    text-decoration: line-through;
    box-shadow: none;
}

/* 已經有人的狀態 (微透明，顯示名字) */
.calendar-cell.is-occupied {
    background-color: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
    border: 1px dashed #ced4da;
    box-shadow: none;
}

.calendar-cell.is-today {
    border: 2px solid #0d6efd;
}

/* RWD: 在手機上把字體縮小一點 */
@media (max-width: 768px) {
    .calendar-cell { font-size: 1rem; }
    .status-badge { font-size: 0.6rem; }
    .calendar-grid { gap: 5px; }
}
</style>