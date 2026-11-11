<script setup lang="ts">
import CredentialsDialog from '@/components/admin/CredentialsDialog.vue';
import AppConfirm from '@/components/app/AppConfirm.vue';
import AppDrawer from '@/components/app/AppDrawer.vue';
import AppToast from '@/components/app/AppToast.vue';
import BrandMark from '@/components/layout/BrandMark.vue';
import NavList, { type NavGroup } from '@/components/layout/NavList.vue';
import NotificationBell from '@/components/layout/NotificationBell.vue';
import UserMenu from '@/components/layout/UserMenu.vue';
import { usePage } from '@inertiajs/vue3';
import { Banknote, CalendarRange, ChartColumn, Layers, LayoutDashboard, Megaphone, Menu, Receipt, ScrollText, Settings, ShieldCheck, Users, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const open = ref(false);

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
</script>

<template>
    <div class="min-h-dvh bg-neutral-50">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-primary-900 px-3 py-5 lg:flex">
            <BrandMark light class="mb-7 px-2" />
            <div class="-mx-1 flex-1 overflow-y-auto px-1"><NavList :groups="groups" /></div>
        </aside>

        <AppDrawer v-model:open="open" title="Menu">
            <div class="flex h-full flex-col px-3 py-5">
                <BrandMark light class="mb-6 px-2" />
                <div class="flex-1 overflow-y-auto"><NavList :groups="groups" @navigate="open = false" /></div>
            </div>
        </AppDrawer>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-neutral-200 bg-white/95 px-3 pt-[env(safe-area-inset-top)] backdrop-blur sm:px-6 lg:h-16">
                <button type="button" class="flex size-11 items-center justify-center rounded-lg text-neutral-800 hover:bg-neutral-100 lg:hidden" aria-label="Buka menu" @click="open = true"><Menu class="size-5" /></button>
                <span class="hidden text-sm text-neutral-600 lg:block">{{ page.props.school?.name }}</span>
                <div class="flex items-center gap-1"><NotificationBell /><UserMenu /></div>
            </header>
            <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-8"><slot /></main>
        </div>
        <CredentialsDialog />
        <AppToast />
        <AppConfirm />
    </div>
</template>
