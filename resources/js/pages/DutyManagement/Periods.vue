<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AlertModal from '../../components/AlertModal.vue';
import type { DutyPeriodPhase, DutyPeriodRow, DutyPeriodsPageProps } from '../../types';
import { toastErrors, toastSuccessMessage } from '../../utils';

const page = usePage<DutyPeriodsPageProps>();
const alertModal = ref<InstanceType<typeof AlertModal> | null>(null);

const WEEKDAYS = [
    { value: 0, label: '日' },
    { value: 1, label: '一' },
    { value: 2, label: '二' },
    { value: 3, label: '三' },
    { value: 4, label: '四' },
    { value: 5, label: '五' },
    { value: 6, label: '六' },
];

const PHASE_TEXT: Record<DutyPeriodPhase, string> = {
    upcoming: '尚未開放',
    open: '開放填寫中',
    closed: '已截止',
    disabled: '已停用',
};

// 用本地時區轉 YYYY-MM-DD。不能用 toISOString()，那是 UTC 會差一天
const toDateString = (d: Date) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

// 直接組本地時間的 Date，避免 new Date('2026-09-01') 被當成 UTC
const parseDate = (s: string) => {
    const [y, m, d] = s.split('-').map(Number);
    return new Date(y, m - 1, d);
};

// 編輯中的期間，null 表示正在新增
const editing = ref<DutyPeriodRow | null>(null);

const blank = () => ({
    name: '',
    duty_start_date: '',
    duty_end_date: '',
    open_at: '',
    close_at: '',
    dates: [] as string[],
    location_ids: page.props.locations.map(l => l.id),
    status: true,
});

const pf = useForm<ReturnType<typeof blank>>(blank());

// 值勤開始日到結束日之間的所有日期
const rangeDates = computed<string[]>(() => {
    if (!pf.duty_start_date || !pf.duty_end_date) return [];

    const start = parseDate(pf.duty_start_date);
    const end = parseDate(pf.duty_end_date);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return [];

    const out: string[] = [];
    for (const d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        out.push(toDateString(d));
        if (out.length > 366) break;
    }
    return out;
});

interface PickerCell {
    empty: boolean,
    day?: number,
    dateString?: string,
    inRange: boolean
}

// 把日期範圍拆成一個個月曆區塊，範圍外的日子照樣顯示但不能點
const monthBlocks = computed(() => {
    const dates = rangeDates.value;
    if (!dates.length) return [];

    const inRange = new Set(dates);
    const first = parseDate(dates[0]);
    const last = parseDate(dates[dates.length - 1]);

    const blocks: { key: string, label: string, cells: PickerCell[] }[] = [];
    const cursor = new Date(first.getFullYear(), first.getMonth(), 1);
    const lastMonth = new Date(last.getFullYear(), last.getMonth(), 1);

    while (cursor <= lastMonth) {
        const y = cursor.getFullYear();
        const m = cursor.getMonth();
        const cells: PickerCell[] = [];

        // 補月初空白，週日是 0 剛好對應
        for (let i = 0; i < new Date(y, m, 1).getDay(); i++) {
            cells.push({ empty: true, inRange: false });
        }
        for (let d = 1; d <= new Date(y, m + 1, 0).getDate(); d++) {
            const ds = toDateString(new Date(y, m, d));
            cells.push({ empty: false, day: d, dateString: ds, inRange: inRange.has(ds) });
        }

        blocks.push({ key: `${y}-${m}`, label: `${y} 年 ${m + 1} 月`, cells });
        cursor.setMonth(cursor.getMonth() + 1);
    }
    return blocks;
});

const isPicked = (dateString: string) => pf.dates.includes(dateString);

const togglePick = (cell: PickerCell) => {
    if (cell.empty || !cell.inRange) return;
    const i = pf.dates.indexOf(cell.dateString!);
    if (i > -1) pf.dates.splice(i, 1);
    else pf.dates.push(cell.dateString!);
};

const datesOfWeekday = (weekday: number) =>
    rangeDates.value.filter(ds => parseDate(ds).getDay() === weekday);

// 點星期表頭：該星期全部勾起來；已經全勾了就整欄取消
const toggleWeekday = (weekday: number) => {
    const targets = datesOfWeekday(weekday);
    if (!targets.length) return;

    if (targets.every(ds => pf.dates.includes(ds))) {
        pf.dates = pf.dates.filter(ds => !targets.includes(ds));
    } else {
        pf.dates = [...new Set([...pf.dates, ...targets])].sort();
    }
};

// 表頭要顯示成哪種狀態：全勾 / 部分 / 沒勾
const weekdayState = (weekday: number) => {
    const targets = datesOfWeekday(weekday);
    if (!targets.length) return 'none';
    const picked = targets.filter(ds => pf.dates.includes(ds)).length;
    if (picked === 0) return 'off';
    return picked === targets.length ? 'all' : 'some';
};

