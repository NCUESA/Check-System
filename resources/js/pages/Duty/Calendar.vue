<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AlertModal from '../../components/AlertModal.vue';
import { DutyCalendarPageProps, DutySlot } from '../../types';
import { toastErrors, toastSuccessMessage } from '../../utils';

// --- 0. 綁定自訂的 Alert 元件 ---
const alertModal = ref<InstanceType<typeof AlertModal> | null>(null);

// --- 1. 後端傳來的狀態 ---
// 目前顯示的地點與月份由後端決定，前端只負責請求，不自己記一份
const page = usePage<DutyCalendarPageProps>();
const locations = computed(() => page.props.locations ?? []);
const slots = computed(() => page.props.slots ?? {});
const myId = computed(() => page.props.auth.user?.id ?? null);
// 管理員不受填寫期間截止的限制
const canBypassLock = computed(() => page.props.is_admin);

const selectedLocationId = computed(() => page.props.locationId);
const selectedLocation = computed(() =>
    locations.value.find(l => l.id === selectedLocationId.value) ?? null
);

const displayYear = computed(() => page.props.year);
const displayMonth = computed(() => page.props.month - 1); // JS 的月份是 0 開始
const monthTitle = computed(() => `${page.props.year} 年 ${page.props.month} 月`);

// 表頭星期 (從週日開始)
const weekdays = ['日', '一', '二', '三', '四', '五', '六'];

// 已選取的名額 id。存 id 而不是日期，因為送出時後端要的是名額
const selectedSlotIds = ref<number[]>([]); // 要認領的空名額
const cancelSlotIds = ref<number[]>([]);   // 要退掉的自己的班
const loading = ref(false);    // 換月份 / 換地點
const submitting = ref(false); // 送出排班 / 取消排班

const clearSelection = () => {
    selectedSlotIds.value = [];
    cancelSlotIds.value = [];
};

// --- 管理模式 ---
// 開啟後點日期不是排班，而是展開那天的名額逐格指派
// 網址帶 ?manage=1 就直接開啟（差勤管理的「指定值勤」入口會帶）
// 非管理員一律不給開，後端也會擋
const adminMode = ref(
    canBypassLock.value && new URLSearchParams(window.location.search).get('manage') === '1'
);
const managingDate = ref<string | null>(null);
// 每個空名額各自記住下拉選到誰
const assignTarget = reactive<Record<number, number | ''>>({});

const closeManager = () => { managingDate.value = null; };

const toggleAdminMode = () => {
    adminMode.value = !adminMode.value;
    closeManager();
    clearSelection();
};

// 用本地時區轉 YYYY-MM-DD。不能用 toISOString()，那是 UTC，台灣早上八點前會少一天
const toDateString = (d: Date) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};
const todayString = toDateString(new Date());

