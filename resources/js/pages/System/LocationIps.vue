<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AlertModal from '../../components/AlertModal.vue';
import type { LocationIpRow, LocationIpsPageProps } from '../../types';
import { toastErrors, toastSuccessMessage } from '../../utils';

const page = usePage<LocationIpsPageProps>();
const alertModal = ref<InstanceType<typeof AlertModal> | null>(null);

const editing = ref<LocationIpRow | null>(null);

const blank = () => ({
    location_id: page.props.locations[0]?.id ?? null,
    ip_address: '',
    description: '',
    status: true,
});

const f = useForm<ReturnType<typeof blank>>(blank());

// 目前這台裝置的 IP，方便管理員直接把它加進來
const myIp = computed(() => page.props.ip);
const myIpRegistered = computed(() => page.props.ips.some(i => i.ip_address === myIp.value));

const useMyIp = () => { f.ip_address = myIp.value; };

const onSelect = (row: LocationIpRow) => {
    editing.value = row;
    f.defaults({
        location_id: row.location_id,
        ip_address: row.ip_address,
        description: row.description ?? '',
        status: row.status,
    });
    f.reset();
    f.clearErrors();
};

const onCancel = () => {
    editing.value = null;
    f.defaults(blank());
    f.reset();
    f.clearErrors();
};

const onSubmit = () => {
    const done = (message: string) => () => {
        toastSuccessMessage(message);
        onCancel();
    };

    if (editing.value) {
        f.put(route('system.location-ips.update', { locationIp: editing.value.id }), {
            preserveScroll: true,
            onSuccess: done('已更新'),
            onError: (err) => toastErrors(err),
        });
    } else {
        f.post(route('system.location-ips.store'), {
            preserveScroll: true,
            onSuccess: done('已新增'),
            onError: (err) => toastErrors(err),
        });
    }
};

const onDelete = (row: LocationIpRow) => {
    alertModal.value?.confirm(
        `確定要刪除「${row.ip_address}」嗎？從這個 IP 打卡將無法判斷所在地點。`,
        () => {
            router.delete(route('system.location-ips.destroy', { locationIp: row.id }), {
                preserveScroll: true,
                onSuccess: () => {
                    toastSuccessMessage('已刪除');
                    onCancel();
                },
                onError: (err) => toastErrors(err),
            });
        },
        '刪除 IP'
    );
};

// 依地點分組顯示，一眼看得出哪個地點還沒設定
const grouped = computed(() =>
    page.props.locations.map(location => ({
        ...location,
        ips: page.props.ips.filter(i => i.location_id === location.id),
    }))
);
</script>