const selectAll = () => { pf.dates = [...rangeDates.value]; };
const clearAll = () => { pf.dates = []; };
const selectWeekdaysOnly = () => {
    pf.dates = rangeDates.value.filter(ds => {
        const w = parseDate(ds).getDay();
        return w >= 1 && w <= 5;
    });
};

// 改了日期範圍就重設成平日，讓管理員有個合理的起點再自己調整
watch(() => [pf.duty_start_date, pf.duty_end_date], () => {
    if (editing.value) return;
    selectWeekdaysOnly();
});

const onSelect = (period: DutyPeriodRow) => {
    editing.value = period;
    pf.defaults({
        ...blank(),
        name: period.name,
        duty_start_date: period.duty_start_date,
        duty_end_date: period.duty_end_date,
        open_at: period.open_at,
        close_at: period.close_at,
        status: period.status,
    });
    pf.reset();
    pf.clearErrors();
};

const onCancel = () => {
    editing.value = null;
    pf.defaults(blank());
    pf.reset();
    pf.clearErrors();
};

const onSubmit = () => {
    if (editing.value) {
        // 編輯只送這幾個欄位，值勤日期與名額不會被動到
        pf.transform(data => ({
            name: data.name,
            open_at: data.open_at,
            close_at: data.close_at,
            status: data.status,
        })).put(route('duty-management.periods.update', { dutyPeriod: editing.value.id }), {
            preserveScroll: true,
            onSuccess: () => {
                toastSuccessMessage('期間已更新');
                onCancel();
            },
            onError: (err) => toastErrors(err),
        });
    } else {
        pf.transform(data => data).post(route('duty-management.periods.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toastSuccessMessage(page.props.message ?? '期間已建立');
                onCancel();
            },
            onError: (err) => toastErrors(err),
        });
    }
};

const onDelete = (period: DutyPeriodRow) => {
    alertModal.value?.confirm(
        `確定要刪除「${period.name}」嗎？這會一併刪掉底下 ${period.slot_count} 個名額。`,
        () => {
            router.delete(route('duty-management.periods.destroy', { dutyPeriod: period.id }), {
                preserveScroll: true,
                onSuccess: () => {
                    toastSuccessMessage('期間已刪除');
                    onCancel();
                },
                onError: (err) => toastErrors(err),
            });
        },
        '刪除期間'
    );
};

const toggleIn = (list: number[], value: number) => {
    const i = list.indexOf(value);
    if (i > -1) list.splice(i, 1);
    else list.push(value);
};

