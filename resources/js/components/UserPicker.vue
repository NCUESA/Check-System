<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { AssignableUser } from '../types';

// 可搜尋的人員下拉。原生 select 在幾十個人的時候很難找，
// 但只為了這個拉一整包套件不划算，所以自己做一個輕量的
const props = defineProps<{
    modelValue: number | '' | null | undefined,
    users: AssignableUser[],
    placeholder?: string,
    disabled?: boolean
}>();

const emit = defineEmits<{ 'update:modelValue': [number | ''] }>();

const root = ref<HTMLElement | null>(null);
const menu = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);
const query = ref('');
const open = ref(false);
const highlighted = ref(0);

// 選單用 Teleport 送到 body 再以 fixed 定位。
// 不這樣做的話，放在 overflow: auto 的容器（例如表格）裡會被裁掉
const menuStyle = ref<Record<string, string>>({});
const MENU_MAX_HEIGHT = 260;

const updateMenuPosition = () => {
    const control = root.value?.querySelector('.picker-control') as HTMLElement | null;
    if (!control) return;

    const rect = control.getBoundingClientRect();
    const style: Record<string, string> = {
        left: `${rect.left}px`,
        width: `${Math.max(rect.width, 220)}px`,
    };

    // 下方空間不夠就往上開
    const spaceBelow = window.innerHeight - rect.bottom;
    if (spaceBelow < MENU_MAX_HEIGHT && rect.top > MENU_MAX_HEIGHT) {
        style.bottom = `${window.innerHeight - rect.top + 4}px`;
    } else {
        style.top = `${rect.bottom + 4}px`;
    }

    menuStyle.value = style;
};

const onViewportChange = () => {
    if (open.value) updateMenuPosition();
};

const selected = computed(() => props.users.find(u => u.id === props.modelValue) ?? null);

// 姓名或學號都能搜。清單長的時候只渲染前 50 筆
const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    const list = q
        ? props.users.filter(u =>
            (u.name ?? '').toLowerCase().includes(q) || u.stu_id.toLowerCase().includes(q))
        : props.users;
    return list.slice(0, 50);
});

const label = (user: AssignableUser) => `${user.stu_id}　${user.name ?? ''}`;

const openList = () => {
    if (props.disabled) return;
    query.value = '';
    highlighted.value = 0;
    open.value = true;
};

const close = () => { open.value = false; };

const pick = (user: AssignableUser) => {
    emit('update:modelValue', user.id);
    close();
};

const clear = () => {
    emit('update:modelValue', '');
    close();
};

const onKeydown = (event: KeyboardEvent) => {
    if (!open.value) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlighted.value = Math.min(highlighted.value + 1, filtered.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const user = filtered.value[highlighted.value];
        if (user) pick(user);
    } else if (event.key === 'Escape') {
        close();
    }
};

watch(query, () => { highlighted.value = 0; });

// 展開時定位選單並把游標移到搜尋框，才能直接打字
watch(open, async (isOpen) => {
    if (!isOpen) return;
    await nextTick();
    updateMenuPosition();
    searchInput.value?.focus();
});

// 點到元件外面就收起來。選單被 Teleport 出去了，所以兩邊都要檢查
const onDocumentMouseDown = (event: MouseEvent) => {
    const target = event.target as Node;
    if (root.value?.contains(target) || menu.value?.contains(target)) return;
    close();
};

onMounted(() => {
    document.addEventListener('mousedown', onDocumentMouseDown);
    // 捲動時要跟著移動，true 是為了捕捉內層容器的捲動
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange);
});
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentMouseDown);
    window.removeEventListener('scroll', onViewportChange, true);
    window.removeEventListener('resize', onViewportChange);
});
</script>

<template>
    <div class="picker" ref="root">
        <div class="picker-control" :class="{ 'is-open': open, 'is-disabled': disabled }" @click="openList">
            <input v-if="open" ref="searchInput" v-model="query" type="text" class="picker-search"
                :placeholder="placeholder ?? '輸入姓名或學號搜尋'" @keydown="onKeydown">

            <template v-else>
                <span class="picker-value" :class="{ 'is-empty': !selected }">
                    {{ selected ? label(selected) : (placeholder ?? '選擇人員…') }}
                </span>
                <button v-if="selected" type="button" class="picker-clear" title="清除" @click.stop="clear">
                    &times;
                </button>
            </template>

            <svg class="picker-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>

        <Teleport to="body">
            <ul class="picker-list" v-if="open" ref="menu" :style="menuStyle">
                <li v-for="(user, i) in filtered" :key="user.id"
                    :class="{ 'is-highlighted': i === highlighted }"
                    @mousedown.prevent="pick(user)"
                    @mouseenter="highlighted = i">
                    <span class="picker-sid">{{ user.stu_id }}</span>
                    <span class="picker-name">{{ user.name }}</span>
                </li>
                <li v-if="filtered.length === 0" class="picker-none">查無符合的人員</li>
            </ul>
        </Teleport>
    </div>
</template>

<style scoped>
.picker {
    position: relative;
    min-width: 0;
}

.picker-control {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    background: #fff;
    font-size: 0.875rem;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.picker-control:hover { border-color: #adb5bd; }
.picker-control.is-open {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
}
.picker-control.is-disabled {
    background: #e9ecef;
    cursor: not-allowed;
}

.picker-value {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #212529;
}
.picker-value.is-empty { color: #adb5bd; }

.picker-search {
    flex: 1;
    min-width: 0;
    border: none;
    outline: none;
    font-size: 0.875rem;
    background: transparent;
    color: #212529;
}

.picker-clear {
    flex-shrink: 0;
    border: none;
    background: transparent;
    color: #adb5bd;
    font-size: 1.1rem;
    line-height: 1;
    padding: 0 2px;
    cursor: pointer;
}
.picker-clear:hover { color: #dc3545; }

.picker-caret {
    flex-shrink: 0;
    width: 14px;
    height: 14px;
    color: #adb5bd;
}

/* 被 Teleport 到 body，位置改由 JS 用 fixed 算出來 */
.picker-list {
    position: fixed;
    z-index: 1060;
    max-height: 260px;
    overflow-y: auto;
    margin: 0;
    padding: 4px;
    list-style: none;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}
.picker-list li {
    display: flex;
    align-items: baseline;
    gap: 8px;
    padding: 7px 10px;
    border-radius: 5px;
    font-size: 0.85rem;
    cursor: pointer;
}
.picker-list li.is-highlighted { background: #eff6ff; }
.picker-sid {
    flex-shrink: 0;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 0.78rem;
    color: #6c757d;
}
.picker-name {
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.picker-none {
    padding: 14px 10px;
    text-align: center;
    color: #adb5bd;
    cursor: default;
}
</style>