// --- 2. 換月份 / 換地點 ---
// 用 Inertia partial reload 只重抓月曆資料，locations 不用重送
const reload = (locationId: number | null, year: number, month: number) => {
    loading.value = true;
    router.get(route('duty.calendar'),
        // 帶著 manage，不然 replace 重寫網址後重新整理就退回一般模式
        { location_id: locationId, year, month, ...(adminMode.value ? { manage: 1 } : {}) },
        {
            only: ['locationId', 'year', 'month', 'slots'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: () => { clearSelection(); },
            onFinish: () => { loading.value = false; },
        }
    );
};

const goMonth = (delta: number) => {
    const d = new Date(displayYear.value, displayMonth.value + delta, 1);
    reload(selectedLocationId.value, d.getFullYear(), d.getMonth() + 1);
};
const prevMonth = () => goMonth(-1);
const nextMonth = () => goMonth(1);
const selectLocation = (id: number) => reload(id, displayYear.value, displayMonth.value + 1);

// --- 3. 計算月曆資料 ---
interface Assignee {
    id: number,
    name: string,
    isMe: boolean
}

interface DayCell {
    empty: boolean,
    date?: number,
    dateString?: string,
    openSlots: DutySlot[],      // 有啟用的名額
    freeSlots: DutySlot[],      // 有啟用且還沒人認領的名額
    claimableSlots: DutySlot[], // 扣掉被 Lock 的之後，真正還能認領的
    mySlot?: DutySlot,          // 我自己認領的名額
    isOpen: boolean,            // 這天有開放值勤
    isFull: boolean,            // 開放的名額都被填滿了
    isLocked: boolean,          // 填寫期間已截止
    isToday: boolean,
    isPast: boolean,            // 已經過去的日期，不能再排
    assignees: Assignee[]       // 已經排這天班的人
}

const emptyCell = (): DayCell => ({
    empty: true, openSlots: [], freeSlots: [], claimableSlots: [],
    isOpen: false, isFull: false, isLocked: false, isToday: false, isPast: false, assignees: []
});

// 顯示用：把一天的人名串起來
const nameList = (day: DayCell) => day.assignees.map(a => a.name).join('、');

const calendarDays = computed<DayCell[]>(() => {
    const year = displayYear.value;
    const month = displayMonth.value;
    const lastDay = new Date(year, month + 1, 0);

    // 填補月初空白。因為從週日開始，getDay() 的週日是 0，剛好對應
    const days: DayCell[] = Array.from(
        { length: new Date(year, month, 1).getDay() },
        emptyCell
    );

    for (let d = 1; d <= lastDay.getDate(); d++) {
        const dateString = toDateString(new Date(year, month, d));
        const daySlots = slots.value[dateString] ?? [];
        const openSlots = daySlots.filter(s => s.status);
        const freeSlots = openSlots.filter(s => !s.user);
        // 管理員可以動被 Lock 的名額，一般人不行
        const claimableSlots = freeSlots.filter(s => !s.locked || canBypassLock.value);

        days.push({
            empty: false,
            date: d,
            dateString,
            openSlots,
            freeSlots,
            claimableSlots,
            mySlot: openSlots.find(s => s.user?.id === myId.value),
            isOpen: openSlots.length > 0,
            isFull: openSlots.length > 0 && freeSlots.length === 0,
            isLocked: openSlots.length > 0 && openSlots.every(s => s.locked),
            isToday: dateString === todayString,
            // YYYY-MM-DD 格式可以直接字串比大小
            isPast: dateString < todayString,
            assignees: openSlots
                .filter(s => s.user)
                .map(s => ({
                    id: s.user!.id,
                    name: s.user!.name ?? '未命名',
                    isMe: s.user!.id === myId.value,
                })),
        });
    }
    return days;
});

// --- 4. 互動邏輯 ---
const isSelected = (day: DayCell) =>
    day.openSlots.some(s => selectedSlotIds.value.includes(s.id));

const isCancelling = (day: DayCell) =>
    !!day.mySlot && cancelSlotIds.value.includes(day.mySlot.id);

const toggleDate = (day: DayCell) => {
    if (day.empty) return;

    // 管理模式：改成打開該天的名額面板
    if (adminMode.value) {
        if (!day.isOpen) {
            alertModal.value?.show('這天沒有可用的名額。若要加開請用「臨時增額」。', '沒有名額');
            return;
        }
        managingDate.value = day.dateString!;
        return;
    }

    if (!day.isOpen) {
        alertModal.value?.show('這天沒有開放值勤喔！', '無法選擇');
        return;
    }

    // 自己的班：切換成「要退掉」
    if (day.mySlot) {
        if (day.mySlot.locked && !canBypassLock.value) {
            alertModal.value?.show('這期的填寫期間已經截止，要退班請聯絡管理員。', '無法取消');
            return;
        }
        if (isCancelling(day)) {
            cancelSlotIds.value = cancelSlotIds.value.filter(id => id !== day.mySlot!.id);
        } else {
            cancelSlotIds.value.push(day.mySlot.id);
        }
        return;
    }

    if (day.isPast) {
        alertModal.value?.show('這天已經過去了，不能排班。', '無法選擇');
        return;
    }

    // 已經選過就取消選取
    const picked = day.openSlots.find(s => selectedSlotIds.value.includes(s.id));
    if (picked) {
        selectedSlotIds.value = selectedSlotIds.value.filter(id => id !== picked.id);
        return;
    }

    if (day.isLocked && !canBypassLock.value) {
        alertModal.value?.show('這期的填寫期間已經截止，要補排請聯絡管理員。', '無法選擇');
        return;
    }

    if (day.claimableSlots.length === 0) {
        alertModal.value?.show(`這天的名額已經滿了（${nameList(day)}）`, '無法選擇');
        return;
    }

    // 一天認領一個名額，取第一個還空著的
    selectedSlotIds.value.push(day.claimableSlots[0].id);
};

const handleSubmit = () => {
    if (!selectedLocation.value) {
        alertModal.value?.show('請先選擇值勤地點！', '操作提示');
        return;
    }
    if (selectedSlotIds.value.length === 0) {
        alertModal.value?.show('請至少選擇一天進行排班！', '操作提示');
        return;
    }

    const count = selectedSlotIds.value.length;
    submitting.value = true;
    router.post(route('duty.fill'),
        { slot_ids: selectedSlotIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                clearSelection();
                toastSuccessMessage(`已排入 ${count} 天的班！`);
            },
            onError: (errors) => { toastErrors(errors); },
            onFinish: () => { submitting.value = false; },
        }
    );
};

