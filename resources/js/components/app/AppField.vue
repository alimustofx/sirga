<script setup lang="ts">
import { useId } from 'vue';

defineProps<{ label?: string; error?: string | string[]; hint?: string; required?: boolean }>();
const id = useId();
</script>

<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="id" class="block text-[13px] font-semibold text-neutral-800">
            {{ label }}<span v-if="required" class="ml-0.5 text-[#b91c1c]" aria-hidden="true">*</span>
        </label>
        <slot :id="id" :invalid="!!error" />
        <p v-if="error" class="text-[13px] font-medium text-danger-fg" role="alert">{{ Array.isArray(error) ? error[0] : error }}</p>
        <p v-else-if="hint" class="text-[13px] text-neutral-600">{{ hint }}</p>
    </div>
</template>
