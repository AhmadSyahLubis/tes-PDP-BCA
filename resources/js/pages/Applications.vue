<script setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, dateTime, statusLabels } from '../api';
import { auth } from '../auth';

const route = useRoute();
const router = useRouter();
const applications = ref({ data: [], current_page: 1, last_page: 1 });
const filter = ref(route.query.status || '');
const error = ref('');
const loading = ref(false);

async function load() {
    loading.value = true;
    error.value = '';
    filter.value = route.query.status || '';
    try {
        const params = new URLSearchParams();
        if (route.query.status) params.set('status', route.query.status);
        if (route.query.page) params.set('page', route.query.page);
        applications.value = await api(`/api/applications?${params}`);
    } catch (exception) {
        error.value = exception.message;
    } finally {
        loading.value = false;
    }
}

watch(() => route.fullPath, load, { immediate: true });
function applyFilter() { router.push({ path: '/applications', query: filter.value ? { status: filter.value } : {} }); }
function goPage(page) { router.push({ path: '/applications', query: { ...route.query, page } }); }
</script>

<template>
    <div class="page-heading"><div><p class="eyebrow">Operasional kredit</p><h1>Daftar pengajuan</h1><p>Pantau posisi setiap pengajuan dari submit sampai pencairan.</p></div><RouterLink v-if="auth.user?.group?.code === 'marketing'" class="button primary" to="/applications/create">Buat pengajuan</RouterLink></div>
    <form class="filter-bar" @submit.prevent="applyFilter"><label for="status">Status</label><select id="status" v-model="filter"><option value="">Semua status</option><option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option></select><button class="button secondary" type="submit">Terapkan</button></form>
    <p v-if="error" class="error-box" role="alert">{{ error }}</p><p v-if="loading" role="status">Memuat pengajuan…</p>
    <div v-else class="paper table-scroll"><table><thead><tr><th>Nomor</th><th>Konsumen</th><th>Kendaraan</th><th>Cabang</th><th>Dibuat</th><th>Status</th><th></th></tr></thead><tbody><tr v-for="item in applications.data" :key="item.id"><td class="mono">{{ item.application_no }}</td><td><strong>{{ item.customer.name }}</strong><small>{{ item.customer.nik }}</small></td><td>{{ item.vehicle_brand }} {{ item.vehicle_model }}</td><td>{{ item.branch.name }}</td><td>{{ dateTime(item.created_at) }}</td><td><span class="status" :class="`status-${item.status}`">{{ statusLabels[item.status] }}</span></td><td><RouterLink :to="`/applications/${item.id}`">Detail</RouterLink></td></tr><tr v-if="!applications.data.length"><td colspan="7" class="empty">Belum ada pengajuan untuk filter ini.</td></tr></tbody></table></div>
    <div v-if="applications.last_page > 1" class="pagination"><button class="button secondary" :disabled="applications.current_page === 1" @click="goPage(applications.current_page - 1)">Sebelumnya</button><span>Halaman {{ applications.current_page }} dari {{ applications.last_page }}</span><button class="button secondary" :disabled="applications.current_page === applications.last_page" @click="goPage(applications.current_page + 1)">Berikutnya</button></div>
</template>
