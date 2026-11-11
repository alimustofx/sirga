<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';

export type NavItem = { label: string; href: string; icon: Component; exact?: boolean };
export type NavGroup = { title?: string; items: NavItem[] };

defineProps<{ groups: NavGroup[] }>();
defineEmits<{ navigate: [] }>();
const page = usePage();

const active = (i: NavItem): boolean => {
    const path = page.url.split('?')[0] ?? '';

    return i.exact ? path === i.href : path === i.href || path.startsWith(`${i.href}/`);
};
</script>

<template>
    <nav class="space-y-5" aria-label="Navigasi utama">
        <div v-for="(g, gi) in groups" :key="gi">
            <p v-if="g.title" class="mb-1.5 px-3 text-[11px] font-semibold tracking-wider text-white/50 uppercase">{{ g.title }}</p>
            <ul class="space-y-0.5">
                <li v-for="i in g.items" :key="i.href">
                    <Link
                        :href="i.href"
                        :aria-current="active(i) ? 'page' : undefined"
                        :class="['flex min-h-11 items-center gap-3 rounded-lg border-l-[3px] px-3 text-sm font-medium transition-colors', active(i) ? 'border-primary-500 bg-white/10 text-white' : 'border-transparent text-white/75 hover:bg-white/5 hover:text-white']"
                        @click="$emit('navigate')"
                    >
                        <component :is="i.icon" class="size-[18px] shrink-0" />
                        {{ i.label }}
                    </Link>
                </li>
            </ul>
        </div>
    </nav>
</template>
