<!-- 檔案位置: resources/js/Layouts/Layout.vue -->
<script setup lang="ts">
// 引入剛剛做好的 Navbar
import Nav from '../layouts/Nav.vue';
</script>

<template>
    <div class="min-vh-100 bg-light">
        <!-- 呼叫 Navbar 元件 -->
        <Nav />

        <!-- 這裡的 slot 就是用來塞各個不同頁面的主體內容 -->
        <main class="container py-5">
            <slot />
        </main>
    </div>
</template>