// 目前打開的那天有哪些名額
const managingSlots = computed<DutySlot[]>(() =>
    managingDate.value ? (slots.value[managingDate.value] ?? []) : []
);

const assignSlot = (slot: DutySlot) => {
    const userId = assignTarget[slot.id];
    if (!userId) {
        alertModal.value?.show('請先選擇要指派的人員。', '操作提示');
        return;
    }

    submitting.value = true;
    router.post(route('duty.assign'),
        { slot_id: slot.id, user_id: userId },
        {
            preserveScroll: true,
            onSuccess: () => {
                assignTarget[slot.id] = '';
                toastSuccessMessage('指派成功');
            },
            onError: (errors) => { toastErrors(errors); },
            onFinish: () => { submitting.value = false; },
        }
    );
};

// 管理員退掉任何人的班，走的是同一個 cancel 端點
const unassignSlot = (slot: DutySlot) => {
    alertModal.value?.confirm(
        `確定要退掉「${slot.user?.name ?? '這個人'}」的班嗎？`,
        () => {
            submitting.value = true;
            router.post(route('duty.cancel'),
                { slot_ids: [slot.id] },
                {
                    preserveScroll: true,
                    onSuccess: () => { toastSuccessMessage('已退掉這個班'); },
                    onError: (errors) => { toastErrors(errors); },
                    onFinish: () => { submitting.value = false; },
                }
            );
        },
        '退掉排班'
    );
};

const handleCancel = () => {
    if (cancelSlotIds.value.length === 0) return;

    const count = cancelSlotIds.value.length;
    submitting.value = true;
    router.post(route('duty.cancel'),
        { slot_ids: cancelSlotIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                clearSelection();
                toastSuccessMessage(`已退掉 ${count} 天的班。`);
            },
            onError: (errors) => { toastErrors(errors); },
            onFinish: () => { submitting.value = false; },
        }
    );
};
</script>

