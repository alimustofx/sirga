<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import { copyText } from '@/composables/useToast';
import type { PaymentInfo } from '@/types';

defineProps<{ account: PaymentInfo['account'] }>();
</script>

<template>
    <section v-if="account" class="rounded-xl bg-hero p-5 text-white sm:p-6">
        <p class="eyebrow !text-white/60">Rekening tujuan · {{ account.bank }}</p>
        <p class="num mt-3 text-[26px] font-bold tracking-[0.08em] sm:text-[30px]">{{ account.number }}</p>
        <div class="mt-4 flex items-end justify-between gap-3 border-t border-white/10 pt-4">
            <div><p class="eyebrow !text-white/60">Atas nama</p><p class="text-sm font-semibold">{{ account.holder }}</p></div>
            <AppButton size="sm" variant="gold" @click="copyText(account.number, 'Nomor rekening')">Salin nomor</AppButton>
        </div>
        <p v-if="account.instruction" class="mt-4 rounded-lg bg-white/8 p-3 text-[13px] text-white/85">{{ account.instruction }}</p>
    </section>
    <div v-else class="rounded-xl border border-dashed border-neutral-400 bg-white p-5 text-sm text-neutral-600">Rekening bendahara belum tersedia. Hubungi admin keuangan sekolah.</div>
</template>
