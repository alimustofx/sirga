<script setup lang="ts">
import { formatDate } from '@/lib/format';
import { ref } from 'vue';

export type Log = { id: number; label: string; action: string; user: string; nip: string | null; entity: string | null; old: Record<string, unknown> | null; new: Record<string, unknown> | null; reason: string | null; ip: string | null; at: string };
defineProps<{ logs: Log[] }>();
const openId = ref<number | null>(null);
const pretty = (v: unknown): string => (typeof v === 'object' && v !== null ? JSON.stringify(v, null, 2) : String(v));
</script>

<template>
    <ul class="divide-y divide-neutral-100">
        <li v-for="l in logs" :key="l.id">
            <button type="button" class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left hover:bg-neutral-50 sm:px-5" :aria-expanded="openId === l.id" @click="openId = openId === l.id ? null : l.id">
                <span class="min-w-0"><span class="block text-sm font-semibold text-neutral-950">{{ l.label }}</span><span class="block text-xs text-neutral-600">{{ l.user }}<template v-if="l.nip"> ({{ l.nip }})</template><template v-if="l.entity"> · {{ l.entity }}</template></span></span>
                <span class="shrink-0 text-right text-xs text-neutral-600">{{ formatDate(l.at, true) }}</span>
            </button>
            <div v-if="openId === l.id" class="space-y-2 bg-neutral-50 px-4 py-3 text-[13px] sm:px-5">
                <p v-if="l.reason"><strong>Alasan:</strong> {{ l.reason }}</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div v-if="l.old"><p class="mb-1 font-semibold text-neutral-600">Sebelum</p><pre class="max-h-60 overflow-auto rounded-lg bg-white p-2 whitespace-pre-wrap">{{ pretty(l.old) }}</pre></div>
                    <div v-if="l.new"><p class="mb-1 font-semibold text-neutral-600">Sesudah</p><pre class="max-h-60 overflow-auto rounded-lg bg-white p-2 whitespace-pre-wrap">{{ pretty(l.new) }}</pre></div>
                </div>
                <p class="text-xs text-neutral-600">Aksi: {{ l.action }} · IP {{ l.ip ?? '-' }}</p>
            </div>
        </li>
        <li v-if="!logs.length" class="px-5 py-10 text-center text-sm text-neutral-600">Tidak ada aktivitas pada filter ini.</li>
    </ul>
</template>
