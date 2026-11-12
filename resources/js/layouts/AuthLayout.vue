<script setup lang="ts">
import AppToast from '@/components/app/AppToast.vue';
import BrandMark from '@/components/layout/BrandMark.vue';

const points = [
    ['Rincian gaji', 'Penghasilan, potongan resmi, dan potongan sekolah per bulan.'],
    ['Pembayaran', 'Kirim bukti transfer potongan sekolah dari ponsel.'],
    ['Keamanan', 'Setiap akun hanya melihat datanya sendiri. Perubahan tercatat.'],
];
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
        <aside class="bg-hero ledger-lines hidden flex-col justify-between p-12 text-white lg:flex">
            <BrandMark light />
            <div class="max-w-md">
                <h2 class="text-[32px] leading-[1.2] font-bold tracking-tight">Rincian gaji bulanan Anda, dalam satu tempat.</h2>
                <dl class="mt-10 divide-y divide-white/12 border-y border-white/12">
                    <div v-for="p in points" :key="p[0]" class="grid grid-cols-[7rem_1fr] gap-4 py-4"><dt class="eyebrow !text-gold-400">{{ p[0] }}</dt><dd class="text-sm text-white/75">{{ p[1] }}</dd></div>
                </dl>
            </div>
            <p class="text-xs text-white/45">Sistem Informasi Rincian Gaji ASN</p>
        </aside>
        <main class="flex items-center justify-center bg-white px-5 py-10 sm:px-8">
            <div class="w-full max-w-sm">
                <BrandMark class="mb-9 lg:hidden" />
                <slot />
            </div>
        </main>
    </div>
    <AppToast />
</template>