// 依目前勾選的日期與地點，預估會產生幾個名額
const estimate = computed(() => {
    if (editing.value || pf.dates.length === 0) return null;

    const perDay = page.props.locations
        .filter(l => pf.location_ids.includes(l.id))
        .reduce((sum, l) => sum + l.capacity, 0);

    return { days: pf.dates.length, slots: pf.dates.length * perDay };
});
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
                    <h1>值勤時間設定</h1>
                    <p>開放一段期間讓部員填寫值勤，截止後僅限管理員能異動</p>
                </div>
            </div>
        </header>

        <div class="layout-split">

            <!-- 左欄：期間清單 -->
            <main class="list-column">
                <section class="list-container">
                    <div class="table-responsive">
                        <table class="data-list">
                            <thead>
                                <tr>
                                    <th>期間名稱</th>
                                    <th>值勤日期</th>
                                    <th>開放填寫</th>
                                    <th>名額</th>
                                    <th>狀態</th>
                                    <th class="align-right">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="period in page.props.periods" :key="period.id"
                                    :class="{ 'is-inactive': !period.status, 'is-selected': editing?.id === period.id }">
                                    <td>
                                        <div class="period-name">{{ period.name }}</div>
                                    </td>
                                    <td class="nowrap">
                                        {{ period.duty_start_date }}<br>
                                        <span class="sub">～ {{ period.duty_end_date }}</span>
                                    </td>
                                    <td class="nowrap">
                                        {{ period.open_at.replace('T', ' ') }}<br>
                                        <span class="sub">～ {{ period.close_at.replace('T', ' ') }}</span>
                                    </td>
                                    <td>
                                        <div class="slot-count">
                                            {{ period.filled_count }} / {{ period.slot_count }}
                                        </div>
                                        <span class="sub">已排 / 總數</span>
                                    </td>
                                    <td>
                                        <span class="phase-badge" :class="`phase-${period.phase}`">
                                            {{ PHASE_TEXT[period.phase] }}
                                        </span>
                                    </td>
                                    <td class="align-right">
                                        <div class="row-actions">
                                            <button class="action-btn edit" @click="onSelect(period)" title="編輯">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            </button>
                                            <button class="action-btn delete" @click="onDelete(period)"
                                                :disabled="period.filled_count > 0"
                                                :title="period.filled_count > 0 ? '已經有人排班，不能刪除' : '刪除'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="page.props.periods.length === 0">
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                                            <h3>尚未建立任何期間</h3>
                                            <p>由右側面板建立第一個值勤填寫期間。</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <!-- 右欄：新增 / 編輯 -->
            <aside class="form-column">
                <section class="control-panel" :class="{ 'is-editing': editing }">
                    <div class="panel-header">
                        <div class="icon-wrapper" :class="editing ? 'edit-mode' : 'create-mode'">
                            <svg v-if="editing" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </div>
                        <h2 class="panel-title">{{ editing ? '編輯期間' : '建立期間' }}</h2>
                    </div>

                    <form @submit.prevent="onSubmit" class="vertical-form">

                        <div class="input-group">
                            <label>期間名稱</label>
                            <input type="text" v-model="pf.name" placeholder="例如：2026 年 9 月班表" required>
                        </div>

                        <!-- 值勤日期：只有新增時能設定 -->
                        <template v-if="!editing">
                            <div class="field-row">
                                <div class="input-group">
                                    <label>值勤開始日</label>
                                    <input type="date" v-model="pf.duty_start_date" required>
                                </div>
                                <div class="input-group">
                                    <label>值勤結束日</label>
                                    <input type="date" v-model="pf.duty_end_date" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label>值勤日期</label>

                                <div class="date-picker" v-if="rangeDates.length">
                                    <div class="picker-toolbar">
                                        <button type="button" class="mini-btn" @click="selectAll">全選</button>
                                        <button type="button" class="mini-btn" @click="selectWeekdaysOnly">只選平日</button>
                                        <button type="button" class="mini-btn" @click="clearAll">全部取消</button>
                                        <span class="picked-count">已選 <strong>{{ pf.dates.length }}</strong> 天</span>
                                    </div>

                                    <div class="month-block" v-for="block in monthBlocks" :key="block.key">
                                        <div class="month-label">{{ block.label }}</div>

                                        <!-- 星期表頭本身就是整欄勾選的按鈕 -->
                                        <div class="mini-grid">
                                            <button type="button" v-for="day in WEEKDAYS" :key="day.value"
                                                class="wd-btn" :class="`wd-${weekdayState(day.value)}`"
                                                :disabled="weekdayState(day.value) === 'none'"
                                                :title="`整欄勾選／取消星期${day.label}`"
                                                @click="toggleWeekday(day.value)">
                                                {{ day.label }}
                                            </button>
                                        </div>

                                        <div class="mini-grid">
                                            <button type="button" v-for="(cell, i) in block.cells" :key="i"
                                                class="day-cell"
                                                :class="{
                                                    'is-empty': cell.empty,
                                                    'out-range': !cell.empty && !cell.inRange,
                                                    'is-picked': !cell.empty && cell.inRange && isPicked(cell.dateString!)
                                                }"
                                                :disabled="cell.empty || !cell.inRange"
                                                @click="togglePick(cell)">
                                                {{ cell.empty ? '' : cell.day }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <p class="hint" v-else>
                                    先選好上面的開始日與結束日，這裡就會列出可以逐日勾選的日期。
                                </p>
                            </div>

                            <div class="input-group">
                                <label>開放的地點 (可複選)</label>
                                <div class="chip-row">
                                    <button type="button" v-for="location in page.props.locations" :key="location.id"
                                        class="chip" :class="{ 'is-on': pf.location_ids.includes(location.id) }"
                                        @click="toggleIn(pf.location_ids, location.id)">
                                        {{ location.name }}
                                        <span class="chip-sub">{{ location.capacity }} 人</span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- 編輯時值勤範圍改為唯讀顯示 -->
                        <div class="readonly-note" v-else>
                            <div class="readonly-row">
                                <span>值勤日期</span>
                                <strong>{{ editing.duty_start_date }} ～ {{ editing.duty_end_date }}</strong>
                            </div>
                            <div class="readonly-row">
                                <span>總人數/已填</span>
                                <strong>{{ editing.slot_count }} 人 / {{ editing.filled_count }} 人</strong>
                            </div>
                            <p>值勤日期與名額建立後無法修改，避免跟已經排好值勤對不起來，請刪除重建</p>
                        </div>

                        <div class="divider"></div>

                        <div class="field-row">
                            <div class="input-group">
                                <label>開放填寫時間</label>
                                <input type="datetime-local" v-model="pf.open_at" required>
                            </div>
                            <div class="input-group">
                                <label>填寫截止時間</label>
                                <input type="datetime-local" v-model="pf.close_at" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>期間狀態</label>
                            <div class="status-toggle">
                                <label class="toggle-option">
                                    <input type="radio" :value="true" v-model="pf.status">
                                    <span class="toggle-btn up">啟用</span>
                                </label>
                                <label class="toggle-option">
                                    <input type="radio" :value="false" v-model="pf.status">
                                    <span class="toggle-btn down">停用</span>
                                </label>
                            </div>
                        </div>

                        <!-- 建立前先讓管理員知道會開出多少名額 -->
                        <div class="estimate" v-if="estimate">
                            這樣會產生
                            <strong>{{ estimate.days }}</strong> 天 ×
                            <strong>{{ estimate.slots }}</strong> 個名額
                        </div>

                        <div class="divider"></div>

                        <div class="action-group">
                            <button type="submit" class="sys-btn-primary" :disabled="pf.processing">
                                {{ editing ? '儲存變更' : '建立並產生名額' }}
                            </button>
                            <button type="button" class="sys-btn-ghost" v-if="editing" @click="onCancel">
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
/* 共用的設計系統在 resources/css/system-settings.css */

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

.period-name {
    font-weight: 700;
    font-size: 1.02rem;
    color: var(--text-main);
}
.nowrap { white-space: nowrap; }
.sub {
    font-size: 0.8rem;
    color: var(--text-muted);
}
.slot-count {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--text-main);
}

