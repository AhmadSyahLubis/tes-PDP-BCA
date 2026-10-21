<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api, rupiah } from '../api';

const route = useRoute();
const application = ref(null);
const error = ref('');
function printPage() { window.print(); }
watch(() => route.fullPath, async () => {
    if (!['contract', 'po'].includes(route.params.type)) { error.value = 'Jenis dokumen tidak dikenal.'; return; }
    try {
        const result = await api(`/api/applications/${route.params.id}`);
        if (!['issued', 'signed', 'disbursed'].includes(result.status)) { error.value = 'Dokumen belum diterbitkan.'; return; }
        application.value = result;
    } catch (exception) { error.value = exception.message; }
}, { immediate: true });
</script>

<template>
    <p v-if="error" class="error-box" role="alert">{{ error }}</p>
    <template v-if="application"><p class="eyebrow">PT. JKL</p><h1>{{ route.params.type === 'contract' ? 'Kontrak Pembiayaan Kendaraan' : 'Purchase Order Kendaraan' }}</h1><p>Nomor pengajuan: <strong>{{ application.application_no }}</strong></p><hr><dl class="detail-list"><div><dt>Konsumen</dt><dd>{{ application.customer.name }}</dd></div><div><dt>NIK</dt><dd>{{ application.customer.nik }}</dd></div><div><dt>Dealer</dt><dd>{{ application.dealer_name }}</dd></div><div><dt>Kendaraan</dt><dd>{{ application.vehicle_brand }} {{ application.vehicle_model }} {{ application.vehicle_type }}, {{ application.vehicle_color }}</dd></div><div><dt>Harga kendaraan</dt><dd>{{ rupiah(application.vehicle_price) }}</dd></div><div><dt>Uang muka</dt><dd>{{ rupiah(application.down_payment) }}</dd></div><div><dt>Nilai pembiayaan</dt><dd>{{ rupiah(application.vehicle_price - application.down_payment) }}</dd></div><template v-if="route.params.type === 'contract'"><div><dt>Jangka waktu</dt><dd>{{ application.tenor_months }} bulan</dd></div><div><dt>Angsuran bulanan</dt><dd>{{ rupiah(application.monthly_installment) }}</dd></div></template></dl><p class="print-note">Dokumen ini merupakan ringkasan data pengajuan untuk proses uji aplikasi. Ketentuan hukum kontrak resmi perlu ditambahkan perusahaan sebelum digunakan secara operasional.</p><div class="signature-row"><div>Konsumen<br><br><br>____________________</div><div>{{ route.params.type === 'po' ? 'Dealer' : 'PT. JKL' }}<br><br><br>____________________</div></div><button class="button primary print-button" @click="printPage">Cetak</button></template>
</template>
