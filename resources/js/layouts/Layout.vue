<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
const page = usePage();
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
                        <a class="nav-link active" aria-current="page" href="./">首頁</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./duty">填寫值勤時間</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./checklist">打卡清單</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./person">人員管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./cards">卡片管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./ip">IP管理</a>
                    </li>
                    
                </ul>
            </div>
            <!-- 新增 IP 和權限顯示 -->
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-2">
                    IP：{{ $clientIp }}
                </span>
                <span class="badge {{ $hasAccess ? 'bg-black' : 'bg-success' }}">
                    {{ $hasAccess ? '管理員' : '一般部員' }}
                </span>
            </div>
        </div>
    </nav>

    <div class="container">
        <slot></slot>
    </div>
</template>