.phase-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    white-space: nowrap;
}
.phase-open { background: var(--success-bg); color: var(--success); }
.phase-upcoming { background: #fef3c7; color: #b45309; }
.phase-closed { background: var(--bg-color); color: var(--text-muted); }
.phase-disabled { background: var(--danger-bg); color: var(--danger); }

.action-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
/* grid 項目預設 min-width: auto，datetime-local 的內建最小寬度會把欄位撐爆，
   所以要允許它縮到比內容還窄 */
.field-row > * {
    min-width: 0;
}
.field-row input {
    max-width: 100%;
    box-sizing: border-box;
}

/* 逐日勾選的月曆 */
.date-picker {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--surface);
    overflow: hidden;
}

.picker-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    padding: 10px 12px;
    background: var(--bg-color);
    border-bottom: 1px solid var(--border);
}
.mini-btn {
    padding: 5px 10px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--surface);
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}
.mini-btn:hover { color: var(--primary); border-color: var(--primary); }
.picked-count {
    margin-left: auto;
    font-size: 0.82rem;
    color: var(--text-muted);
}
.picked-count strong { color: var(--primary); font-size: 0.95rem; }

.month-block { padding: 12px; }
.month-block + .month-block { border-top: 1px solid var(--border); }
.month-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 8px;
}

.mini-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}
.mini-grid + .mini-grid { margin-top: 4px; }

.wd-btn {
    padding: 4px 0;
    border: none;
    border-radius: 5px;
    background: var(--bg-color);
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}
.wd-btn:hover:not(:disabled) { background: var(--border); color: var(--text-main); }
.wd-btn:disabled { opacity: 0.35; cursor: default; }
.wd-all { background: var(--primary); color: #fff; }
.wd-all:hover:not(:disabled) { background: var(--primary-hover); color: #fff; }
.wd-some { background: #dbeafe; color: var(--primary); }

.day-cell {
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--surface);
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-main);
    cursor: pointer;
    padding: 0;
    transition: all 0.12s ease;
}
.day-cell:hover:not(:disabled) { border-color: var(--primary); background: #eff6ff; }
.day-cell.is-picked {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
}
.day-cell.out-range {
    background: transparent;
    border-color: transparent;
    color: var(--border-hover);
    cursor: default;
}
.day-cell.is-empty {
    background: transparent;
    border-color: transparent;
    cursor: default;
}

.hint {
    margin: 0;
    padding: 14px 16px;
    background: var(--bg-color);
    border-radius: var(--radius-md);
    font-size: 0.85rem;
    color: var(--text-muted);
    line-height: 1.6;
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    background: var(--surface);
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}
.chip:hover { border-color: var(--border-hover); }
.chip.is-on {
    background: #eff6ff;
    border-color: var(--primary);
    color: var(--primary);
}
.chip-sub {
    font-size: 0.75rem;
    font-weight: 500;
    opacity: 0.75;
}

.readonly-note {
    padding: 14px 16px;
    background: var(--bg-color);
    border-radius: var(--radius-md);
    font-size: 0.9rem;
}
.readonly-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 6px;
}
.readonly-row span { color: var(--text-muted); }
.readonly-row strong { color: var(--text-main); }
.readonly-note p {
    margin: 10px 0 0;
    font-size: 0.82rem;
    color: var(--text-muted);
    line-height: 1.5;
}

.estimate {
    padding: 12px 16px;
    background: #eff6ff;
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    color: var(--primary);
}
.estimate strong { font-size: 1.05rem; }

@media (max-width: 640px) {
    .field-row { grid-template-columns: 1fr; }
}
</style>
