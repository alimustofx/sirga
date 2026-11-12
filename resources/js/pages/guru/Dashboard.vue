<script setup lang="ts">
import StatStrip from '@/components/admin/StatStrip.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import PaymentTimeline from '@/components/payment/PaymentTimeline.vue';
import TakeHomePayCard from '@/components/payroll/TakeHomePayCard.vue';
import ComposeBar from '@/components/viz/ComposeBar.vue';
import TrendBars from '@/components/viz/TrendBars.vue';
import { formatDate, greeting, rupiah } from '@/lib/format';
import type { PaymentInfo, Period } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Download } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    employee: { name: string; status: string };
    current: { period: Period; gross: string; official_total: string; net: string; school_total: string; take_home: string; payment: PaymentInfo } | null;
    recent: { period_id: number; label: string; short: string; gross: string; net: string; school_total: string; take_home: string }[];
    announcements: { id: number; title: string; excerpt: string; published_at: string }[];
}>();

const parts = computed(() => props.current ? [
    { label: 'Diterimakan', value: Number(props.current.take_home), color: '#287b8c' },
    { label: 'Potongan sekolah', value: Number(props.current.school_total), color: '#c9a24b' },
    { label: 'Potongan resmi', value: Number(props.current.official_total), color: '#142b45' },
] : []);
const bars = computed(() => props.recent.map((r) => ({ label: r.short, value: Number(r.take_home), full: r.label })));
const stats = computed(() => props.current ? [
    { label: 'Gaji kotor', value: rupiah(props.current.gross) },
    { label: 'Penghasilan bersih', value: rupiah(props.current.net), hint: 'Setelah potongan resmi' },
    { label: 'Potongan sekolah', value: rupiah(props.current.school_total), hint: props.current.payment.has_payable ? props.current.payment.status_label : 'Tidak ada tagihan' },
] : []);
</script>

<template>
    <Head title="Beranda" />
    <div class="mb-5">
        <p class="text-sm text-neutral-600">{{ greeting() }},</p>
        <h1 class="page-title">{{ employee.name }}</h1>
    </div>

    <AppCard v-if="!current"><AppEmptyState title="Rincian gaji belum tersedia" description="Rincian gaji akan muncul di sini setelah dipublikasikan oleh admin keuangan sekolah." /></AppCard>

    <div v-else class="space-y-4">
        <TakeHomePayCard :value="current.take_home" :period="current.period.label" :gross="current.gross" :net="current.net" :school="current.school_total">
            <AppButton variant="gold" size="lg" :href="`/saya/rincian/${current.period.id}`">Lihat rincian<ArrowRight class="size-4" /></AppButton>
            <AppButton variant="onDark" size="lg" external :href="`/saya/slip/${current.period.id}`"><Download class="size-4" />Unduh slip PDF</AppButton>
        </TakeHomePayCard>

        <StatStrip :items="stats" />

        <div class="grid gap-4 lg:grid-cols-5">
            <AppCard title="Komposisi gaji kotor" :subtitle="current.period.label" class="lg:col-span-2"><ComposeBar :parts="parts" /></AppCard>
            <AppCard title="Yang diterimakan per bulan" subtitle="Enam periode terakhir" class="lg:col-span-3"><TrendBars :items="bars" :height="150" aria-label="Grafik yang diterimakan per periode" /></AppCard>
        </div>

        <div class="grid gap-4 lg:grid-cols-5">
            <AppCard title="Pembayaran potongan sekolah" class="lg:col-span-3" accent="gold">
                <template v-if="current.payment.has_payable">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-2"><span class="num text-xl font-bold text-primary-900">{{ rupiah(current.payment.total) }}</span><PaymentStatusBadge :status="current.payment.status" /></div>
                    <PaymentTimeline :status="current.payment.status" />
                    <p v-if="current.payment.status === 'ditolak'" class="mt-5 rounded-lg bg-danger-bg p-3 text-[13px] text-danger-fg">Bukti ditolak: {{ current.payment.rejection_reason }}</p>
                    <AppButton v-if="current.payment.can_upload" class="mt-5" block :href="`/saya/pembayaran/${current.period.id}`">{{ current.payment.status === 'ditolak' ? 'Unggah ulang bukti' : 'Bayar & unggah bukti' }}</AppButton>
                    <AppButton v-else class="mt-5" block variant="outline" :href="`/saya/pembayaran/${current.period.id}`">Lihat detail pembayaran</AppButton>
                </template>
                <p v-else class="text-sm text-neutral-600">Tidak ada potongan sekolah yang perlu dibayar pada periode ini.</p>
            </AppCard>

            <AppCard title="Pengumuman" class="lg:col-span-2" flush>
                <template #header><Link href="/saya/pengumuman" class="text-[13px] font-semibold text-primary-600 hover:underline">Semua</Link></template>
                <ul v-if="announcements.length" class="divide-y divide-neutral-100">
                    <li v-for="a in announcements" :key="a.id"><Link :href="`/saya/pengumuman/${a.id}`" class="block px-4 py-3 hover:bg-neutral-50 sm:px-5"><p class="text-sm font-semibold text-neutral-950">{{ a.title }}</p><p class="mt-0.5 line-clamp-2 text-[13px] text-neutral-600">{{ a.excerpt }}</p><p class="mt-1 text-xs text-neutral-400">{{ formatDate(a.published_at) }}</p></Link></li>
                </ul>
                <p v-else class="px-5 py-8 text-center text-sm text-neutral-600">Belum ada pengumuman.</p>
            </AppCard>
        </div>
    </div>
</template>
