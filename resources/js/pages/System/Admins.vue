<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AlertModal from '../../components/AlertModal.vue';
import type { AdminFilter, AdminsPageProps, ManagedUser } from '../../types';
import { toastErrors, toastSuccessMessage } from '../../utils';

const page = usePage<AdminsPageProps>();
const alertModal = ref<InstanceType<typeof AlertModal> | null>(null);

const keyword = ref(page.props.filters.q ?? '');
const processingId = ref<number | null>(null);

const myId = () => page.props.auth.user?.id ?? null;

// 搜尋與篩選都走同一個查詢，只是帶不同參數
const query = (q: string, filter: AdminFilter) => {
    router.get(route('system.admins.index'), { q: q || undefined, filter },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const onSearch = () => query(keyword.value, page.props.filters.filter);
const onFilter = (filter: AdminFilter) => query(keyword.value, filter);

const onReset = () => {
    keyword.value = '';
    query('', 'all');
};

const submitToggle = (user: ManagedUser, next: boolean) => {
    processingId.value = user.id;
    router.put(route('system.admins.update', { user: user.id }),
        { is_admin: next },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toastSuccessMessage(next
                    ? `已授予 ${user.name} 管理員權限`
                    : `已移除 ${user.name} 的管理員權限`);
            },
            onError: (err) => toastErrors(err),
            onFinish: () => { processingId.value = null; },
        }
    );
};

const onToggle = (user: ManagedUser) => {
    if (user.id === myId()) {
        alertModal.value?.show('不能變更自己的管理員權限，請由其他管理員操作。', '無法變更');
        return;
    }

    const next = !user.is_admin;
    const message = next
        ? `確定要授予「${user.name}」管理員權限嗎？對方將可以管理人員、地點與所有差勤資料。`
        : `確定要移除「${user.name}」的管理員權限嗎？`;

    alertModal.value?.confirm(message, () => submitToggle(user, next), next ? '授予管理員' : '移除管理員');
};
</script>

<template>
    <AlertModal ref="alertModal" />

    <div class="sys-container">

        <header class="sys-header">
            <div>
                <h1>管理員名單</h1>
                <p>賦予或移除管理員權限。管理員可以管理人員、地點、差勤資料與系統設定</p>
            </div>
        </header>

        <section class="list-container">

            <!-- 搜尋與篩選 -->
            <div class="toolbar">
                <form class="search-box" @submit.prevent="onSearch">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" v-model="keyword" placeholder="搜尋姓名或學號">
                    <button type="submit" class="sys-btn-primary">搜尋</button>
                </form>

                <div class="filter-tabs">
                    <button type="button" :class="{ 'is-active': page.props.filters.filter === 'all' }"
                        @click="onFilter('all')">全部</button>
                    <button type="button" :class="{ 'is-active': page.props.filters.filter === 'admin' }"
                        @click="onFilter('admin')">管理員</button>
                    <button type="button" :class="{ 'is-active': page.props.filters.filter === 'normal' }"
                        @click="onFilter('normal')">一般部員</button>
                </div>
            </div>

            <div class="result-bar">
                <span>符合條件 <strong>{{ page.props.users.length }}</strong> 人</span>
                <span class="divider-dot">·</span>
                <span>系統目前共有 <strong>{{ page.props.adminCount }}</strong> 位管理員</span>
                <button type="button" class="sys-btn-ghost ms-auto"
                    v-if="page.props.filters.q || page.props.filters.filter !== 'all'"
                    @click="onReset">清除條件</button>
            </div>

            <div class="table-responsive">
                <table class="data-list">
                    <thead>
                        <tr>
                            <th>學號</th>
                            <th>姓名</th>
                            <th>帳號狀態</th>
                            <th>權限</th>
                            <th class="align-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in page.props.users" :key="user.id"
                            :class="{ 'is-inactive': !user.status, 'is-selected': user.id === myId() }">
                            <td class="mono">{{ user.stu_id }}</td>
                            <td>
                                <div class="user-name">
                                    {{ user.name }}
                                    <span class="self-tag" v-if="user.id === myId()">你自己</span>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge" :class="user.status ? 'badge-up' : 'badge-down'">
                                    <span class="status-dot"></span>
                                    {{ user.status ? '啟用中' : '已停用' }}
                                </span>
                            </td>
                            <td>
                                <span class="role-badge" :class="user.is_admin ? 'role-admin' : 'role-member'">
                                    {{ user.is_admin ? '管理員' : '一般部員' }}
                                </span>
                            </td>
                            <td class="align-right">
                                <button type="button" class="toggle-role"
                                    :class="user.is_admin ? 'to-member' : 'to-admin'"
                                    :disabled="user.id === myId() || processingId === user.id"
                                    @click="onToggle(user)">
                                    {{ user.is_admin ? '移除權限' : '授予管理員' }}
                                </button>
                            </td>
                        </tr>

                        <tr v-if="page.props.users.length === 0">
                            <td colspan="5">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="8.5" cy="7" r="4"></circle>
                                        <path d="M20 8v6M23 11h-6"></path>
                                    </svg>
                                    <h3>查無符合的人員</h3>
                                    <p>換個關鍵字，或清除篩選條件再試一次。</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* 共用的設計系統在 resources/css/system-settings.css，這裡只放這頁獨有的 */

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}

.search-box {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1 1 320px;
}
.search-box svg {
    width: 18px;
    height: 18px;
    color: var(--text-muted);
    flex-shrink: 0;
}
.search-box input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.search-box input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
/* 搜尋鈕不要被輸入框擠壓，並對齊輸入框高度 */
.search-box .sys-btn-primary {
    flex-shrink: 0;
    padding: 10px 22px;
}

.filter-tabs {
    display: flex;
    gap: 4px;
    padding: 4px;
    background: var(--bg-color);
    border-radius: var(--radius-md);
}
.filter-tabs button {
    padding: 8px 16px;
    border: none;
    background: transparent;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}
.filter-tabs button:hover { color: var(--text-main); }
.filter-tabs button.is-active {
    background: var(--surface);
    color: var(--primary);
    box-shadow: var(--shadow-sm);
}

.result-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    font-size: 0.9rem;
    color: var(--text-muted);
    background: var(--bg-color);
}
.result-bar strong { color: var(--text-main); }
.divider-dot { opacity: 0.5; }
.ms-auto { margin-left: auto; }
.result-bar .sys-btn-ghost {
    flex-shrink: 0;
    padding: 7px 14px;
    font-size: 0.85rem;
}

.mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    color: var(--text-muted);
}

.user-name {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: var(--text-main);
}
.self-tag {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 999px;
    background: #eff6ff;
    color: var(--primary);
}

.role-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
}
.role-admin {
    background: #eff6ff;
    color: var(--primary);
}
.role-member {
    background: var(--bg-color);
    color: var(--text-muted);
}

.toggle-role {
    padding: 8px 16px;
    border-radius: var(--radius-md);
    border: 1px solid transparent;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.toggle-role:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.toggle-role.to-admin {
    background: var(--primary);
    color: #fff;
}
.toggle-role.to-admin:hover:not(:disabled) { background: var(--primary-hover); }
.toggle-role.to-member {
    background: var(--surface);
    color: var(--danger);
    border-color: var(--border);
}
.toggle-role.to-member:hover:not(:disabled) {
    background: var(--danger-bg);
    border-color: var(--danger);
}

@media (max-width: 640px) {
    .toolbar { flex-direction: column; align-items: stretch; }
    .filter-tabs { justify-content: space-between; }
}
</style>
