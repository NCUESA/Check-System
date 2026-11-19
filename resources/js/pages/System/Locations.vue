<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Location, LocationPageProps } from '../../types';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { toastErrors, toastSuccessMessage } from '../../utils';

const page = usePage<LocationPageProps>();
const lf = useForm<{
    name: string,
    capacity: number,
    allowance: number,
    hours: number,
    status: boolean
}>({
    name: "",
    capacity: 0,
    allowance: 0,
    hours: 0,
    status: true
});

const selectedLocation = ref<Location>();

const onSelect = (location: Location) => {
    selectedLocation.value = location;
    lf.defaults({
        name: location.name,
        capacity: location.capacity,
        allowance: location.allowance,
        hours: location.hours,
        status: location.status
    });
    lf.reset();
}

const onCancel = () => {
    selectedLocation.value = undefined;
    lf.defaults({
        name: "",
        capacity: 0,
        allowance: 0,
        hours: 0,
        status: true
    });
    lf.reset();
}

const onSubmit = () => {
    if (selectedLocation.value) {
        lf.put(route('system.locations.update', { location: selectedLocation.value.id }), {
            onSuccess: () => {
                toastSuccessMessage("修改成功");
                onCancel();
            },
            onError: (err) => {
                toastErrors(err);
            }
        })
    }
    else {
        lf.post(route('system.locations.store'), {
            onSuccess: () => {
                toastSuccessMessage("新增成功");
                onCancel();
            },
            onError: (err) => {
                toastErrors(err);
            }
        })
    }
}
</script>

<template>
    <div class="sys-container">
        
        <!-- 頂部標題區 -->
        <header class="sys-header">
            <div>
                <h1>值勤地點管理</h1>
                <p>設定、追蹤與分配系統內的所有值勤地點與人數上限</p>
            </div>
        </header>

        <!-- 雙欄佈局容器 -->
        <div class="layout-split">
            
            <!-- 左欄：主體清單區塊 -->
            <main class="list-column">
                <section class="list-container">
                    
                    <!-- 現代化數據清單 -->
                    <div class="table-responsive">
                        <table class="data-list">
                            <thead>
                                <tr>
                                    <th>地點名稱</th>
                                    <th>值勤人數上限</th>
                                    <th>值勤津貼</th>
                                    <th>當前狀態</th>
                                    <th class="align-right">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="location in page.props.locations" :key="location.id" :class="{ 'is-inactive': !location.status, 'is-selected': selectedLocation?.id === location.id }">
                                    <td>
                                        <div class="location-name">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            {{ location.name }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="capacity-value">
                                            {{ location.capacity }} <span class="capacity-unit">人</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="capacity-value">
                                            ${{ location.allowance }} <span class="capacity-unit">/ {{ location.hours }} 小時</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge" :class="location.status ? 'badge-up' : 'badge-down'">
                                            <span class="status-dot"></span>
                                            {{ location.status ? '啟用中' : '已停用' }}
                                        </span>
                                    </td>
                                    <td class="align-right">
                                        <div class="row-actions">
                                            <button class="action-btn edit" @click="onSelect(location)" title="編輯地點">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- 空狀態 -->
                                <tr v-if="!page.props.locations || page.props.locations.length === 0">
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            <h3>尚無地點資料</h3>
                                            <p>目前系統中沒有任何地點設定，請由右側面板新增。</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <!-- 右欄：表單控制面板 -->
            <aside class="form-column">
                <section class="control-panel" :class="{ 'is-editing': selectedLocation }">
                    <div class="panel-header">
                        <div class="icon-wrapper" :class="selectedLocation ? 'edit-mode' : 'create-mode'">
                            <svg v-if="selectedLocation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </div>
                        <h2 class="panel-title">{{ selectedLocation ? '編輯地點' : '新增地點' }}</h2>
                    </div>
                    
                    <form @submit.prevent="onSubmit" class="vertical-form">
                        <div class="input-group">
                            <label>地點名稱</label>
                            <input type="text" v-model="lf.name" placeholder="輸入地點名稱" required>
                        </div>

                        <div class="input-group">
                            <label>值勤人數</label>
                            <input type="number" v-model="lf.capacity" min="0" placeholder="設定人數上限" required>
                        </div>

                        <div class="input-group">
                            <label>值勤津貼（元）</label>
                            <input type="number" v-model="lf.allowance" min="0" step="1" placeholder="每次值勤的固定津貼" required>
                        </div>

                        <div class="input-group">
                            <label>值勤時數（小時，僅記錄用）</label>
                            <input type="number" v-model="lf.hours" min="0" step="0.5" placeholder="每次值勤的時數" required>
                        </div>

                        <div class="input-group">
                            <label>地點狀態</label>
                            <div class="status-toggle">
                                <label class="toggle-option">
                                    <input type="radio" :value="true" v-model="lf.status">
                                    <span class="toggle-btn up">啟用</span>
                                </label>
                                <label class="toggle-option">
                                    <input type="radio" :value="false" v-model="lf.status">
                                    <span class="toggle-btn down">停用</span>
                                </label>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="action-group">
                            <button type="submit" class="sys-btn-primary" :disabled="lf.processing">
                                {{ selectedLocation ? '儲存變更' : '新增地點' }}
                            </button>
                            <button type="button" class="sys-btn-ghost" v-if="selectedLocation" @click="onCancel">
                                取消
                            </button>
                        </div>
                    </form>
                </section>
            </aside>

        </div>
    </div>
</template>

<style scoped>
/* 這個頁面獨有的樣式，共用的設計系統在 resources/css/system-settings.css */

/* 表格內容樣式 */
.location-name {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--text-main);
}
.location-name svg {
    width: 20px;
    height: 20px;
    color: var(--primary);
}
.is-inactive .location-name svg {
    color: var(--text-muted);
}
.capacity-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text-main);
}
.capacity-unit {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-muted);
    margin-left: 2px;
}

</style>