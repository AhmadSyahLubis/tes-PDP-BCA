<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';

const router = useRouter();
const form = reactive({ nik: '', name: '', birth_date: '', marital_status: '', spouse_name: '', phone: '', address: '', dealer_name: '', vehicle_brand: '', vehicle_model: '', vehicle_type: '', vehicle_color: '', vehicle_price: '', down_payment: '', tenor_months: '', monthly_installment: '' });
const files = reactive({ ktp: null, kartu_keluarga: null, bukti_tanda_jadi: null });
const error = ref('');
const busy = ref(false);

async function submit() {
    busy.value = true;
    error.value = '';
    const body = new FormData();
    for (const [key, value] of Object.entries(form)) body.append(key, value ?? '');
    for (const [key, value] of Object.entries(files)) if (value) body.append(key, value);
    try {
        const result = await api('/api/applications', { method: 'POST', body });
        router.push(`/applications/${result.id}`);
    } catch (exception) {
        error.value = exception.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="page-heading"><div><p class="eyebrow">Marketing</p><h1>Pengajuan baru</h1><p>Isi data sesuai dokumen konsumen. Semua dokumen awal wajib diunggah.</p></div><RouterLink class="back-link" to="/applications">Kembali ke daftar</RouterLink></div>
    <p v-if="error" class="error-box" role="alert">{{ error }}</p>
    <form class="form-stack" @submit.prevent="submit">
        <section class="paper form-section"><h2>01. Data konsumen</h2><div class="field-grid">
            <div><label for="nik">NIK</label><input id="nik" v-model="form.nik" inputmode="numeric" minlength="16" maxlength="16" required></div>
            <div><label for="name">Nama lengkap</label><input id="name" v-model="form.name" required></div>
            <div><label for="birth_date">Tanggal lahir</label><input id="birth_date" v-model="form.birth_date" type="date" required></div>
            <div><label for="phone">Nomor telepon</label><input id="phone" v-model="form.phone" required></div>
            <div><label for="marital_status">Status perkawinan</label><select id="marital_status" v-model="form.marital_status" required><option value="">Pilih status</option><option value="belum_menikah">Belum menikah</option><option value="menikah">Menikah</option><option value="cerai">Cerai</option></select></div>
            <div><label for="spouse_name">Nama pasangan <span class="hint">jika menikah</span></label><input id="spouse_name" v-model="form.spouse_name" :required="form.marital_status === 'menikah'"></div>
            <div class="span-2"><label for="address">Alamat</label><textarea id="address" v-model="form.address" rows="3" required></textarea></div>
        </div></section>
        <section class="paper form-section"><h2>02. Kendaraan dan pinjaman</h2><div class="field-grid">
            <div><label for="dealer_name">Dealer</label><input id="dealer_name" v-model="form.dealer_name" required></div>
            <div><label for="vehicle_brand">Merek kendaraan</label><input id="vehicle_brand" v-model="form.vehicle_brand" required></div>
            <div><label for="vehicle_model">Model kendaraan</label><input id="vehicle_model" v-model="form.vehicle_model" required></div>
            <div><label for="vehicle_type">Tipe kendaraan</label><input id="vehicle_type" v-model="form.vehicle_type" required></div>
            <div><label for="vehicle_color">Warna</label><input id="vehicle_color" v-model="form.vehicle_color" required></div>
            <div><label for="vehicle_price">Harga kendaraan (Rp)</label><input id="vehicle_price" v-model="form.vehicle_price" type="number" min="1" step="0.01" required></div>
            <div><label for="down_payment">Uang muka (Rp)</label><input id="down_payment" v-model="form.down_payment" type="number" min="0" step="0.01" required></div>
            <div><label for="tenor_months">Lama kredit (bulan)</label><input id="tenor_months" v-model="form.tenor_months" type="number" min="1" max="120" required></div>
            <div><label for="monthly_installment">Angsuran per bulan (Rp)</label><input id="monthly_installment" v-model="form.monthly_installment" type="number" min="1" step="0.01" required></div>
        </div></section>
        <section class="paper form-section"><h2>03. Dokumen awal</h2><p class="hint">PDF, JPG, atau PNG. Maksimal 5 MB per berkas. Berkas tersimpan privat.</p><div class="field-grid">
            <div><label for="ktp">KTP</label><input id="ktp" type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="files.ktp = $event.target.files[0]"></div>
            <div><label for="kartu_keluarga">Kartu keluarga</label><input id="kartu_keluarga" type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="files.kartu_keluarga = $event.target.files[0]"></div>
            <div><label for="bukti_tanda_jadi">Bukti bayar tanda jadi</label><input id="bukti_tanda_jadi" type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="files.bukti_tanda_jadi = $event.target.files[0]"></div>
        </div></section>
        <div class="form-actions"><RouterLink class="button secondary" to="/applications">Batal</RouterLink><button class="button primary" type="submit" :disabled="busy">{{ busy ? 'Mengirim…' : 'Kirim pengajuan' }}</button></div>
    </form>
</template>
