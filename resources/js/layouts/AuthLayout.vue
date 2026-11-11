<script setup lang="ts">
import AppToast from '@/components/app/AppToast.vue';
import BrandMark from '@/components/layout/BrandMark.vue';
import { FileText, ShieldCheck, Wallet } from '@lucide/vue';

const points = [
    { icon: FileText, title: 'Rincian gaji transparan', text: 'Penghasilan, potongan resmi, dan potongan sekolah per bulan.' },
    { icon: Wallet, title: 'Bayar & unggah bukti', text: 'Kirim bukti transfer potongan sekolah langsung dari ponsel.' },
    { icon: ShieldCheck, title: 'Aman & tercatat', text: 'Setiap akun hanya melihat datanya sendiri; perubahan tercatat.' },
];
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-[1.1fr_1fr]">
        <aside class="bg-hero relative hidden flex-col justify-between overflow-hidden p-12 text-white lg:flex">
            <div class="dot-grid pointer-events-none absolute inset-0 opacity-50" aria-hidden="true" />
            <div class="pointer-events-none absolute -right-28 -bottom-28 size-[26rem] rounded-full border border-white/10" aria-hidden="true" />
            <div class="pointer-events-none absolute -right-10 -bottom-10 size-[18rem] rounded-full border border-white/10" aria-hidden="true" />
            <BrandMark light class="relative" />

            <div class="relative max-w-lg">
                <span class="gold-rule mb-5 block" aria-hidden="true" />
                <h2 class="text-[34px] leading-[1.15] font-bold tracking-tight">Rincian gaji Anda,<br />jelas dan transparan.</h2>
                <ul class="mt-9 space-y-5">
                    <li v-for="(p, i) in points" :key="p.title" class="flex animate-fade-up gap-4" :style="{ animationDelay: `${200 + i * 120}ms` }">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-gold-400 ring-1 ring-white/15"><component :is="p.icon" class="size-5" /></span>
                        <span><span class="block text-[15px] font-semibold">{{ p.title }}</span><span class="block text-sm text-white/70">{{ p.text }}</span></span>
                    </li>
                </ul>
            </div>

            <!-- pratinjau slip (dekoratif) -->
            <div class="pointer-events-none absolute top-24 -right-6 hidden w-64 rotate-6 animate-float rounded-2xl bg-white/95 p-4 text-neutral-950 shadow-hero xl:block" aria-hidden="true">
                <div class="mb-3 flex items-center justify-between"><span class="text-[11px] font-bold text-primary-900">SLIP GAJI</span><span class="rounded-full bg-success-bg px-2 py-0.5 text-[10px] font-semibold text-success-fg">Terbit</span></div>
                <div class="space-y-2"><div class="h-2 w-3/4 rounded bg-neutral-200" /><div class="h-2 w-1/2 rounded bg-neutral-200" /><div class="h-2 w-2/3 rounded bg-neutral-200" /></div>
                <div class="mt-4 rounded-xl bg-primary-900 p-3 text-white"><div class="text-[10px] text-white/60">Yang Diterimakan</div><div class="mt-1 h-3 w-24 rounded bg-white/30" /></div>
            </div>

            <p class="relative text-xs text-white/50">Sistem Informasi Rincian Gaji ASN</p>
        </aside>

        <main class="relative flex items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-[26rem] animate-fade-up">
                <BrandMark class="mb-8 lg:hidden" />
                <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-lift sm:p-8"><slot /></div>
                <p class="mt-5 text-center text-xs text-neutral-600">Dilindungi akses per akun · aktivitas tercatat</p>
            </div>
        </main>
    </div>
    <AppToast />
</template>
