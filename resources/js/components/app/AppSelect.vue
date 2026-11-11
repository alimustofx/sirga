<script setup lang="ts">
import type { SelectOption } from '@/types';
import AppField from './AppField.vue';

const model = defineModel<string | number | null>();
defineProps<{ label?: string; error?: string | string[]; hint?: string; options: SelectOption[]; placeholder?: string; required?: boolean; disabled?: boolean }>();
</script>

<template>
    <AppField v-slot="{ id, invalid }" :label="label" :error="error" :hint="hint" :required="required">
        <select
            :id="id"
            v-model="model"
            :disabled="disabled"
            :aria-invalid="invalid"
            :class="['h-11 w-full rounded-xl border bg-white px-3 text-sm text-neutral-950 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 focus:outline-none disabled:bg-neutral-100 sm:h-10', invalid ? 'border-[#dc2626]' : 'border-neutral-200']"
        >
            <option v-if="placeholder !== undefined" value="">{{ placeholder }}</option>
            <option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
    </AppField>
</template>
