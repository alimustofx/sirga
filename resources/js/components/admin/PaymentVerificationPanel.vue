<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppTextarea from '@/components/app/AppTextarea.vue';
import PaymentProofPreview from '@/components/payment/PaymentProofPreview.vue';
import { confirmAction } from '@/composables/useConfirm';
import { rupiah } from '@/lib/format';
import type { Proof } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ paymentId: number; total: string; proof: Proof | null; canVerify: boolean }>();
const rejectOpen = ref(false);
const form = useForm({ reason: '' });

async function approve(): Promise<void> {
    if (await confirmAction({ title: 'Setujui pembayaran?', message: `Pastikan nominal transfer sesuai ${rupiah(props.total)} dan rekening tujuan benar. Status akan menjadi Lunas.`, confirmLabel: 'Setujui, tandai Lunas' })) router.post(`/admin/pembayaran/${props.paymentId}/setujui`, {}, { preserveScroll: true });
}
const reject = () => form.post(`/admin/pembayaran/${props.paymentId}/tolak`, { preserveScroll: true, onSuccess: () => { rejectOpen.value = false; form.reset(); } });
</script>

<template>
    <AppCard title="Bukti Transfer Terbaru">
        <PaymentProofPreview v-if="proof" :proof="proof" />
        <p v-else class="text-sm text-neutral-600">Belum ada bukti transfer.</p>
        <div v-if="canVerify" class="mt-4 flex flex-col gap-2 sm:flex-row"><AppButton size="lg" @click="approve">Setujui</AppButton><AppButton size="lg" variant="danger" @click="rejectOpen = true">Tolak</AppButton></div>
    </AppCard>
    <AppDialog v-model:open="rejectOpen" title="Tolak bukti transfer" description="Guru akan menerima notifikasi beserta alasan ini dan dapat mengunggah ulang.">
        <AppTextarea v-model="form.reason" label="Alasan penolakan" :rows="3" :error="form.errors.reason" placeholder="Mis. nominal tidak sesuai / gambar tidak terbaca" required />
        <template #footer><AppButton variant="outline" @click="rejectOpen = false">Batal</AppButton><AppButton variant="danger" :loading="form.processing" @click="reject">Tolak bukti</AppButton></template>
    </AppDialog>
</template>
