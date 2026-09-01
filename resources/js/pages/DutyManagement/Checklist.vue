<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import { utils, writeFileXLSX } from 'xlsx';
import AlertModal from '../../components/AlertModal.vue';
import CheckRecordForm from '../../components/CheckRecordForm.vue';
import Pagination from '../../components/Pagination.vue';
import type { ChecklistPageProps, ChecklistRow } from '../../types';
import { toastErrorMessage, toastErrors, toastSuccessMessage } from '../../utils';

const page = usePage<ChecklistPageProps>();
const alertModal = ref<InstanceType<typeof AlertModal> | null>(null);

// 目前正在編輯的紀錄，null 表示沒有在編輯
const editing = ref<ChecklistRow | null>(null);
const exporting = ref(false);

const form = reactive({
    q: page.props.filters.q ?? '',
    date_from: page.props.filters.date_from ?? '',
    date_to: page.props.filters.date_to ?? '',
    checkin_location_id: page.props.filters.checkin_location_id ?? '',
    checkout_location_id: page.props.filters.checkout_location_id ?? '',
});

// 空字串的條件不送出去，網址才不會掛一堆空參數
const params = () => {
    const out: Record<string, string> = {};
    for (const [key, value] of Object.entries(form)) {
        if (value !== '' && value !== null) out[key] = String(value);
    }
    return out;
};

