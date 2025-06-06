<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Person, PeoplePageProps } from '../types';
import { route } from 'ziggy-js'

const page = usePage<PeoplePageProps>();
const currentPerson = ref<Person>();

const f = useForm<{
    name: string, 
    stu_id: string, 
    status?: boolean
}>({
    name: "", 
    stu_id: "", 
    status: undefined
});

const setPerson = (person: Person) => {
    currentPerson.value = person;
    f.defaults(person);
    f.reset();
}

const deletePerson = (personId: number) => {
    f.delete(route('people.delete', { person: personId }), {
        onSuccess: () => {
            alert("Success");
        }, 
        onError: () => {
            alert("Failed :(");
        }
    });
}

const cancel = () => {
    currentPerson.value = undefined;
    f.defaults({
        name: "", 
        stu_id: "", 
        status: undefined
    });
    f.reset();
}

const onSubmit = () => {
    if (currentPerson.value) {
        f.put(route('people.update', { person: currentPerson.value.id }), {
            onSuccess: () => {
                
            }, 
            onError: (err) => {
                Object.entries(err)
            }
        })
    }
    else {
        f.post(route('people.store'), {
            onSuccess: () => {
                
            }, 
            onError: (err) => {
                Object.entries(err)
            }
        })
    }
}

const onReset = () => {
    f.reset();
}
</script>

<template>
    <h2>人員清單管理</h2>
    <hr>
    <form id="add-person" @submit.prevent="onSubmit" @reset.prevent="onReset">
        <strong>請注意，此輸入並不防呆，送出前請先再三確認!!!</strong>
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-4">
                <label for="add_person" class="form-label">人員新增(變更)</label>
                <input type="input" id="add_person" class="form-control" placeholder="輸入姓名" required v-model="f.name">
            </div>
            <div class="col-12 col-md-4">
                <label for="stu_id" class="form-label">學號(變更)</label>
                <input type="input" id="stu_id" class="form-control" placeholder="輸入學號" required v-model="f.stu_id">
            </div>
            <div class="col-12 col-md-4">
                <label for="status" class="form-label">狀態</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" :value="true" id="up" required v-model="f.status">
                        <label class="form-check-label" for="up">UP</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" :value="false" id="down" required v-model="f.status">
                        <label class="form-check-label" for="down">DOWN</label>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="btn-group">
                    <button type="submit" class="btn btn-success" v-if="currentPerson">Update</button>
                    <button type="submit" class="btn btn-success" v-else>Create</button>
                    <button type="reset" class="btn btn-danger">Reset</button>
                    <button type="reset" class="btn btn-secondary" v-if="currentPerson" @click="cancel">Cancel</button>
                </div>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">學號</th>
                    <th scope="col">姓名</th>
                    <th scope="col">狀態</th>
                    <th scope="col" class="col-1">異動</th>
                </tr>
            </thead>
            <tbody id='people_status'>
                <tr v-for="person in page.props.people" :key="person.id">
                    <td>{{ person.id }}</td>
                    <td>{{ person.stu_id }}</td>
                    <td>{{ person.name }}</td>
                    <td>{{ person.status === undefined ? "UNKNOWN" : person.status ? "UP" : "DOWN" }}</td>
                    <td>
                        <div class="btn-group align-self-end">
                            <button type="button" class="btn btn-primary" @click="setPerson(person)">Edit</button>
                            <button type="button" class="btn btn-danger" @click="deletePerson(person.id)">Delete</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>