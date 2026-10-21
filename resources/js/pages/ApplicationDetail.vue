<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api, dateTime, rupiah, statusLabels } from '../api';
import { auth } from '../auth';

const route = useRoute();
const application = ref(null);
const error = ref('');
const notice = ref('');
const busy = ref(false);
const decisionNote = ref('');
const signedDocument = ref(null);
const referenceNo = ref('');
const disbursedOn = ref(new Date().toISOString().slice(0, 10));
const role = computed(() => auth.user?.group?.code);
const eventLabels = { submitted: 'Pengajuan dikirim', approved: 'Disetujui', rejected: 'Ditolak', issued: 'Dokumen terbit', signed: 'Dokumen TTD diterima', disbursed: 'Dana dicairkan' };
const documentLabels = { ktp: 'KTP', kartu_keluarga: 'Kartu keluarga', bukti_tanda_jadi: 'Bukti bayar tanda jadi', signed_contract: 'Kontrak bertanda tangan' };

async function load() {
    error.value = '';
    try { application.value = await api(`/api/applications/${route.params.id}`); }
    catch (exception) { error.value = exception.message; }
}
watch(() => route.params.id, load, { immediate: true });

async function action(path, body = null) {
    busy.value = true;
    error.value = '';
    notice.value = '';
    try {
        const result = await api(`/api/applications/${route.params.id}/${path}`, {
            method: 'POST', body: body instanceof FormData ? body : (body ? JSON.stringify(body) : undefined),
        });
        notice.value = result.message;
        await load();
    } catch (exception) { error.value = exception.message; }
    finally { busy.value = false; }
}
function decide(decision) { action('decision', { decision, decision_note: decisionNote.value }); }
function uploadSigned() {
    const body = new FormData();
    body.append('signed_document', signedDocument.value);
    action('signed', body);
}
function disburse() { action('disburse', { reference_no: referenceNo.value, disbursed_on: disbursedOn.value }); }
</script>

