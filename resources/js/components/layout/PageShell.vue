<script setup lang="ts">
import PageSkeleton from '@/components/app/PageSkeleton.vue';
import { navigating } from '@/composables/useNavigating';
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

const page = usePage();
const offs: (() => void)[] = [];
let timer: number | undefined;

onMounted(() => {
    offs.push(
        router.on('start', (event) => {
            const visit = event.detail.visit;
            if (visit.method !== 'get') return; // aksi POST/PUT memakai loading pada tombol, bukan skeleton
            const target = new URL(String(visit.url), window.location.origin).pathname;
            const same = target === window.location.pathname;
            window.clearTimeout(timer);
            // tunda 150 ms agar tidak berkedip pada respons cepat
            timer = window.setTimeout(() => { if (same) navigating.table = true; else navigating.page = true; }, 150);
        }),
        router.on('finish', () => {
            window.clearTimeout(timer);
            navigating.page = false;
            navigating.table = false;
        }),
    );
});
onBeforeUnmount(() => { window.clearTimeout(timer); offs.forEach((off) => off()); });
</script>

<template>
    <div :key="page.component" class="animate-fade-up" :aria-busy="navigating.page || navigating.table">
        <PageSkeleton v-if="navigating.page" />
        <div v-show="!navigating.page"><slot /></div>
    </div>
</template>
