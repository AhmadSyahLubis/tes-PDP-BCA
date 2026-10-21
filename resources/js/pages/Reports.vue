<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api';

const report = ref(null);
const error = ref('');
onMounted(async () => {
    try { report.value = await api('/api/reports'); }
    catch (exception) { error.value = exception.message; }
});
</script>

<template>
    <div class="page-heading"><div><p class="eyebrow">Data master</p><h1>Laporan akses dan area</h1><p>Rekap dari relasi pengguna, grup menu, cabang, dan area.</p></div></div>
    <p v-if="error" class="error-box" role="alert">{{ error }}</p><p v-if="!report && !error" role="status">Memuat laporan…</p>
    <template v-if="report">
        <section class="report-section"><h2>Jumlah user aktif per area</h2><div class="paper table-scroll"><table><thead><tr><th>Area</th><th>Jumlah user aktif</th></tr></thead><tbody><tr v-for="area in report.areas" :key="area.area_id"><td>{{ area.area_name }}</td><td>{{ area.active_user_count }}</td></tr></tbody></table></div></section>
        <section class="report-section"><h2>User aktif</h2><div class="paper table-scroll"><table><thead><tr><th>Username</th><th>Nama</th><th>Grup</th><th>Cabang</th><th>Area</th></tr></thead><tbody><tr v-for="user in report.active_users" :key="user.id"><td class="mono">{{ user.username }}</td><td>{{ user.name }}</td><td>{{ user.group_name }}</td><td>{{ user.branch_name }}</td><td>{{ user.area_name }}</td></tr></tbody></table></div></section>
        <section class="report-section"><h2>Detail hak akses menu per user</h2><div class="paper table-scroll"><table><thead><tr><th>Username</th><th>Grup</th><th>Menu</th><th>URL</th></tr></thead><tbody><tr v-for="item in report.access" :key="`${item.user_id}-${item.menu_id}`"><td class="mono">{{ item.username }}</td><td>{{ item.group_name }}</td><td>{{ item.menu_name }}</td><td class="mono">{{ item.url }}</td></tr></tbody></table></div></section>
    </template>
</template>
