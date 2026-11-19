<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { watch } from 'vue';
import type { ChecklistRow, LocationOption } from '../types';
import { toastErrors, toastSuccessMessage } from '../utils';

// record 有值代表編輯既有紀錄，沒有就是新增一筆
const props = defineProps<{
    record?: ChecklistRow | null,
    locations: LocationOption[]
}>();
const emit = defineEmits<{ saved: []; cancel: [] }>();

const cf = useForm<{
    sid: string,
    checkin_time: string | Date,
    checkout_time: string | Date,
    checkin_location_id: number | null,
    checkout_location_id: number | null
}>('CF', {
    sid: "",
    checkin_time: new Date(),
    checkout_time: new Date(),
    checkin_location_id: null,
    checkout_location_id: null
});

const blankDefaults = () => ({
    sid: "",
    checkin_time: new Date(),
    checkout_time: new Date(),
    checkin_location_id: props.locations[0]?.id ?? null,
    checkout_location_id: props.locations[0]?.id ?? null
});

// 切換編輯對象時把表單帶成該筆紀錄的內容
watch(() => props.record, (record) => {
    if (record) {
        cf.defaults({
            sid: record.user?.stu_id ?? "",
            checkin_time: record.checkin_time ?? new Date(),
            checkout_time: record.checkout_time ?? new Date(),
            checkin_location_id: record.checkin_location_id,
            checkout_location_id: record.checkout_location_id
        });
    } else {
        cf.defaults(blankDefaults());
    }
    cf.reset();
    cf.clearErrors();
}, { immediate: true });

const onSubmit = () => {
    const done = (message: string) => () => {
        toastSuccessMessage(message);
        cf.defaults(blankDefaults());
        cf.reset();
        emit('saved');
    };

    if (props.record) {
        cf.put(route('checklist.update', { checkList: props.record.id }), {
            preserveScroll: true,
            onSuccess: done("紀錄已更新"),
            onError: (err) => toastErrors(err)
        });
    } else {
        cf.post(route('checklist.store'), {
            preserveScroll: true,
            onSuccess: done("手動簽到退成功"),
            onError: (err) => toastErrors(err)
        });
    }
};
</script>

<template>
    <form class="form-grid" @submit.prevent="onSubmit">

        <div class="warn-note span-all">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            此輸入並不防呆，送出前請先再三確認。
        </div>

        <div class="input-group span-all">
            <label for="stu_id">學號</label>
            <input type="text" id="stu_id" v-model="cf.sid" placeholder="輸入學號" required>
        </div>

        <div class="input-group">
            <label>簽到時間</label>
            <VueDatePicker v-model:model-value="cf.checkin_time" :clearable="false"
                :minutes-grid-increment="1" :minutes-increment="1" auto-apply />
        </div>

        <div class="input-group">
            <label>簽退時間</label>
            <VueDatePicker v-model:model-value="cf.checkout_time" :clearable="false"
                :minutes-grid-increment="1" :minutes-increment="1" auto-apply />
        </div>

        <div class="input-group">
            <label for="checkin_location">簽到地點</label>
            <select id="checkin_location" v-model="cf.checkin_location_id">
                <option :value="null">未指定</option>
                <option v-for="location in props.locations" :key="location.id" :value="location.id">
                    {{ location.name }}
                </option>
            </select>
        </div>

        <div class="input-group">
            <label for="checkout_location">簽退地點</label>
            <select id="checkout_location" v-model="cf.checkout_location_id">
                <option :value="null">未指定</option>
                <option v-for="location in props.locations" :key="location.id" :value="location.id">
                    {{ location.name }}
                </option>
            </select>
        </div>

        <div class="divider span-all"></div>

        <div class="action-group span-all">
            <button type="submit" class="sys-btn-primary" :disabled="cf.processing">
                {{ props.record ? '儲存變更' : '手動簽到退' }}
            </button>
            <button type="button" class="sys-btn-ghost" @click="props.record ? emit('cancel') : cf.reset()">
                {{ props.record ? '取消編輯' : '重填' }}
            </button>
        </div>
    </form>
</template>

<style scoped>
/* 欄位夠寬就並排成兩欄，窄的時候自動收成一欄 */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    align-items: start;
}
.span-all { grid-column: 1 / -1; }

/* 共用的 .action-group 是直式的，這裡的表單夠寬，按鈕改成並排 */
.action-group {
    flex-direction: row;
    flex-wrap: wrap;
}

.warn-note {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: var(--radius-md);
    background: #fef3c7;
    color: #b45309;
    font-size: 0.85rem;
    font-weight: 600;
}
.warn-note svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* VueDatePicker 的外框跟設計系統的輸入框對齊 */
.input-group :deep(.dp__input) {
    padding: 10px 14px 10px 38px;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    background-color: var(--bg-color);
    color: var(--text-main);
}
.input-group :deep(.dp__input:hover) {
    border-color: var(--border-hover);
}
.input-group :deep(.dp__input_focus) {
    border-color: var(--primary);
    background-color: var(--surface);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
</style>
