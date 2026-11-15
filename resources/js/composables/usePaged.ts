import type { Paginated } from '@/types';
import { computed, ref, watch } from 'vue';

/** Paginasi sisi-klien untuk daftar yang sudah dimuat seluruhnya (mis. data kecil-menengah). */
export function usePaged<T>(source: () => T[], initialPerPage = 10) {
    const page = ref(1);
    const perPage = ref(initialPerPage);
    const total = computed(() => source().length);
    const items = computed(() => source().slice((page.value - 1) * perPage.value, page.value * perPage.value));
    const meta = computed<Paginated<T>>(() => ({
        data: items.value,
        current_page: page.value,
        last_page: Math.max(1, Math.ceil(total.value / perPage.value)),
        from: total.value ? (page.value - 1) * perPage.value + 1 : 0,
        to: Math.min(page.value * perPage.value, total.value),
        total: total.value,
        prev_page_url: null,
        next_page_url: null,
    }));
    watch([total, perPage], () => { if (page.value > meta.value.last_page) page.value = meta.value.last_page; });

    return { page, perPage, items, meta, reset: () => { page.value = 1; } };
}
