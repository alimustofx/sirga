export * from './ui';

export type Role = 'super_admin' | 'admin_keuangan' | 'guru';

export type AuthUser = {
    id: number;
    nip: string;
    name: string;
    email: string | null;
    role: Role;
    role_label: string;
    has_employee: boolean;
    must_change_password: boolean;
};

export type NotificationItem = {
    id: number;
    type: string;
    title: string;
    body: string | null;
    url: string | null;
    read: boolean;
    created_at: string;
};

export type Credentials = {
    list: { nip: string; name: string; password: string }[];
    skipped: number;
};

export type PeriodStatus = 'draft' | 'review' | 'published' | 'closed';
export type PaymentStatus = 'belum_dibayar' | 'menunggu_verifikasi' | 'lunas' | 'ditolak';

export type Period = {
    id: number;
    label: string;
    month: number;
    year: number;
    status: PeriodStatus;
    status_label: string;
};

export type Line = { name: string; amount: string };

export type PayrollDetail = {
    id: number;
    period: Period;
    employee: {
        name: string;
        nip: string;
        status: string;
        rank: string | null;
        position: string | null;
        unit: string | null;
        account: string | null;
    };
    earnings: Line[];
    official: Line[];
    deductions: Line[];
    gross: string;
    official_total: string;
    net: string;
    school_total: string;
    take_home: string;
};

export type Proof = {
    id: number;
    filename: string;
    size: number;
    mime: string;
    uploaded_at: string | null;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason: string | null;
    verified_at: string | null;
    verifier: string | null;
    url: string;
};

export type PaymentInfo = {
    status: PaymentStatus;
    status_label: string;
    total: string;
    has_payable: boolean;
    rejection_reason: string | null;
    verified_at: string | null;
    proofs: Proof[];
    account: { bank: string; number: string; holder: string; instruction: string | null } | null;
    transfer_note: string;
    can_upload: boolean;
    closed: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type SelectOption = { value: string | number; label: string };

export type ProfileData = {
    name: string;
    nip: string;
    email: string | null;
    role: string;
    last_login_at: string | null;
    employee: { status: string; rank: string | null; position: string | null; unit: string | null; account: string | null } | null;
};
