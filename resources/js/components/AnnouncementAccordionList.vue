<script setup lang="ts">
import AnnouncementAccordion from './AnnouncementAccordion.vue';
import { Announcement } from '../types';

const props = defineProps<{
    id: string, 
    announcements: Announcement[]
}>();
</script>

<template>
    <div :id="id" class="accordion">
        <AnnouncementAccordion v-for="announcement in props.announcements" :key="announcement.id" :parent-id="props.id" :announcement="announcement" />
    </div>
</template>