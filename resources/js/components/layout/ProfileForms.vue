<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppInput from '@/components/app/AppInput.vue';
import { formatDate, formatNip } from '@/lib/format';
import type { ProfileData } from '@/types';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ profile: ProfileData }>();

const email = useForm({ email: props.profile.email ?? '' });
const pwd = useForm({ current_password: '', password: '', password_confirmation: '' });
const savePassword = () => pwd.put('/profil/password', { preserveScroll: true, onSuccess: () => pwd.reset() });
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <AppCard title="Identitas">
            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                <dt class="text-neutral-600">Nama</dt><dd class="font-semibold">{{ profile.name }}</dd>
                <dt class="text-neutral-600">NIP</dt><dd class="num">{{ formatNip(profile.nip) }}</dd>
                <dt class="text-neutral-600">Role</dt><dd>{{ profile.role }}</dd>
                <template v-if="profile.employee">
                    <dt class="text-neutral-600">Status</dt><dd>{{ profile.employee.status }}</dd>
                    <dt class="text-neutral-600">Golongan</dt><dd>{{ profile.employee.rank || '-' }}</dd>
                    <dt class="text-neutral-600">Unit</dt><dd>{{ profile.employee.unit || '-' }}</dd>
                    <dt class="text-neutral-600">Rekening</dt><dd class="num">{{ profile.employee.account || '-' }}</dd>
                </template>
                <dt class="text-neutral-600">Login terakhir</dt><dd>{{ formatDate(profile.last_login_at, true) }}</dd>
            </dl>
            <p class="mt-3 text-[13px] text-neutral-600">Data identitas dikelola Super Admin. Hubungi sekolah bila ada yang perlu diperbaiki.</p>
        </AppCard>

        <div class="space-y-4">
            <AppCard title="Email">
                <form class="space-y-3" @submit.prevent="email.put('/profil/email', { preserveScroll: true })">
                    <AppInput v-model="email.email" label="Alamat email (opsional)" type="email" autocomplete="email" :error="email.errors.email" />
                    <AppButton type="submit" :loading="email.processing">Simpan email</AppButton>
                </form>
            </AppCard>
            <AppCard title="Ganti Password">
                <form class="space-y-3" @submit.prevent="savePassword">
                    <AppInput v-model="pwd.current_password" label="Password saat ini" type="password" autocomplete="current-password" :error="pwd.errors.current_password" />
                    <AppInput v-model="pwd.password" label="Password baru" type="password" autocomplete="new-password" hint="Minimal 8 karakter, mengandung huruf dan angka." :error="pwd.errors.password" />
                    <AppInput v-model="pwd.password_confirmation" label="Ulangi password baru" type="password" autocomplete="new-password" />
                    <AppButton type="submit" :loading="pwd.processing">Simpan password</AppButton>
                </form>
            </AppCard>
        </div>
    </div>
</template>
