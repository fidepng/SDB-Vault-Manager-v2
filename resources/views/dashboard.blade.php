<x-app-layout>
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen" x-data="sdbManager()"
        @keydown.escape.window="handleEscape()" @sdb-locate.window="locateUnit($event.detail)">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-stretch gap-6 py-6">
                {{-- Panel Kiri (Detail) --}}
                <div class="w-96 flex-shrink-0">
                    <div class="sticky top-28">
                        @include('components.sdb-detail-panel')
                    </div>
                </div>

                {{-- Panel Kanan (Grid & Filter) --}}
                <div class="flex-1 flex flex-col space-y-8">
                    @include('components.sdb-search-filter')
                    @include('components.sdb-grid')
                </div>
            </div>

            {{-- ======================================================================== --}}
            {{-- MODAL 1: PERPANJANG SEWA (UPDATED: SECURE & AUDITABLE) --}}
            {{-- ======================================================================== --}}
            <div x-show="isExtendModalOpen"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" x-cloak>
                <div @click.outside="isExtendModalOpen = false"
                    class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md transform transition-all scale-100">

                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Perpanjang Masa Sewa</h3>
                        <button @click="isExtendModalOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-gray-500 mb-6">Update data kontrak untuk SDB <strong class="text-blue-600"
                            x-text="selectedSdb?.nomor_sdb"></strong>.</p>

                    <div class="space-y-5">
                        {{-- Nama Nasabah (LOCKED) --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">
                                Nama Nasabah
                            </label>
                            <input type="text" x-model="modalFormData.nama_nasabah" disabled
                                class="w-full rounded-lg border-gray-300 bg-gray-100 text-gray-500 shadow-sm cursor-not-allowed focus:ring-0">
                            <p class="text-[10px] text-red-500 mt-1 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                                Nama tidak dapat diubah saat perpanjangan.
                            </p>
                        </div>

                        {{-- Tanggal Mulai (AUTO-SUGGEST) --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">
                                Tanggal Mulai Perpanjangan
                            </label>
                            <input type="date" x-model="modalFormData.tanggal_mulai_baru"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="text-[10px] text-blue-600 mt-1">
                                *Sistem otomatis menyarankan H+1 dari jatuh tempo terakhir.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                        <button @click="isExtendModalOpen = false"
                            class="px-5 py-2.5 rounded-xl text-gray-600 font-medium hover:bg-gray-100 transition-colors">
                            Batal
                        </button>
                        <button @click="submitExtendRental()"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isLoading">
                            <span x-show="isLoading" class="mr-2 animate-spin">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

            {{-- ======================================================================== --}}
            {{-- MODAL 2: AKHIRI SEWA (Existing) --}}
            {{-- ======================================================================== --}}
            <div x-show="isEndRentalModalOpen"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" x-cloak>
                <div @click.outside="isEndRentalModalOpen = false"
                    class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-50 mb-6">
                        <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Konfirmasi Akhiri Sewa</h3>
                    <p class="text-gray-500 mb-8">Apakah Anda yakin ingin mengakhiri sewa SDB <strong
                            x-text="selectedSdb?.nomor_sdb"></strong>? Data akan dipindahkan ke arsip.</p>
                    <div class="flex gap-3">
                        <button @click="isEndRentalModalOpen = false"
                            class="flex-1 px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-medium hover:bg-gray-200">Batal</button>
                        <button @click="submitEndRental()"
                            class="flex-1 px-5 py-2.5 rounded-xl bg-red-600 text-white font-medium hover:bg-red-700 shadow-lg shadow-red-500/30">Ya,
                            Akhiri</button>
                    </div>
                </div>
            </div>

            {{-- ======================================================================== --}}
            {{-- MODAL 3: CATAT KUNJUNGAN (BARU FASE 2) --}}
            {{-- ======================================================================== --}}
            <div x-show="isVisitModalOpen"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" x-cloak>
                <div @click.outside="isVisitModalOpen = false"
                    class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Catat Kunjungan Baru</h3>
                        <button @click="isVisitModalOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Nama
                                Pengunjung</label>
                            <input type="text" x-model="visitFormData.nama_pengunjung"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Sesuai KTP / Surat Kuasa">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Waktu
                                Kunjungan</label>
                            <input type="datetime-local" x-model="visitFormData.waktu_kunjung"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Keterangan
                                (Opsional)</label>
                            <textarea x-model="visitFormData.keterangan" rows="3"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Contoh: Ambil dokumen, didampingi kuasa hukum..."></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8">
                        <button @click="isVisitModalOpen = false"
                            class="px-5 py-2.5 rounded-xl text-gray-600 font-medium hover:bg-gray-100 transition-colors">Batal</button>
                        <button @click="submitVisit()"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">
                            <span x-show="!isLoading">Simpan Kunjungan</span>
                            <span x-show="isLoading">Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ======================================================================== --}}
            {{-- MODAL 4: LIHAT RIWAYAT (IMPROVED UI/UX) --}}
            {{-- ======================================================================== --}}
            <div x-show="isHistoryModalOpen"
                class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity duration-300"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>

                <div @click.outside="closeHistoryModal()"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col transform transition-all scale-100"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                    {{-- HEADER: Info Unit --}}
                    <div
                        class="px-8 py-6 border-b border-gray-100 flex justify-between items-start bg-white rounded-t-2xl z-10">
                        <div class="flex items-center gap-4">
                            <div class="bg-blue-50 p-3 rounded-xl border border-blue-100">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                {{-- PERBAIKAN 1: Menghapus 'font-mono' agar font seragam --}}
                                <h3 class="text-2xl font-bold text-gray-900">SDB <span x-text="selectedSdb?.nomor_sdb"
                                        class="text-blue-600"></span></h3>
                                <p class="text-sm text-gray-500 mt-1">Arsip lengkap riwayat penyewaan dan log akses
                                    fisik.</p>
                            </div>
                        </div>
                        <button @click="closeHistoryModal()"
                            class="group p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <svg class="w-6 h-6 text-gray-400 group-hover:text-gray-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- TAB NAVIGATION --}}
                    <div class="flex px-8 border-b border-gray-200 bg-white sticky top-0 z-10">
                        <button @click="activeHistoryTab = 'sewa'"
                            class="relative px-6 py-4 text-sm font-bold transition-colors focus:outline-none"
                            :class="activeHistoryTab === 'sewa' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                            <span>Riwayat Sewa</span>
                            <div x-show="activeHistoryTab === 'sewa'"
                                class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-t-full" x-transition>
                            </div>
                        </button>
                        <button @click="activeHistoryTab = 'kunjungan'"
                            class="relative px-6 py-4 text-sm font-bold transition-colors focus:outline-none"
                            :class="activeHistoryTab === 'kunjungan' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                            <span>Riwayat Kunjungan</span>
                            <div x-show="activeHistoryTab === 'kunjungan'"
                                class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-t-full" x-transition>
                            </div>
                        </button>
                    </div>

                    {{-- CONTENT AREA --}}
                    <div class="flex-1 overflow-y-auto p-8 bg-gray-50/50 scroll-smooth">

                        {{-- Loading State --}}
                        <div x-show="isLoadingHistory"
                            class="flex flex-col justify-center items-center h-full text-gray-400">
                            <svg class="animate-spin h-10 w-10 mb-3 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span class="text-sm font-medium">Memuat data arsip...</span>
                        </div>

                        {{-- TAB 1: RIWAYAT SEWA (TIMELINE STYLE) --}}
                        {{-- TAB 1: RIWAYAT SEWA (TIMELINE STYLE - FIXED ALIGNMENT) --}}
                        <div x-show="!isLoadingHistory && activeHistoryTab === 'sewa'"
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="opacity-0 translate-x-4"
                            x-transition:enter-end="opacity-100 translate-x-0">

                            {{-- Empty State --}}
                            <div x-show="historyData.rental_histories.length === 0"
                                class="flex flex-col items-center justify-center py-20 text-gray-400">
                                <div class="bg-gray-100 p-4 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p>Belum ada sejarah penyewaan.</p>
                            </div>

                            {{-- Timeline Container --}}
                            <div class="relative py-4" x-show="historyData.rental_histories.length > 0">

                                {{-- GARIS VERTIKAL (FIXED POSITION) --}}
                                {{-- left-8 (32px) dari kiri. -translate-x-1/2 agar center garis ada di pixel ke-32 --}}
                                <div
                                    class="absolute left-8 top-4 bottom-4 w-0.5 bg-gray-200 transform -translate-x-1/2">
                                </div>

                                <template x-for="(history, index) in historyData.rental_histories"
                                    :key="history.id">
                                    <div class="relative pl-20 mb-8 group"> {{-- pl-20 memberi jarak aman dari garis --}}

                                        {{-- TIMELINE DOT (FIXED ALIGNMENT) --}}
                                        {{-- left-8 sama dengan garis. -translate-x-1/2 memastikan titik tengahnya sama persis --}}
                                        <div class="absolute left-8 top-6 w-4 h-4 rounded-full border-[3px] bg-white z-10 transform -translate-x-1/2 transition-colors duration-300"
                                            :class="{
                                                'border-green-500 ring-4 ring-green-50': ['SEDANG AKTIF', 'selesai']
                                                    .includes(history.status_akhir),
                                                'border-red-500 ring-4 ring-red-50': ['LEWAT JATUH TEMPO', 'diputus']
                                                    .includes(history.status_akhir),
                                                'border-yellow-500 ring-4 ring-yellow-50': ['AKAN JATUH TEMPO']
                                                    .includes(history.status_akhir),
                                                'border-gray-400': !['SEDANG AKTIF', 'selesai', 'diputus',
                                                    'AKAN JATUH TEMPO', 'LEWAT JATUH TEMPO'
                                                ].includes(history.status_akhir)
                                            }">
                                        </div>

                                        {{-- CARD CONTENT --}}
                                        <div
                                            class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 relative group-hover:border-blue-200">

                                            {{-- Header Card --}}
                                            <div class="flex justify-between items-start mb-4">
                                                <div>
                                                    <h4 class="text-lg font-bold text-gray-900"
                                                        x-text="history.nama_nasabah"></h4>
                                                    <span class="text-xs text-gray-400 font-mono mt-0.5 block"
                                                        x-text="'No. Ref: #' + history.id"></span>
                                                </div>
                                                {{-- Badge Status --}}
                                                <span
                                                    class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border shadow-sm"
                                                    :class="{
                                                        'bg-green-50 text-green-700 border-green-200': ['selesai',
                                                            'SEDANG AKTIF'
                                                        ].includes(history.status_akhir),
                                                        'bg-red-50 text-red-700 border-red-200': ['diputus',
                                                            'LEWAT JATUH TEMPO'
                                                        ].includes(history.status_akhir),
                                                        'bg-yellow-50 text-yellow-700 border-yellow-200': ['pindah',
                                                            'AKAN JATUH TEMPO'
                                                        ].includes(history.status_akhir)
                                                    }"
                                                    x-text="history.status_akhir">
                                                </span>
                                            </div>

                                            {{-- Info Tanggal (Box Style agar lebih rapi) --}}
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                                <div
                                                    class="bg-gray-50 rounded-lg p-3 border border-gray-100 flex items-center">
                                                    <div
                                                        class="bg-white p-1.5 rounded-md border border-gray-200 mr-3 text-blue-600 shadow-sm">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                            </path>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p
                                                            class="text-[10px] uppercase text-gray-400 font-bold tracking-wide">
                                                            Mulai Sewa</p>
                                                        <p class="text-sm font-semibold text-gray-800"
                                                            x-text="formatDate(history.tanggal_mulai)"></p>
                                                    </div>
                                                </div>

                                                <div
                                                    class="bg-gray-50 rounded-lg p-3 border border-gray-100 flex items-center">
                                                    <div
                                                        class="bg-white p-1.5 rounded-md border border-gray-200 mr-3 text-red-500 shadow-sm">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p
                                                            class="text-[10px] uppercase text-gray-400 font-bold tracking-wide">
                                                            Berakhir</p>
                                                        <p class="text-sm font-semibold text-gray-800"
                                                            x-text="formatDate(history.tanggal_berakhir)"></p>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Catatan --}}
                                            <div class="pt-3 border-t border-gray-100 flex items-start gap-2">
                                                <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                                                    </path>
                                                </svg>
                                                <p class="text-sm text-gray-500 italic leading-snug"
                                                    x-text="history.catatan || 'Tidak ada catatan khusus'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- TAB 2: RIWAYAT KUNJUNGAN --}}
                        <div x-show="!isLoadingHistory && activeHistoryTab === 'kunjungan'"
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="opacity-0 translate-x-4"
                            x-transition:enter-end="opacity-100 translate-x-0">

                            <div x-show="historyData.visits.length === 0"
                                class="flex flex-col items-center justify-center py-20 text-gray-400">
                                <div class="bg-gray-100 p-4 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                </div>
                                <p>Belum ada data kunjungan tercatat.</p>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm"
                                x-show="historyData.visits.length > 0">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                Waktu Kunjungan</th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                Pengunjung</th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                Petugas</th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <template x-for="visit in historyData.visits" :key="visit.id">
                                            <tr class="hover:bg-blue-50/50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-bold text-gray-800"
                                                            x-text="formatDateTime(visit.waktu_kunjung).split(',')[1]"></span>
                                                        <span class="text-xs text-gray-500"
                                                            x-text="formatDateTime(visit.waktu_kunjung).split(',')[0]"></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div
                                                            class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs mr-3">
                                                            <span x-text="visit.nama_pengunjung.charAt(0)"></span>
                                                        </div>
                                                        <span class="text-sm font-medium text-gray-900"
                                                            x-text="visit.nama_pengunjung"></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <span x-text="visit.petugas?.name || 'Unknown'"></span>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <span x-text="visit.keterangan || '-'" class="italic"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- SCRIPT UTAMA ALPINE.JS (FINAL & STANDARDIZED) --}}
    <script>
        function sdbManager() {
            return {
                // =================================================================
                // 1. DATA & STATE
                // =================================================================
                sdbLayouts: @json($sdbLayouts ?? []),
                sdbDataMap: @json($sdbDataMap ?? []),
                statistics: @json($statistics ?? []),

                // COMPUTED PROPERTIES
                get allUnits() {
                    return Object.values(this.sdbDataMap);
                },

                get isFilterActive() {
                    return !!this.filters.search || !!this.filters.status || !!this.filters.tipe;
                },

                // UI State
                selectedSdb: null,
                editMode: false,
                isLoading: false,
                isLoadingHistory: false,

                // Filter State
                filters: {
                    search: '',
                    status: '',
                    tipe: ''
                },
                filteredUnits: [],

                // =================================================================
                // 2. FORM MODELS
                // =================================================================
                formData: {
                    nama_nasabah: '',
                    tanggal_sewa: '',
                    tanggal_jatuh_tempo: '',
                    nomor_sdb: '',
                    tipe: ''
                },

                // Modal States
                isExtendModalOpen: false,
                modalFormData: {
                    nama_nasabah: '',
                    tanggal_mulai_baru: ''
                },

                isEndRentalModalOpen: false,

                isVisitModalOpen: false,
                visitFormData: {
                    nama_pengunjung: '',
                    waktu_kunjung: '',
                    keterangan: ''
                },

                isHistoryModalOpen: false,
                historyData: {
                    rental_histories: [],
                    visits: []
                },
                activeHistoryTab: 'sewa',

                search: '',
                selectedSdb: null,

                // =================================================================
                // FUNGSI BARU: LOCATE UNIT (SEAMLESS)
                // =================================================================
                locateUnit(nomorSdb) {
                    // 1. Validasi Unit
                    if (!this.sdbDataMap.hasOwnProperty(nomorSdb)) return;
                    const unit = this.sdbDataMap[nomorSdb];

                    // 2. Bersihkan Filter (Agar denah utuh)
                    this.clearFilters();

                    // 3. Buka Panel Detail
                    this.selectedSdb = unit;
                    this.editMode = false; // Pastikan tidak dalam mode edit agar aman

                    // 4. Scroll ke Lokasi (Seamless)
                    this.$nextTick(() => {
                        const elementId = 'unit-' + unit.id; // Pastikan ID ini ada di sdb-grid.blade.php
                        const element = document.getElementById(elementId);

                        if (element) {
                            element.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                                inline: 'center'
                            });

                            // Opsional: Efek visual sementara (Highlight)
                            element.classList.add('ring-4', 'ring-blue-400');
                            setTimeout(() => element.classList.remove('ring-4', 'ring-blue-400'), 2000);
                        }
                    });
                },

                // =================================================================
                // 3. INITIALIZATION & WATCHERS
                // =================================================================
                init() {
                    // 1. Setup Filter Awal
                    this.applyFilters();

                    // 2. Listener Global (Refresh Data)
                    window.addEventListener('sdb-data-updated', () => window.location.reload());

                    // 3. [RESTORED & IMPROVED] Watcher Tanggal Sewa
                    // Kita panggil fungsi terpisah agar init() tetap rapi
                    this.$watch('formData.tanggal_sewa', (newDate) => {
                        this.handleRentalDateChange(newDate);
                    });
                },

                // =================================================================
                // LOGIC & BUSINESS RULES (BEST PRACTICE)
                // =================================================================

                // Fungsi khusus untuk menangani perubahan tanggal
                handleRentalDateChange(newDate) {
                    // Hanya jalan jika mode edit aktif dan tanggal valid
                    if (this.editMode && newDate) {
                        try {
                            const startDate = new Date(newDate);

                            // Validasi: Pastikan tanggal benar-benar valid
                            if (!isNaN(startDate.getTime())) {
                                const endDate = new Date(startDate);

                                // ATURAN BISNIS: Default 1 Tahun
                                // JS otomatis handle Leap Year (Tahun Kabisat)
                                endDate.setFullYear(startDate.getFullYear() + 1);

                                // Set ke format input HTML (YYYY-MM-DD)
                                this.formData.tanggal_jatuh_tempo = endDate.toISOString().split('T')[0];
                            }
                        } catch (e) {
                            console.error("Gagal menghitung jatuh tempo:", e);
                        }
                    }
                },

                checkDeepLink() {
                    // 1. Ambil parameter dari URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const unitNomor = urlParams.get('open_unit');

                    // 2. Validasi
                    if (unitNomor && this.sdbDataMap.hasOwnProperty(unitNomor)) {
                        const unitToOpen = this.sdbDataMap[unitNomor];

                        // --- [LOGIKA LAMA DIHAPUS] ---
                        // this.filters.search = unitNomor;  <-- HAPUS INI (Penyebab filter aktif)
                        // this.applyFilters();              <-- HAPUS INI

                        // --- [LOGIKA BARU: SELECT & SCROLL] ---

                        // A. Pastikan filter kosong agar denah tampil utuh
                        this.clearFilters();

                        // B. Select Unit (Buka Panel Kanan)
                        this.selectedSdb = unitToOpen;
                        this.editMode = false;

                        // C. Scroll ke elemen visual unit tersebut (Auto-Focus)
                        this.$nextTick(() => {
                            const elementId = 'sdb-unit-' + unitToOpen.id;
                            const element = document.getElementById(elementId);

                            if (element) {
                                // Scroll halus ke posisi unit
                                element.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center', // Taruh unit di tengah layar vertikal
                                    inline: 'center' // Taruh di tengah horizontal
                                });

                                // Opsional: Beri efek visual "kedip" agar mata user tertuju kesana
                                element.classList.add('ring-4', 'ring-red-500', 'ring-offset-2');
                                setTimeout(() => {
                                    element.classList.remove('ring-4', 'ring-red-500', 'ring-offset-2');
                                }, 2000);
                            }
                        });

                        // D. Bersihkan URL
                        const cleanUrl = window.location.pathname;
                        window.history.replaceState({}, document.title, cleanUrl);
                    }
                },

                // =================================================================
                // 4. CORE HELPERS
                // =================================================================

                getLocalISOString() {
                    const now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    return now.toISOString().slice(0, 16);
                },
                getLocalDateString() {
                    return this.getLocalISOString().slice(0, 10);
                },

                notify(message, type = 'success') {
                    if (window.showNotification) window.showNotification(message, type);
                    else alert(message);
                },

                updateLocalUnitData(updatedUnit) {
                    if (!updatedUnit) return;
                    this.sdbDataMap[updatedUnit.nomor_sdb] = updatedUnit;
                    if (this.selectedSdb && this.selectedSdb.id === updatedUnit.id) {
                        this.selectedSdb = JSON.parse(JSON.stringify(updatedUnit));
                        if (this.editMode) this.initFormData();
                    }
                    this.applyFilters();
                },

                // =================================================================
                // 5. INTERACTION LOGIC
                // =================================================================

                searchAndSelect() {
                    this.applyFilters();
                    const keyword = this.filters.search.toLowerCase();
                    if (!keyword) return;

                    const visibleUnits = this.allUnits.filter(u => this.filteredUnits.includes(u.id));
                    const exactMatch = visibleUnits.find(u => u.nomor_sdb.toLowerCase() === keyword);

                    if (exactMatch) {
                        this.showDetail(exactMatch.id);
                    } else if (visibleUnits.length === 1) {
                        this.showDetail(visibleUnits[0].id);
                    }
                },

                showDetail(id) {
                    const unit = this.allUnits.find(u => u.id === id);
                    if (!unit) return;

                    this.selectedSdb = JSON.parse(JSON.stringify(unit));
                    this.editMode = false;
                },

                // [DITAMBAHKAN KEMBALI] Fungsi Clear Selection (Tombol X)
                clearSelection() {
                    this.selectedSdb = null;
                    this.editMode = false;
                },

                initFormData() {
                    this.editMode = true;

                    this.formData = {
                        id: this.selectedSdb.id,
                        nomor_sdb: this.selectedSdb.nomor_sdb,
                        tipe: this.selectedSdb.tipe,
                        nama_nasabah: this.selectedSdb.nama_nasabah || '',
                        tanggal_sewa: '',
                        tanggal_jatuh_tempo: ''
                    };

                    if (this.selectedSdb.status === 'kosong') {
                        this.formData.tanggal_sewa = this.getLocalDateString();
                    } else {
                        this.formData.tanggal_sewa = this.selectedSdb.tanggal_sewa ? this.selectedSdb.tanggal_sewa.split(
                            'T')[0] : '';
                        this.formData.tanggal_jatuh_tempo = this.selectedSdb.tanggal_jatuh_tempo ? this.selectedSdb
                            .tanggal_jatuh_tempo.split('T')[0] : '';
                    }
                },

                selectUnit(unit) {
                    // Jika unit yang sama diklik lagi -> Deselect (Tutup Panel)
                    if (this.selectedSdb && this.selectedSdb.nomor_sdb === unit.nomor_sdb) {
                        this.clearSelection();
                    } else {
                        // Buka Panel
                        this.selectedSdb = unit;
                        this.editMode = false;

                        // Scroll halus ke atas agar detail panel terlihat (Mobile Friendly)
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                },

                cancelEdit() {
                    // CRITICAL FIX: Reset editMode first BEFORE clearing formData
                    // This prevents Alpine from trying to sync stale formData to UI
                    this.editMode = false;

                    // Wait for DOM to update before clearing form
                    this.$nextTick(() => {
                        this.formData = {
                            nama_nasabah: '',
                            tanggal_sewa: '',
                            tanggal_jatuh_tempo: '',
                            nomor_sdb: '',
                            tipe: ''
                        };
                    });
                },

                async saveData() {
                    if (!this.formData.nama_nasabah) {
                        this.notify('Nama Nasabah wajib diisi!', 'warning');
                        return;
                    }
                    this.isLoading = true;

                    let url = this.selectedSdb.status === 'kosong' ?
                        `/sdb/${this.selectedSdb.id}/rent` :
                        `/sdb/${this.selectedSdb.id}`;
                    let method = this.selectedSdb.status === 'kosong' ? 'POST' : 'PUT';

                    try {
                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.formData)
                        });

                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message);

                        this.updateLocalUnitData(result.data);
                        this.editMode = false;
                        this.notify(result.message, 'success');

                    } catch (e) {
                        this.notify(e.message, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                // =================================================================
                // 6. MODAL ACTIONS
                // =================================================================
                extendRental() {
                    if (!this.selectedSdb) return;

                    let nextStart;
                    if (this.selectedSdb.tanggal_jatuh_tempo) {
                        let jt = new Date(this.selectedSdb.tanggal_jatuh_tempo);
                        jt.setDate(jt.getDate() + 1);
                        nextStart = jt.toISOString().split('T')[0];
                    } else {
                        nextStart = this.getLocalDateString();
                    }

                    this.modalFormData = {
                        nama_nasabah: this.selectedSdb.nama_nasabah,
                        tanggal_mulai_baru: nextStart
                    };

                    this.isExtendModalOpen = true;
                },

                async submitExtendRental() {
                    this.isLoading = true;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}/extend`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.modalFormData)
                        });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message);

                        this.updateLocalUnitData(result.data);
                        this.isExtendModalOpen = false;
                        this.notify(result.message, 'success');
                    } catch (e) {
                        this.notify(e.message, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                endRental() {
                    this.isEndRentalModalOpen = true;
                },

                async submitEndRental() {
                    this.isLoading = true;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message);

                        this.updateLocalUnitData(result.data);
                        this.isEndRentalModalOpen = false;
                        this.notify('Sewa berhasil diakhiri.', 'success');
                    } catch (e) {
                        this.notify(e.message, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                openVisitModal() {
                    if (!this.selectedSdb) return;
                    this.visitFormData = {
                        nama_pengunjung: this.selectedSdb.nama_nasabah || '',
                        waktu_kunjung: this.getLocalISOString(),
                        keterangan: ''
                    };
                    this.isVisitModalOpen = true;
                },

                async submitVisit() {
                    this.isLoading = true;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}/visit`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.visitFormData)
                        });
                        if (response.ok) {
                            this.notify('Kunjungan dicatat.', 'success');
                            this.isVisitModalOpen = false;
                        } else {
                            this.notify('Gagal.', 'error');
                        }
                    } catch (e) {
                        this.notify('Error.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async openHistoryModal() {
                    if (!this.selectedSdb) return;
                    this.isHistoryModalOpen = true;
                    this.isLoadingHistory = true;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}/history`);
                        const data = await response.json();
                        this.historyData = {
                            rental_histories: data.rental_histories || [],
                            visits: data.visits || []
                        };
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isLoadingHistory = false;
                    }
                },

                closeHistoryModal() {
                    this.isHistoryModalOpen = false;
                },

                // =================================================================
                // 7. HELPER VIEW
                // =================================================================
                getStatusText(status) {
                    const map = {
                        'kosong': 'Tersedia',
                        'terisi': 'Terisi',
                        'akan_jatuh_tempo': 'Akan Jatuh Tempo',
                        'lewat_jatuh_tempo': 'Lewat Jatuh Tempo'
                    };
                    return map[status] || status;
                },

                getExpiryTooltipText(sdb) {
                    if (!sdb || sdb.status === 'kosong') return '';
                    const days = sdb.days_until_expiry;
                    if (days === undefined || days === null) return '';
                    if (days < 0) return `${Math.abs(days)} hari lalu`;
                    if (days === 0) return 'Jatuh tempo hari ini';
                    return `${days} hari lagi`;
                },

                getExpiryText(status, days) {
                    if (status === 'kosong' || days === undefined || days === null) return '';
                    if (days < 0) return `(Lewat ${Math.abs(days)} hari)`;
                    return `(${days} hari lagi)`;
                },

                getStatusHeaderBadgeClass(status) {
                    const map = {
                        'terisi': 'bg-blue-100 text-blue-800',
                        'akan_jatuh_tempo': 'bg-yellow-100 text-yellow-800',
                        'lewat_jatuh_tempo': 'bg-red-100 text-red-800',
                        'kosong': 'bg-gray-100 text-gray-800'
                    };
                    return map[status] || 'bg-gray-100 text-gray-800';
                },
                getHeaderGradientClass() {
                    if (!this.selectedSdb) return 'from-blue-600 via-blue-700 to-blue-800 text-white';
                    const map = {
                        'kosong': 'from-gray-500 via-gray-600 to-gray-700 text-white',
                        'akan_jatuh_tempo': 'from-yellow-400 via-yellow-500 to-yellow-600 text-white',
                        'lewat_jatuh_tempo': 'from-red-500 via-red-600 to-red-700 text-white',
                        'terisi': 'from-blue-600 via-blue-700 to-blue-800 text-white'
                    };
                    return map[this.selectedSdb.status] || map['terisi'];
                },
                formatDate(dateStr) {
                    if (!dateStr) return '-';
                    return new Date(dateStr).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                },
                formatDateTime(dateStr) {
                    if (!dateStr) return '-';
                    return new Date(dateStr).toLocaleString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                applyFilters() {
                    const s = this.filters.search.toLowerCase();
                    const allUnits = Object.values(this.sdbDataMap);

                    const result = allUnits.filter(unit => {
                        const matchSearch = unit.nomor_sdb.toLowerCase().includes(s) ||
                            (unit.nama_nasabah && unit.nama_nasabah.toLowerCase().includes(s));
                        const matchStatus = this.filters.status === '' || unit.status === this.filters.status;
                        const matchTipe = this.filters.tipe === '' || unit.tipe === this.filters.tipe;

                        return matchSearch && matchStatus && matchTipe;
                    });

                    // Simpan ID unit yang lolos filter
                    this.filteredUnits = result.map(u => u.id);
                },

                clearFilters() {
                    this.filters = {
                        search: '',
                        status: '',
                        tipe: ''
                    };
                    this.applyFilters();
                },

                /**
                 * CRITICAL FIX: Export with current filter state
                 * This method is called from sdb-search-filter.blade.php
                 */
                exportWithCurrentFilters() {
                    // Get current filter values from Alpine component state
                    const currentFilters = {
                        search: this.filters.search ? this.filters.search.trim() : '',
                        status: this.filters.status || '',
                        tipe: this.filters.tipe || ''
                    };

                    // Build query string
                    const params = new URLSearchParams();

                    if (currentFilters.search !== '') {
                        params.append('search', currentFilters.search);
                    }

                    if (currentFilters.status !== '') {
                        params.append('status', currentFilters.status);
                    }

                    if (currentFilters.tipe !== '') {
                        params.append('tipe', currentFilters.tipe);
                    }

                    // Build export URL
                    const baseUrl = '{{ route('sdb.export') }}';
                    const exportUrl = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;

                    // Debug log (optional - remove in production)
                    console.log('[EXPORT] Filter state:', currentFilters);
                    console.log('[EXPORT] URL:', exportUrl);

                    // Trigger download
                    window.location.href = exportUrl;
                },

                getTotalUnitsByType(type) {
                    if (!this.sdbLayouts[type]) return 0;
                    return this.sdbLayouts[type].grid.flat().length;
                },
                getFilteredUnitsCount() {
                    return this.filteredUnits.length;
                },

                handleEscape() {
                    if (this.isExtendModalOpen) this.isExtendModalOpen = false;
                    else if (this.isHistoryModalOpen) this.isHistoryModalOpen = false;
                    else if (this.isVisitModalOpen) this.isVisitModalOpen = false;
                    else if (this.editMode) this.cancelEdit();
                    else if (this.selectedSdb) this.clearSelection();
                }
            };
        }
    </script>
</x-app-layout>
