<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ light?: boolean; compact?: boolean }>();
const page = usePage();
const school = computed(() => page.props.school);
</script>

<template>
    <div class="flex min-w-0 items-center gap-3">
        <img v-if="school?.has_logo" src="/sekolah/logo" alt="Logo sekolah" class="size-10 shrink-0 rounded-lg bg-white object-contain p-0.5" />
        <div v-else :class="['flex size-10 shrink-0 items-center justify-center rounded-lg text-base font-bold', light ? 'bg-white text-primary-900' : 'bg-primary-900 text-white']">S</div>
        <div v-if="!compact" class="min-w-0 leading-tight">
            <p :class="['truncate text-sm font-bold', light ? 'text-white' : 'text-primary-900']">{{ school?.name ?? 'SMA Negeri 1 Turen' }}</p>
            <p :class="['truncate text-xs', light ? 'text-white/70' : 'text-neutral-600']">Rincian Gaji ASN</p>
        </div>
    </div>
</template>
