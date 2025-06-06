import { PageProps, Page } from '@inertiajs/core'

export interface SharedData extends PageProps {

}

export interface Person {
    id: number 
    name: string, 
    stu_id: string, 
    status: boolean
}

export interface PeoplePageProps extends SharedData {
    people: Person[]
}