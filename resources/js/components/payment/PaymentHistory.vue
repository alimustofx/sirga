<script setup lang="ts">
import AppBadge from '@/components/app/AppBadge.vue';
import AppCard from '@/components/app/AppCard.vue';
import { fileSize, formatDate } from '@/lib/format';
import type { Proof } from '@/types';
import PaymentProofPreview from './PaymentProofPreview.vue';

defineProps<{ proofs: Proof[]; preview?: boolean }>();
const tone = { pending: 'info', approved: 'success', rejected: 'danger' } as const;
const label = { pending: 'Menunggu verifikasi', approved: 'Disetujui', rejected: 'Ditolak' } as const;
</script>

<template>
    <AppCard title="Riwayat Bukti Transfer">
        <p v-if="!proofs.length" class="text-sm text-neutral-600">Belum ada bukti transfer yang diunggah.</p>
        <ul v-else class="space-y-4">
            <li v-for="p in proofs" :key="p.id" class="space-y-2 border-b border-neutral-100 pb-4 last:border-0 last:pb-0">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="min-w-0">
                        <a :href="p.url" target="_blank" rel="noopener" class="block truncate text-sm font-semibold text-primary-600 hover:underline">{{ p.filename }}</a>
                        <p class="text-xs text-neutral-600">{{ fileSize(p.size) }} · diunggah {{ formatDate(p.uploaded_at, true) }}</p>
                    </div>
                    <AppBadge :tone="tone[p.status]">{{ label[p.status] }}</AppBadge>
                </div>
                <p v-if="p.status === 'rejected' && p.rejection_reason" class="rounded-lg bg-danger-bg p-3 text-[13px] text-danger-fg">Alasan penolakan: {{ p.rejection_reason }}</p>
                <p v-if="p.verifier" class="text-xs text-neutral-600">Diperiksa oleh {{ p.verifier }} · {{ formatDate(p.verified_at, true) }}</p>
                <PaymentProofPreview v-if="preview" :proof="p" />
            </li>
        </ul>
    </AppCard>
</template>
