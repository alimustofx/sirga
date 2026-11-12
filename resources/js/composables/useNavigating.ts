import { reactive } from 'vue';

/** Status pemuatan global (diisi PageShell). page = pindah halaman, table = filter/halaman pada halaman yang sama. */
export const navigating = reactive({ page: false, table: false });
