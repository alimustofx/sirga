<script setup lang="ts">
import AppField from './AppField.vue';

const model = defineModel<string | null>();
defineProps<{ label?: string; error?: string | string[]; hint?: string; rows?: number; placeholder?: string; required?: boolean; maxlength?: number }>();
</script>

<template>
    <AppField v-slot="{ id, invalid }" :label="label" :error="error" :hint="hint" :required="required">
        <textarea
            :id="id"
            v-model="model"
            :rows="rows ?? 4"
            :placeholder="placeholder"
            :maxlength="maxlength"
            :aria-invalid="invalid"
            :class="['w-full rounded-xl border bg-white px-3 py-2.5 text-sm text-neutral-950 placeholder:text-neutral-400 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 focus:outline-none', invalid ? 'border-[#dc2626]' : 'border-neutral-200']"
        />
    </AppField>
</template>
