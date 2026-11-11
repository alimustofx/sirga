<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const busy = ref(false);
const offs: (() => void)[] = [];

onMounted(() => {
    offs.push(router.on('start', () => { busy.value = true; }), router.on('finish', () => { busy.value = false; }));
});
onBeforeUnmount(() => offs.forEach((off) => off()));
</script>

<template>
    <!-- key = komponen halaman: animasi masuk saat pindah halaman, bukan saat filter berubah -->
    <div :key="page.component" :class="['animate-fade-up transition-opacity duration-200', busy ? 'opacity-60' : 'opacity-100']" :aria-busy="busy">
        <slot />
    </div>
</template>
