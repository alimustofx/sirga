<script setup lang="ts">
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import BankAccountCard from '@/components/payment/BankAccountCard.vue';
import PaymentHistory from '@/components/payment/PaymentHistory.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import PaymentTimeline from '@/components/payment/PaymentTimeline.vue';
import PaymentUpload from '@/components/payment/PaymentUpload.vue';
import TransferInstructionCard from '@/components/payment/TransferInstructionCard.vue';
import PayrollPeriodSelector from '@/components/payroll/PayrollPeriodSelector.vue';
import { formatDate } from '@/lib/format';
import type { PaymentInfo, Period } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{ periods: Period[]; period: Period | null; payment: PaymentInfo | null; maxKb: number }>();
</script>

<template>
    <Head title="Pembayaran Potongan" />
    <div class="mb-5 flex  flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Pembayaran Potongan</h1>
            <p v-if="period" class="mt-1.5 text-sm text-neutral-600">Potongan sekolah periode {{ period.label }}</p>
        </div>
        <PayrollPeriodSelector :periods="periods" :current="period?.id" base="/saya/pembayaran" />
    </div>

    <AppCard v-if="!period || !payment"><AppEmptyState title="Belum ada tagihan" description="Tagihan potongan sekolah akan muncul setelah rincian gaji dipublikasikan." /></AppCard>
    <AppCard v-else-if="!payment.has_payable"><AppEmptyState title="Tidak ada potongan yang perlu dibayar" description="Tidak ada potongan sekolah pada periode ini." /></AppCard>

    <div v-else class="space-y-4">
        <AppCard>
            <div class="mb-5 flex flex-wrap items-center gap-3"><span class="text-sm font-semibold text-neutral-800">Status pembayaran</span><PaymentStatusBadge :status="payment.status" /><span v-if="payment.status === 'lunas'" class="text-[13px] text-neutral-600">Diverifikasi {{ formatDate(payment.verified_at, true) }}</span></div>
            <PaymentTimeline :status="payment.status" />
        </AppCard>

        <div v-if="payment.status === 'ditolak'" class="animate-pop rounded-2xl border border-[#fca5a5] bg-danger-bg p-4 text-sm text-danger-fg" role="alert"><p class="font-semibold">Bukti transfer sebelumnya ditolak.</p><p class="mt-1">{{ payment.rejection_reason }}</p><p class="mt-1">Silakan unggah ulang bukti transfer yang benar.</p></div>
        <div v-else-if="payment.status === 'menunggu_verifikasi'" class="rounded-2xl border border-[#93c5fd] bg-info-bg p-4 text-sm text-info-fg">Bukti transfer Anda sedang diperiksa bendahara. Anda akan mendapat notifikasi setelah diverifikasi.</div>
        <div v-else-if="payment.closed" class="rounded-2xl border border-neutral-200 bg-neutral-100 p-4 text-sm text-neutral-800">Periode pembayaran ini telah ditutup.</div>

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <BankAccountCard :account="payment.account" />
            <TransferInstructionCard :total="payment.total" :note="payment.transfer_note" />
        </div>

        <PaymentUpload v-if="payment.can_upload && period" :period-id="period.id" :max-kb="maxKb" :retry="payment.status === 'ditolak'" />
        <PaymentHistory :proofs="payment.proofs" preview />
    </div>
</template>
