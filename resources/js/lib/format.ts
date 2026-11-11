const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

export const monthName = (m: number): string => MONTHS[m - 1] ?? String(m);
export const months = MONTHS.map((label, i) => ({ value: i + 1, label }));

/** Rp5.423.600 — angka penuh, format Indonesia, tanpa singkatan. */
export function rupiah(value: string | number | null | undefined): string {
    const n = Number(value ?? 0);
    const text = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: Number.isInteger(n) ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(Math.abs(n));

    return `${n < 0 ? '-' : ''}Rp${text}`;
}

const TZ = 'Asia/Jakarta';

export function formatDate(iso: string | null | undefined, withTime = false): string {
    if (!iso) {
        return '-';
    }
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        return '-';
    }
    const date = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric', timeZone: TZ }).format(d);
    if (!withTime) {
        return date;
    }
    const time = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: TZ }).format(d).replace('.', ':');

    return `${date}, ${time} WIB`;
}

export function relativeTime(iso: string): string {
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return 'baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} hari lalu`;

    return formatDate(iso);
}

/** 196903081993032006 → 19690308 199303 2 006 */
export function formatNip(nip: string | null | undefined): string {
    if (!nip) return '-';
    const s = nip.replace(/\s+/g, '');

    return /^\d{18}$/.test(s) ? `${s.slice(0, 8)} ${s.slice(8, 14)} ${s.slice(14, 15)} ${s.slice(15)}` : nip;
}

export function fileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

export function initials(name: string): string {
    return name.replace(/,.*$/, '').split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]?.toUpperCase()).join('');
}

export function greeting(): string {
    const h = Number(new Intl.DateTimeFormat('id-ID', { hour: '2-digit', hour12: false, timeZone: TZ }).format(new Date()));
    if (h < 11) return 'Selamat pagi';
    if (h < 15) return 'Selamat siang';
    if (h < 18) return 'Selamat sore';

    return 'Selamat malam';
}
