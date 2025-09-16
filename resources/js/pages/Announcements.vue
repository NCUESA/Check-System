<script setup lang="ts">
import AnnouncementAccordionList from '../components/AnnouncementAccordionList.vue';
import { router, usePage } from '@inertiajs/vue3';
import { AnnouncementsPageProps } from '../types';
import { route } from 'ziggy-js';

const page = usePage<AnnouncementsPageProps>();
const onCreateBtnClicked = () => {
    router.visit(route('announcements.create'));
}
</script>

<template>
    <h2>值勤指南一覽</h2>
    <hr></hr>
    <div class="d-grid pb-3">
        <button type="button" class="btn btn-primary" @click="onCreateBtnClicked" v-if="page.props.is_allowed">新增值勤指南</button>
    </div>
    <AnnouncementAccordionList id="announcements-accordion" :announcements="page.props.announcements" />
</template>