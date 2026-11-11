<script setup lang="ts">
import PaymentVerificationPanel from '@/components/admin/PaymentVerificationPanel.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import PaymentHistory from '@/components/payment/PaymentHistory.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import { copyText } from '@/composables/useToast';
import { formatDate, formatNip, rupiah } from '@/lib/format';
import type { PaymentStatus, Period, Proof } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    payment: { id: number; status: PaymentStatus; status_label: string; total: string; submitted_at: string | null; verified_at: string | null; verifier: string | null; rejection_reason: string | null };
    employee: { name: string; nip: string; status: string; account: string | null };
    period: Period; proofs: Proof[]; transfer_note: string; record_id: number; canVerify: boolean;
}>();
</script>

<template>
    <Head title="Verifikasi Pembayaran" />
    <AppButton variant="ghost" size="sm" href="/admin/pembayaran" class="mb-2 -ml-2">← Daftar pembayaran</AppButton>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-2"><h1 class="page-title">{{ employee.name }}</h1><PaymentStatusBadge :status="payment.status" :label="payment.status_label" /></div>
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="space-y-4">
            <AppCard title="Tagihan">
                <p class="num text-3xl font-bold text-primary-900">{{ rupiah(payment.total) }}</p>
                <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
                    <dt class="text-neutral-600">Periode</dt><dd>{{ period.label }}</dd>
                    <dt class="text-neutral-600">NIP</dt><dd class="num">{{ formatNip(employee.nip) }}</dd>
                    <dt class="text-neutral-600">Status ASN</dt><dd>{{ employee.status }}</dd>
                    <dt class="text-neutral-600">Diunggah</dt><dd>{{ formatDate(payment.submitted_at, true) }}</dd>
                    <template v-if="payment.verified_at"><dt class="text-neutral-600">Diperiksa</dt><dd>{{ formatDate(payment.verified_at, true) }} oleh {{ payment.verifier }}</dd></template>
                </dl>
                <div class="mt-3 rounded-lg bg-neutral-50 p-3 text-[13px]"><span class="text-neutral-600">Berita transfer seharusnya:</span> <strong>{{ transfer_note }}</strong> <button type="button" class="ml-1 font-semibold text-primary-600 hover:underline" @click="copyText(transfer_note, 'Berita transfer')">Salin</button></div>
                <p v-if="payment.rejection_reason" class="mt-3 rounded-lg bg-danger-bg p-3 text-[13px] text-danger-fg">Ditolak: {{ payment.rejection_reason }}</p>
                <AppButton class="mt-3" size="sm" variant="outline" :href="`/admin/penghasilan/${record_id}`">Lihat rincian gaji</AppButton>
            </AppCard>
            <PaymentHistory :proofs="proofs" />
        </div>
        <PaymentVerificationPanel :payment-id="payment.id" :total="payment.total" :proof="proofs[0] ?? null" :can-verify="canVerify" />
    </div>
</template>
