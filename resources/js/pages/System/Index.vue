<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const features = [
    {
        title: '卡片管理',
        icon: 'bi-credit-card',
        desc: '將人員與卡片號碼綁定、解除綁定',
        routeName: 'cards.index',
        color: 'text-primary'
    },
    {
        title: '管理員名單',
        icon: 'bi-person-badge',
        desc: '賦予或移除管理員權限',
        routeName: 'system.admins.index',
        color: 'text-success'
    },
    {
        title: '地點管理',
        icon: 'bi-geo-alt',
        desc: '新增、調整地點名稱與值勤人數',
        routeName: 'system.locations.index',
        color: 'text-warning'
    },
    {
        title: '臨時增額',
        icon: 'bi-calendar-plus',
        desc: '針對特定日期、地點調整臨時值勤名額',
        routeName: 'system.capacity.index',
        color: 'text-danger'
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
                <Link :href="route(feature.routeName)" class="text-decoration-none">
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
