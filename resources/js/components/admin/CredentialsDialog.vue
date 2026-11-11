<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import { copyText } from '@/composables/useToast';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const data = computed(() => page.props.credentials);
const open = ref(false);
watch(data, (v) => { open.value = !!v?.list?.length; }, { immediate: true });

function csv(): void {
    const rows = [['NIP', 'Nama', 'Password awal'], ...(data.value?.list ?? []).map((c) => [c.nip, c.name, c.password])];
    const text = rows.map((r) => r.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([`\uFEFF${text}`], { type: 'text/csv;charset=utf-8' }));
    a.download = 'akun-awal-asn.csv';
    a.click();
    URL.revokeObjectURL(a.href);
}
</script>

<template>
    <AppDialog v-model:open="open" title="Password awal akun" description="Catat atau unduh sekarang. Password ini hanya ditampilkan sekali dan wajib diganti saat login pertama." size="lg">
        <ul class="divide-y divide-neutral-100 rounded-lg border border-neutral-200">
            <li v-for="c in data?.list ?? []" :key="c.nip" class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5">
                <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ c.name }}</p><p class="num text-xs text-neutral-600">{{ c.nip }}</p></div>
                <div class="flex items-center gap-2">
                    <code class="rounded bg-neutral-100 px-2 py-1 text-sm font-semibold">{{ c.password }}</code>
                    <AppButton size="sm" variant="outline" @click="copyText(c.password, 'Password')">Salin</AppButton>
                </div>
            </li>
        </ul>
        <p v-if="data?.skipped" class="mt-3 text-[13px] text-neutral-600">{{ data.skipped }} baris dilewati (NIP kosong/tidak valid/sudah terdaftar).</p>
        <template #footer>
            <AppButton v-if="(data?.list.length ?? 0) > 1" variant="outline" @click="csv">Unduh CSV</AppButton>
            <AppButton @click="open = false">Selesai</AppButton>
        </template>
    </AppDialog>
</template>
