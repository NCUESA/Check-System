<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Card, CardPageProps } from '../types';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const page = usePage<CardPageProps>();
const cf = useForm<{
    inner_code: string, 
    person_id?: number, 
    status: boolean
}>({
    inner_code: "", 
    person_id: undefined, 
    status: true
});

const selectedCard = ref<Card>();

const onSelect = (card: Card) => {
    selectedCard.value = card;
}

const onSubmit = () => {
    if (selectedCard.value) {

    }
    else {
        cf.post(route('cards.store'), {
            onSuccess: () => {
                cf.reset();
            },
            onError: (err) => {

            }
        })
    }
}

const onReset = () => {
    cf.reset();
}
</script>

<template>
    <h2 class="pb-3">卡片管理</h2>
    <form id='add-card' @submit.prevent="onSubmit" @reset.prevent="onReset">
        <h6 class="mb-3"><strong>請注意，此輸入並不防呆，送出前請先再三確認!!!</strong></h6>
        <div class="row g-3 align-items-center">
            <div class="col-2" hidden>
                <input type="input" id="id" class="form-control" placeholder="id">
            </div>
            <div class="col-1">
                <label for="inner-code" class="col-form-label">卡號內碼</label>
            </div>
            <div class="col-2">
                <input type="input" id="inner-code" class="form-control" placeholder="請輸入卡號內碼" required v-model="cf.inner_code">
            </div>
            <div class="col-1">
                <label for="person-select" class="col-form-label" >卡片擁有者</label>
            </div>
            <div class="col-2">
                <select id="person-select" required class="form-select" v-model="cf.person_id">
                    <option :value="undefined" selected>請選擇卡片擁有者</option>
                    <option v-for="person in page.props.people" :key="person.id" :value="person.id">{{ person.stu_id }} {{ person.name }}</option>
                </select>
            </div>
            <div class="col-2">
                <label for="status" class="col-form-label">狀態</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="status" :value="true" required v-model="cf.status">
                    <label class="form-check-label" for="up">
                        UP
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="status" :value="false" required v-model="cf.status">
                    <label class="form-check-label" for="down">
                        DOWN
                    </label>
                </div>
            </div>
            <div class="col-2 d-grid gap-2">
                <button type="submit" class="btn btn-success btn-block" id="submit">送出新增(調整)</button>
            </div>
            <div class="col-1 d-grid gap-2">
                <button type="button" class="btn btn-warning btn-block" id="delete">刪除此筆</button>
            </div>
            <div class="col-1 d-grid gap-2">
                <button type="reset" class="btn btn-danger btn-block" id="reset">取消重填</button>
            </div>
        </div>
    </form>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">擁有者</th>
                <th scope="col">卡號內碼</th>
                <th scope="col">狀態</th>
                <th scope="col">異動</th>
            </tr>
        </thead>
        <tbody id='cards_table'>
            <tr v-for="card in page.props.cards" :key="card.id">
                <td>{{ card.owner?.stu_id }} {{ card.owner?.name }}</td>
                <td>{{ card.inner_code }}</td>
                <td>{{ card.status ? "UP" : "DOWN" }}</td>
                <td>
                    <div class="btn-grouzp">
                        <button type="button" class="btn btn-info">異動</button>
                        <button type="button" class="btn btn-danger">刪除</button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</template>