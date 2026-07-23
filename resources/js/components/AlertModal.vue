<!-- resources/js/Components/AlertModal.vue -->
<script setup lang="ts">
import { ref } from 'vue';

const isVisible = ref(false);
const message = ref('');
const title = ref('提示');

// 開放給父元件呼叫的方法
const show = (msg: string, titleText: string = '系統提示') => {
    message.value = msg;
    title.value = titleText;
    isVisible.value = true;
};

const close = () => {
    isVisible.value = false;
};

// 暴露方法讓外部可以使用 ref 來呼叫
defineExpose({ show, close });
</script>

<template>
    <!-- 黑色半透明遮罩 -->
    <div v-if="isVisible" class="modal-backdrop fade show" style="z-index: 1050;"></div>
    
    <!-- 彈窗主體 -->
    <div 
        v-if="isVisible" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="z-index: 1055;"
        @click.self="close"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>{{ title }}
                    </h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>
                <div class="modal-body py-4 fs-5 text-center text-secondary">
                    {{ message }}
                </div>
                <div class="modal-footer border-top-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-primary px-5 rounded-pill fw-bold" @click="close">
                        我知道了
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>