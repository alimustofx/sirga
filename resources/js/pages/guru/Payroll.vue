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
import { rupiah } from '@/lib/format';
import type { PaymentInfo, PayrollDetail, Period } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{ periods: Period[]; payroll: PayrollDetail | null; payment: PaymentInfo | null }>();
</script>

<template>
    <Head title="Rincian Gaji" />
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Rincian Gaji</h1>
            <p v-if="payroll" class="mt-1 flex items-center gap-2 text-sm text-neutral-600">Periode {{ payroll.period.label }} <PayrollStatusBadge v-if="payroll.period.status === 'closed'" :status="payroll.period.status" label="Ditutup" /></p>
        </div>
        <PayrollPeriodSelector :periods="periods" :current="payroll?.period.id" base="/saya/rincian" />
    </div>

    <AppCard v-if="!payroll"><AppEmptyState title="Rincian gaji belum tersedia" description="Belum ada periode gaji yang dipublikasikan untuk Anda." /></AppCard>

    <div v-else class="space-y-4">
        <TakeHomePayCard :value="payroll.take_home" :period="payroll.period.label" :net="payroll.net" :school="payroll.school_total" />

        <div class="flex flex-col gap-2 sm:flex-row">
            <AppButton :href="`/saya/slip/${payroll.period.id}`" external>Unduh Slip PDF</AppButton>
            <AppButton v-if="payment?.has_payable" variant="outline" :href="`/saya/pembayaran/${payroll.period.id}`">Pembayaran Potongan</AppButton>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="space-y-4">
                <EarningBreakdown :lines="payroll.earnings" :total="payroll.gross" />
                <OfficialDeductionBreakdown :lines="payroll.official" :total="payroll.official_total" />
                <div class="flex items-baseline justify-between rounded-xl border border-primary-600 bg-[#e6f2f4] px-4 py-3.5 sm:px-5">
                    <span class="text-sm font-bold text-primary-900">Penghasilan Bersih</span>
                    <span class="num text-lg font-bold text-primary-900">{{ rupiah(payroll.net) }}</span>
                </div>
            </div>
            <div class="space-y-4">
                <SchoolDeductionBreakdown :lines="payroll.deductions" :total="payroll.school_total" />
                <AppCard v-if="payment?.has_payable" title="Status Pembayaran Potongan">
                    <div class="flex items-center justify-between gap-2"><span class="num font-semibold">{{ rupiah(payment.total) }}</span><PaymentStatusBadge :status="payment.status" /></div>
                </AppCard>
                <PayrollEmployeeInfo :employee="payroll.employee" />
            </div>
        </div>
    </div>
</template>
