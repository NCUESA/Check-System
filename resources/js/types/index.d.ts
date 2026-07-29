import { PageProps } from '@inertiajs/core'

export interface AuthUser {
    id: number
    name: string | null
    stu_id: string
    email: string | null
    status: boolean
}

export interface SharedData extends PageProps {
    ip: string,
    is_admin: boolean,
    message?: string,
    auth: {
        user: AuthUser | null
    }
}

export interface IndexPageProps extends SharedData {
    checklist?: Checklist, 
    time?: Date, 
    status?: boolean
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

// Laravel 分頁器送來的形狀
export interface Paginated<T> {
    data: T[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
    from: number | null,
    to: number | null,
    links: { url: string | null, label: string, active: boolean }[]
}

export interface LocationOption {
    id: number,
    name: string
}

// 簽到表總覽列出的一筆紀錄（後端 presentRow 整理過的形狀）
export interface ChecklistRow {
    id: number,
    checkin_time: string | null,
    checkout_time: string | null,
    // true = 手動補登，false = 刷卡
    checkin_manual: boolean,
    checkout_manual: boolean,
    checkin_ip: string,
    checkout_ip: string,
    checkin_location_id: number | null,
    checkout_location_id: number | null,
    checkin_location: string | null,
    checkout_location: string | null,
    user: { id: number, name: string | null, stu_id: string } | null
}

export interface ChecklistFilters {
    q: string | null,
    date_from: string | null,
    date_to: string | null,
    checkin_location_id: number | null,
    checkout_location_id: number | null
}

// 首頁打卡結果用的原始 model 形狀
export interface Checklist {
    id: number
    checkin_time: Date,
    checkin_operation: boolean,
    checkout_time: Date,
    checkout_operation : boolean,
    checkin_ip: string,
    checkout_ip: string,
    // SSO 改版後 person 已併入 users，關聯改成 user
    user_id: number,
    user?: AuthUser
}

export interface ChecklistPageProps extends SharedData {
    checklists: Paginated<ChecklistRow>,
    locations: LocationOption[],
    filters: ChecklistFilters,
    // 只有按下匯出時才會被載入
    exportRows?: ChecklistRow[]
}

export interface ManualCheckPageProps extends SharedData {
    locations: LocationOption[]
}

export interface Card {
    id: number,
    inner_code: string,
    user_id: number,
    status: boolean,
    owner?: Person
}

export interface CardPageProps extends SharedData {
    cards: Card[], 
    people?: Person[]
}

export interface Location {
    id: number,
    name: string,
    capacity: number,
    allowance: number,
    hours: number,
    status: boolean
}

export interface LocationPageProps extends SharedData {
    locations: Location[]
}

// calendar 資料表的一列 = 一個值勤名額
// user 為 null 表示還沒有人認領；status 為 false 表示這個名額停用（不開放）
export interface DutySlot {
    id: number,
    status: boolean,
    // 填寫期間已截止（或名額不屬於任何期間）→ 只有管理員能異動
    locked: boolean,
    user: { id: number, name: string | null } | null
}

export interface AssignableUser {
    id: number,
    name: string | null,
    stu_id: string
}

export interface DutyCalendarPageProps extends SharedData {
    locations: Location[],
    locationId: number | null,
    year: number,
    month: number,
    // 以日期字串分組，例如 { '2026-07-05': [slot, slot] }
    slots: Record<string, DutySlot[]>,
    // 只有管理員拿得到，用來指派名額
    users: AssignableUser[]
}

// 管理員名單頁列出的人員
export interface ManagedUser {
    id: number,
    name: string | null,
    stu_id: string,
    status: boolean,
    is_admin: boolean
}

export type AdminFilter = 'all' | 'admin' | 'normal';

export interface AdminsPageProps extends SharedData {
    users: ManagedUser[],
    filters: { q: string | null, filter: AdminFilter },
    adminCount: number
}

// 值勤期間目前處於哪個階段
export type DutyPeriodPhase = 'upcoming' | 'open' | 'closed' | 'disabled';

export interface DutyPeriodRow {
    id: number,
    name: string,
    duty_start_date: string,
    duty_end_date: string,
    open_at: string,
    close_at: string,
    status: boolean,
    slot_count: number,
    filled_count: number,
    phase: DutyPeriodPhase
}

export interface DutyPeriodsPageProps extends SharedData {
    periods: DutyPeriodRow[],
    locations: Location[]
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

export interface Announcement {
    id: number, 
    title: string, 
    content: string, 
    on_top: boolean
}

export interface AnnouncementsPageProps extends SharedData {
    announcements: Announcement[]
}

export interface UpdateAnnouncementPageProps extends SharedData {
    announcement?: Announcement
}