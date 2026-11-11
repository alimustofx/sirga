<script setup lang="ts">
import AppCard from '@/components/app/AppCard.vue';
import { formatNip } from '@/lib/format';
import type { PayrollDetail } from '@/types';

defineProps<{ employee: PayrollDetail['employee'] }>();
</script>

<template>
    <AppCard title="Data Pegawai">
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
            <dt class="text-neutral-600">Nama</dt><dd class="font-semibold text-neutral-950">{{ employee.name }}</dd>
            <dt class="text-neutral-600">NIP</dt><dd class="num text-neutral-950">{{ formatNip(employee.nip) }}</dd>
            <dt class="text-neutral-600">Status</dt><dd class="text-neutral-950">{{ employee.status }}</dd>
            <dt class="text-neutral-600">Golongan</dt><dd class="text-neutral-950">{{ employee.rank || '-' }}</dd>
            <dt v-if="employee.position" class="text-neutral-600">Jabatan</dt><dd v-if="employee.position" class="text-neutral-950">{{ employee.position }}</dd>
            <dt class="text-neutral-600">Rekening</dt><dd class="num text-neutral-950">{{ employee.account || '-' }}</dd>
        </dl>
    </AppCard>
</template>
