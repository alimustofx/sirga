<script setup lang="ts">
// Kerangka generik saat pindah halaman: header, deretan statistik, lalu tabel/kartu.
</script>

<template>
    <div class="space-y-6" role="status" aria-live="polite" aria-label="Memuat halaman">
        <div class="border-b border-neutral-200 pb-5">
            <div class="skeleton mb-2 h-3 w-24 rounded" /><div class="skeleton h-8 w-64 rounded-md" /><div class="skeleton mt-3 h-4 w-96 max-w-full rounded" />
        </div>
        <div class="grid grid-cols-2 divide-x divide-neutral-200 overflow-hidden rounded-xl border border-neutral-200 bg-white lg:grid-cols-4">
            <div v-for="n in 4" :key="n" class="space-y-3 p-4"><div class="skeleton h-3 w-20 rounded" /><div class="skeleton h-7 w-28 rounded-md" /><div class="skeleton h-3 w-24 rounded" /></div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white">
            <div class="border-b border-neutral-200 p-4"><div class="skeleton h-5 w-40 rounded" /></div>
            <div v-for="n in 6" :key="n" class="flex items-center gap-4 border-b border-neutral-100 p-4 last:border-0">
                <div class="skeleton size-8 shrink-0 rounded-full" /><div class="skeleton h-4 flex-1 rounded" /><div class="skeleton hidden h-4 w-24 rounded sm:block" /><div class="skeleton h-4 w-20 rounded" />
            </div>
        </div>
    </div>
</template>
