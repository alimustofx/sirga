<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import { copyText } from '@/composables/useToast';
import type { PaymentInfo } from '@/types';

defineProps<{ account: PaymentInfo['account'] }>();
</script>

<template>
    <AppCard title="Rekening Tujuan">
        <template v-if="account">
            <p class="text-sm text-neutral-600">{{ account.bank }}</p>
            <div class="mt-1 flex items-center justify-between gap-3">
                <p class="num text-2xl font-bold tracking-wide text-primary-900">{{ account.number }}</p>
                <AppButton size="sm" variant="outline" @click="copyText(account.number, 'Nomor rekening')">Salin</AppButton>
            </div>
            <p class="mt-1 text-sm text-neutral-800">a.n. <strong>{{ account.holder }}</strong></p>
            <p v-if="account.instruction" class="mt-3 rounded-lg bg-neutral-50 p-3 text-[13px] text-neutral-800">{{ account.instruction }}</p>
        </template>
        <p v-else class="text-sm text-neutral-600">Rekening bendahara belum tersedia. Hubungi admin keuangan sekolah.</p>
    </AppCard>
</template>
