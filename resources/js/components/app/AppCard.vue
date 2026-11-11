<script setup lang="ts">
withDefaults(defineProps<{ title?: string; padded?: boolean; flush?: boolean }>(), { padded: true });
</script>

<template>
    <section class="rounded-xl border border-neutral-200 bg-white shadow-card">
        <header v-if="title || $slots.header" class="flex items-center justify-between gap-3 border-b border-neutral-200 px-4 py-3 sm:px-5">
            <h2 v-if="title" class="text-base font-semibold text-primary-900">{{ title }}</h2>
            <slot name="header" />
        </header>
        <div :class="padded && !flush ? 'p-4 sm:p-5' : ''"><slot /></div>
    </section>
</template>
