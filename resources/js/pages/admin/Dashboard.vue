<script setup lang="ts">
import StatCard from '@/components/admin/StatCard.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import { formatDate, relativeTime, rupiah } from '@/lib/format';
import type { Period } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    cards: { total: number; pns: number; pppk: number; incomplete: number; pending_payments: number };
    period: (Period & { complete: number; employees: number; issues: number }) | null;
    pending: { id: number; name: string; nip: string; period: string; amount: string; submitted_at: string | null }[];
    activity: { id: number; label: string; user: string; at: string }[];
}>();
</script>

<template>
    <Head title="Dashboard" />
    <AppPageHeader title="Dashboard" subtitle="Ringkasan penggajian dan pekerjaan yang perlu ditindaklanjuti." />

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <StatCard label="ASN aktif" :value="cards.total" :hint="`${cards.pns} PNS · ${cards.pppk} PPPK`" />
        <StatCard label="Data belum lengkap" :value="cards.incomplete" :tone="cards.incomplete ? 'warning' : 'default'" hint="Pada periode berjalan" />
        <StatCard label="Menunggu verifikasi" :value="cards.pending_payments" :tone="cards.pending_payments ? 'warning' : 'default'" hint="Bukti transfer" />
        <StatCard label="Periode berjalan" :value="period?.label ?? '-'" class="col-span-2 lg:col-span-2" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <AppCard title="Periode Gaji">
                <template v-if="period">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><p class="text-lg font-bold text-primary-900">{{ period.label }}</p><PayrollStatusBadge :status="period.status" /></div>
                        <AppButton :href="`/admin/periode/${period.id}`" size="sm">Kelola periode</AppButton>
                    </div>
                    <div class="mt-4">
                        <div class="mb-1 flex justify-between text-[13px] text-neutral-600"><span>Kelengkapan data</span><span class="num font-semibold text-neutral-950">{{ period.complete }} / {{ period.employees }} ASN</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-neutral-100"><div class="h-full rounded-full bg-primary-600" :style="{ width: `${period.employees ? (period.complete / period.employees) * 100 : 0}%` }" /></div>
                        <p v-if="period.issues && period.status !== 'closed'" class="mt-2 text-[13px] text-warning-fg">{{ period.issues }} hal perlu diselesaikan sebelum publikasi.</p>
                    </div>
                </template>
                <AppEmptyState v-else title="Belum ada periode" description="Buat periode gaji pertama untuk mulai."><AppButton href="/admin/periode">Buat periode</AppButton></AppEmptyState>
            </AppCard>

            <AppCard title="Bukti Transfer Menunggu Verifikasi" flush>
                <template #header><Link href="/admin/pembayaran" class="text-[13px] font-semibold text-primary-600 hover:underline">Semua pembayaran</Link></template>
                <p v-if="!pending.length" class="px-5 py-6 text-center text-sm text-neutral-600">Tidak ada bukti transfer yang menunggu.</p>
                <ul v-else class="divide-y divide-neutral-100">
                    <li v-for="p in pending" :key="p.id">
                        <Link :href="`/admin/pembayaran/${p.id}`" class="flex min-h-14 items-center justify-between gap-3 px-4 py-2.5 hover:bg-neutral-50 sm:px-5">
                            <span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ p.name }}</span><span class="text-xs text-neutral-600">{{ p.period }} · {{ formatDate(p.submitted_at) }}</span></span>
                            <span class="num text-sm font-semibold text-primary-900">{{ rupiah(p.amount) }}</span>
                        </Link>
                    </li>
                </ul>
            </AppCard>
        </div>

        <AppCard title="Aktivitas Terbaru" flush>
            <ul class="divide-y divide-neutral-100">
                <li v-for="a in activity" :key="a.id" class="px-4 py-2.5 sm:px-5"><p class="text-sm font-medium">{{ a.label }}</p><p class="text-xs text-neutral-600">{{ a.user }} · {{ relativeTime(a.at) }}</p></li>
                <li v-if="!activity.length" class="px-5 py-6 text-center text-sm text-neutral-600">Belum ada aktivitas.</li>
            </ul>
        </AppCard>
    </div>
</template>
