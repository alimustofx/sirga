<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import EarningBreakdown from '@/components/payroll/EarningBreakdown.vue';
import OfficialDeductionBreakdown from '@/components/payroll/OfficialDeductionBreakdown.vue';
import PayrollEmployeeInfo from '@/components/payroll/PayrollEmployeeInfo.vue';
import PayrollPeriodSelector from '@/components/payroll/PayrollPeriodSelector.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import SchoolDeductionBreakdown from '@/components/payroll/SchoolDeductionBreakdown.vue';
import TakeHomePayCard from '@/components/payroll/TakeHomePayCard.vue';
import ComposeBar from '@/components/viz/ComposeBar.vue';
import { rupiah } from '@/lib/format';
import type { PaymentInfo, PayrollDetail, Period } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Download, Wallet } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ periods: Period[]; payroll: PayrollDetail | null; payment: PaymentInfo | null }>();
const parts = computed(() => props.payroll ? [
    { label: 'Diterimakan', value: Number(props.payroll.take_home), color: '#287b8c' },
    { label: 'Potongan sekolah', value: Number(props.payroll.school_total), color: '#c9a24b' },
    { label: 'Potongan resmi', value: Number(props.payroll.official_total), color: '#142b45' },
] : []);
</script>

<template>
    <Head title="Rincian Gaji" />
    <div class="mb-5 flex animate-fade-up flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="gold-rule mb-2.5 block" aria-hidden="true" />
            <h1 class="page-title">Rincian Gaji</h1>
            <p v-if="payroll" class="mt-1.5 flex items-center gap-2 text-sm text-neutral-600">Periode {{ payroll.period.label }} <PayrollStatusBadge v-if="payroll.period.status === 'closed'" :status="payroll.period.status" label="Ditutup" /></p>
        </div>
        <PayrollPeriodSelector :periods="periods" :current="payroll?.period.id" base="/saya/rincian" />
    </div>

    <AppCard v-if="!payroll"><AppEmptyState title="Rincian gaji belum tersedia" description="Belum ada periode gaji yang dipublikasikan untuk Anda." /></AppCard>

    <div v-else class="space-y-4">
        <TakeHomePayCard :value="payroll.take_home" :period="payroll.period.label" :gross="payroll.gross" :net="payroll.net" :school="payroll.school_total">
            <AppButton variant="gold" size="lg" external :href="`/saya/slip/${payroll.period.id}`"><Download class="size-4" />Unduh slip PDF</AppButton>
            <AppButton v-if="payment?.has_payable" variant="outline" size="lg" :href="`/saya/pembayaran/${payroll.period.id}`"><Wallet class="size-4" />Pembayaran potongan</AppButton>
        </TakeHomePayCard>

        <AppCard title="Komposisi gaji kotor" :subtitle="`Dari ${rupiah(payroll.gross)}`"><ComposeBar :parts="parts" /></AppCard>

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <div class="space-y-4">
                <EarningBreakdown :lines="payroll.earnings" :total="payroll.gross" />
                <OfficialDeductionBreakdown :lines="payroll.official" :total="payroll.official_total" />
                <div class="flex items-baseline justify-between rounded-2xl border border-primary-600/30 bg-primary-600/[0.07] px-4 py-4 sm:px-5">
                    <span class="text-sm font-bold text-primary-900">Penghasilan Bersih</span>
                    <span class="num text-xl font-bold tracking-tight text-primary-900">{{ rupiah(payroll.net) }}</span>
                </div>
            </div>
            <div class="space-y-4">
                <SchoolDeductionBreakdown :lines="payroll.deductions" :total="payroll.school_total" />
                <AppCard v-if="payment?.has_payable" title="Status Pembayaran Potongan" accent="gold">
                    <div class="flex items-center justify-between gap-2"><span class="num font-semibold">{{ rupiah(payment.total) }}</span><PaymentStatusBadge :status="payment.status" /></div>
                </AppCard>
                <PayrollEmployeeInfo :employee="payroll.employee" />
            </div>
        </div>
    </div>
</template>
