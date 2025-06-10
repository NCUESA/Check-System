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

export type CheckLoc = "jinde" | "bousan" | "other";
export interface Checklist {
    id: number
    name: string, 
    checkin_time: Date,
    checkin_operation: string,
    checkout_time: Date,
    checkout_operation : boolean,
    checkin_ip: string,
    checkout_ip: string,
    person_id: number, 
    person?: Person
}

export interface ChecklistPageProps extends SharedData {
    checklists: Checklist[]
}

export interface Card {
    id: number, 
    inner_code: string, 
    person_id: number, 
    status: boolean, 
    owner?: Person
}

export interface CardPageProps extends SharedData {
    cards: Card[], 
    people?: Person[]
}

export interface AuthIp {
    id: number, 
    ip_address: string, 
    description: string, 
    status: boolean
}

export interface IpPageProps extends SharedData {
    ips: AuthIp[]
}