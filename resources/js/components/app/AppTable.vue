<script setup lang="ts" generic="T extends Record<string, any>">
import { navigating } from '@/composables/useNavigating';
import AppEmptyState from './AppEmptyState.vue';

export type Column = { key: string; label: string; align?: 'left' | 'right'; mobile?: boolean; primary?: boolean; class?: string };

// loading: undefined = ikuti status navigasi global (jangan dibiarkan jadi false oleh casting boolean Vue)
const props = withDefaults(defineProps<{ columns: Column[]; rows: T[]; rowKey?: string; emptyTitle?: string; emptyText?: string; loading?: boolean; skeletonRows?: number }>(), { loading: undefined });
const key = (r: T, i: number) => String(r[props.rowKey ?? 'id'] ?? i);
const isLoading = () => props.loading ?? navigating.table;
</script>

<template>
    <!-- skeleton: saat filter/halaman berganti -->
    <template v-if="isLoading()">
        <div class="hidden md:block" role="status" aria-label="Memuat data">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-neutral-200 bg-neutral-50"><th v-for="c in columns" :key="c.key" class="px-4 py-3 text-left"><span class="eyebrow">{{ c.label }}</span></th></tr></thead>
                <tbody><tr v-for="n in skeletonRows ?? 8" :key="n" class="border-b border-neutral-100"><td v-for="(c, ci) in columns" :key="c.key" class="px-4 py-4"><div class="skeleton h-4 rounded" :style="{ width: ci === 0 ? '70%' : '55%', marginLeft: c.align === 'right' ? 'auto' : undefined }" /></td></tr></tbody>
            </table>
        </div>
        <ul class="divide-y divide-neutral-100 md:hidden" role="status" aria-label="Memuat data"><li v-for="n in 5" :key="n" class="space-y-2 px-4 py-4"><div class="skeleton h-4 w-2/3 rounded" /><div class="skeleton h-3 w-full rounded" /><div class="skeleton h-3 w-4/5 rounded" /></li></ul>
    </template>

    <div v-else-if="!rows.length"><AppEmptyState :title="emptyTitle ?? 'Belum ada data'" :description="emptyText"><slot name="empty" /></AppEmptyState></div>
    <template v-else>
        <div class="thin-scroll hidden overflow-x-auto md:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 bg-neutral-50">
                        <th v-for="c in columns" :key="c.key" scope="col" :class="['px-4 py-3 whitespace-nowrap', c.align === 'right' ? 'text-right' : 'text-left']"><span class="eyebrow">{{ c.label }}</span></th>
                        <th v-if="$slots.actions" class="w-px px-3" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(r, i) in rows" :key="key(r, i)" class="border-b border-neutral-100 transition-colors last:border-0 hover:bg-neutral-50">
                        <td v-for="c in columns" :key="c.key" :class="['px-4 py-3 align-middle', c.align === 'right' ? 'num text-right' : '', c.class]">
                            <slot :name="`cell-${c.key}`" :row="r" :value="r[c.key]">{{ r[c.key] }}</slot>
                        </td>
                        <td v-if="$slots.actions" class="px-3 py-2 text-right whitespace-nowrap"><slot name="actions" :row="r" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <ul class="divide-y divide-neutral-100 md:hidden">
            <li v-for="(r, i) in rows" :key="key(r, i)" class="space-y-2.5 px-4 py-3.5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 font-semibold text-primary-900"><template v-for="c in columns.filter((x) => x.primary)" :key="c.key"><slot :name="`cell-${c.key}`" :row="r" :value="r[c.key]">{{ r[c.key] }}</slot></template></div>
                    <slot name="actions" :row="r" />
                </div>
                <dl class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-[13px]">
                    <template v-for="c in columns.filter((x) => !x.primary && x.mobile !== false)" :key="c.key">
                        <dt class="text-neutral-600">{{ c.label }}</dt>
                        <dd :class="['text-right text-neutral-950', c.align === 'right' ? 'num' : '']"><slot :name="`cell-${c.key}`" :row="r" :value="r[c.key]">{{ r[c.key] }}</slot></dd>
                    </template>
                </dl>
            </li>
        </ul>
    </template>
</template>
