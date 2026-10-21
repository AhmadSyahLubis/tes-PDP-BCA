<script setup>
import { useRouter } from 'vue-router';
import { auth } from './auth';
import { api } from './api';

const router = useRouter();
async function logout() {
    await api('/api/logout', { method: 'POST' });
    auth.user = null;
    router.push('/login');
}
</script>

<template>
    <header class="site-header" v-if="!$route.path.includes('/print/')">
        <div class="header-inner">
            <RouterLink class="brand" to="/applications"><span class="brand-mark">JKL</span><span>Portal Kredit Kendaraan</span></RouterLink>
            <template v-if="auth.user">
                <nav aria-label="Navigasi utama">
                    <RouterLink to="/applications">Pengajuan</RouterLink>
                    <RouterLink v-if="auth.user.group?.code === 'marketing'" to="/applications/create">Pengajuan baru</RouterLink>
                    <RouterLink v-if="['approver', 'backoffice'].includes(auth.user.group?.code)" to="/reports">Laporan</RouterLink>
                </nav>
                <div class="account"><span>{{ auth.user.name }}<small>{{ auth.user.group?.name }}</small></span><button class="text-button" type="button" @click="logout">Keluar</button></div>
            </template>
        </div>
    </header>
    <main :class="$route.path.includes('/print/') ? 'print-sheet' : 'container'"><RouterView /></main>
</template>
