<script setup lang="ts">
import AnimatedNumber from '@/components/viz/AnimatedNumber.vue';
import { rupiah } from '@/lib/format';

defineProps<{ value: string; period: string; net?: string; school?: string; gross?: string }>();
</script>

<template>
    <section class="bg-hero relative overflow-hidden rounded-3xl p-6 text-white shadow-hero sm:p-8">
        <div class="dot-grid pointer-events-none absolute inset-0 opacity-60" aria-hidden="true" />
        <div class="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full border border-white/10" aria-hidden="true" />
        <div class="pointer-events-none absolute -top-8 -right-8 size-40 rounded-full border border-white/10" aria-hidden="true" />
        <div class="relative">
            <span class="gold-rule mb-3 block" aria-hidden="true" />
            <p class="text-[13px] font-medium tracking-wide text-white/70 uppercase">Yang Diterimakan · {{ period }}</p>
            <p class="mt-2 text-[34px] leading-tight font-bold tracking-tight sm:text-5xl"><AnimatedNumber :value="value" :duration="1100" /></p>
            <div v-if="net && school" class="mt-5 flex flex-wrap gap-2">
                <span v-if="gross" class="num rounded-full bg-white/10 px-3 py-1 text-xs font-medium ring-1 ring-white/15">Kotor {{ rupiah(gross) }}</span>
                <span class="num rounded-full bg-white/10 px-3 py-1 text-xs font-medium ring-1 ring-white/15">Bersih {{ rupiah(net) }}</span>
                <span class="num rounded-full bg-gold-500/20 px-3 py-1 text-xs font-medium text-gold-100 ring-1 ring-gold-400/30">Potongan sekolah {{ rupiah(school) }}</span>
            </div>
            <div v-if="$slots.default" class="mt-6 flex flex-col gap-2 sm:flex-row"><slot /></div>
        </div>
    </section>
</template>
