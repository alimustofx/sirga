<script setup lang="ts">
import StatCard from '@/components/admin/StatCard.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import ComposeBar from '@/components/viz/ComposeBar.vue';
import ProgressRing from '@/components/viz/ProgressRing.vue';
import TrendBars from '@/components/viz/TrendBars.vue';
import { formatDate, relativeTime, rupiah } from '@/lib/format';
import type { Period } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarRange, Clock, TriangleAlert, Users } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    cards: { total: number; pns: number; pppk: number; incomplete: number; pending_payments: number };
    period: (Period & { complete: number; employees: number; issues: number }) | null;
    trend: { id: number; label: string; full: string; gross: string; school: string; take_home: string }[];
    collection: { total: string; paid: string; pending: string; unpaid: string; count: number; count_paid: number } | null;
    pending: { id: number; name: string; nip: string; period: string; amount: string; submitted_at: string | null }[];
    activity: { id: number; label: string; user: string; at: string }[];
}>();

const bars = computed(() => props.trend.map((t) => ({ label: t.label, value: Number(t.take_home), full: t.full })));
const spark = computed(() => props.trend.map((t) => Number(t.take_home)));
const completePct = computed(() => (props.period && props.period.employees ? Math.round((props.period.complete / props.period.employees) * 100) : 0));
const parts = computed(() => props.collection ? [
    { label: 'Lunas', value: Number(props.collection.paid), color: '#287b8c' },
    { label: 'Menunggu verifikasi', value: Number(props.collection.pending), color: '#c9a24b' },
    { label: 'Belum dibayar', value: Number(props.collection.unpaid), color: '#cbd5e1' },
] : []);
const collectedPct = computed(() => (props.collection && Number(props.collection.total) > 0 ? Math.round((Number(props.collection.paid) / Number(props.collection.total)) * 100) : 0));
</script>

<template>
    <Head title="Dashboard" />
    <AppPageHeader title="Dashboard" subtitle="Ringkasan penggajian dan pekerjaan yang perlu ditindaklanjuti." />

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard label="ASN aktif" :value="cards.total" :hint="`${cards.pns} PNS · ${cards.pppk} PPPK`" :icon="Users" to="/admin/asn" />
        <StatCard label="Data belum lengkap" :value="cards.incomplete" :tone="cards.incomplete ? 'warning' : 'default'" hint="Pada periode berjalan" :icon="TriangleAlert" to="/admin/penghasilan" />
        <StatCard label="Menunggu verifikasi" :value="cards.pending_payments" :tone="cards.pending_payments ? 'warning' : 'default'" hint="Bukti transfer" :icon="Clock" to="/admin/pembayaran" />
        <StatCard label="Periode berjalan" :value="period?.label ?? '-'" :icon="CalendarRange" :trend="spark" :to="period ? `/admin/periode/${period.id}` : '/admin/periode'" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <AppCard title="Tren Total Diterimakan" subtitle="Enam periode terakhir">
                <TrendBars v-if="bars.length" :items="bars" aria-label="Grafik total diterimakan per periode" />
                <AppEmptyState v-else title="Belum ada data periode" />
            </AppCard>

            <AppCard v-if="collection" title="Penagihan Potongan Sekolah" :subtitle="`${period?.label} · ${collection.count_paid} dari ${collection.count} ASN lunas`" accent="gold">
                <div class="flex flex-col items-center gap-5 sm:flex-row">
                    <ProgressRing :value="collectedPct" :size="104" :thickness="11" color="#c9a24b" />
                    <div class="w-full"><p class="mb-3 text-[13px] text-neutral-600">Total tagihan <strong class="num text-neutral-950">{{ rupiah(collection.total) }}</strong></p><ComposeBar :parts="parts" /></div>
                </div>
            </AppCard>

            <AppCard title="Bukti Transfer Menunggu Verifikasi" flush>
                <template #header><Link href="/admin/pembayaran" class="text-[13px] font-semibold text-primary-600 hover:underline">Semua pembayaran</Link></template>
                <p v-if="!pending.length" class="px-5 py-8 text-center text-sm text-neutral-600">Tidak ada bukti transfer yang menunggu. ✨</p>
                <ul v-else class="divide-y divide-neutral-100">
                    <li v-for="p in pending" :key="p.id"><Link :href="`/admin/pembayaran/${p.id}`" class="flex min-h-14 items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-neutral-50 sm:px-5"><span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ p.name }}</span><span class="text-xs text-neutral-600">{{ p.period }} · {{ formatDate(p.submitted_at) }}</span></span><span class="num text-sm font-semibold text-primary-900">{{ rupiah(p.amount) }}</span></Link></li>
                </ul>
            </AppCard>
        </div>

        <div class="space-y-4">
            <AppCard title="Periode Gaji" accent="teal">
                <template v-if="period">
                    <div class="flex items-center gap-4">
                        <ProgressRing :value="completePct" :size="88" :thickness="9" />
                        <div><p class="text-lg font-bold tracking-tight text-primary-900">{{ period.label }}</p><PayrollStatusBadge :status="period.status" /><p class="num mt-1.5 text-xs text-neutral-600">{{ period.complete }}/{{ period.employees }} ASN lengkap</p></div>
                    </div>
                    <p v-if="period.issues && period.status !== 'closed'" class="mt-4 rounded-xl bg-warning-bg px-3 py-2 text-[13px] text-warning-fg">{{ period.issues }} hal perlu diselesaikan sebelum publikasi.</p>
                    <AppButton class="mt-4" block :href="`/admin/periode/${period.id}`">Kelola periode</AppButton>
                </template>
                <AppEmptyState v-else title="Belum ada periode" description="Buat periode gaji pertama."><AppButton href="/admin/periode">Buat periode</AppButton></AppEmptyState>
            </AppCard>

            <AppCard title="Aktivitas Terbaru" flush>
                <ol v-if="activity.length" class="relative px-4 py-3 sm:px-5">
                    <span class="absolute top-5 bottom-5 left-[26px] w-px bg-neutral-200 sm:left-[30px]" aria-hidden="true" />
                    <li v-for="a in activity" :key="a.id" class="relative flex gap-3 py-2"><span class="relative z-10 mt-1.5 size-2.5 shrink-0 rounded-full bg-primary-600 ring-4 ring-white" /><span class="min-w-0"><span class="block text-sm font-medium">{{ a.label }}</span><span class="text-xs text-neutral-600">{{ a.user }} · {{ relativeTime(a.at) }}</span></span></li>
                </ol>
                <p v-else class="px-5 py-8 text-center text-sm text-neutral-600">Belum ada aktivitas.</p>
            </AppCard>
        </div>
    </div>
</template>
