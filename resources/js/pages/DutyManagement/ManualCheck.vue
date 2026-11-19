<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import CheckRecordForm from '../../components/CheckRecordForm.vue';
import type { ManualCheckPageProps } from '../../types';

const page = usePage<ManualCheckPageProps>();
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
                    <h1>手動簽到退</h1>
                    <p>為人員補登簽到與簽退紀錄，補登的紀錄會標記為「手動」</p>
                </div>
            </div>
        </header>

        <div class="single-column">
            <section class="control-panel">
                <div class="panel-header">
                    <div class="icon-wrapper create-mode">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </div>
                    <h2 class="panel-title">補登紀錄</h2>
                </div>

                <CheckRecordForm :locations="page.props.locations" />
            </section>

            <p class="foot-hint">
                要查詢或修改既有紀錄，請到
                <Link :href="route('duty-management.checklist')">簽到表總覽</Link>。
            </p>
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

.single-column {
    width: 100%;
}

.foot-hint {
    margin: 16px 0 0;
    font-size: 0.88rem;
    color: var(--text-muted);
}
.foot-hint a {
    color: var(--primary);
    font-weight: 600;
    text-decoration: none;
}
.foot-hint a:hover { text-decoration: underline; }
</style>
