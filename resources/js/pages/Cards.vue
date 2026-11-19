<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Card, CardPageProps } from '../types';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { toastErrors, toastSuccessMessage } from '../utils';
import AlertModal from '../components/AlertModal.vue';

const page = usePage<CardPageProps>();
const alertModal = ref<InstanceType<typeof AlertModal> | null>(null);

const cf = useForm<{
    inner_code: string,
    user_id?: number,
    status: boolean
}>({
    inner_code: "",
    user_id: undefined,
    status: true
});

const selectedCard = ref<Card>();

const onSelect = (card: Card) => {
    selectedCard.value = card;
    cf.defaults({
        inner_code: card.inner_code,
        user_id: card.user_id,
        status: card.status
    });
    cf.reset();
}

const onCancel = () => {
    selectedCard.value = undefined;
    cf.defaults({
        inner_code: "",
        user_id: undefined,
        status: true
    });
    cf.reset();
}

const onSubmit = () => {
    if (selectedCard.value) {
        cf.put(route('cards.update', { card: selectedCard.value.id }), {
            onSuccess: () => {
                toastSuccessMessage("修改成功");
                onCancel();
            },
            onError: (err) => {
                toastErrors(err);
            }
        })
    }
    else {
        cf.post(route('cards.store'), {
            onSuccess: () => {
                toastSuccessMessage("新增成功");
                onCancel();
            },
            onError: (err) => {
                toastErrors(err);
            }
        })
    }
}

const confirmDelete = (card: Card) => {
    alertModal.value?.confirm(
        `確定要刪除卡號「${card.inner_code}」嗎？此動作無法復原。`,
        () => {
            cf.delete(route('cards.destroy', { card: card.id }), {
                onSuccess: () => {
                    toastSuccessMessage("刪除成功");
                    onCancel();
                },
                onError: (err) => {
                    toastErrors(err);
                }
            });
        },
        '刪除確認'
    );
}

// --- 搜尋 / 篩選 / 分頁 ---
const search = ref('');
const statusFilter = ref<'all' | 'up' | 'down'>('all');
const currentPage = ref(1);
const perPage = 10;

const filteredCards = computed(() => {
    const keyword = search.value.trim().toLowerCase();
    return (page.props.cards ?? []).filter(card => {
        if (statusFilter.value === 'up' && !card.status) return false;
        if (statusFilter.value === 'down' && card.status) return false;
        if (!keyword) return true;
        return (
            card.inner_code.toLowerCase().includes(keyword) ||
            (card.owner?.stu_id?.toLowerCase().includes(keyword) ?? false) ||
            (card.owner?.name?.toLowerCase().includes(keyword) ?? false)
        );
    });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredCards.value.length / perPage)));

const pagedCards = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredCards.value.slice(start, start + perPage);
});

watch([search, statusFilter], () => {
    currentPage.value = 1;
});

const goToPage = (p: number) => {
    if (p < 1 || p > totalPages.value) return;
    currentPage.value = p;
}
</script>

