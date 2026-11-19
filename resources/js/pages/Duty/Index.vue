<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import type { DutyIndexPageProps, DutyStatus } from '../../types';

const page = usePage<DutyIndexPageProps>();

const STATUS_TEXT: Record<DutyStatus, string> = {
    absent: '未到',
    on_duty: '值勤中',
    checked_out: '簽退',
};

// --- 輪詢時鐘 ---
const currentTime = ref('');
let timer: ReturnType<typeof setInterval>;

const updateTime = () => {
    currentTime.value = new Date().toLocaleString('zh-TW', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
    });
};

onMounted(() => {
    updateTime();
    timer = setInterval(updateTime, 1000);
});
onUnmounted(() => clearInterval(timer));

// --- 打卡 ---
const cardInput = ref<HTMLInputElement | null>(null);
const cardId = ref('');
const submitting = ref(false);

// 打卡結果。成功走 message，失敗走 errors.message
const result = computed(() => {
    const error = (page.props.errors as Record<string, string> | undefined)?.message;
    if (error) return { ok: false, text: error };
    if (page.props.message) return { ok: true, text: page.props.message };
    return null;
});

const handleSwipe = () => {
    if (!cardId.value || submitting.value) return;

    submitting.value = true;
    router.put(route('check'), { inner_code: cardId.value }, {
        preserveScroll: true,
        onFinish: () => {
            submitting.value = false;
            cardId.value = '';
            // 感應器是當鍵盤用的，焦點要一直留在輸入框
            cardInput.value?.focus();
        },
    });
};

const stats = computed(() => {
    const list = page.props.dutyList;
    return {
        total: list.length,
        onDuty: list.filter(d => d.status === 'on_duty').length,
        absent: list.filter(d => d.status === 'absent').length,
    };
});

// 只顯示時分秒
const timeOnly = (value: string | null) => value ? value.slice(11, 19) || value : '—';
</script>

<template>
    <!-- 上半部：時間與打卡輸入區 -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-8 text-center">
            <div class="card shadow border-0 rounded-4 bg-white">
                <div class="card-body py-5">
                    <h5 class="text-muted mb-3">現在時間</h5>
                    <h1 class="display-3 fw-bold text-dark mb-4 font-monospace" style="letter-spacing: -2px;">
                        {{ currentTime }}
                    </h1>

                    <!-- 打卡結果 -->
                    <div v-if="result" class="alert d-flex align-items-center justify-content-center gap-2 w-75 mx-auto fw-bold rounded-3"
                        :class="result.ok ? 'alert-success' : 'alert-danger'">
                        <i class="bi" :class="result.ok ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                        {{ result.text }}
                    </div>

                    <div class="input-group input-group-lg w-75 mx-auto shadow-sm">
                        <input ref="cardInput" type="text" class="form-control focus-ring"
                            placeholder="請將卡片靠近感應區 (嗶卡)" v-model="cardId"
                            :disabled="submitting" @keyup.enter="handleSwipe" autofocus>
                        <button class="btn btn-primary px-4" type="button"
                            :disabled="submitting" @click="handleSwipe">打卡</button>
                    </div>

                    <p class="text-muted small mt-3 mb-0">
                        <i class="bi bi-hdd-network me-1"></i>
                        本機 IP {{ page.props.terminal.ip }}
                        <span v-if="page.props.terminal.location">
                            · 地點 <strong class="text-dark">{{ page.props.terminal.location }}</strong>
                        </span>
                        <span v-else class="text-danger fw-bold">
                            · 這個 IP 尚未設定所屬地點，無法打卡
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 下半部：今日值勤清單與注意事項 -->
    <div class="row g-4">

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-list-check me-2"></i>今日值勤清單</h5>
                    <span class="ms-auto text-muted small">
                        共 {{ stats.total }} 人 · 值勤中 {{ stats.onDuty }} · 未到 {{ stats.absent }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>學號</th>
                                    <th>人名</th>
                                    <th>地點</th>
                                    <th>簽到時間</th>
                                    <th>簽退時間</th>
                                    <th>狀態</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="duty in page.props.dutyList" :key="duty.id">
                                    <td class="font-monospace text-muted">{{ duty.stu_id }}</td>
                                    <td class="fw-bold">{{ duty.name }}</td>
                                    <td>{{ duty.location }}</td>
                                    <td class="font-monospace">{{ timeOnly(duty.checkin_time) }}</td>
                                    <td class="font-monospace">{{ timeOnly(duty.checkout_time) }}</td>
                                    <td>
                                        <span class="badge rounded-pill" :class="{
                                            'bg-secondary': duty.status === 'absent',
                                            'bg-success': duty.status === 'on_duty',
                                            'bg-primary': duty.status === 'checked_out'
                                        }">
                                            {{ STATUS_TEXT[duty.status] }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="page.props.dutyList.length === 0">
                                    <td colspan="6" class="text-muted py-5">
                                        今天沒有任何人排班
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>注意事項
                    </h5>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-3 d-flex align-items-center">
                            <span class="badge rounded-pill bg-secondary me-2" style="width: 72px;">未到</span>
                            <span class="text-muted small">尚未簽到</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <span class="badge rounded-pill bg-success me-2" style="width: 72px;">值勤中</span>
                            <span class="text-muted small">已簽到，尚未簽退</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <span class="badge rounded-pill bg-primary me-2" style="width: 72px;">簽退</span>
                            <span class="text-muted small">今日值勤已完成</span>
                        </li>
                    </ul>
                    <hr class="text-muted">
                    <ul class="text-muted small ps-3 mt-3">
                        <li class="mb-2"><strong>當天沒有排班的人無法打卡</strong>，請先確認值勤班表。</li>
                        <li class="mb-2">簽到與簽退中間有 <strong>兩分鐘</strong> 的緩衝時間。</li>
                        <li>如果遇到卡片無法感應，請立即通知人資／行政部處理。</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.focus-ring:focus {
    box-shadow: none;
    border-color: #dee2e6;
}
</style>
