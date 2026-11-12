import { reactive, watch } from 'vue';

const KEY = 'sirga.sidebar';
type SidebarState = { collapsed: boolean; closed: Record<string, boolean> };

/** Status sidebar admin: mode rail (ikon saja) dan grup menu yang diciutkan — tersimpan di browser. */
export const sidebar = reactive<SidebarState>({ collapsed: false, closed: {} });

try {
    const raw = localStorage.getItem(KEY);
    if (raw) Object.assign(sidebar, JSON.parse(raw));
} catch { /* abaikan (mis. mode privat) */ }

watch(sidebar, () => {
    try { localStorage.setItem(KEY, JSON.stringify(sidebar)); } catch { /* abaikan */ }
}, { deep: true });
