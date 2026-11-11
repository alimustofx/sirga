<script setup lang="ts">
import { rupiah } from '@/lib/format';

defineProps<{ value: string; period: string; net?: string; school?: string }>();
</script>

<template>
    <section class="rounded-xl bg-primary-900 p-5 text-white shadow-card sm:p-6">
        <p class="text-sm font-medium text-white/70">Yang Diterimakan · {{ period }}</p>
        <p class="num mt-1 text-3xl font-bold sm:text-4xl">{{ rupiah(value) }}</p>
        <p v-if="net && school" class="mt-3 text-[13px] text-white/70">Penghasilan bersih {{ rupiah(net) }} − potongan sekolah {{ rupiah(school) }}</p>
    </section>
</template>
