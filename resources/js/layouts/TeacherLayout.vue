<script setup lang="ts">
import AppConfirm from '@/components/app/AppConfirm.vue';
import AppToast from '@/components/app/AppToast.vue';
import BrandMark from '@/components/layout/BrandMark.vue';
import NotificationBell from '@/components/layout/NotificationBell.vue';
import PageShell from '@/components/layout/PageShell.vue';
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
const unread = computed(() => page.props.notifications?.unread ?? 0);
</script>

<template>
    <div class="min-h-dvh">
        <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-primary-900 focus:px-3 focus:py-2 focus:text-white">Lewati ke konten</a>
        <header class="glass sticky top-0 z-30 border-b border-neutral-200/70 pt-[env(safe-area-inset-top)]">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
                <Link href="/saya"><BrandMark /></Link>
                <nav class="hidden items-center gap-1 rounded-2xl bg-neutral-100/80 p-1 md:flex" aria-label="Navigasi utama">
                    <Link v-for="n in nav" :key="n.href" :href="n.href" :aria-current="active(n) ? 'page' : undefined" :class="['rounded-xl px-3.5 py-1.5 text-sm font-semibold transition-all', active(n) ? 'bg-white text-primary-900 shadow-card' : 'text-neutral-600 hover:text-neutral-950']">{{ n.label }}</Link>
                    <Link href="/saya/pengumuman" :class="['rounded-xl px-3.5 py-1.5 text-sm font-semibold transition-all', path.startsWith('/saya/pengumuman') ? 'bg-white text-primary-900 shadow-card' : 'text-neutral-600 hover:text-neutral-950']">Pengumuman</Link>
                </nav>
                <div class="flex items-center gap-1">
                    <Link v-if="isAdmin" href="/admin" class="mr-1 hidden rounded-xl border border-neutral-200 bg-white px-3 py-1.5 text-[13px] font-semibold text-neutral-800 shadow-card hover:bg-neutral-50 sm:block">Ke Admin</Link>
                    <NotificationBell />
                    <UserMenu />
                </div>
            </div>
        </header>

        <main id="konten" class="mx-auto max-w-5xl px-4 pt-6 pb-32 sm:px-6 sm:pt-9 md:pb-14"><PageShell><slot /></PageShell></main>

        <nav class="fixed inset-x-3 bottom-3 z-30 mb-[env(safe-area-inset-bottom)] md:hidden" aria-label="Navigasi bawah">
            <ul class="glass grid grid-cols-5 gap-0.5 rounded-2xl border border-white/70 p-1 shadow-lift">
                <li v-for="n in nav" :key="n.href">
                    <Link :href="n.href" :aria-current="active(n) ? 'page' : undefined" :class="['relative flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-xl text-[11px] font-semibold transition-all duration-200', active(n) ? 'bg-primary-900 text-white shadow-[0_6px_14px_-4px_rgba(20,43,69,0.6)]' : 'text-neutral-600 active:bg-neutral-100']">
                        <component :is="n.icon" class="size-5" />{{ n.short }}
                        <span v-if="n.href === '/profil' && unread" class="absolute top-1.5 right-3 size-2 rounded-full bg-[#dc2626] ring-2 ring-white" aria-label="Ada notifikasi" />
                    </Link>
                </li>
            </ul>
        </nav>
        <AppToast />
        <AppConfirm />
    </div>
</template>
