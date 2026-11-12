<script setup lang="ts">
import StatStrip from '@/components/admin/StatStrip.vue';
import AppBadge from '@/components/app/AppBadge.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppTable from '@/components/app/AppTable.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import ComposeBar from '@/components/viz/ComposeBar.vue';
import TrendBars from '@/components/viz/TrendBars.vue';
import { relativeTime, rupiah } from '@/lib/format';
import type { Period, PeriodStatus } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import { computed, ref } from 'vue';

type Task = { key: string; tone: 'warning' | 'success' | 'info'; title: string; href: string; action: string };
type Trend = { id: number; label: string; full: string; status: PeriodStatus; status_label: string; gross: string; school: string; take_home: string };
const props = defineProps<{
    tasks: Task[];
    cards: { total: number; pns: number; pppk: number; incomplete: number; pending_payments: number };
    period: (Period & { complete: number; employees: number; issues: number }) | null;
    trend: Trend[];
    collection: { total: string; paid: string; pending: string; unpaid: string; count: number; count_paid: number } | null;
    pending: { id: number; name: string; nip: string; period: string; amount: string; submitted_at: string | null }[];
    activity: { id: number; label: string; user: string; at: string }[];
}>();

const view = ref<'chart' | 'table'>('chart');
const current = computed(() => props.trend.find((t) => t.id === props.period?.id) ?? null);
const bars = computed(() => props.trend.map((t) => ({ label: t.label, value: Number(t.take_home), full: t.full })));
const pct = (a: string | number, b: string | number) => (Number(b) > 0 ? Math.round((Number(a) / Number(b)) * 100) : 0);
const stats = computed(() => [
    { label: 'ASN aktif', value: props.cards.total, hint: `${props.cards.pns} PNS · ${props.cards.pppk} PPPK`, to: '/admin/asn' },
    { label: 'Total diterimakan', value: current.value ? rupiah(current.value.take_home) : '—', hint: props.period ? `Periode ${props.period.label}` : 'Belum ada periode', to: props.period ? `/admin/periode/${props.period.id}` : undefined },
    { label: 'Potongan sekolah lunas', value: props.collection ? rupiah(props.collection.paid) : '—', hint: props.collection ? `${pct(props.collection.paid, props.collection.total)}% dari ${rupiah(props.collection.total)}` : 'Menunggu publikasi', to: '/admin/pembayaran' },
    { label: 'Kelengkapan data', value: props.period ? `${props.period.complete} / ${props.period.employees}` : '—', hint: props.period ? `${pct(props.period.complete, props.period.employees)}% ASN punya rincian` : undefined, tone: props.cards.incomplete ? ('warning' as const) : ('default' as const), to: '/admin/penghasilan' },
]);
const parts = computed(() => props.collection ? [
    { label: 'Lunas', value: Number(props.collection.paid), color: '#287b8c' },
    { label: 'Menunggu verifikasi', value: Number(props.collection.pending), color: '#c9a24b' },
    { label: 'Belum dibayar', value: Number(props.collection.unpaid), color: '#cbd5e1' },
] : []);
const tcols = [{ key: 'full', label: 'Periode', primary: true }, { key: 'status', label: 'Status' }, { key: 'gross', label: 'Gaji kotor', align: 'right' as const }, { key: 'school', label: 'Pot. sekolah', align: 'right' as const }, { key: 'take_home', label: 'Diterimakan', align: 'right' as const }];
const tone = { warning: 'bg-gold-500', success: 'bg-primary-600', info: 'bg-[#2563eb]' };
</script>

