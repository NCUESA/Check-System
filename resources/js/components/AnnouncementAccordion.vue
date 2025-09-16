<script setup lang="ts">
import Editor from '@tinymce/tinymce-vue'
import { Announcement } from '../types';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const apiKey = import.meta.env.VITE_TINYMCE_API_KEY

const props = defineProps<{
    parentId: string
    announcement: Announcement
}>();

const tinyMCEConfig = ref({
    menubar: false, 
    toolbar: false
});

const collapseId = computed(() => {
    return "collapse" + props.announcement.id
});

const headerId = computed(() => {
    return "heading" + props.announcement.id
});

const onUpdateBtnClicked = () => {
    router.visit(route('announcements.edit', { announcement: props.announcement.id }));
}
</script>

<template>
    <div class="accordion-item">
        <h2 class="accordion-header" :id="headerId">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" :data-bs-target="`#${collapseId}`" aria-expanded="false" :aria-controls="collapseId" :class="{
            'fw-bold': announcement.on_top
        }">
            {{ props.announcement.title }}
        </button>
        </h2>
        <div :id="collapseId" class="accordion-collapse collapse" :aria-labelledby="headerId" :data-bs-parent="`#${parentId}`">
            <div class="accordion-body">
                <Editor class="accordion-body"
                    :api-key="apiKey"
                    :initial-value="props.announcement.content"
                    :init="tinyMCEConfig"
                    :readonly="true"
                />
                <div class="d-grid">
                    <button type="button" class="btn btn-warning btn-block" @click="onUpdateBtnClicked">更新指南</button>
                </div>    
            </div>
        </div>
    </div>
</template>