<!-- File Location: resources/js/Components/Nav.vue -->
<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useNavigation } from '../composables/useNavigation'; 

const page = usePage();
const { services } = useNavigation(); // 取得路由資料

</script>

<template>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <Link class="navbar-brand fw-bold" :href="route('home')">值勤打卡系統</Link>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Left Side Links -->                
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item" v-for="service in services" :key="service.title">
                        <Link class="nav-link" :href="route(service.routeName)">
                            <!-- 你甚至可以把 icon 也放進導覽列裡，看你喜好 -->
                            <i class="bi me-1 d-lg-none" :class="service.icon"></i>
                            {{ service.title }}
                        </Link>
                    </li>
                </ul>

                <!-- Right Side Information -->
                <div class="d-flex align-items-center gap-3">
                    <div class="text-white text-end">
                        <div class="fw-bold fs-6">您好，{{ page.props.auth?.user?.name || '王小明' }}</div>
                    </div>
                    <span class="badge rounded-pill" :class="page.props.is_allowed ? 'bg-success' : 'bg-secondary'">
                        {{ page.props.is_allowed ? '管理員' : '一般部員' }}
                    </span>
                </div>
            </div>
        </div>
    </nav>
</template>