<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';
import { auth } from '../auth';

const router = useRouter();
const username = ref('');
const password = ref('');
const error = ref('');
const busy = ref(false);

async function submit() {
    error.value = '';
    busy.value = true;
    try {
        const result = await api('/api/login', { method: 'POST', body: JSON.stringify({ username: username.value, password: password.value }) });
        auth.user = result.user;
        router.push('/applications');
    } catch (exception) {
        error.value = exception.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="login-wrap">
        <div class="login-intro"><p class="eyebrow">PT. JKL</p><h1>Pengajuan kredit kendaraan</h1><p>Catat pengajuan, beri keputusan, dan proses dokumen dalam satu alur kerja.</p></div>
        <form class="paper login-form" @submit.prevent="submit"><h2>Masuk ke portal</h2><p v-if="error" class="error-box" role="alert">{{ error }}</p><label for="username">Username</label><input id="username" v-model="username" required autocomplete="username" autofocus><label for="password">Kata sandi</label><input id="password" v-model="password" type="password" required autocomplete="current-password"><button class="button primary full" :disabled="busy" type="submit">{{ busy ? 'Memproses…' : 'Masuk' }}</button></form>
    </div>
</template>
