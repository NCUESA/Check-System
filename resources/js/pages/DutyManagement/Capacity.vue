<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AlertModal from '../../components/AlertModal.vue';
import UserPicker from '../../components/UserPicker.vue';
import type { CapacityPageProps, CapacitySlot } from '../../types';
import { toastErrors, toastSuccessMessage } from '../../utils';

const page = usePage<CapacityPageProps>();
const alertModal = ref<InstanceType<typeof AlertModal> | null>(null);

// 目前正在看的日期與地點。改了就重新查
const viewDate = ref(page.props.date);
const viewLocationId = ref<number | null>(page.props.locationId);

const reload = () => {
    router.get(route('duty-management.capacity'),
        { date: viewDate.value, location_id: viewLocationId.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const currentLocation = computed(() =>
    page.props.locations.find(l => l.id === page.props.locationId) ?? null
);

const stats = computed(() => {
    const slots = page.props.slots;
    return {
        total: slots.length,
        filled: slots.filter(s => s.user).length,
        disabled: slots.filter(s => !s.status).length,
    };
});

const af = useForm<{
    date: string,
    location_id: number | null,
    total: number,
    period_id: number | null
}>({
    date: page.props.date,
    location_id: page.props.locationId,
    total: page.props.slots.length,
    period_id: null,
});

// 換日期／地點或存檔之後，欄位跟著帶入該天目前的名額數
watch(() => page.props.slots.length, (n) => { af.total = n; });

// 送出後會變成加開還是移除，先讓管理員看到
const delta = computed(() => {
    const diff = (af.total ?? 0) - page.props.slots.length;
    if (diff > 0) return { type: 'add' as const, amount: diff };
    if (diff < 0) return { type: 'remove' as const, amount: -diff };
    return null;
});

const onApply = () => {
    // 一律針對目前正在看的日期與地點
    af.date = page.props.date;
    af.location_id = page.props.locationId;

    af.post(route('duty-management.capacity.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toastSuccessMessage(page.props.message ?? '名額已更新');
            af.reset('period_id');
        },
        onError: (err) => toastErrors(err),
    });
};

const onToggle = (slot: CapacitySlot) => {
    router.put(route('duty-management.capacity.toggle', { slot: slot.id }),
        { status: !slot.status },
        {
            preserveScroll: true,
            onSuccess: () => toastSuccessMessage(slot.status ? '名額已停用' : '名額已啟用'),
            onError: (err) => toastErrors(err),
        }
    );
};

// 就地指派：每個空名額各自記住下拉選到誰
const assignTarget = reactive<Record<number, number | ''>>({});
const assigning = ref<number | null>(null);

const onAssign = (slot: CapacitySlot) => {
    const userId = assignTarget[slot.id];
    if (!userId) return;

    assigning.value = slot.id;
    router.post(route('duty.assign'), { slot_id: slot.id, user_id: userId }, {
        preserveScroll: true,
        onSuccess: () => {
            assignTarget[slot.id] = '';
            toastSuccessMessage('指派成功');
        },
        onError: (err) => toastErrors(err),
        onFinish: () => { assigning.value = null; },
    });
};

// 把名額上的人退掉。沿用月曆那邊的 duty.cancel，
// 管理員本來就能退任何人的班，不需要另外寫一個端點
const onUnassign = (slot: CapacitySlot) => {
    alertModal.value?.confirm(
        `確定要退掉「${slot.user?.name ?? '這個人'}」在這個名額的班嗎？名額會留著變成空的。`,
        () => {
            router.post(route('duty.cancel'), { slot_ids: [slot.id] }, {
                preserveScroll: true,
                onSuccess: () => toastSuccessMessage('已退掉這個班'),
                onError: (err) => toastErrors(err),
            });
        },
        '退掉排班'
    );
};

const onDelete = (slot: CapacitySlot) => {
    alertModal.value?.confirm(
        '確定要刪除這個名額嗎？',
        () => {
            router.delete(route('duty-management.capacity.destroy', { slot: slot.id }), {
                preserveScroll: true,
                onSuccess: () => toastSuccessMessage('名額已刪除'),
                onError: (err) => toastErrors(err),
            });
        },
        '刪除名額'
    );
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
                    <h1>臨時增額</h1>
                    <p>對特定日期、地點加開名額，不受值勤期間的日期範圍限制</p>
                </div>
            </div>
        </header>

        <div class="layout-split">

            <!-- 左欄：該日該地點的名額 -->
            <main class="list-column">
                <section class="list-container">

                    <div class="toolbar">
                        <div class="field">
                            <label>日期</label>
                            <input type="date" v-model="viewDate" @change="reload">
                        </div>
                        <div class="field">
                            <label>地點</label>
                            <select v-model="viewLocationId" @change="reload">
                                <option v-for="location in page.props.locations" :key="location.id" :value="location.id">
                                    {{ location.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="result-bar" v-if="currentLocation">
                        <span>常態上限 <strong>{{ currentLocation.capacity }}</strong> 人</span>
                        <span class="divider-dot">·</span>
                        <span>這天共 <strong>{{ stats.total }}</strong> 個名額</span>
                        <span class="divider-dot">·</span>
                        <span>已排 <strong>{{ stats.filled }}</strong></span>
                        <span v-if="stats.disabled">
                            <span class="divider-dot">·</span>
                            停用 <strong>{{ stats.disabled }}</strong>
                        </span>
                        <span class="over-badge" v-if="stats.total > currentLocation.capacity">
                            已超出常態上限
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-list">
                            <thead>
                                <tr>
                                    <th>名額</th>
                                    <th>歸屬期間</th>
                                    <th>排班者</th>
                                    <th>狀態</th>
                                    <th class="align-right">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(slot, i) in page.props.slots" :key="slot.id"
                                    :class="{ 'is-inactive': !slot.status }">
                                    <td><span class="slot-no">#{{ i + 1 }}</span></td>
                                    <td>
                                        <span v-if="slot.period" class="period-tag">{{ slot.period }}</span>
                                        <span v-else class="period-tag standalone" title="不歸屬任何期間，只有管理員能指派">
                                            不歸屬
                                        </span>
                                    </td>
                                    <td>
                                        <div class="who" v-if="slot.user">
                                            {{ slot.user.name }}
                                            <span class="mono">{{ slot.user.stu_id }}</span>
                                        </div>
                                        <!-- 空名額可以直接在這裡指派，不用跳到月曆 -->
                                        <div class="assign-cell" v-else>
                                            <UserPicker class="assign-picker"
                                                v-model="assignTarget[slot.id]"
                                                :users="page.props.users"
                                                placeholder="搜尋姓名或學號" />
                                            <button type="button" class="assign-btn"
                                                :disabled="!assignTarget[slot.id] || assigning === slot.id"
                                                @click="onAssign(slot)">
                                                指派
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge" :class="slot.status ? 'badge-up' : 'badge-down'">
                                            <span class="status-dot"></span>
                                            {{ slot.status ? '啟用中' : '已停用' }}
                                        </span>
                                    </td>
                                    <td class="align-right">
                                        <div class="row-actions">
                                            <!-- 有人排班時才出現，退掉之後才能停用或刪除這個名額 -->
                                            <button v-if="slot.user" class="action-btn unassign"
                                                @click="onUnassign(slot)" title="退掉這個人的班">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                            </button>
                                            <button class="action-btn" @click="onToggle(slot)"
                                                :disabled="!!slot.user"
                                                :title="slot.user ? '請先按左邊的按鈕退掉排班' : (slot.status ? '停用' : '啟用')">
                                                <svg v-if="slot.status" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64A9 9 0 1 1 5.64 5.64"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                                                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            </button>
                                            <button class="action-btn delete" @click="onDelete(slot)"
                                                :disabled="!!slot.user"
                                                :title="slot.user ? '請先按左邊的按鈕退掉排班' : '刪除'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="page.props.slots.length === 0">
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                            <h3>這天還沒有任何名額</h3>
                                            <p>由右側面板加開，或到「值勤時間設定」建立整期班表。</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <!-- 右欄：加開 -->
            <aside class="form-column">
                <section class="control-panel">
                    <div class="panel-header">
                        <div class="icon-wrapper create-mode">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </div>
                        <h2 class="panel-title">加開名額</h2>
                    </div>

                    <form @submit.prevent="onApply" class="vertical-form">

                        <div class="target-note">
                            <div class="target-row">
                                <span>日期</span>
                                <strong>{{ page.props.date }}</strong>
                            </div>
                            <div class="target-row">
                                <span>地點</span>
                                <strong>{{ currentLocation?.name ?? '—' }}</strong>
                            </div>
                            <p>要換日期或地點，請用左側的選單。</p>
                        </div>

                        <div class="input-group">
                            <label>總共幾個名額</label>
                            <input type="number" v-model="af.total" min="0" max="20" required>
                            <span class="field-note">
                                目前 {{ page.props.slots.length }} 個<span v-if="stats.filled">，其中 {{ stats.filled }} 個已有人排班</span>
                            </span>
                        </div>

                        <!-- 送出前先講清楚會發生什麼 -->
                        <div class="delta" :class="delta?.type" v-if="delta">
                            <template v-if="delta.type === 'add'">
                                會<strong>加開 {{ delta.amount }} 個</strong>名額
                            </template>
                            <template v-else>
                                會<strong>移除 {{ delta.amount }} 個</strong>名額，
                                優先移除已停用與最後加開的，已有人排班的不會被動到
                            </template>
                        </div>

                        <div class="input-group" v-if="delta?.type === 'add'">
                            <label>新名額的歸屬期間</label>
                            <select v-model="af.period_id">
                                <option :value="null">不歸屬（只有管理員能指派）</option>
                                <option v-for="period in page.props.periods" :key="period.id" :value="period.id">
                                    {{ period.name }}{{ period.is_open ? '（開放填寫中）' : '（未開放／已截止）' }}
                                </option>
                            </select>
                            <span class="field-note">只影響這次新加開的名額，既有的不會變動。</span>
                        </div>

                        <p class="hint" v-if="delta?.type === 'add'">
                            不歸屬的名額一律視為已鎖定，部員看得到但不能自己排，
                            要由管理員到「指定值勤」指派。<br>
                            歸屬到<strong>開放填寫中</strong>的期間，部員才能自己來搶。
                        </p>

                        <div class="divider"></div>

                        <div class="action-group">
                            <button type="submit" class="sys-btn-primary"
                                :disabled="af.processing || !page.props.locationId || !delta">
                                {{ delta ? '套用' : '沒有變動' }}
                            </button>
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

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}
.field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
    flex: 1 1 180px;
}
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
.over-badge {
    margin-left: auto;
    padding: 3px 12px;
    border-radius: 999px;
    background: #fef3c7;
    color: #b45309;
    font-size: 0.78rem;
    font-weight: 700;
}

.slot-no {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-weight: 700;
    color: var(--text-muted);
}

.period-tag {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    background: #eff6ff;
    color: var(--primary);
}
.period-tag.standalone {
    background: var(--bg-color);
    color: var(--text-muted);
    cursor: help;
}

.who {
    font-weight: 700;
    color: var(--text-main);
}
.who .mono {
    margin-left: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-weight: 500;
    font-size: 0.82rem;
    color: var(--text-muted);
}

.action-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}
.action-btn.unassign:hover {
    color: #b45309;
    background: #fef3c7;
}

/* 空名額的就地指派 */
.assign-cell {
    display: flex;
    align-items: center;
    gap: 6px;
}
.assign-picker {
    flex: 1 1 auto;
    min-width: 180px;
}
.assign-btn {
    flex-shrink: 0;
    padding: 6px 14px;
    border: 1px solid var(--primary);
    border-radius: var(--radius-md);
    background: var(--primary);
    color: #fff;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
}
.assign-btn:hover:not(:disabled) { background: var(--primary-hover); }
.assign-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.target-note {
    padding: 14px 16px;
    background: var(--bg-color);
    border-radius: var(--radius-md);
    font-size: 0.9rem;
}
.target-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 6px;
}
.target-row span { color: var(--text-muted); }
.target-row strong { color: var(--text-main); }
.target-note p {
    margin: 10px 0 0;
    font-size: 0.82rem;
    color: var(--text-muted);
}

.hint {
    margin: 0;
    font-size: 0.82rem;
    color: var(--text-muted);
    line-height: 1.7;
}
.hint strong { color: var(--text-main); }

.field-note {
    font-size: 0.78rem;
    color: var(--text-muted);
    line-height: 1.5;
}

/* 送出前預告這次會加開還是移除 */
.delta {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    font-size: 0.86rem;
    line-height: 1.6;
}
.delta.add {
    background: #eff6ff;
    color: var(--primary);
}
.delta.remove {
    background: #fef3c7;
    color: #b45309;
}
.delta strong { font-size: 0.95rem; }
</style>
