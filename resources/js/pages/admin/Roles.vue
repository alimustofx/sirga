<script setup lang="ts">
import AppCard from '@/components/app/AppCard.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import { Head } from '@inertiajs/vue3';
import { Check, Minus } from '@lucide/vue';

defineProps<{ roles: { id: number; name: string; label: string; description: string | null; users_count: number }[]; matrix: [string, boolean, boolean, boolean][] }>();
</script>

<template>
    <Head title="Manajemen Role" />
    <AppPageHeader title="Manajemen Role" subtitle="Hak akses ditetapkan oleh sistem. Role pengguna diatur dari menu Data ASN." />
    <div class="grid gap-3 sm:grid-cols-3">
        <div v-for="r in roles" :key="r.id" class="rounded-xl border border-neutral-200 bg-white p-4 shadow-card"><p class="text-base font-bold text-primary-900">{{ r.label }}</p><p class="mt-1 text-[13px] text-neutral-600">{{ r.description }}</p><p class="num mt-3 text-sm font-semibold">{{ r.users_count }} pengguna</p></div>
    </div>
    <AppCard class="mt-4" title="Matriks Hak Akses" flush>
        <div class="overflow-x-auto">
            <table class="w-full text-sm"><thead><tr class="border-b border-neutral-200 bg-neutral-50 text-xs text-neutral-600 uppercase"><th class="px-4 py-2.5 text-left">Fitur</th><th v-for="r in roles" :key="r.id" class="px-3 py-2.5 text-center">{{ r.label }}</th></tr></thead>
                <tbody><tr v-for="m in matrix" :key="m[0]" class="border-b border-neutral-100 last:border-0"><td class="px-4 py-2.5">{{ m[0] }}</td><td v-for="i in [1, 2, 3]" :key="i" class="px-3 py-2.5 text-center"><Check v-if="m[i]" class="mx-auto size-4 text-success-fg" aria-label="Ya" /><Minus v-else class="mx-auto size-4 text-neutral-400" aria-label="Tidak" /></td></tr></tbody>
            </table>
        </div>
    </AppCard>
</template>
