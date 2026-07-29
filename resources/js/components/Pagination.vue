<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Paginated } from '../types';

// Laravel 分頁器的 links 第一個是「上一頁」、最後一個是「下一頁」，
// 中間才是頁碼，所以拆開來各自處理
const props = defineProps<{ meta: Paginated<unknown> }>();

const pages = () => props.meta.links.slice(1, -1);
const prev = () => props.meta.links[0];
const next = () => props.meta.links[props.meta.links.length - 1];
</script>

<template>
    <nav class="pager" v-if="meta.last_page > 1">
        <div class="pager-info">
            顯示第 <strong>{{ meta.from ?? 0 }}</strong>–<strong>{{ meta.to ?? 0 }}</strong> 筆，
            共 <strong>{{ meta.total }}</strong> 筆
        </div>

        <div class="pager-links">
            <component :is="prev().url ? Link : 'span'" :href="prev().url ?? undefined"
                class="pager-btn" :class="{ 'is-disabled': !prev().url }" preserve-scroll>
                上一頁
            </component>

            <component v-for="(link, i) in pages()" :key="i"
                :is="link.url ? Link : 'span'" :href="link.url ?? undefined"
                class="pager-btn" :class="{ 'is-active': link.active, 'is-disabled': !link.url }"
                preserve-scroll>
                {{ link.label }}
            </component>

            <component :is="next().url ? Link : 'span'" :href="next().url ?? undefined"
                class="pager-btn" :class="{ 'is-disabled': !next().url }" preserve-scroll>
                下一頁
            </component>
        </div>
    </nav>
</template>

<style scoped>
.pager {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid var(--border);
}

.pager-info {
    font-size: 0.88rem;
    color: var(--text-muted);
}
.pager-info strong { color: var(--text-main); }

.pager-links {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.pager-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    padding: 7px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--surface);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-muted);
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pager-btn:hover:not(.is-active):not(.is-disabled) {
    color: var(--primary);
    border-color: var(--primary);
}
.pager-btn.is-active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    cursor: default;
}
.pager-btn.is-disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

@media (max-width: 640px) {
    .pager { justify-content: center; }
}
</style>
