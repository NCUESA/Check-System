<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { SharedData } from '../types';

const page = usePage<SharedData>();

const onClick = (routeName: string) => {
    router.visit(route(routeName));
}
</script>

<template>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand name-cn" href="./">值勤打卡系統</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="javascript:void(0)" @click="onClick('home')">首頁</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="javascript:void(0)" @click="onClick('home')">填寫值勤時間</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="javascript:void(0)" @click="onClick('announcements.index')">值勤指南一覽</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="javascript:void(0)" @click="onClick('checklist.index')" v-if="page.props.is_allowed">打卡清單</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="javascript:void(0)" @click="onClick('people.index')" v-if="page.props.is_allowed">人員管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="javascript:void(0)" @click="onClick('cards.index')" v-if="page.props.is_allowed">卡片管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="javascript:void(0)" @click="onClick('ip.index')" v-if="page.props.is_allowed">IP管理</a>
                    </li>
                </ul>
            </div>
            <!-- 新增 IP 和權限顯示 -->
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-2">
                    IP：{{ page.props.ip }}
                </span>
                <span class="badge" :class="{ 'bg-success': page.props.is_allowed, 'bg-dark': !page.props.is_allowed }">
                    {{ page.props.is_allowed ? '管理員' : '一般部員' }}
                </span>
            </div>
        </div>
    </nav>

    <div class="container p-3">
        <slot></slot>
    </div>
</template>