<div class="flex-shrink-0 flex flex-col bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden backdrop-blur-sm max-h-full"
    :class="selectedSdb ? 'opacity-100' : 'opacity-60'" x-transition:all.300ms>

    {{-- ========================================================== --}}
    {{-- PERUBAHAN UTAMA: Header sekarang menggunakan :class dinamis --}}
    {{-- ========================================================== --}}
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
                    class="text-blue-100 hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10"
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
        {{-- Tampilan Default (Belum ada SDB yang dipilih) --}}
        <template x-if="!selectedSdb">
            <div class="flex flex-col items-center justify-center text-center text-gray-500 h-full">
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-full p-8 mb-6 shadow-inner">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <h4 class="text-lg font-semibold text-gray-700 mb-3">Pilih Safe Deposit Box</h4>
                <p class="max-w-xs text-sm leading-relaxed text-gray-500">Klik pada kotak SDB untuk melihat detail
                    dan mengelola data penyewa.</p>
            </div>
        </template>

        {{-- Tampilan Saat SDB Sudah Dipilih --}}
        <template x-if="selectedSdb">
            <div>
                <div class="pb-6">
                    {{-- Form Fields & Info Display --}}
                    <div class="space-y-6">

                        {{-- ========================================================== --}}
                        {{-- KODE BARU: Tampilan Untuk SDB yang Kosong (UI Improvement) --}}
                        {{-- ========================================================== --}}
                        <div x-show="selectedSdb.status === 'kosong' && !editMode"
                            class="text-center text-gray-600 py-8">
                            <div class="inline-block bg-gray-100 rounded-full p-5 mb-5">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-800">SDB Ini Kosong</h4>
                            <p class="text-sm mt-1">Belum ada data penyewa. Klik tombol di bawah untuk
                                menambahkan penyewa baru.</p>
                        </div>

                        {{-- Field Nama Nasabah --}}
                        <div x-show="selectedSdb.status !== 'kosong' || editMode">
                            <label for="nama_nasabah"
                                class="text-sm font-medium text-gray-500 flex items-center mb-2"><svg
                                    class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>Nama Nasabah</label>
                            <div x-show="!editMode"
                                class="bg-gray-50 rounded-lg px-4 py-3 text-gray-900 font-semibold text-base"
                                x-text="selectedSdb?.nama_nasabah || '—'"></div>
                            <input id="nama_nasabah" x-show="editMode" type="text" x-model="formData.nama_nasabah"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Nama lengkap">
                        </div>

                        {{-- Field Tanggal Sewa --}}
                        <div x-show="selectedSdb.status !== 'kosong' || editMode">
                            <label for="tanggal_sewa" class="text-sm font-medium text-gray-500 flex items-center mb-2">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                Tanggal Sewa
                            </label>
                            <div x-show="!editMode"
                                class="bg-gray-50 rounded-lg px-4 py-3 text-gray-900 font-semibold text-base"
                                x-text="formatDate(selectedSdb?.tanggal_sewa)">
                            </div>
                            <input id="tanggal_sewa" x-show="editMode" type="date" x-model="formData.tanggal_sewa"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        {{-- Field Jatuh Tempo --}}
                        <div x-show="selectedSdb.status !== 'kosong' || editMode">
                            <label for="tanggal_jatuh_tempo"
                                class="text-sm font-medium text-gray-500 flex items-center mb-2"><svg
                                    class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>Jatuh Tempo</label>

                            {{-- Tampilan saat tidak dalam mode edit --}}
                            <div x-show="!editMode"
                                class="bg-gray-50 rounded-lg px-4 py-3 font-semibold text-base flex items-center justify-between"
                                :class="{
                                    'text-red-600': selectedSdb?.status === 'lewat_jatuh_tempo',
                                    'text-yellow-600': selectedSdb?.status === 'akan_jatuh_tempo',
                                    'text-gray-900': selectedSdb?.tanggal_jatuh_tempo
                                }">

                                {{-- Tanggal Jatuh Tempo --}}
                                <span x-text="formatDate(selectedSdb?.tanggal_jatuh_tempo)"></span>

                                {{-- ========================================================== --}}
                                {{-- BARU: Teks sisa hari yang dinamis dan lebih cerdas --}}
                                {{-- ========================================================== --}}
                                <span class="text-xs font-medium"
                                    x-text="getExpiryText(selectedSdb.status, selectedSdb.days_until_expiry)">
                                </span>
                                {{-- ========================================================== --}}

                            </div>

                            {{-- Input saat dalam mode edit --}}
                            <div x-show="editMode">
                                <input type="date" x-model="formData.tanggal_jatuh_tempo"
                                    class="w-full rounded-lg border-gray-300 shadow-sm bg-gray-100 cursor-not-allowed"
                                    readonly>
                                <p class="text-xs text-gray-500 mt-2">
                                    Tanggal jatuh tempo otomatis dihitung 1 tahun dari tanggal sewa.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi di Footer --}}
                <div class="pt-6 border-t border-gray-200">
                    <div x-show="!editMode" class="space-y-3">

                        {{-- Tombol untuk SDB Kosong --}}
                        <button @click="editMode = true; initFormData()" x-show="selectedSdb?.status === 'kosong'"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tambah Penyewa
                        </button>

                        {{-- Tombol untuk Status Terisi (Normal) --}}
                        <button @click="editMode = true; initFormData()" x-show="selectedSdb?.status === 'terisi'"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            Edit Data
                        </button>

                        {{-- Grup Tombol untuk AKAN Jatuh Tempo --}}
                        <div x-show="selectedSdb?.status === 'akan_jatuh_tempo'" class="flex items-stretch gap-2">
                            {{-- Tombol Perpanjang Sewa (Fleksibel) --}}
                            <button @click="extendRental()"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Perpanjang Sewa
                            </button>
                            {{-- Tombol Edit (Kotak Adaptif) --}}
                            <button @click="editMode = true; initFormData()"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold p-3 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl flex-shrink-0 aspect-square"
                                title="Edit Data">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                            </button>
                        </div>

                        {{-- Tombol untuk LEWAT Jatuh Tempo --}}
                        <button @click="extendRental()" x-show="selectedSdb?.status === 'lewat_jatuh_tempo'"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Perpanjang Sewa
                        </button>

                        {{-- Tombol Akhiri Sewa (Tampil untuk semua status terisi) --}}
                        <button @click="endRental()" x-show="selectedSdb?.nama_nasabah"
                            class="w-full bg-red-100 text-red-700 hover:bg-red-200 font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            Akhiri Sewa
                        </button>
                    </div>

                    <div x-show="editMode" class="flex items-center gap-3">
                        <button @click="saveData()"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors flex items-center justify-center shadow-lg hover:shadow-xl"><svg
                                class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7">
                                </path>
                            </svg>Simpan</button>
                        <button @click="cancelEdit()"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-4 rounded-xl transition-colors">Batal</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
