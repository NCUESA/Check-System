<script setup lang="ts">
import Editor from '@tinymce/tinymce-vue'
import { useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { toastErrorMessage, toastErrors, toastInfoMessage, toastSuccessMessage } from '../utils';
import { computed } from 'vue';

const apiKey = import.meta.env.VITE_TINYMCE_API_KEY
const f = useForm<{
    title: string, 
    content: string, 
    on_top: boolean
}>({
    title: "", 
    content: "", 
    on_top: false
});

const valid = computed(() => {
    return f.title.length > 0 && f.content.length > 0 && !f.processing;
})

const onSubmit = () => {
    f.post(route('announcements.store'), {
        onSuccess: () => {
            toastSuccessMessage('值勤指南新增成功');
        }, 
        onError: (errors) => {
            toastErrorMessage('值勤指南新增失敗');
        }
    })
}

const onReset = () => {
    f.reset();
}
</script>

<template>
    <h2>值勤指南管理</h2>
    <hr></hr>
    <h3>新增值勤指南</h3>
    <form @submit.prevent="onSubmit" @reset.prevent="onReset">
        <div class="pb-3">
            <label class="form-label">標題</label>
            <input class="form-control" v-model="f.title"></input>
        </div>
        <div class="pb-3">
            <label class="form-label">內容</label>
            <Editor
                :api-key="apiKey"
                :init="{
                    toolbar_mode: 'sliding',
                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                }"
                initial-value=""
                license-key="gpl"
                v-model="f.content"
            />
        </div>
        <div class="form-check pb-3">
            <input type="checkbox" class="form-check-input" v-model="f.on_top"></input>
            <label class="form-check-label">置頂</label>
        </div>
        <div class="btn-group">
            <button type="submit" :disabled="!valid" class="btn btn-success">送出新增</button>
            <button type="button" @click="onReset" class="btn btn-secondary">取消重填</button>
        </div>
    </form>
</template>

<style lang="css"></style>