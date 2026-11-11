<script setup lang="ts">
import AppBadge from '@/components/app/AppBadge.vue';
import AppButton from '@/components/app/AppButton.vue';
import { formatNip, rupiah } from '@/lib/format';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

export type ImportRowData = {
    id: number; row_number: number; nip: string | null; name: string | null; status: 'valid' | 'error' | 'skipped';
    gross: string; official: string; net: string; source: { gross: string | null; official: string | null; net: string | null };
    errors: { type: string; message: string; blocking: boolean; resolved: boolean }[];
};
const props = defineProps<{ rows: ImportRowData[]; editable: boolean }>();
const filter = ref<'all' | 'problem' | 'warning'>('all');
const shown = computed(() => props.rows.filter((r) => filter.value === 'all' || (filter.value === 'problem' ? r.status !== 'valid' : r.errors.some((e) => !e.blocking))));
const skip = (r: ImportRowData) => router.post(`/admin/impor/baris/${r.id}/abaikan`, {}, { preserveScroll: true });
const restore = (r: ImportRowData) => router.post(`/admin/impor/baris/${r.id}/pulihkan`, {}, { preserveScroll: true });
const tone = { valid: 'success', error: 'danger', skipped: 'neutral' } as const;
const label = { valid: 'Valid', error: 'Bermasalah', skipped: 'Diabaikan' } as const;
</script>

<template>
    <div>
        <div class="flex gap-1 border-b border-neutral-200 px-4 py-2" role="group" aria-label="Filter baris">
            <button v-for="o in [['all', 'Semua'], ['problem', 'Bermasalah / diabaikan'], ['warning', 'Selisih sumber']] as const" :key="o[0]" type="button" :aria-pressed="filter === o[0]" :class="['min-h-9 rounded-lg px-3 text-[13px] font-semibold', filter === o[0] ? 'bg-primary-900 text-white' : 'text-neutral-600 hover:bg-neutral-100']" @click="filter = o[0]">{{ o[1] }}</button>
        </div>
        <ul class="divide-y divide-neutral-100">
            <li v-for="r in shown" :key="r.id" :class="['px-4 py-3 sm:px-5', r.status === 'skipped' ? 'bg-neutral-50 opacity-70' : '']">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0"><p class="text-sm font-semibold text-primary-900">{{ r.name || '(tanpa nama)' }} <span class="text-xs font-normal text-neutral-600">baris {{ r.row_number }}</span></p><p class="num text-xs text-neutral-600">{{ r.nip ? formatNip(r.nip) : 'NIP kosong' }}</p></div>
                    <div class="flex items-center gap-2"><AppBadge :tone="tone[r.status]">{{ label[r.status] }}</AppBadge>
                        <template v-if="editable"><AppButton v-if="r.status === 'error'" size="sm" variant="outline" @click="skip(r)">Abaikan</AppButton><AppButton v-if="r.status === 'skipped'" size="sm" variant="outline" @click="restore(r)">Pulihkan</AppButton></template>
                    </div>
                </div>
                <dl class="mt-2 grid grid-cols-3 gap-2 text-[13px]"><div><dt class="text-neutral-600">Gaji kotor</dt><dd class="num font-medium">{{ rupiah(r.gross) }}</dd></div><div><dt class="text-neutral-600">Pot. resmi</dt><dd class="num font-medium">{{ rupiah(r.official) }}</dd></div><div><dt class="text-neutral-600">Bersih</dt><dd class="num font-medium">{{ rupiah(r.net) }}</dd></div></dl>
                <ul v-if="r.errors.length" class="mt-2 space-y-1">
                    <li v-for="(e, i) in r.errors" :key="i" :class="['rounded-md px-2.5 py-1.5 text-[13px]', e.resolved ? 'bg-neutral-100 text-neutral-600 line-through' : e.blocking ? 'bg-danger-bg text-danger-fg' : 'bg-warning-bg text-warning-fg']">{{ e.message }}</li>
                </ul>
            </li>
            <li v-if="!shown.length" class="px-5 py-8 text-center text-sm text-neutral-600">Tidak ada baris pada filter ini.</li>
        </ul>
    </div>
</template>