<template>
    <!-- 引入客製化彈窗元件 -->
    <AlertModal ref="alertModal" />

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow border-0 rounded-4 mt-4">
                <div class="card-body p-4 p-md-5">

                    <h4 class="fw-bold text-center mb-3 text-primary">
                        <i class="bi bi-calendar-check me-2"></i>值勤時間填寫
                    </h4>

                    <!-- 管理模式：開啟後點日期是指派名額，不是自己排班 -->
                    <div class="d-flex justify-content-center mb-4" v-if="canBypassLock">
                        <button type="button" class="btn btn-sm rounded-pill px-3"
                            :class="adminMode ? 'btn-dark' : 'btn-outline-dark'"
                            @click="toggleAdminMode">
                            <i class="bi me-1" :class="adminMode ? 'bi-toggle-on' : 'bi-toggle-off'"></i>
                            管理模式{{ adminMode ? '：開啟中' : '' }}
                        </button>
                    </div>

                    <!-- 地點切換按鈕 (Radio Button Style) -->
                    <div class="d-flex justify-content-center mb-5">
                        <div class="btn-group shadow-sm flex-wrap" role="group" v-if="locations.length">
                            <template v-for="location in locations" :key="location.id">
                                <input type="radio" class="btn-check" name="btnradio" :id="`loc-${location.id}`" autocomplete="off"
                                    :value="location.id" :checked="selectedLocationId === location.id"
                                    :disabled="loading" @change="selectLocation(location.id)">
                                <label class="btn btn-outline-primary px-4 fw-bold" :for="`loc-${location.id}`">
                                    {{ location.name }}
                                </label>
                            </template>
                        </div>
                        <div class="alert alert-warning mb-0 text-center" v-else>
                            目前沒有啟用中的值勤地點，請先到系統設定新增。
                        </div>
                    </div>

                    <!-- 月份控制列 -->
                    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                        <button class="btn btn-light rounded-pill px-3 shadow-sm border" :disabled="loading" @click="prevMonth">
                            <i class="bi bi-chevron-left me-1"></i>上個月
                        </button>
                        <h5 class="fw-bold mb-0 text-dark">{{ monthTitle }}</h5>
                        <button class="btn btn-light rounded-pill px-3 shadow-sm border" :disabled="loading" @click="nextMonth">
                            下個月<i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>

                    <!-- 月曆區塊 -->
                    <div class="calendar-wrapper bg-light p-3 p-md-4 rounded-4 border position-relative" :class="{ 'is-loading': loading }">
                        <div class="calendar-grid fw-bold text-muted mb-2 text-center">
                            <!-- 特別把週末標紅 -->
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
                                    'is-selected': isSelected(day),
                                    'is-cancelling': isCancelling(day),
                                    'is-today': day.isToday && !isSelected(day) && !isCancelling(day),
                                    'is-disabled': !day.empty && (!day.isOpen || (day.isPast && !day.mySlot)),
                                    'is-locked': !day.empty && day.isLocked && !day.mySlot && !day.isPast,
                                    'is-occupied': !day.empty && day.isFull && !day.mySlot && !day.isPast && !day.isLocked,
                                    'is-mine': !day.empty && !!day.mySlot && !isCancelling(day)
                                }"
                                :title="day.assignees.length ? nameList(day) : undefined"
                                @click="toggleDate(day)"
                            >
                                <template v-if="!day.empty">
                                    <span class="date-number fw-bold">{{ day.date }}</span>

                                    <!-- 已經排這天班的人。名額多的時候只列前三個 -->
                                    <div class="assignees" v-if="day.assignees.length">
                                        <span v-for="who in day.assignees.slice(0, 3)" :key="who.id"
                                            class="assignee" :class="{ 'is-me': who.isMe }">
                                            {{ who.name }}
                                        </span>
                                        <span v-if="day.assignees.length > 3" class="assignee more">
                                            +{{ day.assignees.length - 3 }}
                                        </span>
                                    </div>

                                    <!-- 狀態提示小標 -->
                                    <span v-if="!day.isOpen" class="status-badge text-danger">不開放</span>
                                    <span v-else-if="isCancelling(day)" class="status-badge">要退掉</span>
                                    <span v-else-if="day.isPast" class="status-badge text-muted">已結束</span>
                                    <span v-else-if="day.isLocked" class="status-badge text-muted">已截止</span>
                                    <span v-else-if="day.isFull" class="status-badge text-muted">額滿</span>
                                    <span v-else class="status-badge text-success">剩 {{ day.claimableSlots.length }} 位</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- 管理模式：某一天的名額指派面板 -->
                    <div class="manage-panel mt-4" v-if="adminMode && managingDate">
                        <div class="d-flex align-items-center mb-3">
                            <h6 class="fw-bold mb-0">
                                {{ managingDate }} · {{ selectedLocation?.name }}
                            </h6>
                            <button type="button" class="btn-close ms-auto" @click="closeManager"></button>
                        </div>

                        <div class="slot-row" v-for="(slot, i) in managingSlots" :key="slot.id"
                            :class="{ 'is-off': !slot.status }">
                            <span class="slot-no">名額 {{ i + 1 }}</span>

                            <template v-if="!slot.status">
                                <span class="text-muted">此名額已停用</span>
                            </template>

                            <template v-else-if="slot.user">
                                <span class="fw-bold">{{ slot.user.name }}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-auto"
                                    :disabled="submitting" @click="unassignSlot(slot)">
                                    退掉
                                </button>
                            </template>

                            <template v-else>
                                <select class="form-select form-select-sm slot-select"
                                    v-model="assignTarget[slot.id]">
                                    <option value="">選擇人員…</option>
                                    <option v-for="u in page.props.users" :key="u.id" :value="u.id">
                                        {{ u.stu_id }}　{{ u.name }}
                                    </option>
                                </select>
                                <button type="button" class="btn btn-sm btn-primary"
                                    :disabled="submitting || !assignTarget[slot.id]"
                                    @click="assignSlot(slot)">
                                    指派
                                </button>
                            </template>
                        </div>

                        <p class="text-muted small mb-0 mt-3">
                            管理模式下不受填寫期間截止的限制。
                        </p>
                    </div>

                    <!-- 底部送出 -->
                    <div class="mt-4 pt-4 d-flex flex-wrap gap-3 justify-content-between align-items-center"
                        v-if="!adminMode">
                        <div class="text-muted">
                            目前地點：<span class="fw-bold text-primary">{{ selectedLocation?.name ?? '—' }}</span>
                            <span class="ms-3">
                                要排 <span class="fw-bold text-primary fs-5">{{ selectedSlotIds.length }}</span> 天
                            </span>
                            <span class="ms-3" v-if="cancelSlotIds.length">
                                要退 <span class="fw-bold text-danger fs-5">{{ cancelSlotIds.length }}</span> 天
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-danger btn-lg px-4 rounded-3 fw-bold"
                                v-if="cancelSlotIds.length"
                                :disabled="loading || submitting" @click="handleCancel">
                                {{ submitting ? '處理中…' : '取消排班' }}
                            </button>
                            <button class="btn btn-primary btn-lg px-5 rounded-3 fw-bold shadow-sm"
                                :disabled="loading || submitting || !selectedLocation" @click="handleSubmit">
                                {{ submitting ? '送出中…' : '送出排班' }}
                            </button>
                        </div>
                    </div>

                    <p class="text-muted small mt-3 mb-0" v-if="!adminMode">
                        點空的日子可以排班，點自己的班可以退掉。填寫期間截止之後就只有管理員能異動。
                    </p>
                    <p class="text-muted small mt-3 mb-0" v-else>
                        管理模式：點任一天可以展開該天的名額，指派給特定人員或退掉既有的班。
                    </p>

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