const onSearch = () => {
    router.get(route('duty-management.checklist'), params(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const onReset = () => {
    form.q = '';
    form.date_from = '';
    form.date_to = '';
    form.checkin_location_id = '';
    form.checkout_location_id = '';
    onSearch();
};

const hasFilters = () => Object.keys(params()).length > 0;

const onEdit = (row: ChecklistRow) => {
    editing.value = row;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const onDelete = (row: ChecklistRow) => {
    alertModal.value?.confirm(
        `確定要刪除「${row.user?.name ?? '未知人員'}」在 ${row.checkin_time ?? '—'} 的紀錄嗎？`,
        () => {
            router.delete(route('checklist.destroy', { checkList: row.id }), {
                preserveScroll: true,
                onSuccess: () => {
                    toastSuccessMessage('刪除成功');
                    editing.value = null;
                },
                onError: (err) => toastErrors(err),
            });
        },
        '刪除紀錄'
    );
};

const buildSheet = (rows: ChecklistRow[]) => {
    const data = rows.map((row) => ({
        stu_id: row.user?.stu_id ?? '',
        name: row.user?.name ?? '',
        checkin_time: row.checkin_time ?? '',
        checkin_location: row.checkin_location ?? '',
        checkin_type: row.checkin_manual ? '手動' : '刷卡',
        checkout_time: row.checkout_time ?? '',
        checkout_location: row.checkout_location ?? '',
        checkout_type: row.checkout_manual ? '手動' : '刷卡',
        checkin_ip: row.checkin_ip,
        checkout_ip: row.checkout_ip,
    }));

    const book = utils.book_new();
    const sheet = utils.json_to_sheet(data, { skipHeader: true });
    utils.sheet_add_aoa(sheet, [[
        '學號', '姓名', '簽到時間', '簽到地點', '簽到方式',
        '簽退時間', '簽退地點', '簽退方式', '簽到IP', '簽退IP'
    ]], { origin: 'A1' });
    utils.book_append_sheet(book, sheet, '簽到紀錄');

    const stamp = [form.date_from || 'All', form.date_to || 'All', form.q || 'All'].join('_');
    writeFileXLSX(book, `簽到紀錄_${stamp}.xlsx`);
};

// 匯出要的是「所有符合條件的紀錄」而不是當頁那 50 筆，
// 所以用 partial reload 去要後端那個平常不載入的 exportRows
const onExport = () => {
    exporting.value = true;
    router.reload({
        only: ['exportRows'],
        data: params(),
        onSuccess: () => {
            const rows = page.props.exportRows ?? [];
            if (rows.length === 0) {
                toastErrorMessage('沒有符合條件的紀錄可以匯出');
                return;
            }
            buildSheet(rows);
            toastSuccessMessage(`已匯出 ${rows.length} 筆紀錄`);
        },
        onError: (err) => toastErrors(err),
        onFinish: () => { exporting.value = false; },
    });
};
</script>

<template>
    <AlertModal ref="alertModal" />

    <div class="sys-container">

        <header class="sys-header">
            <div class="d-flex align-items-center gap-3">
                <Link :href="route('duty-management.index')" class="back-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                </Link>
                <div>
                    <h1>簽到表總覽</h1>
                    <p>查詢所有簽到紀錄、修改內容，或匯出報表</p>
                </div>
            </div>
        </header>

        <!-- 編輯既有紀錄時才展開 -->
        <section class="control-panel edit-panel" v-if="editing">
            <div class="panel-header">
                <div class="icon-wrapper edit-mode">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </div>
                <h2 class="panel-title">編輯紀錄 #{{ editing.id }}</h2>
            </div>
            <CheckRecordForm
                :record="editing"
                :locations="page.props.locations"
                @saved="editing = null"
                @cancel="editing = null"
            />
        </section>

        <section class="list-container">

            <!-- 查詢：全部條件排成一列，超出就自動換行 -->
            <form class="toolbar" @submit.prevent="onSearch">
                <div class="field grow">
                    <label>姓名或學號</label>
                    <input type="text" v-model="form.q" placeholder="輸入關鍵字">
                </div>
                <div class="field">
                    <label>簽到日期起</label>
                    <input type="date" v-model="form.date_from">
                </div>
                <div class="field">
                    <label>簽到日期迄</label>
                    <input type="date" v-model="form.date_to">
                </div>
                <div class="field">
                    <label>簽到地點</label>
                    <select v-model="form.checkin_location_id">
                        <option value="">全部</option>
                        <option v-for="location in page.props.locations" :key="location.id" :value="location.id">
                            {{ location.name }}
                        </option>
                    </select>
                </div>
                <div class="field">
                    <label>簽退地點</label>
                    <select v-model="form.checkout_location_id">
                        <option value="">全部</option>
                        <option v-for="location in page.props.locations" :key="location.id" :value="location.id">
                            {{ location.name }}
                        </option>
                    </select>
                </div>
                <div class="field actions">
                    <button type="submit" class="sys-btn-primary">查詢</button>
                </div>
            </form>

            <div class="result-bar">
                <span>共 <strong>{{ page.props.checklists.total }}</strong> 筆</span>
                <span class="divider-dot">·</span>
                <span>第 {{ page.props.checklists.current_page }} / {{ page.props.checklists.last_page }} 頁</span>

                <div class="bar-actions">
                    <button type="button" class="sys-btn-ghost" v-if="hasFilters()" @click="onReset">
                        清除條件
                    </button>
                    <button type="button" class="export-btn" :disabled="exporting" @click="onExport">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        {{ exporting ? '匯出中…' : '匯出報表' }}
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-list">
                    <thead>
                        <tr>
                            <th>學號</th>
                            <th>姓名</th>
                            <th>簽到</th>
                            <th>簽退</th>
                            <th class="align-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in page.props.checklists.data" :key="row.id"
                            :class="{ 'is-selected': editing?.id === row.id }">
                            <td class="mono">{{ row.user?.stu_id ?? '—' }}</td>
                            <td><div class="user-name">{{ row.user?.name ?? '—' }}</div></td>
                            <td>
                                <div class="stamp">{{ row.checkin_time ?? '—' }}</div>
                                <div class="stamp-meta">
                                    <span class="loc-badge">{{ row.checkin_location ?? '未分類' }}</span>
                                    <span class="type-badge" :class="row.checkin_manual ? 'type-manual' : 'type-card'">
                                        {{ row.checkin_manual ? '手動' : '刷卡' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="stamp">{{ row.checkout_time ?? '—' }}</div>
                                <div class="stamp-meta" v-if="row.checkout_time">
                                    <span class="loc-badge">{{ row.checkout_location ?? '未分類' }}</span>
                                    <span class="type-badge" :class="row.checkout_manual ? 'type-manual' : 'type-card'">
                                        {{ row.checkout_manual ? '手動' : '刷卡' }}
                                    </span>
                                </div>
                            </td>
                            <td class="align-right">
                                <div class="row-actions">
                                    <button class="action-btn edit" @click="onEdit(row)" title="編輯">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button class="action-btn delete" @click="onDelete(row)" title="刪除">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="page.props.checklists.data.length === 0">
                            <td colspan="5">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                                    <h3>查無符合條件的紀錄</h3>
                                    <p>換個關鍵字或日期範圍再試一次。</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :meta="page.props.checklists" />
        </section>
    </div>
</template>

<style scoped>
.back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text-muted);
    flex-shrink: 0;
    transition: all 0.15s ease;
}
.back-btn:hover { color: var(--primary); border-color: var(--primary); }
.back-btn svg { width: 18px; height: 18px; }

.edit-panel { margin-bottom: 24px; }

/* 查詢列 */
.toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 12px;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}
.field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
    flex: 0 0 150px;
}
.field.grow { flex: 1 1 200px; }
.field.actions { flex: 0 0 auto; }
/* 查詢鈕的高度對齊旁邊的輸入框 */
.field.actions .sys-btn-primary { padding: 9px 24px; }
.field label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
}
.field input,
.field select {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    padding: 9px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    font-size: 0.92rem;
    background: var(--bg-color);
    color: var(--text-main);
    outline: none;
    transition: all 0.15s ease;
}
.field input:focus,
.field select:focus {
    border-color: var(--primary);
    background: var(--surface);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.result-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px 24px;
    font-size: 0.9rem;
    color: var(--text-muted);
    background: var(--bg-color);
}
.result-bar strong { color: var(--text-main); }
.divider-dot { opacity: 0.5; }
/* 空間不夠時整組換到下一行，不要被壓縮成一個字寬 */
.bar-actions {
    margin-left: auto;
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.export-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    white-space: nowrap;
    padding: 8px 16px;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--surface);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--success);
    cursor: pointer;
    transition: all 0.15s ease;
}
.export-btn:hover:not(:disabled) {
    background: var(--success-bg);
    border-color: var(--success);
}
.export-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.export-btn svg { width: 16px; height: 16px; }

.mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    color: var(--text-muted);
}
.user-name {
    font-weight: 700;
    color: var(--text-main);
}

.stamp {
    font-size: 0.92rem;
    color: var(--text-main);
    white-space: nowrap;
}
.stamp-meta {
    display: flex;
    gap: 6px;
    margin-top: 4px;
}
.loc-badge,
.type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
}
.loc-badge {
    background: var(--bg-color);
    color: var(--text-muted);
}
.type-card {
    background: #eff6ff;
    color: var(--primary);
}
.type-manual {
    background: #fef3c7;
    color: #b45309;
}

@media (max-width: 640px) {
    .field, .field.grow { flex: 1 1 100%; }
    .bar-actions { width: 100%; margin-left: 0; }
}
</style>
