<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
</script>

<template>
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
                    <p>針對特定日期、地點加開臨時值勤名額</p>
                </div>
            </div>
        </header>

        <section class="list-container">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <line x1="12" y1="14" x2="12" y2="18"></line>
                    <line x1="10" y1="16" x2="14" y2="16"></line>
                </svg>
                <h3>建置中</h3>
                <p>這裡之後可以對單一日期加開名額，不受值勤期間的限制。</p>
            </div>
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
</style>