<template>
    <div v-if="error" class="error-box" role="alert">{{ error }}</div>
    <div v-if="notice" class="notice" role="status">{{ notice }}</div>
    <p v-if="!application && !error" role="status">Memuat detail pengajuan…</p>
    <template v-if="application">
        <div class="page-heading"><div><p class="eyebrow">Detail pengajuan</p><h1 class="mono">{{ application.application_no }}</h1><p><span class="status" :class="`status-${application.status}`">{{ statusLabels[application.status] }}</span> <span class="muted">Diajukan {{ dateTime(application.created_at) }}</span></p></div><RouterLink class="back-link" to="/applications">Kembali ke daftar</RouterLink></div>
        <div class="detail-grid"><div class="main-column">
            <section class="paper detail-section"><h2>Data konsumen</h2><dl class="detail-list"><div><dt>Nama</dt><dd>{{ application.customer.name }}</dd></div><div><dt>NIK</dt><dd class="mono">{{ application.customer.nik }}</dd></div><div><dt>Tanggal lahir</dt><dd>{{ application.customer.birth_date }}</dd></div><div><dt>Status perkawinan</dt><dd>{{ application.customer.marital_status.replace('_', ' ') }}</dd></div><div v-if="application.customer.spouse_name"><dt>Pasangan</dt><dd>{{ application.customer.spouse_name }}</dd></div><div><dt>Telepon</dt><dd>{{ application.customer.phone }}</dd></div><div><dt>Alamat</dt><dd>{{ application.customer.address }}</dd></div></dl></section>
            <section class="paper detail-section"><h2>Kendaraan dan pinjaman</h2><dl class="detail-list"><div><dt>Dealer</dt><dd>{{ application.dealer_name }}</dd></div><div><dt>Kendaraan</dt><dd>{{ application.vehicle_brand }} {{ application.vehicle_model }} / {{ application.vehicle_type }}</dd></div><div><dt>Warna</dt><dd>{{ application.vehicle_color }}</dd></div><div><dt>Harga kendaraan</dt><dd>{{ rupiah(application.vehicle_price) }}</dd></div><div><dt>Uang muka</dt><dd>{{ rupiah(application.down_payment) }}</dd></div><div><dt>Pokok pencairan</dt><dd>{{ rupiah(application.vehicle_price - application.down_payment) }}</dd></div><div><dt>Tenor</dt><dd>{{ application.tenor_months }} bulan</dd></div><div><dt>Angsuran</dt><dd>{{ rupiah(application.monthly_installment) }} / bulan</dd></div><div><dt>Cabang</dt><dd>{{ application.branch.name }} ({{ application.branch.area.name }})</dd></div></dl></section>
            <section class="paper detail-section"><h2>Dokumen</h2><ul class="document-list"><li v-for="document in application.documents" :key="document.id"><span>{{ documentLabels[document.type] || document.type }}<small>{{ document.original_name }}</small></span><a :href="`/api/documents/${document.id}`">Unduh</a></li></ul><div v-if="['issued','signed','disbursed'].includes(application.status)" class="document-actions"><RouterLink class="button secondary" :to="`/applications/${application.id}/print/contract`" target="_blank">Cetak kontrak</RouterLink><RouterLink class="button secondary" :to="`/applications/${application.id}/print/po`" target="_blank">Cetak PO</RouterLink></div></section>
            <section class="paper detail-section"><h2>Riwayat proses</h2><ol class="timeline"><li v-for="event in [...application.events].reverse()" :key="event.id"><strong>{{ eventLabels[event.action] || event.action }}</strong><small>{{ dateTime(event.created_at) }} oleh {{ event.actor?.name }}</small><p v-if="event.note">{{ event.note }}</p></li></ol></section>
        </div><aside class="side-column">
            <section class="paper action-panel"><h2>Tindakan berikutnya</h2>
                <form v-if="application.status === 'submitted' && role === 'approver'" @submit.prevent><p>Periksa data dan dokumen sebelum memberi keputusan.</p><label for="decision_note">Catatan keputusan</label><textarea id="decision_note" v-model="decisionNote" rows="3" placeholder="Wajib jika ditolak"></textarea><div class="action-row"><button class="button primary" :disabled="busy" type="button" @click="decide('approved')">Setujui</button><button class="button danger" :disabled="busy || !decisionNote.trim()" type="button" @click="decide('rejected')">Tolak</button></div></form>
                <div v-else-if="application.status === 'approved' && role === 'backoffice'"><p>Terbitkan kontrak dan PO setelah keputusan disetujui.</p><button class="button primary full" :disabled="busy" @click="action('issue')">Terbitkan dokumen</button></div>
                <form v-else-if="application.status === 'issued' && role === 'marketing'" @submit.prevent="uploadSigned"><p>Unggah kontrak yang sudah ditandatangani konsumen dan dealer.</p><label for="signed_document">Dokumen bertanda tangan</label><input id="signed_document" type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="signedDocument = $event.target.files[0]"><button class="button primary full" :disabled="busy || !signedDocument" type="submit">Unggah dokumen</button></form>
                <form v-else-if="application.status === 'signed' && role === 'backoffice'" @submit.prevent="disburse"><p>Catat pencairan setelah dokumen bertanda tangan lengkap.</p><label for="reference_no">Nomor referensi pencairan</label><input id="reference_no" v-model="referenceNo" required><label for="disbursed_on">Tanggal pencairan</label><input id="disbursed_on" v-model="disbursedOn" type="date" required><button class="button primary full" :disabled="busy" type="submit">Catat pencairan</button></form>
                <p v-else-if="application.status === 'rejected'">Pengajuan ditolak. {{ application.decision_note }}</p>
                <p v-else-if="application.status === 'disbursed'">Pencairan tercatat dengan referensi <strong>{{ application.disbursement?.reference_no }}</strong>.</p>
                <p v-else>Menunggu tindakan dari petugas pada tahap berikutnya.</p>
            </section>
            <section class="paper action-panel"><h2>Penanggung jawab</h2><dl class="stack-list"><dt>Marketing</dt><dd>{{ application.marketing.name }}</dd><dt>Atasan marketing</dt><dd>{{ application.reviewer?.name || 'Belum diputuskan' }}</dd></dl></section>
        </aside></div>
    </template>
</template>
