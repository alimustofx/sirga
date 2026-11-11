import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, reactive, watch } from 'vue';

/** Filter daftar yang tersinkron ke query string (debounce untuk pencarian). */
export function useFilters<T extends Record<string, string>>(path: string, initial: T, extra: () => Record<string, unknown> = () => ({})): T {
    const filters = reactive({ ...initial }) as T;
    let timer: number | undefined;

    watch(
        filters,
        () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => {
                const params: Record<string, unknown> = { ...extra() };
                Object.entries(filters).forEach(([k, v]) => v !== '' && (params[k] = v));
                router.get(path, params as Record<string, string>, { preserveState: true, preserveScroll: true, replace: true });
            }, 300);
        },
        { deep: true },
    );
    onBeforeUnmount(() => window.clearTimeout(timer));

    return filters;
}
