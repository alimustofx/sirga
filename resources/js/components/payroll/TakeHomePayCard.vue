<script setup lang="ts">
import AnimatedNumber from '@/components/viz/AnimatedNumber.vue';
import { rupiah } from '@/lib/format';

defineProps<{ value: string; period: string; net?: string; school?: string; gross?: string }>();
</script>

<template>
    <section class="overflow-hidden rounded-xl bg-hero text-white">
        <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <p class="eyebrow !text-white/60">Yang diterimakan · {{ period }}</p>
                <p class="mt-2 text-[34px] leading-tight font-bold tracking-tight sm:text-[44px]"><AnimatedNumber :value="value" :duration="700" /></p>
            </div>
            <dl v-if="net && school" class="grid grid-cols-3 divide-x divide-white/15 text-left">
                <div v-if="gross" class="pr-4 sm:pr-6"><dt class="eyebrow !text-white/60">Gaji kotor</dt><dd class="num mt-1 text-[13px] font-semibold sm:text-sm">{{ rupiah(gross) }}</dd></div>
                <div class="px-4 sm:px-6"><dt class="eyebrow !text-white/60">Bersih</dt><dd class="num mt-1 text-[13px] font-semibold sm:text-sm">{{ rupiah(net) }}</dd></div>
                <div class="pl-4 sm:pl-6"><dt class="eyebrow !text-white/60">Pot. sekolah</dt><dd class="num mt-1 text-[13px] font-semibold text-gold-400 sm:text-sm">{{ rupiah(school) }}</dd></div>
            </dl>
        </div>
        <div v-if="$slots.default" class="flex flex-col gap-2 border-t border-white/10 px-5 py-4 sm:flex-row sm:px-7"><slot /></div>
    </section>
</template>
