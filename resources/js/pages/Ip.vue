<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { IpPageProps, AuthIp } from '../types';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { toastErrors, toastInfoMessage, toastErrorMessage } from '../utils';

const cf = useForm<{
    ip_address: string, 
    description: string, 
    status: boolean
}>({
    ip_address: "", 
    description: "", 
    status: true
});

const selectedIp = ref<AuthIp>();
const page = usePage<IpPageProps>();

const onSubmit = () => {
    if (selectedIp.value) {
        cf.put(route('ip.update', { authIp: selectedIp.value.id }), {
            onSuccess: () => {
                toastInfoMessage("更新成功");
                onCancel();
            }, 
            onError: (errors) => {
                toastErrors(errors);
            }
        })
    }
    else {
        cf.post(route('ip.index'), {
            onSuccess: () => {
                toastInfoMessage("新增成功");
                onCancel();
            }, 
            onError: (errors) => {
                toastErrors(errors);
            }
        })
    }
}

const onReset = () => {
    cf.reset();
}

const onEdit = (authIp: AuthIp) => {
    selectedIp.value = authIp;
    cf.defaults({
        ip_address: authIp.ip_address, 
        description: authIp.description, 
        status: authIp.status
    });
    cf.reset();
}

const onCancel = () => {
    selectedIp.value = undefined;
    cf.defaults({
        ip_address: "", 
        description: "", 
        status: true
    });
    cf.reset();
}

const onDelete = (authIpId: number) => {
    const yes = confirm("你確定要刪除這個IP嗎");
    if (!yes) return;
    cf.delete(route('ip.destroy', { authIp: authIpId }), {
        onSuccess: () => {
            toastInfoMessage("刪除成功");
        }, 
        onError: (err) => {
            toastErrorMessage("刪除失敗");
        }
    })
}
</script>

<template>
    <h2>IP通過清單管理</h2>
    <hr>
    <form id='add-ip' @submit.prevent="onSubmit" @reset.prevent="onReset">
        <h6><strong>請注意，此輸入並不防呆，送出前請先再三確認!!!</strong></h6>
        <div class="row g-3 align-items-center">
            <div class="col-1">
                <label for="add_ip" class="col-form-label">IP新增(變更)</label>
            </div>
            <div class="col-2">
                <input type="input" id="add_ip" class="form-control" placeholder="輸入IP" required v-model="cf.ip_address">
            </div>
            <div class="col-1">
                <label for="description" class="col-form-label">IP描述(50字上限)</label>
            </div>
            <div class="col-2">
                <input type="input" id="description" class="form-control" placeholder="做一點描述" required v-model="cf.description">
            </div>
            <div class="col-1">
                <label for="status" class="col-form-label">狀態</label>
            </div>
            <div class="col-2">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="status" :value="true" id="up" required v-model="cf.status">
                    <label class="form-check-label" for="up">
                        UP
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="status" :value="false" id="down" required v-model="cf.status">
                    <label class="form-check-label" for="down">
                        DOWN
                    </label>
                </div>
            </div>
            <div class="col-1 d-grid gap-2">
                <button type="submit" class="btn btn-success btn-block" id="add-ip">送出新增(調整)</button>
            </div>
            <div class="col-1 d-grid gap-2">
                <button type="reset" class="btn btn-danger btn-block">取消重填</button>
            </div>
            <div class="col-1 d-grid gap-2">
                <button type="button" class="btn btn-warning btn-block" @click="onCancel">取消編輯</button>
            </div>
        </div>
    </form>
    <table class="table">
        <thead>
            <tr>
                <th scope="col" hidden></th>
                <th scope="col">IP</th>
                <th scope="col">狀態</th>
                <th scope="col">描述</th>
                <th scope="col">異動</th>
            </tr>
        </thead>
        <tbody id='ip_table'>
            <tr v-for="ip in page.props.ips" :key="ip.id">
                <td>{{ ip.ip_address }}</td>
                <td>{{ ip.status ? "UP" : "DOWN" }}</td>
                <td>{{ ip.description }}</td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info" @click="onEdit(ip)">異動</button>
                        <button type="button" class="btn btn-danger" @click="onDelete(ip.id)">刪除</button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</template>