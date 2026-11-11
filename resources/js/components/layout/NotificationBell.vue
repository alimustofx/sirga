<script setup lang="ts">
import { relativeTime } from '@/lib/format';
import type { NotificationItem } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';
import { computed, ref } from 'vue';

const page = usePage();
const data = computed(() => page.props.notifications);
const open = ref(false);

function openItem(n: NotificationItem): void {
    open.value = false;
    router.post(`/notifikasi/${n.id}/baca`, {}, { preserveScroll: true, preserveState: true, onSuccess: () => n.url && router.visit(n.url) });
}

function readAll(): void {
    router.post('/notifikasi/baca-semua', {}, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <PopoverRoot v-model:open="open">
        <PopoverTrigger class="relative flex size-11 items-center justify-center rounded-lg text-neutral-800 hover:bg-neutral-100 sm:size-10" aria-label="Notifikasi">
            <Bell class="size-5" />
            <span v-if="data?.unread" class="absolute top-1.5 right-1.5 flex min-w-4 items-center justify-center rounded-full bg-[#b91c1c] px-1 text-[10px] font-bold text-white">{{ data.unread > 9 ? '9+' : data.unread }}</span>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent align="end" :side-offset="8" class="z-50 w-[min(22rem,calc(100vw-1.5rem))] rounded-xl border border-neutral-200 bg-white shadow-modal">
                <div class="flex items-center justify-between border-b border-neutral-200 px-4 py-3">
                    <p class="text-sm font-semibold text-primary-900">Notifikasi</p>
                    <button v-if="data?.unread" type="button" class="text-[13px] font-semibold text-primary-600 hover:underline" @click="readAll">Tandai semua dibaca</button>
                </div>
                <ul v-if="data?.items.length" class="max-h-96 divide-y divide-neutral-100 overflow-y-auto">
                    <li v-for="n in data.items" :key="n.id">
                        <button type="button" class="flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-neutral-50" @click="openItem(n)">
                            <span :class="['mt-1.5 size-2 shrink-0 rounded-full', n.read ? 'bg-transparent' : 'bg-primary-600']" />
                            <span class="min-w-0 flex-1">
                                <span :class="['block text-sm', n.read ? 'text-neutral-800' : 'font-semibold text-neutral-950']">{{ n.title }}</span>
                                <span v-if="n.body" class="mt-0.5 line-clamp-2 block text-[13px] text-neutral-600">{{ n.body }}</span>
                                <span class="mt-1 block text-xs text-neutral-400">{{ relativeTime(n.created_at) }}</span>
                            </span>
                        </button>
                    </li>
                </ul>
                <p v-else class="px-4 py-8 text-center text-sm text-neutral-600">Belum ada notifikasi.</p>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
