<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import type { DutyPhonePageProps } from '../../types';

const page = usePage<DutyPhonePageProps>();
const submitting = ref(false);

// 打卡結果。成功走 message，失敗走 errors.message
const result = computed(() => {
    const error = (page.props.errors as Record<string, string> | undefined)?.message;
    if (error) return { ok: false, text: error };
    if (page.props.message) return { ok: true, text: page.props.message };
    return null;
});

const buttonConfig = computed(() => {
    // 當天沒有值勤就不給簽，後端也會擋
    if (!page.props.hasDuty) {
        return { text: '今天沒有你的值勤', color: 'btn-secondary', icon: 'bi-calendar-x', disabled: true };
    }
    if (page.props.status === 'on_duty') {
        return { text: '我要簽退', color: 'btn-warning', icon: 'bi-box-arrow-right', disabled: false };
    }
    if (page.props.status === 'checked_out') {
        return { text: '今日已完成值勤', color: 'btn-secondary', icon: 'bi-check-circle-fill', disabled: true };
    }
    return { text: '我要簽到', color: 'btn-primary', icon: 'bi-box-arrow-in-right', disabled: false };
});

const handleCheck = () => {
    if (buttonConfig.value.disabled || submitting.value) return;

    submitting.value = true;
    router.post(route('duty.check'), {}, {
        preserveScroll: true,
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 mt-4">
                <div class="card-body text-center p-5">

                    <div class="mb-4 text-primary">
                        <i class="bi bi-phone" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="fw-bold mb-4">手機簽到</h3>

                    <div v-if="result" class="alert mb-4 rounded-3 fw-bold"
                        :class="result.ok ? 'alert-success' : 'alert-danger'">
                        <i class="bi me-2" :class="result.ok ? 'bi-check-circle' : 'bi-x-circle'"></i>
                        {{ result.text }}
                    </div>

                    <!-- 今天的值勤 -->
                    <div class="mb-4 p-3 rounded-3" :class="page.props.hasDuty ? 'bg-light' : 'bg-warning-subtle'">
                        <p class="text-muted mb-1 small">今日值勤</p>
                        <h5 class="fw-bold mb-0" :class="page.props.hasDuty ? 'text-dark' : 'text-warning-emphasis'">
                            <template v-if="page.props.hasDuty">
                                <i class="bi bi-geo-alt me-1"></i>{{ page.props.dutyLocation }}
                            </template>
                            <template v-else>今天沒有排班</template>
                        </h5>
                    </div>

                    <div class="mb-5 bg-light p-3 rounded-3">
                        <p class="text-muted mb-1 small">上次打卡時間</p>
                        <h4 class="fw-bold text-dark mb-0 font-monospace">
                            {{ page.props.lastRecordTime || '尚無紀錄' }}
                        </h4>
                    </div>

                    <button
                        class="btn btn-lg w-100 py-3 fw-bold fs-5 shadow-sm rounded-3 d-flex justify-content-center align-items-center gap-2"
                        :class="buttonConfig.color"
                        :disabled="buttonConfig.disabled || submitting"
                        @click="handleCheck">
                        <i class="bi" :class="buttonConfig.icon" style="font-size: 1.5rem;"></i>
                        {{ submitting ? '處理中…' : buttonConfig.text }}
                    </button>

                    <p class="text-muted small mt-4 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        簽到與簽退中間需間隔 2 分鐘
                    </p>

                    <!-- 目前不驗證地點，之後啟用時這裡會顯示認定結果 -->
                    <p class="text-muted small mt-2 mb-0" v-if="page.props.checksLocation">
                        <i class="bi bi-hdd-network me-1"></i>
                        來源 IP {{ page.props.terminal.ip }}
                        <span v-if="page.props.terminal.location">· 地點 {{ page.props.terminal.location }}</span>
                        <span v-else class="text-danger">· 這個 IP 尚未設定所屬地點</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.btn {
    transition: all 0.2s;
}
.btn:active:not(:disabled) {
    transform: scale(0.96);
}
</style>
