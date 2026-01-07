<div class="flex-shrink-0 flex flex-col bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden backdrop-blur-sm transition-all duration-300"
    :class="selectedSdb ? 'opacity-100' : 'opacity-60'" style="min-height: 500px; will-change: auto;"
    x-data="{
        // CRITICAL: Sync formData whenever selectedSdb changes
    }" x-init="// Watch for unit changes and sync formData immediately
    $watch('selectedSdb', (unit) => {
        if (unit) {
            // Force update formData to prevent stale data
            formData.nama_nasabah = unit.nama_nasabah || '';
            formData.tanggal_sewa = unit.tanggal_sewa ? unit.tanggal_sewa.substring(0, 10) : '';
            formData.tanggal_jatuh_tempo = unit.tanggal_jatuh_tempo ? unit.tanggal_jatuh_tempo.substring(0, 10) : '';
        }
    });">

    {{-- Enhanced Loading Overlay (Optional - untuk future use) --}}
    <div x-show="isLoading" x-cloak
        class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-3xl z-50 flex items-center justify-center"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100">
        <div class="flex flex-col items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span class="text-sm font-medium text-gray-600">Memproses...</span>
        </div>
    </div>

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
                        <div x-show="selectedSdb?.status === 'terisi'"
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
            {{-- CONTAINER UTAMA DENGAN PROPER SPACING --}}
            <div class="space-y-4">

                {{-- SECTION 1: INFO CARD --}}
                <div x-show="selectedSdb?.status !== 'kosong' || editMode"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    class="bg-gradient-to-br from-white to-gray-50/50 rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">

                    {{-- Header Strip --}}
                    <div class="h-1.5 w-full"
                        :class="{
                            'bg-gradient-to-r from-blue-400 to-blue-600': selectedSdb?.status === 'terisi',
                            'bg-gradient-to-r from-yellow-400 to-orange-500': selectedSdb
                                ?.status === 'akan_jatuh_tempo',
                            'bg-gradient-to-r from-red-500 to-red-700': selectedSdb?.status === 'lewat_jatuh_tempo'
                        }">
                    </div>

                    {{-- Content Area --}}
                    <div class="p-6 space-y-5">

                        {{-- 1. NAMA NASABAH --}}
                        <div class="group relative">
                            <div class="flex items-center justify-between mb-2">
                                <label
                                    class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                    Penyewa
                                </label>

                                <button x-show="!editMode && selectedSdb?.status !== 'kosong'" @click="initFormData()"
                                    class="opacity-0 group-hover:opacity-100 transition-all duration-200 p-1.5 rounded-lg hover:bg-blue-50 text-gray-400 hover:text-blue-600"
                                    title="Edit Data">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>
                            </div>

                            <div x-show="!editMode"
                                class="block w-full text-lg font-bold text-gray-900 px-2 py-1.5 rounded-xl hover:bg-gray-50 transition-colors min-h-[44px] flex items-center"
                                x-text="selectedSdb?.nama_nasabah || 'Masukkan Nama Nasabah'">
                            </div>

                            <input x-show="editMode" type="text" x-model="formData.nama_nasabah"
                                class="block w-full text-lg font-bold bg-white border-2 border-blue-300 text-gray-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 focus:outline-none px-4 py-3 rounded-xl transition-all duration-200"
                                placeholder="Masukkan Nama Nasabah">
                        </div>

                        {{-- 2. TANGGAL --}}
                        <div class="grid grid-cols-2 gap-4">

                            <div>
                                <label
                                    class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1.5 block flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    Mulai
                                </label>

                                <div x-show="!editMode"
                                    class="block w-full text-sm font-semibold bg-gray-50 border border-gray-200 text-gray-700 px-3 py-2 rounded-lg"
                                    x-text="selectedSdb?.tanggal_sewa ? new Date(selectedSdb.tanggal_sewa).toLocaleDateString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric'}) : '-'">
                                </div>

                                <input x-show="editMode" type="date" x-model="formData.tanggal_sewa"
                                    class="block w-full text-sm font-semibold bg-white border-gray-300 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg transition-all">
                            </div>

                            <div>
                                <label
                                    class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1.5 block flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Jatuh Tempo
                                </label>

                                <div x-show="!editMode" class="block w-full text-sm font-bold px-3 py-2 rounded-lg"
                                    :class="{
                                        'bg-red-50 border border-red-200 text-red-700': selectedSdb
                                            ?.status === 'lewat_jatuh_tempo',
                                        'bg-yellow-50 border border-yellow-200 text-yellow-700': selectedSdb
                                            ?.status === 'akan_jatuh_tempo',
                                        'bg-gray-50 border border-gray-200 text-gray-600': selectedSdb
                                            ?.status === 'terisi'
                                    }"
                                    x-text="selectedSdb?.tanggal_jatuh_tempo ? new Date(selectedSdb.tanggal_jatuh_tempo).toLocaleDateString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric'}) : '-'">
                                </div>

                                <input x-show="editMode" type="date" x-model="formData.tanggal_jatuh_tempo"
                                    disabled
                                    class="block w-full text-sm font-bold rounded-lg cursor-not-allowed bg-gray-50 border-gray-200 text-gray-600">

                                <p x-show="editMode" x-transition
                                    class="text-[10px] text-blue-600 mt-1.5 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Otomatis 1 tahun dari tanggal sewa
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Empty State --}}
                <div x-show="selectedSdb?.status === 'kosong' && !editMode"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    class="text-center py-10">

                    <div class="relative inline-flex items-center justify-center mb-6">
                        <div class="absolute inset-0 rounded-full bg-blue-100 animate-ping opacity-20"></div>
                        <div
                            class="relative bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 border-2 border-blue-200/50 shadow-sm">
                            <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <h4 class="text-lg font-bold text-gray-800 mb-2">
                        Safe Deposit Box Tersedia
                    </h4>
                    <p class="text-sm text-gray-500 max-w-[220px] mx-auto leading-relaxed">
                        Unit ini belum memiliki penyewa aktif. Klik tombol di bawah untuk memulai kontrak baru.
                    </p>
                </div>

                {{-- SECTION 2: ACTION BUTTONS (SEPARATED WITH PROPER SPACING) --}}

                {{-- A. MODE EDIT (Save/Cancel) --}}
                <div x-show="editMode" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2" class="flex items-center gap-2.5">

                    <button @click="cancelEdit()"
                        class="flex-1 px-4 py-2.5 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-400 active:scale-95 focus:outline-none focus:ring-4 focus:ring-gray-200 transition-all duration-150">
                        Batal
                    </button>

                    <button @click="saveData()" :disabled="isLoading"
                        class="flex-1 px-4 py-2.5 bg-gradient-to-r from-green-500 to-green-600 text-white font-bold rounded-xl shadow-lg shadow-green-500/30 hover:shadow-green-600/40 hover:from-green-600 hover:to-green-700 active:scale-95 focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 transition-all duration-150 flex items-center justify-center gap-2">
                        <svg x-show="!isLoading" class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <svg x-show="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span x-text="isLoading ? 'Menyimpan...' : 'Simpan'"></span>
                    </button>
                </div>

                {{-- B. MODE VIEW (Normal Actions) - FIXED BLINKING --}}
                <div x-show="!editMode" x-cloak x-transition:enter="transition ease-out duration-200 delay-100"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2" class="space-y-3">

                    {{-- Status KOSONG --}}
                    <template x-if="selectedSdb?.status === 'kosong'">
                        <div class="space-y-2.5">
                            <button @click="initFormData()"
                                class="w-full px-5 py-3.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-600/40 hover:from-blue-700 hover:to-blue-800 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Mulai Sewa Baru</span>
                            </button>

                            <button @click="openHistoryModal()"
                                class="w-full py-2 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-lg transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Lihat Riwayat Sebelumnya
                            </button>
                        </div>
                    </template>

                    {{-- Status TIDAK KOSONG --}}
                    <template x-if="selectedSdb?.status !== 'kosong'">
                        <div class="space-y-3.5">

                            {{-- Quick Actions Bar --}}
                            <div class="grid grid-cols-2 gap-2.5">

                                <button @click="openVisitModal()"
                                    class="relative flex flex-col items-center justify-center gap-2 py-3.5 bg-white border-2 border-blue-100 text-blue-700 font-semibold rounded-xl hover:border-blue-300 hover:bg-blue-50 active:scale-95 focus:outline-none focus:ring-4 focus:ring-blue-100 transition-all duration-150 group overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    </div>
                                    <svg class="w-5 h-5 relative z-10 transition-transform group-hover:scale-110 group-hover:-rotate-6"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                    <span class="text-xs relative z-10">Catat Kunjungan</span>
                                </button>

                                <button @click="openHistoryModal()"
                                    class="relative flex flex-col items-center justify-center gap-2 py-3.5 bg-white border-2 border-gray-200 text-gray-700 font-semibold rounded-xl hover:border-gray-300 hover:bg-gray-50 active:scale-95 focus:outline-none focus:ring-4 focus:ring-gray-100 transition-all duration-150 group overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-gray-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    </div>
                                    <svg class="w-5 h-5 relative z-10 transition-transform group-hover:scale-110 group-hover:rotate-12"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-xs relative z-10">Lihat Riwayat</span>
                                </button>
                            </div>

                            {{-- Conditional Actions --}}
                            <div class="space-y-2">

                                {{-- TERISI: Edit Button --}}
                                <button @click="initFormData()" x-show="selectedSdb?.status === 'terisi'"
                                    class="w-full px-4 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-[0.98] shadow-md hover:shadow-lg transition-all duration-150 flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                    Edit Data Sewa
                                </button>

                                {{-- WARNING: Urgent Actions --}}
                                <template
                                    x-if="['akan_jatuh_tempo', 'lewat_jatuh_tempo'].includes(selectedSdb?.status)">
                                    <div class="space-y-2">

                                        <button @click="extendRental()"
                                            class="w-full px-4 py-3 text-white font-bold rounded-xl shadow-lg active:scale-[0.98] transition-all duration-150 flex items-center justify-center gap-2"
                                            :class="selectedSdb?.status === 'lewat_jatuh_tempo' ?
                                                'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 shadow-red-500/30 hover:shadow-red-600/40' :
                                                'bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 shadow-yellow-500/30 hover:shadow-yellow-600/40'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                </path>
                                            </svg>
                                            Perpanjang Sewa
                                        </button>

                                        <a :href="`/sdb/${selectedSdb?.id}/print-letter`" target="_blank"
                                            class="w-full px-4 py-2.5 bg-white text-sm font-semibold rounded-xl border-2 active:scale-[0.98] transition-all duration-150 flex items-center justify-center gap-2"
                                            :class="selectedSdb?.status === 'lewat_jatuh_tempo' ?
                                                'border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400' :
                                                'border-yellow-300 text-yellow-700 hover:bg-yellow-50 hover:border-yellow-400'">
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
                            </div>

                            {{-- Danger Zone --}}
                            <div class="pt-3 border-t border-dashed border-gray-200">
                                <button @click="endRental()"
                                    class="group w-full py-2.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg focus:outline-none focus:ring-4 focus:ring-red-100 transition-all duration-150 flex items-center justify-center gap-1.5 relative overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-r from-transparent via-red-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    </div>
                                    <svg class="w-4 h-4 relative z-10 transition-transform group-hover:scale-110 group-hover:rotate-12"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    <span class="relative z-10">Akhiri Sewa</span>
                                </button>
                            </div>

                        </div>
                    </template>

                </div>

            </div>
        </template>
    </div>
</div>
