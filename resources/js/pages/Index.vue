<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

// Import the route from definition file
// (But the definition file is not sync with Route) 
import { useNavigation } from '../composables/useNavigation';
import { SharedData } from '../types';

const page = usePage<SharedData>();
const { services } = useNavigation(); // 取得路由資料
const user = computed(() => page.props.auth?.user ?? null);
</script>

<template>
    <div>
        <!-- 上半部：功能捷徑卡片區 (Grid System) -->
        <div class="row g-4 mb-5" v-if="user">
            <div
                class="col-12 col-sm-6 col-lg-4"
                v-for="service in services"
                :key="service.title"
            >
                <Link :href="route(service.routeName)" class="text-decoration-none">
                    <div class="card h-100 shadow-sm border-0 service-card">
                        <div class="card-body text-center p-4 d-flex flex-column justify-content-center">
                            <!-- 圖示 -->
                            <i class="bi mb-3" :class="[service.icon, service.color]" style="font-size: 2.5rem;"></i>
                            <!-- 標題與描述 -->
                            <h5 class="fw-bold text-dark mb-2">{{ service.title }}</h5>
                            <p class="text-muted small mb-0">{{ service.desc }}</p>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
        <div class="text-center text-muted py-5" v-else>
            請先登入以使用系統功能
        </div>
    </div>
</template>

<style scoped>
/* 讓卡片有可點擊的互動感：滑鼠移過去時會微微上浮並加深陰影 */
.service-card {
    transition: all 0.2s ease-in-out;
}
.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
}
</style>