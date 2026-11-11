<script setup lang="ts">
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import ProfileForms from '@/components/layout/ProfileForms.vue';
import { Head } from '@inertiajs/vue3';
import type { ProfileData } from '@/types';

defineProps<{ profile: ProfileData }>();
</script>

<template>
    <Head title="Profil" />
    <AppPageHeader title="Profil & Akun" />
    <ProfileForms :profile="profile" />
</template>