/* 換月份 / 換地點時，讓整塊月曆淡出表示正在載入 */
.calendar-wrapper.is-loading {
    opacity: 0.55;
    pointer-events: none;
    transition: opacity 0.15s ease;
}

/* 用 min-height 而不是 aspect-ratio：格子裡要列人名，
   名字多的時候要能自己長高，不然內容會被壓爆 */
.calendar-cell {
    min-height: 96px;
    padding: 6px 4px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.2rem;
    background-color: #ffffff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
    overflow: hidden;
}

.calendar-cell.is-empty {
    cursor: default;
    background-color: transparent;
    box-shadow: none;
}

/* 日期下方列出排班的人 */
.assignees {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
    margin-top: 3px;
    max-width: 100%;
}
.assignee {
    max-width: 100%;
    font-size: 0.7rem;
    font-weight: 600;
    line-height: 1.25;
    color: #495057;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
/* 自己的班標出來 */
.assignee.is-me { color: #0d6efd; }
.assignee.more { color: #adb5bd; font-weight: 500; }

/* 日期下方的文字狀態 */
.status-badge {
    font-size: 0.7rem;
    margin-top: 3px;
    font-weight: 600;
}

/* 正常可選的 hover 狀態 */
.calendar-cell:not(.is-empty):not(.is-disabled):not(.is-occupied):not(.is-mine):hover {
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
.calendar-cell.is-selected .status-badge,
.calendar-cell.is-selected .assignee {
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

/* 已經額滿的狀態 (微透明，hover 可看名字) */
.calendar-cell.is-occupied {
    background-color: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
    border: 1px dashed #ced4da;
    box-shadow: none;
}

/* 我自己已經排的班 */
.calendar-cell.is-mine {
    background-color: #e7f1ff;
    border: 2px solid #0d6efd;
}

/* 這次要退掉的班 */
.calendar-cell.is-cancelling {
    background-color: #dc3545;
    color: white;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    transform: scale(1.05);
}
.calendar-cell.is-cancelling .status-badge,
.calendar-cell.is-cancelling .assignee {
    color: #ffe3e6 !important;
}

/* 填寫期間已截止 */
.calendar-cell.is-locked {
    background-color: #f8f9fa;
    color: #adb5bd;
    cursor: not-allowed;
    border: 1px dashed #dee2e6;
    box-shadow: none;
}

.calendar-cell.is-today {
    border: 2px solid #0d6efd;
}

/* 管理模式的名額面板 */
.manage-panel {
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 20px;
    background: #fff;
}
.slot-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-top: 1px solid #f1f3f5;
}
.slot-row:first-of-type { border-top: none; }
.slot-row.is-off { opacity: 0.6; }
.slot-no {
    flex-shrink: 0;
    font-size: 0.78rem;
    font-weight: 600;
    color: #6c757d;
    background: #f1f3f5;
    padding: 3px 10px;
    border-radius: 999px;
}
.slot-select {
    flex: 1 1 auto;
    min-width: 0;
    max-width: 320px;
}

/* RWD: 在手機上把字體縮小一點 */
@media (max-width: 768px) {
    .calendar-cell { font-size: 1rem; min-height: 78px; padding: 4px 2px; }
    .status-badge { font-size: 0.6rem; }
    .assignee { font-size: 0.6rem; }
    .calendar-grid { gap: 5px; }
}
</style>
