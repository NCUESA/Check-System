<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

interface Feature {
    title: string,
    icon: string,
    desc: string,
    routeName: string,
    color: string,
    // 額外的查詢參數，例如「指定值勤」要帶 manage=1
    routeParams?: Record<string, string | number>
}

const features: Feature[] = [
    {
        title: '手動簽到退',
        icon: 'bi-hand-index-thumb',
        desc: '為人員補登簽到與簽退紀錄',
        routeName: 'duty-management.manual',
        color: 'text-primary'
    },
    {
        title: '簽到表總覽',
        icon: 'bi-table',
        desc: '查詢所有簽到紀錄、產生月報表',
        routeName: 'duty-management.checklist',
        color: 'text-success'
    },
    {
        title: '值勤時間設定',
        icon: 'bi-clock-history',
        desc: '開放值勤填寫期間、設定截止時間',
        routeName: 'duty-management.periods',
        color: 'text-warning'
    },
    {
        title: '臨時增額',
        icon: 'bi-calendar-plus',
        desc: '針對特定日期、地點加開臨時值勤名額',
        routeName: 'duty-management.capacity',
        color: 'text-danger'
    },
    {
        title: '指定值勤',
        icon: 'bi-person-check',
        desc: '把值勤名額指派給特定人員，或退掉既有的班',
        // manage=1 讓月曆一進去就是管理模式
        routeName: 'duty.calendar',
        routeParams: { manage: 1 },
        color: 'text-info'
    },
];
</script>

<template>
    <div>
        <div class="row g-4">
            <div
                class="col-12 col-sm-6 col-lg-4"
                v-for="feature in features"
                :key="feature.title"
            >
                <Link :href="route(feature.routeName, feature.routeParams ?? {})" class="text-decoration-none">
                    <div class="card h-100 shadow-sm border-0 feature-card">
                        <div class="card-body text-center p-4 d-flex flex-column justify-content-center">
                            <i class="bi mb-3" :class="[feature.icon, feature.color]" style="font-size: 2.5rem;"></i>
                            <h5 class="fw-bold text-dark mb-2">{{ feature.title }}</h5>
                            <p class="text-muted small mb-0">{{ feature.desc }}</p>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.feature-card {
    transition: all 0.2s ease-in-out;
}
.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
}
</style>
