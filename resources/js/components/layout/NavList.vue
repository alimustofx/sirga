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
                        :class="['relative flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-all duration-200', active(i) ? 'bg-white/12 text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.07)] before:absolute before:top-2.5 before:bottom-2.5 before:left-0 before:w-[3px] before:rounded-full before:bg-gold-400' : 'text-white/70 hover:bg-white/6 hover:text-white']"
                        @click="$emit('navigate')"
                    >
                        <component :is="i.icon" :class="['size-[18px] shrink-0 transition-colors', active(i) ? 'text-gold-400' : '']" />
                        {{ i.label }}
                    </Link>
                </li>
            </ul>
        </div>
    </nav>
</template>
