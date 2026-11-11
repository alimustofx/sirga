<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import { copyText } from '@/composables/useToast';
import type { PaymentInfo } from '@/types';
import { Landmark } from '@lucide/vue';

defineProps<{ account: PaymentInfo['account'] }>();
</script>

<template>
    <div v-if="account" class="bg-hero relative overflow-hidden rounded-3xl p-5 text-white shadow-hero sm:p-6">
        <div class="dot-grid pointer-events-none absolute inset-0 opacity-40" aria-hidden="true" />
        <div class="relative">
            <div class="flex items-center justify-between"><span class="flex items-center gap-2 text-[13px] font-semibold text-white/80"><Landmark class="size-4" />{{ account.bank }}</span><span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-white/15">Rekening tujuan</span></div>
            <div class="mt-6 h-7 w-10 rounded-md bg-gold-400/80" aria-hidden="true" />
            <p class="num mt-4 text-2xl font-bold tracking-[0.12em] sm:text-[28px]">{{ account.number }}</p>
            <div class="mt-4 flex items-end justify-between gap-3"><div><p class="text-[11px] tracking-wider text-white/60 uppercase">Atas nama</p><p class="text-sm font-semibold">{{ account.holder }}</p></div>
                <AppButton size="sm" variant="gold" @click="copyText(account.number, 'Nomor rekening')">Salin nomor</AppButton></div>
            <p v-if="account.instruction" class="mt-4 rounded-xl bg-white/10 p-3 text-[13px] text-white/85 ring-1 ring-white/10">{{ account.instruction }}</p>
        </div>
    </div>
    <div v-else class="rounded-2xl border border-dashed border-neutral-400 bg-white p-5 text-sm text-neutral-600">Rekening bendahara belum tersedia. Hubungi admin keuangan sekolah.</div>
</template>