<template>
    <AlertModal ref="alertModal" />

    <div class="sys-container">

        <header class="sys-header">
            <div class="d-flex align-items-center gap-3">
                <Link :href="route('system.index')" class="back-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                </Link>
                <div>
                    <h1>地點 IP 設定</h1>
                    <p>打卡時用來判斷這台機器在哪個地點。一個 IP 只能對應一個地點</p>
                </div>
            </div>
        </header>

        <div class="layout-split">

            <main class="list-column">
                <section class="list-container">

                    <div class="my-ip" :class="{ 'is-known': myIpRegistered }">
                        <div>
                            <span class="my-ip-label">你目前的 IP</span>
                            <span class="my-ip-value">{{ myIp }}</span>
                        </div>
                        <span class="my-ip-state" v-if="myIpRegistered">已設定</span>
                        <button v-else type="button" class="my-ip-add" @click="useMyIp">帶入右側表單</button>
                    </div>

                    <div v-for="group in grouped" :key="group.id" class="group">
                        <div class="group-head">
                            <h3>{{ group.name }}</h3>
                            <span class="badge-count">{{ group.ips.length }} 個 IP</span>
                            <span class="badge-off" v-if="!group.status">地點已停用</span>
                        </div>

                        <div class="table-responsive" v-if="group.ips.length">
                            <table class="data-list">
                                <thead>
                                    <tr>
                                        <th>IP 位址</th>
                                        <th>說明</th>
                                        <th>狀態</th>
                                        <th class="align-right">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in group.ips" :key="row.id"
                                        :class="{ 'is-inactive': !row.status, 'is-selected': editing?.id === row.id }">
                                        <td>
                                            <span class="ip">{{ row.ip_address }}</span>
                                            <span class="here" v-if="row.ip_address === myIp">你在這裡</span>
                                        </td>
                                        <td class="desc">{{ row.description || '—' }}</td>
                                        <td>
                                            <span class="status-badge" :class="row.status ? 'badge-up' : 'badge-down'">
                                                <span class="status-dot"></span>
                                                {{ row.status ? '啟用中' : '已停用' }}
                                            </span>
                                        </td>
                                        <td class="align-right">
                                            <div class="row-actions">
                                                <button class="action-btn edit" @click="onSelect(row)" title="編輯">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                                </button>
                                                <button class="action-btn delete" @click="onDelete(row)" title="刪除">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="group-empty" v-else>
                            這個地點還沒有設定任何 IP，從這裡打卡會被拒絕。
                        </p>
                    </div>
                </section>
            </main>

            <aside class="form-column">
                <section class="control-panel" :class="{ 'is-editing': editing }">
                    <div class="panel-header">
                        <div class="icon-wrapper" :class="editing ? 'edit-mode' : 'create-mode'">
                            <svg v-if="editing" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </div>
                        <h2 class="panel-title">{{ editing ? '編輯 IP' : '新增 IP' }}</h2>
                    </div>

                    <form @submit.prevent="onSubmit" class="vertical-form">
                        <div class="input-group">
                            <label>所屬地點</label>
                            <select v-model="f.location_id" required>
                                <option v-for="location in page.props.locations" :key="location.id" :value="location.id">
                                    {{ location.name }}{{ location.status ? '' : '（已停用）' }}
                                </option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>IP 位址</label>
                            <input type="text" v-model="f.ip_address" placeholder="例如 10.21.51.7" required>
                            <button type="button" class="inline-link" v-if="!editing && !myIpRegistered" @click="useMyIp">
                                帶入我目前的 IP（{{ myIp }}）
                            </button>
                        </div>

                        <div class="input-group">
                            <label>說明</label>
                            <input type="text" v-model="f.description" placeholder="例如 進德打卡機" maxlength="100">
                        </div>

                        <div class="input-group">
                            <label>狀態</label>
                            <div class="status-toggle">
                                <label class="toggle-option">
                                    <input type="radio" :value="true" v-model="f.status">
                                    <span class="toggle-btn up">啟用</span>
                                </label>
                                <label class="toggle-option">
                                    <input type="radio" :value="false" v-model="f.status">
                                    <span class="toggle-btn down">停用</span>
                                </label>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="action-group">
                            <button type="submit" class="sys-btn-primary" :disabled="f.processing">
                                {{ editing ? '儲存變更' : '新增' }}
                            </button>
                            <button type="button" class="sys-btn-ghost" v-if="editing" @click="onCancel">取消</button>
                        </div>
                    </form>
                </section>
            </aside>

        </div>
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

.my-ip {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 24px;
    background: #fef3c7;
    border-bottom: 1px solid var(--border);
    font-size: 0.9rem;
}
.my-ip.is-known { background: var(--success-bg); }
.my-ip-label { color: var(--text-muted); margin-right: 8px; }
.my-ip-value {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-weight: 700;
    color: var(--text-main);
}
.my-ip-state {
    margin-left: auto;
    font-weight: 700;
    color: var(--success);
}
.my-ip-add {
    margin-left: auto;
    border: 1px solid #b45309;
    background: transparent;
    color: #b45309;
    border-radius: var(--radius-md);
    padding: 5px 12px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
}
.my-ip-add:hover { background: #b45309; color: #fff; }

.group { border-bottom: 1px solid var(--border); }
.group:last-child { border-bottom: none; }
.group-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 24px 8px;
}
.group-head h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-main);
}
.badge-count {
    padding: 2px 10px;
    border-radius: 999px;
    background: var(--bg-color);
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-off {
    padding: 2px 10px;
    border-radius: 999px;
    background: var(--danger-bg);
    color: var(--danger);
    font-size: 0.75rem;
    font-weight: 700;
}
.group-empty {
    margin: 0;
    padding: 4px 24px 20px;
    font-size: 0.85rem;
    color: var(--text-muted);
}

.ip {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-weight: 700;
    color: var(--text-main);
}
.here {
    margin-left: 8px;
    padding: 2px 8px;
    border-radius: 999px;
    background: #eff6ff;
    color: var(--primary);
    font-size: 0.7rem;
    font-weight: 700;
}
.desc { color: var(--text-muted); }

.inline-link {
    align-self: flex-start;
    border: none;
    background: transparent;
    padding: 0;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--primary);
    cursor: pointer;
    text-decoration: underline;
}
</style>
