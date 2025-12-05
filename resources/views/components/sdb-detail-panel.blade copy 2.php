<div class="flex-shrink-0 flex flex-col bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden backdrop-blur-sm transition-all duration-300 min-h-[500px]"
    x-init="$watch('selectedSdb', unit => {
        if (unit) {
            // Salin Nama
            formData.nama_nasabah = unit.nama_nasabah;
    
            // Format Tanggal (Ambil 10 karakter pertama: YYYY-MM-DD) agar muncul di input date
            formData.tanggal_sewa = unit.tanggal_sewa ? unit.tanggal_sewa.substring(0, 10) : '';
            formData.tanggal_jatuh_tempo = unit.tanggal_jatuh_tempo ? unit.tanggal_jatuh_tempo.substring(0, 10) : '';
        }
    })">
    {{-- HEADER BARU (Sesuai Request) --}}
    <div class="bg-gradient-to-r px-6 py-6 flex-shrink-0 transition-all duration-500 relative overflow-hidden"
        :class="getHeaderGradientClass()">

        {{-- Background Pattern (Aesthetic) --}}
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-black opacity-5 rounded-full blur-lg"></div>

        <div class="flex items-center justify-between relative z-10">
            <h3 class="text-xl font-bold text-white flex items-center tracking-wide">
                {{-- State: Belum Pilih (Default) --}}
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

                {{-- State: Sudah Pilih --}}
                <template x-if="selectedSdb">
                    <div class="flex items-center">
                        <span class="opacity-80 font-medium mr-2 text-sm uppercase">SDB No.</span>
                        <span class="text-3xl font-extrabold" x-text="selectedSdb.nomor_sdb"></span>
                    </div>
                </template>
            </h3>

            {{-- Status Badge (Pill) --}}
            <template x-if="selectedSdb">
                <div class="flex items-center gap-2">
                    <div class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm backdrop-blur-md border border-white/20"
                        :class="{
                            'bg-white/20 text-white': selectedSdb.status === 'kosong',
                            'bg-white text-blue-600': selectedSdb.status === 'terisi',
                            'bg-white text-yellow-600': selectedSdb.status === 'akan_jatuh_tempo',
                            'bg-white text-red-600': selectedSdb.status === 'lewat_jatuh_tempo'
                        }">
                        <span x-text="getStatusText(selectedSdb.status)"></span>
                    </div>

                    {{-- Tombol Close X Kecil --}}
                    <button @click="clearSelection()" class="text-white/60 hover:text-white transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- CONTENT AREA (Body Panel) --}}
    <div class="flex-1 overflow-y-auto p-6 flex flex-col h-full bg-gray-50/50 flex-grow">

        {{-- State Belum Pilih SDB (Center Alignment Fixed) --}}
        <template x-if="!selectedSdb">
            <div class="flex flex-col items-center justify-center h-full text-center space-y-5 opacity-60">
                <div class="bg-white p-6 rounded-full shadow-sm border border-gray-100">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-gray-600 font-bold text-lg">Belum Ada Unit Dipilih</h4>
                    <p class="text-gray-400 text-sm mt-1">Silakan klik salah satu kotak pada denah<br>untuk melihat
                        detail informasi.</p>
                </div>
            </div>
        </template>

        {{-- State: Unit Terpilih --}}
        <template x-if="selectedSdb">
            <div class="space-y-6 animate-fade-in-up">

                {{-- Tampilan Khusus Saat KOSONG (Menggantikan Form) --}}
                <div x-show="selectedSdb.status === 'kosong' && !editMode"
                    class="bg-white rounded-2xl p-8 text-center space-y-3">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-50 mb-2">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h4 class="text-gray-800 font-bold text-base">SDB Ini Kosong</h4>
                    <p class="text-gray-500 text-sm">Belum ada data penyewa aktif pada unit ini.</p>
                </div>

                {{-- SECTION 1: INFORMASI UTAMA --}}
                <div x-show="selectedSdb.status !== 'kosong' || editMode"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6 relative overflow-hidden">

                    {{-- Dekorasi visual halus (Optional) --}}
                    <div
                        class="absolute top-0 right-0 w-20 h-20 bg-blue-50 rounded-bl-full -mr-10 -mt-10 opacity-50 pointer-events-none">
                    </div>

                    {{-- 1. GROUP NAMA PENYEWA --}}
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <label
                                class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Nama Penyewa
                            </label>
                        </div>

                        <div class="relative group">
                            {{-- Input Nama --}}
                            <input type="text" x-model="formData.nama_nasabah" :disabled="!editMode"
                                class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 font-bold text-lg py-3 pl-4 pr-12 transition-all duration-300 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 disabled:cursor-default"
                                :class="!editMode ? 'border-transparent bg-gray-50/50' : 'bg-white shadow-sm'"
                                placeholder="Masukkan Nama Nasabah">

                            {{-- Tombol Edit (Selalu Muncul, Posisi Fixed Absolute) --}}
                            <div class="absolute right-2 top-1/2 -translate-y-1/2"
                                x-show="!editMode && selectedSdb.status !== 'kosong'">
                                <button @click="initFormData()"
                                    class="p-2 text-gray-400 bg-white border border-gray-200 rounded-lg hover:text-blue-600 hover:border-blue-300 hover:shadow-md transform hover:scale-105 transition-all duration-200"
                                    title="Edit Data Penyewa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 2. GROUP TANGGAL (GRID) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2 border-t border-gray-100 border-dashed">

                        {{-- A. Mulai Sewa --}}
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">
                                Mulai Sewa
                            </label>
                            <div class="relative">
                                <input type="date" x-model="formData.tanggal_sewa" :disabled="!editMode"
                                    class="block w-full text-sm font-semibold rounded-lg border-gray-200 bg-white py-2.5 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:bg-gray-50 disabled:text-gray-600 disabled:border-transparent">
                            </div>
                        </div>

                        {{-- B. Jatuh Tempo --}}
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    Jatuh Tempo
                                </label>

                                {{-- Expiry Badge (Modern Pill Style) --}}
                                {{-- <template x-if="!editMode && selectedSdb.days_until_expiry !== undefined">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border shadow-sm"
                                        :class="{
                                            'bg-red-50 text-red-600 border-red-100': selectedSdb
                                                .status === 'lewat_jatuh_tempo',
                                            'bg-yellow-50 text-yellow-700 border-yellow-100': selectedSdb
                                                .status === 'akan_jatuh_tempo',
                                            'bg-emerald-50 text-emerald-600 border-emerald-100': selectedSdb
                                                .status === 'terisi'
                                        }">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                                            :class="{
                                                'bg-red-500': selectedSdb.status === 'lewat_jatuh_tempo',
                                                'bg-yellow-500': selectedSdb.status === 'akan_jatuh_tempo',
                                                'bg-emerald-500': selectedSdb.status === 'terisi'
                                            }"></span>
                                        <span
                                            x-text="selectedSdb.days_until_expiry < 0 
                                            ? 'Lewat ' + Math.abs(selectedSdb.days_until_expiry) + ' Hari' 
                                            : 'Sisa ' + selectedSdb.days_until_expiry + ' Hari'">
                                        </span>
                                    </span>
                                </template> --}}
                            </div>

                            <div class="relative">
                                <input type="date" x-model="formData.tanggal_jatuh_tempo" disabled
                                    class="block w-full text-sm font-bold rounded-lg border-gray-200 py-2.5 cursor-not-allowed transition-colors"
                                    :class="{
                                        'text-red-600 bg-red-50 border-red-100': ['akan_jatuh_tempo',
                                            'lewat_jatuh_tempo'
                                        ].includes(selectedSdb.status),
                                        'text-gray-600 bg-gray-100': selectedSdb.status === 'terisi'
                                    }">

                                {{-- Auto Calculator Indicator --}}
                                <div x-show="editMode"
                                    class="absolute right-0 -bottom-5 flex items-center text-blue-500 transition-opacity"
                                    x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    <span class="text-[10px] font-medium italic">Otomatis 1 Tahun</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: ACTION BUTTONS AREA --}}

                {{-- A. MODE EDIT (Save/Cancel) --}}
                <div x-show="editMode" class="flex items-center gap-3" x-transition>
                    <button @click="cancelEdit()"
                        class="flex-1 px-4 py-3 bg-white border border-gray-200 text-gray-600 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button @click="saveData()"
                        class="flex-1 px-4 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:bg-blue-700 transform hover:-translate-y-0.5 transition-all">
                        Simpan Perubahan
                    </button>
                </div>

                {{-- B. MODE VIEW (Normal Actions) --}}
                <div x-show="!editMode" class="space-y-4" x-transition>

                    {{-- KONDISI 1: UNIT KOSONG --}}
                    <template x-if="selectedSdb.status === 'kosong'">
                        <div class="space-y-3">
                            {{-- FIX: Mengganti enableEdit() menjadi initFormData() --}}
                            <button @click="initFormData()"
                                class="w-full flex items-center justify-center gap-3 px-6 py-4 bg-blue-600 text-white font-bold rounded-2xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 hover:scale-[1.02] transition-all group">
                                <div class="bg-white/20 p-1.5 rounded-lg group-hover:bg-white/30 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <span>Mulai Sewa Baru</span>
                            </button>

                            <button @click="openHistoryModal(selectedSdb.id)"
                                class="w-full py-3 text-sm text-gray-500 font-medium hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Lihat Riwayat Sebelumnya
                            </button>
                        </div>
                    </template>

                    {{-- KONDISI 2: UNIT TERISI --}}
                    <template x-if="selectedSdb.status !== 'kosong'">
                        <div class="flex flex-col gap-4">

                            {{-- 2.1 TOMBOL GRID UTAMA (Kunjungan & History) --}}
                            <div class="grid grid-cols-2 gap-3">
                                <button @click="isVisitModalOpen = true"
                                    class="col-span-1 flex flex-col items-center justify-center p-3 bg-white border border-gray-200 rounded-xl hover:border-blue-400 hover:shadow-md transition-all group h-24">
                                    <div
                                        class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-2 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600">Catat Kunjungan</span>
                                </button>

                                <button @click="openHistoryModal(selectedSdb.id)"
                                    class="col-span-1 flex flex-col items-center justify-center p-3 bg-white border border-gray-200 rounded-xl hover:border-gray-400 hover:shadow-md transition-all group h-24">
                                    <div
                                        class="w-10 h-10 bg-gray-50 text-gray-600 rounded-full flex items-center justify-center mb-2 group-hover:bg-gray-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600">Lihat History</span>
                                </button>
                            </div>

                            {{-- 2.2 URGENT ACTIONS (Hanya jika Warning/Expired) --}}
                            <template x-if="['akan_jatuh_tempo', 'lewat_jatuh_tempo'].includes(selectedSdb.status)">
                                <div class="bg-yellow-50 border border-yellow-100 rounded-2xl p-4 space-y-3">
                                    <h4
                                        class="text-xs font-bold text-yellow-800 uppercase tracking-wider flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                            </path>
                                        </svg>
                                        Tindakan Diperlukan
                                    </h4>

                                    {{-- Tombol Perpanjang --}}
                                    {{-- FIX: Mengganti openExtendModal() menjadi extendRental() --}}
                                    <button @click="extendRental()"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-yellow-400 to-orange-400 text-white font-bold rounded-xl shadow-md hover:from-yellow-500 hover:to-orange-500 hover:shadow-lg transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        Perpanjang Sewa
                                    </button>

                                    {{-- Tombol Cetak Surat --}}
                                    <a :href="`/sdb/${selectedSdb.id}/print-letter`" target="_blank"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-yellow-300 text-yellow-700 font-bold rounded-xl hover:bg-yellow-50 transition-all text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                            </path>
                                        </svg>
                                        Cetak Surat Peringatan
                                    </a>
                                </div>
                            </template>

                            {{-- DANGER ZONE --}}
                            <div class="pt-4 mt-2 border-t border-gray-200">
                                <button @click="endRental()"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 text-red-500 font-medium rounded-xl hover:bg-red-50 hover:text-red-600 transition-colors text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    Akhiri / Putus Sewa
                                </button>
                            </div>

                        </div>
                    </template>
                </div>

            </div>
        </template>
    </div>
</div>
