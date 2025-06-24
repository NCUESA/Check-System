<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { SharedData } from '../types';

const page = usePage<SharedData>();
const currentTime = ref<Date>(new Date());

setInterval(function () {
    currentTime.value = new Date();
});

const cf = useForm<{
    inner_code: string
}>({
    inner_code: ""
});

const check = () => {
    cf.put(route('check'), {
        onSuccess: () => {
            alert(page.props.message);
        }, 
        onError: (err) => {

        }
    })
}
</script>

<template>
    <br>
        <h2 class="name-cn" style="font-size: 52px">現在時間： <span class="badge bg-secondary">{{ currentTime.toLocaleString() }}</span>
    </h2>
    <hr>
    <div class="input-group input-group-lg">
        <span class="input-group-text" id=""><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                fill="currentColor" class="bi bi-bookmark-check" viewBox="0 0 16 16">
                <path fill-rule="evenodd"
                    d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0" />
                <path
                    d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1z" />
            </svg>打卡欄位</span>
        <input type="text" class="form-control" placeholder="請嗶卡" aria-label="" aria-describedby="basic-addon1"
            id="check_input" inputmode="numeric" @keydown.enter="check" v-model="cf.inner_code">
    </div>


    <hr>
    <div class="row">
        <div class="input-group input-group-lg">
            <span class="input-group-text" id=""><svg xmlns="http://www.w3.org/2000/svg" width="16"
                    height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
                    <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                </svg>人員</span>
            <input type="text" class="form-control" placeholder="" aria-label="" aria-describedby="basic-addon1"
                disabled id="person">
            <span class="input-group-text" id=""><svg xmlns="http://www.w3.org/2000/svg" width="16"
                    height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z" />
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0" />
                </svg>打卡時間</span>
            <input type="text" class="form-control" placeholder="" aria-label="" aria-describedby="basic-addon1"
                disabled id="check_time">
            <span class="input-group-text" id=""><svg xmlns="http://www.w3.org/2000/svg" width="16"
                    height="16" fill="currentColor" class="bi bi-chat-dots-fill" viewBox="0 0 16 16">
                    <path
                        d="M16 8c0 3.866-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7M5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                </svg>打卡狀態</span>
            <input type="text" class="form-control" placeholder="" aria-label="" aria-describedby="basic-addon1"
                disabled id="check_status">
        </div>
    </div>
    <hr>
    <div class="alert alert-secondary" role="alert">
        <h3>注意事項</h3>
        <ul>
            <li style="padding: 5px">
                <button type="button" class="btn btn-success">
                    簽到成功 <span class="badge bg-secondary">圖標是綠色的</span>
                </button>
            </li>
            <li style="padding: 5px">
                <button type="button" class="btn btn-primary">
                    簽退成功 <span class="badge bg-secondary">圖標是藍色的</span>
                </button>
            </li>
            <li style="padding: 5px">
                <button type="button" class="btn btn-danger">
                    簽到失敗 <span class="badge bg-secondary">圖標是紅色的</span>
                </button>
            </li>
            <li>簽到簽退中間有兩分鐘的緩衝，但簽退後再簽到則沒有</li>
            <li>如果卡不能刷請通知人文部做處理</li>
        </ul>
    </div>
</template>