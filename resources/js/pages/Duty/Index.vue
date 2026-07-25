<!-- 檔案位置: resources/js/Pages/Index.vue -->
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';


// --- 時間與打卡邏輯 (跟之前一模一樣，保留在這裡) ---
const currentTime = ref('');
let timer: ReturnType<typeof setInterval>;

const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleString('zh-TW', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
};

onMounted(() => {
    updateTime();
    timer = setInterval(updateTime, 1000);
});

onUnmounted(() => { clearInterval(timer); });

const cardId = ref('');
const handleSwipe = () => {
    if (!cardId.value) return;
    console.log('準備送出打卡資料:', cardId.value);
    cardId.value = '';
};
</script>

<template>
    <!-- 使用 Layout 包覆整個頁面內容 -->
        <!-- 上半部：時間與打卡輸入區 -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-8 text-center">
                <div class="card shadow border-0 rounded-4 bg-white">
                    <div class="card-body py-5">
                        <h5 class="text-muted mb-3">現在時間</h5>
                        <h1 class="display-3 fw-bold text-dark mb-4 font-monospace" style="letter-spacing: -2px;">
                            {{ currentTime }}
                        </h1>

                        <div class="input-group input-group-lg w-75 mx-auto shadow-sm">
                            
                            <input type="text" class="form-control border-start-0 focus-ring"
                                placeholder="請將卡片靠近感應區 (嗶卡)" v-model="cardId" @keyup.enter="handleSwipe" autofocus>
                            <button class="btn btn-primary px-4" type="button" @click="handleSwipe">打卡</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 下半部：紀錄與注意事項 -->
        <div class="row g-4">

            <!-- 左欄：打卡紀錄清單 -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="fw-bold"><i class="bi bi-list-check me-2"></i>今日打卡紀錄</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>人員</th>
                                        <th>打卡時間</th>
                                        <th>打卡狀態</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>王小明</td>
                                        <td class="text-muted">下午 8:30:15</td>
                                        <td><span class="badge bg-success">簽到成功</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右欄：注意事項 -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>注意事項
                        </h5>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-3 d-flex align-items-center"><span class="badge bg-success me-2"
                                    style="width: 80px;">簽到成功</span><span class="text-muted small">圖標為綠色</span></li>
                            <li class="mb-3 d-flex align-items-center"><span class="badge bg-primary me-2"
                                    style="width: 80px;">簽退成功</span><span class="text-muted small">圖標為藍色</span></li>
                            <li class="mb-3 d-flex align-items-center"><span class="badge bg-danger me-2"
                                    style="width: 80px;">簽到失敗</span><span class="text-muted small">圖標為紅色</span></li>
                        </ul>
                        <hr class="text-muted">
                        <ul class="text-muted small ps-3 mt-3">
                            <li class="mb-2">簽到與簽退中間有 <strong>兩分鐘</strong> 的緩衝時間，若簽退後立刻再簽到則無效。</li>
                            <li>如果遇到卡片無法感應（不能刷），請立即通知人資/行政部做後續處理。</li>
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