<template>
    <AlertModal ref="alertModal" />

    <div class="sys-container">
        
        <!-- 頂部標題區 -->
        <header class="sys-header">
            <div>
                <h1>門禁卡片管理</h1>
                <p>設定、追蹤與分配系統內的所有實體卡片</p>
            </div>
        </header>

        <!-- 雙欄佈局容器 -->
        <div class="layout-split">
            
            <!-- 左欄：主體清單區塊 -->
            <main class="list-column">
                <section class="list-container">
                    
                    <!-- 工具列 (搜尋與篩選) -->
                    <div class="toolbar">
                        <div class="search-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" v-model="search" placeholder="搜尋學號、姓名、內碼...">
                        </div>
                        <div class="filter-tabs">
                            <button :class="{ active: statusFilter === 'all' }" @click="statusFilter = 'all'">全部</button>
                            <button :class="{ active: statusFilter === 'up' }" @click="statusFilter = 'up'">啟用中</button>
                            <button :class="{ active: statusFilter === 'down' }" @click="statusFilter = 'down'">已停用</button>
                        </div>
                    </div>

                    <!-- 現代化數據清單 -->
                    <div class="table-responsive">
                        <table class="data-list">
                            <thead>
                                <tr>
                                    <th>卡片擁有者</th>
                                    <th>RFID 內碼</th>
                                    <th>當前狀態</th>
                                    <th class="align-right">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="card in pagedCards" :key="card.id" :class="{ 'is-inactive': !card.status, 'is-selected': selectedCard?.id === card.id }">
                                    <td>
                                        <div class="owner-info">
                                            <div class="details">
                                                <div class="name">{{ card.owner?.name || '尚未綁定' }}</div>
                                                <div class="stu-id">{{ card.owner?.stu_id || '---' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="">{{ card.inner_code }}</div>
                                    </td>
                                    <td>
                                        <span class="status-badge" :class="card.status ? 'badge-up' : 'badge-down'">
                                            <span class="status-dot"></span>
                                            {{ card.status ? 'UP' : 'DOWN' }}
                                        </span>
                                    </td>
                                    <td class="align-right">
                                        <div class="row-actions">
                                            <button class="action-btn edit" @click="onSelect(card)" title="編輯卡片">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            </button>
                                            <button class="action-btn delete" @click="confirmDelete(card)" title="刪除卡片">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- 空狀態 -->
                                <tr v-if="pagedCards.length === 0">
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                                            <h3>查無資料</h3>
                                            <p>找不到符合目前搜尋條件的卡片紀錄。</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 自訂分頁器 -->
                    <nav class="custom-pagination" v-if="totalPages > 1">
                        <span class="page-info">第 <strong>{{ currentPage }}</strong> 頁，共 <strong>{{ totalPages }}</strong> 頁</span>
                        <div class="pagination-controls">
                            <button :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">上一頁</button>
                            <div class="page-numbers">
                                <button 
                                    v-for="p in totalPages" 
                                    :key="p" 
                                    :class="{ active: p === currentPage }" 
                                    @click="goToPage(p)"
                                >
                                    {{ p }}
                                </button>
                            </div>
                            <button :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">下一頁</button>
                        </div>
                    </nav>
                </section>
            </main>

            <!-- 右欄：表單控制面板 -->
            <aside class="form-column">
                <section class="control-panel" :class="{ 'is-editing': selectedCard }">
                    <div class="panel-header">
                        <div class="icon-wrapper" :class="selectedCard ? 'edit-mode' : 'create-mode'">
                            <svg v-if="selectedCard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </div>
                        <h2 class="panel-title">{{ selectedCard ? '編輯卡片' : '新增卡片' }}</h2>
                    </div>
                    
                    <form @submit.prevent="onSubmit" class="vertical-form">
                        <div class="input-group">
                            <label>卡號內碼</label>
                            <input type="text" v-model="cf.inner_code" placeholder="輸入 RFID 內碼" required>
                        </div>

                        <div class="input-group">
                            <label>卡片擁有者</label>
                            <div class="select-wrapper">
                                <select v-model="cf.user_id" required>
                                    <option :value="undefined" disabled>-- 請選擇人員 --</option>
                                    <option v-for="person in page.props.people" :key="person.id" :value="person.id">
                                        {{ person.stu_id }} - {{ person.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>卡片狀態</label>
                            <div class="status-toggle">
                                <label class="toggle-option">
                                    <input type="radio" :value="true" v-model="cf.status">
                                    <span class="toggle-btn up">UP</span>
                                </label>
                                <label class="toggle-option">
                                    <input type="radio" :value="false" v-model="cf.status">
                                    <span class="toggle-btn down">DOWN</span>
                                </label>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="action-group">
                            <button type="submit" class="sys-btn-primary" :disabled="cf.processing">
                                {{ selectedCard ? '儲存變更' : '新增卡片' }}
                            </button>
                            <button type="button" class="sys-btn-ghost" v-if="selectedCard" @click="onCancel">
                                取消
                            </button>
                        </div>
                    </form>
                </section>
            </aside>

        </div>
    </div>
</template>

<style scoped>
/* 這個頁面獨有的樣式，共用的設計系統在 resources/css/system-settings.css */

/* 工具列 */
.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--surface);
    flex-wrap: wrap;
    gap: 16px;
}
.search-box {
    position: relative;
    width: 280px;
}
.search-box svg {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--text-muted);
}
.search-box input {
    width: 100%;
    padding: 8px 16px 8px 38px;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    outline: none;
    background: var(--bg-color);
    transition: all 0.2s;
}
.search-box input:focus { 
    background: var(--surface);
    border-color: var(--primary); 
}

.filter-tabs {
    display: flex;
    gap: 4px;
}
.filter-tabs button {
    border: 1px solid transparent;
    background: transparent;
    padding: 6px 14px;
    border-radius: 99px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
}
.filter-tabs button:hover {
    background: var(--bg-color);
}
.filter-tabs button.active {
    background: var(--bg-color);
    border-color: var(--border);
    color: var(--text-main);
}

/* 表格內容樣式 */
.owner-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e0e7ff;
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
}
.is-inactive .avatar { background: var(--border); color: var(--text-muted); }
.owner-info .name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--text-main);
}
.owner-info .stu-id {
    font-size: 0.8rem;
    color: var(--text-muted);
}
.code-value {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 500;
    color: var(--text-main);
    background: var(--bg-color);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9rem;
}

/* 狀態標籤 */
/* 左欄分頁器 */
.custom-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-top: 1px solid var(--border);
    background: var(--surface);
    flex-wrap: wrap;
    gap: 16px;
}
.page-info {
    font-size: 0.875rem;
    color: var(--text-muted);
}
.page-info strong {
    color: var(--text-main);
}
.pagination-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}
.pagination-controls button {
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 6px 12px;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-main);
    cursor: pointer;
    transition: all 0.2s;
}
.pagination-controls button:disabled { opacity: 0.4; cursor: not-allowed; }
.pagination-controls button:hover:not(:disabled) { background: var(--bg-color); }
.page-numbers { display: flex; gap: 4px; }
.page-numbers button.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* 響應式：手機版調整工具列與表格 */
@media (max-width: 640px) {
    .toolbar { flex-direction: column; align-items: stretch; }
    .search-box { width: 100%; }
    .custom-pagination { flex-direction: column; justify-content: center; }
}
</style>