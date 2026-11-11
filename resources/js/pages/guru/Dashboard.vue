<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import SalarySummaryCard from '@/components/payroll/SalarySummaryCard.vue';
import TakeHomePayCard from '@/components/payroll/TakeHomePayCard.vue';
import { formatDate, greeting, rupiah } from '@/lib/format';
import type { PaymentInfo, Period } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    employee: { name: string; status: string };
    current: { period: Period; net: string; school_total: string; take_home: string; payment: PaymentInfo } | null;
    recent: { period_id: number; label: string; take_home: string }[];
    announcements: { id: number; title: string; excerpt: string; published_at: string }[];
}>();
</script>

<template>
    <Head title="Beranda" />
    <div class="mb-5">
        <p class="text-sm text-neutral-600">{{ greeting() }},</p>
        <h1 class="page-title">{{ employee.name }}</h1>
    </div>

    <AppCard v-if="!current"><AppEmptyState title="Rincian gaji belum tersedia" description="Rincian gaji akan muncul di sini setelah dipublikasikan oleh admin keuangan sekolah." /></AppCard>

    <div v-else class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <TakeHomePayCard :value="current.take_home" :period="current.period.label" :net="current.net" :school="current.school_total" />
            <div class="grid gap-3 sm:grid-cols-2">
                <SalarySummaryCard label="Penghasilan bersih" :value="current.net" />
                <SalarySummaryCard label="Potongan sekolah" :value="current.school_total" />
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <AppButton :href="`/saya/rincian/${current.period.id}`" size="lg">Lihat Rincian Gaji</AppButton>
                <AppButton :href="`/saya/slip/${current.period.id}`" external variant="outline" size="lg">Unduh Slip PDF</AppButton>
            </div>
        </div>
        <div class="space-y-4">
            <AppCard title="Pembayaran Potongan">
                <template v-if="current.payment.has_payable">
                    <div class="flex items-center justify-between gap-2"><span class="num text-lg font-bold text-primary-900">{{ rupiah(current.payment.total) }}</span><PaymentStatusBadge :status="current.payment.status" /></div>
                    <p v-if="current.payment.status === 'ditolak'" class="mt-2 rounded-lg bg-danger-bg p-2.5 text-[13px] text-danger-fg">Bukti ditolak: {{ current.payment.rejection_reason }}</p>
                    <AppButton v-if="current.payment.can_upload" class="mt-3" block :href="`/saya/pembayaran/${current.period.id}`">{{ current.payment.status === 'ditolak' ? 'Unggah Ulang Bukti' : 'Bayar & Unggah Bukti' }}</AppButton>
                    <AppButton v-else class="mt-3" block variant="outline" :href="`/saya/pembayaran/${current.period.id}`">Lihat Detail</AppButton>
                </template>
                <p v-else class="text-sm text-neutral-600">Tidak ada potongan sekolah yang perlu dibayar pada periode ini.</p>
            </AppCard>
            <AppCard title="Tiga Periode Terakhir" flush>
                <ul class="divide-y divide-neutral-100">
                    <li v-for="r in recent" :key="r.period_id"><Link :href="`/saya/rincian/${r.period_id}`" class="flex min-h-12 items-center justify-between gap-3 px-4 py-2.5 hover:bg-neutral-50"><span class="text-sm font-medium">{{ r.label }}</span><span class="num text-sm font-semibold text-primary-900">{{ rupiah(r.take_home) }}</span></Link></li>
                </ul>
            </AppCard>
        </div>
    </div>

    <AppCard v-if="announcements.length" class="mt-4" title="Pengumuman" flush>
        <template #header><Link href="/saya/pengumuman" class="text-[13px] font-semibold text-primary-600 hover:underline">Lihat semua</Link></template>
        <ul class="divide-y divide-neutral-100">
            <li v-for="a in announcements" :key="a.id">
                <Link :href="`/saya/pengumuman/${a.id}`" class="block px-4 py-3 hover:bg-neutral-50 sm:px-5">
                    <p class="text-sm font-semibold text-neutral-950">{{ a.title }}</p>
                    <p class="mt-0.5 line-clamp-2 text-[13px] text-neutral-600">{{ a.excerpt }}</p>
                    <p class="mt-1 text-xs text-neutral-400">{{ formatDate(a.published_at) }}</p>
                </Link>
            </li>
        </ul>
    </AppCard>
</template>
