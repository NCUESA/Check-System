<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

// 接收從 Controller 傳過來的資料
const props = defineProps<{
    status: 'none' | 'checked_in' | 'checked_out'; // 目前狀態：未打卡、已簽到、已簽退
    lastRecordTime: string | null;                 // 上次打卡時間
    message?: string;                              // 系統提示訊息 (例如：簽到成功)
}>();

// 利用 computed 自動根據狀態切換按鈕的外觀與文字
const buttonConfig = computed(() => {
    if (props.status === 'checked_in') {
        return {
            text: '我要簽退',
            color: 'btn-warning',
            icon: 'bi-box-arrow-right',
            disabled: false
        };
    }
    if (props.status === 'checked_out') {
        return {
            text: '今日已完成值勤',
            color: 'btn-secondary',
            icon: 'bi-check-circle-fill',
            disabled: true
        };
    }
    // 預設 (none)
    return {
        text: '我要簽到',
        color: 'btn-primary',
        icon: 'bi-box-arrow-in-right',
        disabled: false
    };
});

// 點擊按鈕觸發打卡 API
const handleCheck = () => {
    if (buttonConfig.value.disabled) return;

    // 呼叫你在 web.php 定義的 check 路由 (使用 PUT)
    router.put(route('check'), {}, {
        preserveScroll: true,
        // 如果想在發送請求時讓按鈕轉圈圈，可以在這裡處理
    });
};
</script>

<template>
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 mt-4">
                <div class="card-body text-center p-5">

                    <!-- Icons -->
                    <div class="mb-4 text-primary">
                        <i class="bi bi-phone" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="fw-bold mb-4">手機簽到</h3>

                    <!-- System Message (如果有 message 才顯示) -->
                    <div v-if="props.message" class="alert alert-success mb-4 rounded-3 fw-bold">
                        <i class="bi bi-check-circle me-2"></i>{{ props.message }}
                    </div>

                    <!-- Time Info -->
                    <div class="mb-5 bg-light p-3 rounded-3">
                        <p class="text-muted mb-1 small">上次打卡時間</p>
                        <h4 class="fw-bold text-dark mb-0 font-monospace">
                            {{ props.lastRecordTime || '尚無紀錄' }}
                        </h4>
                    </div>

                    <!-- 打卡按鈕 -->
                    <button
                        class="btn btn-lg w-100 py-3 fw-bold fs-5 shadow-sm rounded-3 d-flex justify-content-center align-items-center gap-2"
                        :class="buttonConfig.color" :disabled="buttonConfig.disabled" @click="handleCheck">
                        <i class="bi" :class="buttonConfig.icon" style="font-size: 1.5rem;"></i>
                        {{ buttonConfig.text }}
                    </button>

                    <!-- 底部小提示 -->
                    <p class="text-muted small mt-4 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        簽到與簽退中間需間隔 2 分鐘
                    </p>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* 讓按鈕點擊時有比較明顯的按壓回饋 */
.btn {
    transition: all 0.2s;
}

.btn:active:not(:disabled) {
    transform: scale(0.96);
}
</style>