<template>
    <Head title="Dashboard" />
    <AppPageHeader title="Dashboard" :eyebrow="period ? `Periode ${period.label}` : 'Ringkasan'" subtitle="Yang perlu dikerjakan sekarang dan ringkasan angka penggajian.">
        <AppButton v-if="period" :href="`/admin/periode/${period.id}`">Kelola periode {{ period.label }}</AppButton>
        <AppButton v-else href="/admin/periode">Buat periode pertama</AppButton>
    </AppPageHeader>

    <div class="space-y-4">
        <AppCard title="Perlu tindakan" :subtitle="tasks.length ? `${tasks.length} hal menunggu` : undefined" flush>
            <ul v-if="tasks.length" class="divide-y divide-neutral-100">
                <li v-for="t in tasks" :key="t.key" class="flex items-center gap-3 px-4 py-3 sm:px-5">
                    <span :class="['h-8 w-1 shrink-0 rounded-full', tone[t.tone]]" aria-hidden="true" />
                    <p class="min-w-0 flex-1 text-sm text-neutral-950">{{ t.title }}</p>
                    <AppButton size="sm" :variant="t.tone === 'success' ? 'primary' : 'outline'" :href="t.href">{{ t.action }}</AppButton>
                </li>
            </ul>
            <p v-else class="flex items-center gap-2 px-5 py-5 text-sm text-neutral-600"><CircleCheck class="size-4 text-primary-600" />Tidak ada tindakan yang tertunda.</p>
        </AppCard>

        <StatStrip :items="stats" />

        <div class="grid gap-4 lg:grid-cols-3">
            <AppCard class="lg:col-span-2" title="Total diterimakan per periode" subtitle="Enam periode terakhir">
                <template #header>
                    <div class="flex rounded-lg border border-neutral-200 p-0.5 text-[13px] font-semibold" role="group" aria-label="Tampilan">
                        <button v-for="o in [['chart', 'Grafik'], ['table', 'Tabel']] as const" :key="o[0]" type="button" :aria-pressed="view === o[0]" :class="['min-h-8 rounded-md px-3', view === o[0] ? 'bg-primary-900 text-white' : 'text-neutral-600 hover:text-neutral-950']" @click="view = o[0]">{{ o[1] }}</button>
                    </div>
                </template>
                <template v-if="trend.length">
                    <TrendBars v-if="view === 'chart'" :items="bars" aria-label="Grafik total diterimakan per periode" />
                    <div v-else class="-mx-4 -my-4 sm:-mx-5 sm:-my-5">
                        <AppTable :columns="tcols" :rows="trend" :loading="false">
                            <template #cell-status="{ row }"><PayrollStatusBadge :status="row.status" :label="row.status_label" /></template>
                            <template #cell-gross="{ row }">{{ rupiah(row.gross) }}</template>
                            <template #cell-school="{ row }">{{ rupiah(row.school) }}</template>
                            <template #cell-take_home="{ row }"><strong class="text-primary-900">{{ rupiah(row.take_home) }}</strong></template>
                        </AppTable>
                    </div>
                </template>
                <AppEmptyState v-else title="Belum ada data periode" />
            </AppCard>

            <div class="space-y-4">
                <AppCard v-if="collection" title="Penagihan potongan sekolah" :subtitle="`${collection.count_paid} dari ${collection.count} ASN lunas`" accent="gold">
                    <p class="text-[13px] text-neutral-600">Total tagihan</p>
                    <p class="num mb-4 text-2xl font-bold tracking-tight text-primary-900">{{ rupiah(collection.total) }}</p>
                    <ComposeBar :parts="parts" />
                </AppCard>
                <AppCard v-else title="Penagihan potongan sekolah"><p class="text-sm text-neutral-600">Tampil setelah periode dipublikasikan.</p></AppCard>

                <AppCard title="Menunggu verifikasi" flush>
                    <template #header><Link href="/admin/pembayaran" class="text-[13px] font-semibold text-primary-600 hover:underline">Lihat semua</Link></template>
                    <p v-if="!pending.length" class="px-5 py-5 text-sm text-neutral-600">Tidak ada bukti transfer yang menunggu.</p>
                    <ul v-else class="divide-y divide-neutral-100">
                        <li v-for="p in pending.slice(0, 5)" :key="p.id"><Link :href="`/admin/pembayaran/${p.id}`" class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-neutral-50 sm:px-5"><span class="truncate text-sm font-medium">{{ p.name }}</span><span class="num text-sm text-neutral-800">{{ rupiah(p.amount) }}</span></Link></li>
                    </ul>
                </AppCard>
            </div>
        </div>

        <AppCard title="Aktivitas terbaru" flush>
            <ul v-if="activity.length" class="divide-y divide-neutral-100">
                <li v-for="a in activity" :key="a.id" class="flex items-baseline justify-between gap-3 px-4 py-2.5 sm:px-5"><span class="min-w-0 truncate text-sm"><span class="font-medium text-neutral-950">{{ a.label }}</span> <span class="text-neutral-600">· {{ a.user }}</span></span><span class="shrink-0 text-xs text-neutral-600">{{ relativeTime(a.at) }}</span></li>
            </ul>
            <p v-else class="px-5 py-6 text-sm text-neutral-600">Belum ada aktivitas.</p>
        </AppCard>
    </div>
</template>
