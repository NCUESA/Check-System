<!-- resources/js/Components/AlertModal.vue -->
<script setup lang="ts">
import { ref } from 'vue';

const isVisible = ref(false);
const message = ref('');
const title = ref('提示');
const mode = ref<'alert' | 'confirm'>('alert');
const onConfirmCallback = ref<(() => void) | null>(null);

// 開放給父元件呼叫的方法
const show = (msg: string, titleText: string = '系統提示') => {
    message.value = msg;
    title.value = titleText;
    mode.value = 'alert';
    isVisible.value = true;
};

// 確認/取消模式，onConfirm 只有按下「確定」才會被呼叫
const confirm = (msg: string, onConfirm: () => void, titleText: string = '請確認') => {
    message.value = msg;
    title.value = titleText;
    mode.value = 'confirm';
    onConfirmCallback.value = onConfirm;
    isVisible.value = true;
};

const close = () => {
    isVisible.value = false;
};

const onConfirmClick = () => {
    onConfirmCallback.value?.();
    close();
};

// 暴露方法讓外部可以使用 ref 來呼叫
defineExpose({ show, confirm, close });
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
                        <i class="bi me-2" :class="mode === 'confirm' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-info-circle-fill text-primary'"></i>{{ title }}
                    </h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>
                <div class="modal-body py-4 fs-5 text-center text-secondary">
                    {{ message }}
                </div>
                <div class="modal-footer border-top-0 pt-0 justify-content-center">
                    <template v-if="mode === 'confirm'">
                        <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-bold" @click="close">
                            取消
                        </button>
                        <button type="button" class="btn btn-danger px-4 rounded-pill fw-bold" @click="onConfirmClick">
                            確定
                        </button>
                    </template>
                    <button v-else type="button" class="btn btn-primary px-5 rounded-pill fw-bold" @click="close">
                        我知道了
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>