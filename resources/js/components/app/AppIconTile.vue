<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(defineProps<{ icon: Component; tone?: 'teal' | 'navy' | 'gold' | 'success' | 'warning' | 'danger'; size?: 'sm' | 'md' }>(), { tone: 'teal', size: 'md' });
const tones = {
    teal: 'bg-primary-600/10 text-primary-600', navy: 'bg-primary-900/10 text-primary-900', gold: 'bg-gold-100 text-gold-700',
    success: 'bg-success-bg text-success-fg', warning: 'bg-warning-bg text-warning-fg', danger: 'bg-danger-bg text-danger-fg',
};
</script>

<template><span :class="['inline-flex shrink-0 items-center justify-center rounded-xl', size === 'sm' ? 'size-8' : 'size-10', tones[tone]]"><component :is="icon" :class="size === 'sm' ? 'size-4' : 'size-5'" /></span></template>
