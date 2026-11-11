<script setup lang="ts">
import AppConfirm from '@/components/app/AppConfirm.vue';
import AppToast from '@/components/app/AppToast.vue';
import BrandMark from '@/components/layout/BrandMark.vue';
import NotificationBell from '@/components/layout/NotificationBell.vue';
import UserMenu from '@/components/layout/UserMenu.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { FileText, History, House, User, Wallet } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const nav = [
    { label: 'Beranda', short: 'Beranda', href: '/saya', icon: House, exact: true },
    { label: 'Rincian Gaji', short: 'Rincian', href: '/saya/rincian', icon: FileText },
    { label: 'Riwayat', short: 'Riwayat', href: '/saya/riwayat', icon: History },
    { label: 'Pembayaran', short: 'Bayar', href: '/saya/pembayaran', icon: Wallet },
    { label: 'Profil', short: 'Profil', href: '/profil', icon: User },
];
const path = computed(() => page.url.split('?')[0] ?? '');
const active = (n: (typeof nav)[number]) => (n.exact ? path.value === n.href : path.value === n.href || path.value.startsWith(`${n.href}/`));
const isAdmin = computed(() => page.props.auth.user?.role !== 'guru');
</script>

<template>
    <div class="min-h-dvh bg-neutral-50">
        <header class="sticky top-0 z-30 border-b border-neutral-200 bg-white pt-[env(safe-area-inset-top)]">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
                <Link href="/saya"><BrandMark /></Link>
                <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                    <Link v-for="n in nav" :key="n.href" :href="n.href" :aria-current="active(n) ? 'page' : undefined" :class="['rounded-lg px-3 py-2 text-sm font-semibold transition-colors', active(n) ? 'bg-[#e6f2f4] text-primary-700' : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950']">{{ n.label }}</Link>
                    <Link href="/saya/pengumuman" :class="['rounded-lg px-3 py-2 text-sm font-semibold transition-colors', path.startsWith('/saya/pengumuman') ? 'bg-[#e6f2f4] text-primary-700' : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950']">Pengumuman</Link>
                </nav>
                <div class="flex items-center gap-1">
                    <Link v-if="isAdmin" href="/admin" class="mr-1 hidden rounded-lg border border-neutral-200 px-3 py-1.5 text-[13px] font-semibold text-neutral-800 hover:bg-neutral-50 sm:block">Ke Admin</Link>
                    <NotificationBell />
                    <UserMenu />
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 pt-5 pb-28 sm:px-6 sm:pt-8 md:pb-12"><slot /></main>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-neutral-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="Navigasi bawah">
            <ul class="grid grid-cols-5">
                <li v-for="n in nav" :key="n.href">
                    <Link :href="n.href" :aria-current="active(n) ? 'page' : undefined" :class="['flex min-h-14 flex-col items-center justify-center gap-0.5 text-[11px] font-semibold', active(n) ? 'text-primary-700' : 'text-neutral-600']">
                        <component :is="n.icon" class="size-5" />
                        {{ n.short }}
                    </Link>
                </li>
            </ul>
        </nav>
        <AppToast />
        <AppConfirm />
    </div>
</template>
