<script setup lang="ts">
import { rupiah } from '@/lib/format';
import { computed } from 'vue';
import AppInput from './AppInput.vue';

const model = defineModel<string>({ default: '' });
defineProps<{ label?: string; error?: string | string[]; required?: boolean; disabled?: boolean }>();

const preview = computed(() => (model.value !== '' ? rupiah(model.value) : undefined));

function onInput(v: string | number | null | undefined): void {
    // hanya angka dan satu titik desimal; tidak ada pemisah ribuan agar tidak ambigu
    const clean = String(v ?? '').replace(',', '.').replace(/[^0-9.]/g, '');
    const [i, ...rest] = clean.split('.');
    model.value = rest.length ? `${i}.${rest.join('').slice(0, 2)}` : i;
}
</script>

<template>
    <AppInput :model-value="model" inputmode="decimal" prefix="Rp" :label="label" :error="error" :hint="preview" :required="required" :disabled="disabled" @update:model-value="onInput" />
</template>
