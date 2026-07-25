// 檔案位置：resources/js/composables/useNavigation.ts
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { SharedData } from '../types';

export function useNavigation() {
    const page = usePage<SharedData>();
    const isAllowed = computed(() => page.props.is_admin);

    const services = computed(() => [
        { 
            title: '嗶卡簽到', 
            icon: 'bi-credit-card-2-front', 
            desc: '使用實體卡片感應打卡', 
            routeName: 'duty.index', 
            color: 'text-primary' 
        },
        { 
            title: '手機簽到', 
            icon: 'bi-phone', 
            desc: '使用行動裝置快速打卡', 
            routeName: 'duty.phone', 
            color: 'text-success' 
        },
        { 
            title: '填寫值勤', 
            icon: 'bi-pencil-square', 
            desc: '手動補登或填寫時數', 
            routeName: 'duty.calendar', 
            color: 'text-warning' 
        },
        { 
            title: '指南路一段', 
            icon: 'bi-book', 
            desc: '查看系統操作與值勤規範', 
            routeName: 'announcements.index', 
            color: 'text-info' 
        },
        { 
            title: '打卡全表管理', 
            icon: 'bi-list-check', 
            desc: '檢視與匯出所有打卡紀錄', 
            routeName: 'checklist.index', 
            color: 'text-danger',
            requireAdmin: true 
        },
        { 
            title: '系統設定', 
            icon: 'bi-gear', 
            desc: '系統參數、IP與權限設定', 
            routeName: 'system.index', 
            color: 'text-secondary',
            requireAdmin: true
        },
    ].filter(service => !service.requireAdmin || isAllowed.value));

    return {
        services
    };
}