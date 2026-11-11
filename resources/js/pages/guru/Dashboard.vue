<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import PaymentTimeline from '@/components/payment/PaymentTimeline.vue';
import SalarySummaryCard from '@/components/payroll/SalarySummaryCard.vue';
import TakeHomePayCard from '@/components/payroll/TakeHomePayCard.vue';
import DonutChart from '@/components/viz/DonutChart.vue';
import TrendBars from '@/components/viz/TrendBars.vue';
import { formatDate, greeting, rupiah } from '@/lib/format';
import type { PaymentInfo, Period } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Download, Landmark, PiggyBank, Receipt } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    employee: { name: string; status: string };
    current: { period: Period; gross: string; official_total: string; net: string; school_total: string; take_home: string; payment: PaymentInfo } | null;
    recent: { period_id: number; label: string; short: string; gross: string; net: string; school_total: string; take_home: string }[];
    announcements: { id: number; title: string; excerpt: string; published_at: string }[];
}>();

const segments = computed(() => props.current ? [
    { label: 'Diterimakan', value: Number(props.current.take_home), color: '#287b8c' },
    { label: 'Potongan sekolah', value: Number(props.current.school_total), color: '#c9a24b' },
    { label: 'Potongan resmi', value: Number(props.current.official_total), color: '#142b45' },
] : []);
const share = computed(() => (props.current && Number(props.current.gross) > 0 ? Math.round((Number(props.current.take_home) / Number(props.current.gross)) * 100) : 0));
const bars = computed(() => props.recent.map((r) => ({ label: r.short, value: Number(r.take_home), full: r.label })));
</script>

<template>
    <Head title="Beranda" />
    <div class="mb-5 animate-fade-up">
        <p class="text-sm text-neutral-600">{{ greeting() }},</p>
        <h1 class="page-title">{{ employee.name }}</h1>
    </div>

    <AppCard v-if="!current"><AppEmptyState title="Rincian gaji belum tersedia" description="Rincian gaji akan muncul di sini setelah dipublikasikan oleh admin keuangan sekolah." /></AppCard>

    <div v-else class="space-y-4">
        <TakeHomePayCard :value="current.take_home" :period="current.period.label" :gross="current.gross" :net="current.net" :school="current.school_total">
            <AppButton variant="gold" size="lg" :href="`/saya/rincian/${current.period.id}`">Lihat rincian<ArrowRight class="size-4" /></AppButton>
            <AppButton variant="outline" size="lg" external :href="`/saya/slip/${current.period.id}`"><Download class="size-4" />Unduh slip PDF</AppButton>
        </TakeHomePayCard>

        <div class="grid gap-3 sm:grid-cols-3">
            <SalarySummaryCard label="Gaji kotor" :value="current.gross" :icon="PiggyBank" tone="navy" />
            <SalarySummaryCard label="Penghasilan bersih" :value="current.net" :icon="Landmark" tone="teal" />
            <SalarySummaryCard label="Potongan sekolah" :value="current.school_total" :icon="Receipt" tone="gold" />
        </div>

        <div class="grid gap-4 lg:grid-cols-5">
            <AppCard title="Ke mana gaji Anda mengalir" class="lg:col-span-2">
                <div class="flex flex-col items-center gap-4 sm:flex-row lg:flex-col xl:flex-row">
                    <DonutChart :segments="segments" :size="150" :thickness="16"><span class="num text-2xl font-bold text-primary-900">{{ share }}%</span><span class="text-[11px] text-neutral-600">diterimakan</span></DonutChart>
                    <ul class="w-full space-y-2.5"><li v-for="s in segments" :key="s.label" class="flex items-start gap-2.5"><span class="mt-1.5 size-2.5 shrink-0 rounded-full" :style="{ background: s.color }" /><span class="min-w-0 flex-1"><span class="block text-[13px] text-neutral-600">{{ s.label }}</span><span class="num block text-sm font-semibold">{{ rupiah(s.value) }}</span></span></li></ul>
                </div>
            </AppCard>
            <AppCard title="Tren 6 periode terakhir" subtitle="Yang diterimakan per bulan" class="lg:col-span-3"><TrendBars :items="bars" aria-label="Grafik yang diterimakan per periode" /></AppCard>
        </div>

        <div class="grid gap-4 lg:grid-cols-5">
            <AppCard title="Pembayaran Potongan Sekolah" class="lg:col-span-3" accent="gold">
                <template v-if="current.payment.has_payable">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-2"><span class="num text-xl font-bold text-primary-900">{{ rupiah(current.payment.total) }}</span><PaymentStatusBadge :status="current.payment.status" /></div>
                    <PaymentTimeline :status="current.payment.status" />
                    <p v-if="current.payment.status === 'ditolak'" class="mt-5 rounded-xl bg-danger-bg p-3 text-[13px] text-danger-fg">Bukti ditolak: {{ current.payment.rejection_reason }}</p>
                    <AppButton v-if="current.payment.can_upload" class="mt-5" block :href="`/saya/pembayaran/${current.period.id}`">{{ current.payment.status === 'ditolak' ? 'Unggah ulang bukti' : 'Bayar & unggah bukti' }}</AppButton>
                    <AppButton v-else class="mt-5" block variant="outline" :href="`/saya/pembayaran/${current.period.id}`">Lihat detail pembayaran</AppButton>
                </template>
                <p v-else class="text-sm text-neutral-600">Tidak ada potongan sekolah yang perlu dibayar pada periode ini. 🎉</p>
            </AppCard>

            <AppCard title="Pengumuman" class="lg:col-span-2" flush>
                <template #header><Link href="/saya/pengumuman" class="text-[13px] font-semibold text-primary-600 hover:underline">Semua</Link></template>
                <ul v-if="announcements.length" class="divide-y divide-neutral-100">
                    <li v-for="a in announcements" :key="a.id"><Link :href="`/saya/pengumuman/${a.id}`" class="block px-4 py-3 transition-colors hover:bg-neutral-50 sm:px-5"><p class="text-sm font-semibold text-neutral-950">{{ a.title }}</p><p class="mt-0.5 line-clamp-2 text-[13px] text-neutral-600">{{ a.excerpt }}</p><p class="mt-1 text-xs text-neutral-400">{{ formatDate(a.published_at) }}</p></Link></li>
                </ul>
                <p v-else class="px-5 py-8 text-center text-sm text-neutral-600">Belum ada pengumuman.</p>
            </AppCard>
        </div>
    </div>
</template>
