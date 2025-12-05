<div class="flex-shrink-0 flex flex-col bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden backdrop-blur-sm max-h-full transition-opacity duration-300"
    :class="selectedSdb ? 'opacity-100' : 'opacity-60'">
    {{-- HEADER DENGAN WARNA DINAMIS --}}
    <div class="bg-gradient-to-r px-6 py-6 flex-shrink-0 transition-colors duration-500"
        :class="getHeaderGradientClass()">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-semibold text-white flex items-center">
                {{-- Tampilan saat belum ada SDB dipilih --}}
                <template x-if="!selectedSdb">
                    <div class="flex items-center">
                        <div class="bg-white/20 rounded-full p-2 mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                        </div>
                        <span class="text-xl">Detail SDB</span>
                    </div>
                </template>

                {{-- Tampilan saat SDB sudah dipilih --}}
                <template x-if="selectedSdb">
                    <div class="flex items-center">
                        <div class="bg-white/20 rounded-full p-2 mr-3">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9z">
                                </path>
                            </svg>
                        </div>
                        <span x-text="'SDB ' + selectedSdb?.nomor_sdb" class="text-xl"></span>

                        {{-- Status badge di header --}}
                        <div x-show="selectedSdb.status === 'terisi'"
                            class="ml-3 px-2.5 py-1 rounded-full text-xs font-semibold"
                            :class="getStatusHeaderBadgeClass(selectedSdb.status)">
                            <span x-text="getStatusText(selectedSdb.status)"></span>
                        </div>
                    </div>
                </template>
            </h3>
            <template x-if="selectedSdb">
                <button @click="clearSelection()"
                    class="text-white/80 hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10"
                    title="Tutup detail">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </template>
        </div>
    </div>

    {{-- KONTEN PANEL --}}
    <div class="flex-1 p-8 overflow-y-auto">
        {{--
        <div
            x-show="new Date(selectedSdb.tanggal_sewa) > new Date()"
            class="bg-yellow-50 text-yellow-700 p-2 text-xs rounded mb-2"
        >
            ⚠️ Kontrak ini bersifat Pre-booking (Aktif mulai
            <span x-text="formatDate(selectedSdb.tanggal_sewa)"></span>)
        </div>
        --}}

        {{-- Default State --}}
        <template x-if="!selectedSdb">
            <div class="flex flex-col items-center justify-center text-center text-gray-500 h-full">
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-full p-8 mb-6 shadow-inner">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <h4 class="text-lg font-semibold text-gray-700 mb-3">
                    Pilih Safe Deposit Box
                </h4>
                <p class="max-w-xs text-sm leading-relaxed text-gray-500">
                    Klik pada kotak SDB untuk melihat detail dan mengelola data
                    penyewa.
                </p>
            </div>
        </template>

        {{-- SDB Selected State --}}
        <template x-if="selectedSdb">
            <div>
                <div class="pb-6">
                    {{-- Form Fields & Info Display --}}
                    <div class="space-y-6">
                        {{-- Tampilan Untuk SDB yang Kosong --}}
                        <div x-show="selectedSdb.status === 'kosong' && !editMode"
                            class="text-center text-gray-600 py-8">
                            <div class="inline-block bg-gray-50 rounded-full p-5 mb-5 border border-gray-100">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-800">
                                SDB Ini Kosong
                            </h4>
                            <p class="text-sm mt-1 text-gray-500">
                                Belum ada data penyewa aktif.
                            </p>
                        </div>

                        {{-- Field Nama Nasabah --}}
                        <div x-show="selectedSdb.status !== 'kosong' || editMode">
                            <label for="nama_nasabah"
                                class="text-xs font-bold text-gray-400 uppercase tracking-wide flex items-center mb-2">
                                Nama Nasabah
                            </label>
                            <div x-show="!editMode"
                                class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-900 font-bold text-lg shadow-sm"
                                x-text="selectedSdb?.nama_nasabah || '—'"></div>
                            <input id="nama_nasabah" x-show="editMode" type="text" x-model="formData.nama_nasabah"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg"
                                placeholder="Nama lengkap" />
                        </div>

                        {{-- Field Tanggal Sewa --}}
                        <div x-show="selectedSdb.status !== 'kosong' || editMode">
                            <label
                                class="text-xs font-bold text-gray-400 uppercase tracking-wide flex items-center mb-2">
                                Tanggal Sewa
                            </label>
                            <div x-show="!editMode"
                                class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-900 font-medium text-base shadow-sm"
                                x-text="formatDate(selectedSdb?.tanggal_sewa)"></div>
                            <input type="date" x-show="editMode" x-model="formData.tanggal_sewa"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>

                        {{-- Field Jatuh Tempo --}}
                        <div x-show="selectedSdb.status !== 'kosong' || editMode">
                            <label
                                class="text-xs font-bold text-gray-400 uppercase tracking-wide flex items-center mb-2">
                                Jatuh Tempo
                            </label>

                            <div x-show="!editMode"
                                class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-medium text-base flex items-center justify-between shadow-sm"
                                :class="{
                                    'text-red-600 bg-red-50 border-red-100': selectedSdb
                                        ?.status === 'lewat_jatuh_tempo',
                                    'text-yellow-700 bg-yellow-50 border-yellow-100': selectedSdb
                                        ?.status === 'akan_jatuh_tempo',
                                    'text-gray-900': selectedSdb?.status === 'terisi'
                                }">
                                <span x-text="formatDate(selectedSdb?.tanggal_jatuh_tempo)"></span>
                                <span class="text-xs font-bold px-2 py-1 rounded-md bg-white/50"
                                    x-text="getExpiryText(selectedSdb.status, selectedSdb.days_until_expiry)">
                                </span>
                            </div>

                            <div x-show="editMode">
                                <input type="date" x-model="formData.tanggal_jatuh_tempo"
                                    class="w-full rounded-xl border-gray-300 shadow-sm bg-gray-100 cursor-not-allowed text-gray-500"
                                    readonly />
                                <p class="text-xs text-gray-400 mt-2 italic">
                                    *Jatuh tempo otomatis 1 tahun setelah sewa.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TOMBOL AKSI (FOOTER) --}}
                <div class="pt-6 border-t border-gray-100">
                    {{-- Mode Normal --}}
                    <div x-show="!editMode" class="space-y-3">
                        {{-- 1. BUTTON UNTUK SDB KOSONG --}}
                        <button @click="editMode = true; initFormData()" x-show="selectedSdb?.status === 'kosong'"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 px-4 rounded-xl transition-all shadow-lg hover:shadow-blue-500/30 flex items-center justify-center group">
                            <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tambah Penyewa Baru
                        </button>

                        {{-- 2. BUTTONS UTAMA (JIKA TERISI) --}}
                        <div x-show="selectedSdb?.status !== 'kosong'" class="grid grid-cols-2 gap-3">
                            {{-- Catat Kunjungan (Kiri) --}}
                            <button @click="openVisitModal()"
                                class="col-span-1 bg-white border-2 border-blue-50 text-blue-600 hover:border-blue-200 hover:bg-blue-50 font-semibold py-3 rounded-xl transition-colors flex flex-col items-center justify-center h-20 shadow-sm">
                                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                    </path>
                                </svg>
                                <span class="text-xs">Catat Kunjungan</span>
                            </button>

                            {{-- Lihat History (Kanan) --}}
                            <button @click="openHistoryModal()"
                                class="col-span-1 bg-white border-2 border-gray-100 text-gray-600 hover:border-gray-300 hover:bg-gray-50 font-semibold py-3 rounded-xl transition-colors flex flex-col items-center justify-center h-20 shadow-sm">
                                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-xs">Lihat Riwayat</span>
                            </button>
                        </div>

                        {{-- 3. BUTTONS OPERASIONAL (EDIT/PERPANJANG/AKHIRI) --}}

                        {{-- Tombol untuk STATUS NORMAL (Terisi) --}}
                        <button @click="editMode = true; initFormData()" x-show="selectedSdb?.status === 'terisi'"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            Edit Data Sewa
                        </button>

                        {{-- Tombol untuk STATUS WARNING (Akan/Lewat Jatuh Tempo) --}}
                        <button @click="extendRental()"
                            x-show="['akan_jatuh_tempo', 'lewat_jatuh_tempo'].includes(selectedSdb?.status)"
                            class="w-full text-white font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl"
                            :class="selectedSdb?.status === 'lewat_jatuh_tempo' ? 'bg-red-600 hover:bg-red-700' :
                                'bg-yellow-500 hover:bg-yellow-600'">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Perpanjang Sewa
                        </button>

                        {{-- Tombol Cetak Surat Peringatan (Hanya Muncul Jika Perlu) --}}
                        <template
                            x-if="selectedSdb && ['akan_jatuh_tempo', 'lewat_jatuh_tempo'].includes(selectedSdb.status)">
                            <div class="mt-4 mb-2">
                                <a :href="`/sdb/${selectedSdb.id}/print-letter`" target="_blank"
                                    class="flex items-center justify-center w-full px-4 py-3 text-sm font-bold text-white transition-all transform rounded-xl shadow-md hover:scale-[1.02] focus:ring-4 focus:ring-opacity-50"
                                    :class="selectedSdb.status === 'lewat_jatuh_tempo' ?
                                        'bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 focus:ring-red-300' :
                                        'bg-gradient-to-r from-yellow-500 to-yellow-400 hover:from-yellow-400 hover:to-yellow-300 text-yellow-900 focus:ring-yellow-200'">
                                    {{-- Icon Printer --}}
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                        </path>
                                    </svg>
                                    <span>Cetak Surat Peringatan</span>
                                </a>
                            </div>
                        </template>

                        {{-- Tombol AKHIRI SEWA (Selalu ada jika ada nasabah) --}}
                        <button @click="endRental()" x-show="selectedSdb?.nama_nasabah"
                            class="w-full bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center border border-transparent hover:border-red-100">
                            Akhiri Sewa
                        </button>

                        {{-- Tombol LIHAT RIWAYAT (Khusus Status Kosong - agar tetap bisa cek history) --}}
                        {{--
                        <button
                            @click="openHistoryModal()"
                            x-show="selectedSdb?.status === 'kosong'"
                            class="w-full bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 font-medium py-2 px-4 rounded-xl transition-colors text-sm flex items-center justify-center"
                        >
                            Lihat Arsip Riwayat SDB Ini
                        </button>
                        --}}

                        <button @click="openHistoryModal()" x-show="selectedSdb?.status === 'kosong'"
                            class="w-full flex items-center justify-center p-3 mb-3 rounded-xl bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors border border-gray-200 text-sm font-medium">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Lihat Arsip Riwayat SDB
                        </button>
                    </div>

                    {{-- Mode Edit --}}
                    <div x-show="editMode" class="flex items-center gap-3">
                        <button @click="saveData()"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors shadow-lg shadow-green-500/30">
                            Simpan
                        </button>
                        <button @click="cancelEdit()"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-4 rounded-xl transition-colors">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
