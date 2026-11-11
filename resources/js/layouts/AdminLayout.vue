<script setup lang="ts">
import CredentialsDialog from '@/components/admin/CredentialsDialog.vue';
import AppConfirm from '@/components/app/AppConfirm.vue';
import AppDrawer from '@/components/app/AppDrawer.vue';
import AppToast from '@/components/app/AppToast.vue';
import BrandMark from '@/components/layout/BrandMark.vue';
import CommandPalette from '@/components/layout/CommandPalette.vue';
import NavList, { type NavGroup } from '@/components/layout/NavList.vue';
import NotificationBell from '@/components/layout/NotificationBell.vue';
import PageShell from '@/components/layout/PageShell.vue';
import UserMenu from '@/components/layout/UserMenu.vue';
import { initials } from '@/lib/format';
import { router, usePage } from '@inertiajs/vue3';
import { Banknote, CalendarRange, ChartColumn, Layers, LayoutDashboard, LogOut, Megaphone, Menu, Receipt, ScrollText, Search, Settings, ShieldCheck, Users, Wallet } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const open = ref(false);
const palette = ref(false);

const groups = computed<NavGroup[]>(() => {
    const u = user.value;
    const g: NavGroup[] = [
        { items: [{ label: 'Dashboard', href: '/admin', icon: LayoutDashboard, exact: true }] },
        {
            title: 'Penggajian',
            items: [
                { label: 'Periode Gaji', href: '/admin/periode', icon: CalendarRange },
                { label: 'Penghasilan', href: '/admin/penghasilan', icon: Banknote },
                { label: 'Potongan Sekolah', href: '/admin/potongan', icon: Receipt },
                { label: 'Pembayaran', href: '/admin/pembayaran', icon: Wallet },
            ],
        },
        {
            title: 'Data & Informasi',
            items: [
                { label: 'Data ASN', href: '/admin/asn', icon: Users },
                { label: 'Komponen Gaji', href: '/admin/komponen', icon: Layers },
                { label: 'Pengumuman', href: '/admin/pengumuman', icon: Megaphone },
                { label: 'Laporan & Export', href: '/admin/laporan', icon: ChartColumn },
            ],
        },
        {
            title: 'Sistem',
            items: [
                { label: 'Audit Aktivitas', href: '/admin/audit', icon: ScrollText },
                { label: 'Pengaturan', href: '/admin/pengaturan', icon: Settings },
                ...(u?.role === 'super_admin' ? [{ label: 'Manajemen Role', href: '/admin/role', icon: ShieldCheck }] : []),
            ],
        },
    ];
    if (u?.has_employee) {
        g.push({ items: [{ label: 'Gaji Saya', href: '/saya', icon: Wallet, exact: true }] });
    }

    return g;
});

const current = computed(() => {
    const path = page.url.split('?')[0] ?? '';
    const items = groups.value.flatMap((x) => x.items).sort((a, b) => b.href.length - a.href.length);

    return items.find((i) => (i.exact ? path === i.href : path === i.href || path.startsWith(`${i.href}/`)))?.label ?? 'Dashboard';
});

function shortcut(e: KeyboardEvent): void {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        palette.value = !palette.value;
    }
}
onMounted(() => window.addEventListener('keydown', shortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', shortcut));
</script>

<template>
    <div class="min-h-dvh">
        <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:rounded-lg focus:bg-primary-900 focus:px-3 focus:py-2 focus:text-white">Lewati ke konten</a>

        <aside class="bg-sidebar fixed inset-y-0 left-0 z-30 hidden w-64 flex-col px-3 py-5 lg:flex">
            <BrandMark light class="mb-5 px-2" />
            <button type="button" class="mb-5 flex min-h-10 items-center gap-2 rounded-xl bg-white/8 px-3 text-left text-[13px] text-white/60 ring-1 ring-white/10 transition-colors hover:bg-white/12 hover:text-white/80" @click="palette = true">
                <Search class="size-4" /><span class="flex-1">Cari menu…</span><kbd class="rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold">⌘K</kbd>
            </button>
            <div class="-mx-1 flex-1 overflow-y-auto px-1"><NavList :groups="groups" /></div>
            <div v-if="user" class="mt-3 flex items-center gap-3 rounded-xl bg-white/6 p-2.5 ring-1 ring-white/10">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gold-500 text-xs font-bold text-primary-900">{{ initials(user.name) }}</span>
                <span class="min-w-0 flex-1 leading-tight"><span class="block truncate text-[13px] font-semibold text-white">{{ user.name.replace(/,.*$/, '') }}</span><span class="block truncate text-[11px] text-white/60">{{ user.role_label }}</span></span>
                <button type="button" class="flex size-9 items-center justify-center rounded-lg text-white/60 hover:bg-white/10 hover:text-white" aria-label="Keluar" @click="router.post('/logout')"><LogOut class="size-4" /></button>
            </div>
        </aside>

        <AppDrawer v-model:open="open" title="Menu">
            <div class="bg-sidebar flex h-full flex-col px-3 py-5">
                <BrandMark light class="mb-6 px-2" />
                <div class="flex-1 overflow-y-auto"><NavList :groups="groups" @navigate="open = false" /></div>
            </div>
        </AppDrawer>

        <div class="lg:pl-64">
            <header class="glass sticky top-0 z-20 flex h-14 items-center justify-between gap-2 border-b border-neutral-200/70 px-3 pt-[env(safe-area-inset-top)] sm:px-6 lg:h-16">
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" class="flex size-11 items-center justify-center rounded-xl text-neutral-800 hover:bg-neutral-100 lg:hidden" aria-label="Buka menu" @click="open = true"><Menu class="size-5" /></button>
                    <p class="truncate text-sm text-neutral-600"><span class="hidden sm:inline">{{ page.props.school?.name }} <span class="mx-1 text-neutral-400">/</span></span><span class="font-semibold text-primary-900">{{ current }}</span></p>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" class="hidden min-h-10 items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 text-[13px] text-neutral-600 shadow-card hover:border-neutral-400 md:flex" @click="palette = true"><Search class="size-4" />Cari<kbd class="rounded bg-neutral-100 px-1.5 py-0.5 text-[10px] font-semibold">⌘K</kbd></button>
                    <button type="button" class="flex size-11 items-center justify-center rounded-xl text-neutral-800 hover:bg-neutral-100 md:hidden" aria-label="Cari menu" @click="palette = true"><Search class="size-5" /></button>
                    <NotificationBell /><UserMenu />
                </div>
            </header>
            <main id="konten" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-9"><PageShell><slot /></PageShell></main>
        </div>
        <CommandPalette v-model:open="palette" :groups="groups" />
        <CredentialsDialog />
        <AppToast />
        <AppConfirm />
    </div>
</template>
