<script setup lang="ts">
import AppDropdown, { type DropdownItem } from '@/components/app/AppDropdown.vue';
import { initials } from '@/lib/format';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const items = computed<DropdownItem[]>(() => {
    const u = user.value;
    if (!u) return [];
    const list: DropdownItem[] = [{ label: 'Profil & Akun', action: () => router.visit('/profil') }];
    if (u.role !== 'guru' && u.has_employee) list.push({ label: 'Gaji Saya', action: () => router.visit('/saya') });
    if (u.role !== 'guru' && !page.url.startsWith('/admin')) list.push({ label: 'Dashboard Admin', action: () => router.visit('/admin') });
    list.push({ label: 'Keluar', danger: true, action: () => router.post('/logout') });

    return list;
});
</script>

<template>
    <AppDropdown v-if="user" :items="items" label="Menu pengguna">
        <template #trigger>
            <button type="button" class="flex min-h-11 items-center gap-2 rounded-lg px-1.5 hover:bg-neutral-100 sm:min-h-10">
                <span class="flex size-8 items-center justify-center rounded-full bg-primary-700 text-xs font-bold text-white">{{ initials(user.name) }}</span>
                <span class="hidden max-w-40 truncate text-left text-sm leading-tight font-semibold text-neutral-950 md:block">{{ user.name.replace(/,.*$/, '') }}<span class="block text-xs font-normal text-neutral-600">{{ user.role_label }}</span></span>
                <ChevronDown class="hidden size-4 text-neutral-600 md:block" />
            </button>
        </template>
        <template #header>
            <div class="border-b border-neutral-100 px-3 py-2 md:hidden">
                <p class="truncate text-sm font-semibold">{{ user.name }}</p>
                <p class="text-xs text-neutral-600">{{ user.role_label }}</p>
            </div>
        </template>
    </AppDropdown>
</template>
