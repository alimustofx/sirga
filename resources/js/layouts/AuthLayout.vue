<script setup lang="ts">
import AppToast from '@/components/app/AppToast.vue';
import BrandMark from '@/components/layout/BrandMark.vue';
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-primary-900 p-12 text-white lg:flex">
            <div class="pointer-events-none absolute -right-24 -bottom-24 size-96 rounded-full bg-primary-700/40" aria-hidden="true" />
            <div class="pointer-events-none absolute -top-16 -left-16 size-64 rounded-full bg-primary-800/70" aria-hidden="true" />
            <BrandMark light class="relative" />
            <div class="relative max-w-md">
                <h2 class="text-3xl leading-snug font-bold">Rincian gaji Anda, jelas dan transparan.</h2>
                <ul class="mt-6 space-y-3 text-[15px] text-white/80">
                    <li>Lihat penghasilan, potongan resmi, dan potongan sekolah per bulan.</li>
                    <li>Unduh slip gaji kapan saja.</li>
                    <li>Kirim bukti pembayaran potongan sekolah secara daring.</li>
                </ul>
            </div>
            <p class="relative text-xs text-white/50">Sistem Informasi Rincian Gaji ASN</p>
        </aside>
        <main class="flex items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <BrandMark class="mb-8 lg:hidden" />
                <slot />
            </div>
        </main>
    </div>
    <AppToast />
</template>
