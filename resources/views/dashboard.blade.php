<x-app-layout>
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen" x-data="sdbManager()"
        @keydown.escape.window="handleEscape()">
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
            {{-- MODAL 1: PERPANJANG SEWA (Existing) --}}
            {{-- ======================================================================== --}}
            <div x-show="isExtendModalOpen"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" x-cloak>
                <div @click.outside="isExtendModalOpen = false"
                    class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md transform transition-all scale-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Perpanjang Masa Sewa</h3>
                    <p class="text-sm text-gray-500 mb-6">Update data untuk SDB <strong
                            x-text="selectedSdb?.nomor_sdb"></strong>.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Nama
                                Nasabah</label>
                            <input type="text" x-model="modalFormData.nama_nasabah"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Tanggal
                                Mulai Baru</label>
                            <input type="date" x-model="modalFormData.tanggal_mulai_baru"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8">
                        <button @click="isExtendModalOpen = false"
                            class="px-5 py-2.5 rounded-xl text-gray-600 font-medium hover:bg-gray-100 transition-colors">Batal</button>
                        <button @click="submitExtendRental()"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">Simpan
                            Perubahan</button>
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
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Waktu
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
            {{-- MODAL 4: LIHAT RIWAYAT (BARU FASE 2) --}}
            {{-- ======================================================================== --}}
            <div x-show="isHistoryModalOpen"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" x-cloak>
                <div @click.outside="closeHistoryModal()"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl h-[80vh] flex flex-col">

                    {{-- Header Modal --}}
                    <div
                        class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Arsip Data SDB <span
                                    x-text="selectedSdb?.nomor_sdb"></span></h3>
                            <p class="text-sm text-gray-500 mt-1">Rekam jejak penyewaan dan log kunjungan nasabah.</p>
                        </div>
                        <button @click="closeHistoryModal()"
                            class="p-2 hover:bg-gray-200 rounded-full transition-colors">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Tab Navigation --}}
                    <div class="flex px-8 border-b border-gray-200 bg-white">
                        <button @click="activeHistoryTab = 'sewa'"
                            class="px-6 py-4 text-sm font-semibold border-b-2 transition-colors"
                            :class="activeHistoryTab === 'sewa' ? 'border-blue-600 text-blue-600' :
                                'border-transparent text-gray-500 hover:text-gray-700'">
                            Riwayat Sewa
                        </button>
                        <button @click="activeHistoryTab = 'kunjungan'"
                            class="px-6 py-4 text-sm font-semibold border-b-2 transition-colors"
                            :class="activeHistoryTab === 'kunjungan' ? 'border-blue-600 text-blue-600' :
                                'border-transparent text-gray-500 hover:text-gray-700'">
                            Riwayat Kunjungan
                        </button>
                    </div>

                    {{-- Content Area --}}
                    <div class="flex-1 overflow-y-auto p-8 bg-gray-50/50">

                        {{-- Loading State --}}
                        <div x-show="isLoadingHistory" class="flex justify-center items-center h-full">
                            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                        </div>

                        {{-- TAB 1: DATA HISTORY SEWA --}}
                        <div x-show="!isLoadingHistory && activeHistoryTab === 'sewa'">
                            <div x-show="historyData.rental_histories.length === 0"
                                class="text-center py-12 text-gray-500">
                                Belum ada arsip sewa untuk unit ini.
                            </div>
                            <div x-show="historyData.rental_histories.length > 0" class="space-y-4">
                                <template x-for="history in historyData.rental_histories" :key="history.id">
                                    <div
                                        class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <h4 class="font-bold text-gray-900" x-text="history.nama_nasabah">
                                                </h4>
                                                {{-- <span
                                                    class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded mt-1 inline-block"
                                                    x-text="history.nomor_sdb"></span> --}}
                                            </div>
                                            <span
                                                class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 uppercase"
                                                x-text="history.status_akhir"></span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-3">
                                            <div>
                                                <p class="text-xs text-gray-400">Mulai Sewa</p>
                                                <p class="font-medium" x-text="formatDate(history.tanggal_mulai)"></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-400">Berakhir</p>
                                                <p class="font-medium" x-text="formatDate(history.tanggal_berakhir)">
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 border-t border-gray-50 pt-3">
                                            <span class="font-medium">Catatan:</span> <span
                                                x-text="history.catatan || '-'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- TAB 2: DATA KUNJUNGAN --}}
                        <div x-show="!isLoadingHistory && activeHistoryTab === 'kunjungan'">
                            <div x-show="historyData.visits.length === 0" class="text-center py-12 text-gray-500">
                                Belum ada data kunjungan tercatat.
                            </div>
                            <table x-show="historyData.visits.length > 0"
                                class="min-w-full bg-white rounded-xl overflow-hidden shadow-sm">
                                <thead class="bg-gray-100 text-gray-600 text-xs uppercase font-semibold">
                                    <tr>
                                        <th class="px-6 py-4 text-left">Waktu</th>
                                        <th class="px-6 py-4 text-left">Pengunjung</th>
                                        <th class="px-6 py-4 text-left">Petugas</th>
                                        <th class="px-6 py-4 text-left">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-sm">
                                    <template x-for="visit in historyData.visits" :key="visit.id">
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-gray-900 font-medium"
                                                x-text="formatDateTime(visit.waktu_kunjung)"></td>
                                            <td class="px-6 py-4 text-gray-700" x-text="visit.nama_pengunjung"></td>
                                            <td class="px-6 py-4 text-gray-500"
                                                x-text="visit.petugas?.name || 'Unknown'"></td>
                                            <td class="px-6 py-4 text-gray-500 italic"
                                                x-text="visit.keterangan || '-'"></td>
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

    {{-- SCRIPT UTAMA ALPINE.JS (FINAL & STANDARDIZED) --}}
    {{-- SCRIPT UTAMA ALPINE.JS (FIXED: Added searchAndSelect) --}}
    <script>
        function sdbManager() {
            return {
                // --- STATE UTAMA ---
                selectedSdb: null,
                editMode: false,
                isLoading: false,

                // --- FILTER STATE ---
                filters: {
                    search: '',
                    status: '',
                    tipe: ''
                },

                // --- DATA ---
                sdbLayouts: @json($sdbLayouts ?? []),
                sdbDataMap: @json($sdbDataMap ?? []),
                allUnits: @json($allUnits ?? []),
                filteredUnits: @json(collect($allUnits ?? [])->pluck('id')->toArray()),

                // --- STATE FORM & MODAL ---
                formData: {
                    nama_nasabah: '',
                    tanggal_sewa: '',
                    tanggal_jatuh_tempo: ''
                },

                // Modal Perpanjang
                isExtendModalOpen: false,
                modalFormData: {
                    nama_nasabah: '',
                    tanggal_mulai_baru: ''
                },

                // Modal Akhiri Sewa
                isEndRentalModalOpen: false,

                // --- STATE FASE 2 (HISTORY & VISIT) ---
                isVisitModalOpen: false,
                visitFormData: {
                    nama_pengunjung: '',
                    waktu_kunjung: '',
                    keterangan: ''
                },

                isHistoryModalOpen: false,
                isLoadingHistory: false,
                activeHistoryTab: 'sewa',
                historyData: {
                    rental_histories: [],
                    visits: []
                },

                // --- COMPUTED PROPERTIES ---
                get isFilterActive() {
                    return !!this.filters.search || !!this.filters.status || !!this.filters.tipe;
                },

                // --- TIMEZONE HELPER (BEST PRACTICE) ---
                getLocalISOString() {
                    const now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    return now.toISOString().slice(0, 16);
                },

                getLocalDateString() {
                    return this.getLocalISOString().slice(0, 10);
                },

                // --- INISIALISASI ---
                init() {
                    this.applyFilters();
                    this.$watch('formData.tanggal_sewa', (newDate) => this.autoCalculateDueDate(newDate));
                },

                initFormData() {
                    const isEditing = !!this.selectedSdb?.nama_nasabah;
                    this.formData = {
                        nama_nasabah: this.selectedSdb?.nama_nasabah || '',
                        tanggal_sewa: isEditing ? this.selectedSdb.tanggal_sewa : this.getLocalDateString(),
                        tanggal_jatuh_tempo: this.selectedSdb?.tanggal_jatuh_tempo || '',
                        tipe: this.selectedSdb?.tipe || ''
                    };
                },

                // --- LOGIC FASE 2 ---
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
                    if (!this.visitFormData.nama_pengunjung || !this.visitFormData.waktu_kunjung) {
                        window.showNotification('Nama & Waktu wajib diisi', 'warning');
                        return;
                    }
                    this.isLoading = true;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}/visit`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify(this.visitFormData)
                        });
                        if (response.ok) {
                            window.showNotification('Kunjungan berhasil dicatat', 'success');
                            this.isVisitModalOpen = false;
                        } else {
                            window.showNotification('Gagal menyimpan data', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        window.showNotification('Terjadi kesalahan sistem', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async openHistoryModal() {
                    if (!this.selectedSdb) return;
                    this.isHistoryModalOpen = true;
                    this.isLoadingHistory = true;
                    this.activeHistoryTab = 'sewa';
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}/history`);
                        const result = await response.json();
                        this.historyData = {
                            rental_histories: result.rental_histories || [],
                            visits: result.visits || []
                        };
                    } catch (e) {
                        console.error(e);
                        window.showNotification('Gagal memuat riwayat', 'error');
                    } finally {
                        this.isLoadingHistory = false;
                    }
                },

                closeHistoryModal() {
                    this.isHistoryModalOpen = false;
                    this.historyData = {
                        rental_histories: [],
                        visits: []
                    };
                },

                // --- LOGIC OPERASIONAL UTAMA ---

                // [RESTORED] Fungsi Auto-Select saat Enter ditekan
                async searchAndSelect() {
                    // 1. Pastikan filter diterapkan terbaru
                    await this.applyFilters();

                    if (this.filters.search) {
                        const keyword = this.filters.search.toLowerCase();

                        // Filter manual dari data lokal untuk mencari match
                        // Kita cari unit yang ID-nya ada di filteredUnits
                        const visibleUnits = this.allUnits.filter(u => this.filteredUnits.includes(u.id));

                        // A. Prioritas 1: Mencari yang NOMOR SDB-nya persis sama (Exact Match)
                        const exactMatch = visibleUnits.find(u => u.nomor_sdb.toLowerCase() === keyword);

                        if (exactMatch) {
                            this.showDetail(exactMatch.id);
                        }
                        // B. Prioritas 2: Jika hasil filter cuma tersisa 1, langsung pilih itu
                        else if (visibleUnits.length === 1) {
                            this.showDetail(visibleUnits[0].id);
                        }
                    }
                },

                handleEscape() {
                    if (this.isVisitModalOpen) this.isVisitModalOpen = false;
                    else if (this.isHistoryModalOpen) this.closeHistoryModal();
                    else if (this.isExtendModalOpen) this.isExtendModalOpen = false;
                    else if (this.editMode) this.cancelEdit();
                    else if (this.selectedSdb) this.clearSelection();
                },

                autoCalculateDueDate(newDate) {
                    if (this.editMode && this.selectedSdb?.status !== 'lewat_jatuh_tempo' && newDate) {
                        const date = new Date(newDate);
                        date.setFullYear(date.getFullYear() + 1);
                        this.formData.tanggal_jatuh_tempo = date.toISOString().split('T')[0];
                    }
                },

                async applyFilters() {
                    if (!this.filters.search) this.clearSelection();
                    this.isLoading = true;
                    try {
                        const params = new URLSearchParams(this.filters).toString();
                        const response = await fetch(`/sdb-filtered?${params}`);
                        const data = await response.json();
                        this.allUnits = data.units;
                        this.filteredUnits = data.units.map(u => u.id);
                    } catch (e) {
                        this.applyClientSideFilters();
                    } finally {
                        this.isLoading = false;
                    }
                },

                applyClientSideFilters() {
                    let filtered = this.allUnits.filter(u => {
                        const matchSearch = !this.filters.search || u.nomor_sdb.toLowerCase().includes(this.filters
                            .search.toLowerCase()) || (u.nama_nasabah || '').toLowerCase().includes(this.filters
                            .search.toLowerCase());
                        const matchStatus = !this.filters.status || u.status === this.filters.status;
                        const matchTipe = !this.filters.tipe || u.tipe === this.filters.tipe;
                        return matchSearch && matchStatus && matchTipe;
                    });
                    this.filteredUnits = filtered.map(u => u.id);
                },

                async showDetail(unitId) {
                    if (this.selectedSdb?.id === unitId) {
                        this.clearSelection();
                        return;
                    }
                    try {
                        const response = await fetch(`/sdb/${unitId}`);
                        const result = await response.json();
                        if (result.data) this.selectedSdb = result.data;
                        this.editMode = false;
                    } catch (e) {
                        console.error(e);
                        this.clearSelection();
                    }
                },

                clearSelection() {
                    this.selectedSdb = null;
                    this.editMode = false;
                },

                cancelEdit() {
                    this.editMode = false;
                },

                validateForm() {
                    if (!this.formData.nama_nasabah.trim()) return false;
                    return true;
                },

                async saveData() {
                    if (!this.validateForm()) return;
                    this.isLoading = true;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.formData)
                        });
                        const result = await response.json();
                        if (result.success) {
                            this.updateLocalUnitData(result.data);
                            this.editMode = false;
                            window.showNotification('Disimpan', 'success');
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                updateLocalUnitData(updatedUnit) {
                    const idx = this.allUnits.findIndex(u => u.id === updatedUnit.id);
                    if (idx !== -1) this.allUnits[idx] = {
                        ...this.allUnits[idx],
                        ...updatedUnit
                    };
                    if (this.selectedSdb?.id === updatedUnit.id) this.selectedSdb = {
                        ...this.selectedSdb,
                        ...updatedUnit
                    };
                    this.sdbDataMap[updatedUnit.nomor_sdb] = {
                        ...this.sdbDataMap[updatedUnit.nomor_sdb],
                        ...updatedUnit
                    };
                },

                endRental() {
                    if (this.selectedSdb?.nama_nasabah) this.isEndRentalModalOpen = true;
                },

                async submitEndRental() {
                    this.isLoading = true;
                    this.isEndRentalModalOpen = false;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        const result = await response.json();
                        if (result.success) {
                            this.updateLocalUnitData(result.data);
                            this.clearSelection();
                            window.showNotification('Sewa Berakhir', 'success');
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                extendRental() {
                    if (this.selectedSdb) {
                        this.modalFormData = {
                            nama_nasabah: this.selectedSdb.nama_nasabah,
                            tanggal_mulai_baru: new Date().toISOString().slice(0, 10)
                        };
                        this.isExtendModalOpen = true;
                    }
                },

                async submitExtendRental() {
                    this.isLoading = true;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}/extend-rental`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.modalFormData)
                        });
                        const result = await response.json();
                        if (result.success) {
                            this.updateLocalUnitData(result.data);
                            this.isExtendModalOpen = false;
                            window.showNotification('Diperpanjang', 'success');
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                // --- HELPER VIEW ---

                getExpiryTooltipText(status, days) {
                    if (status === 'kosong' || days === null) return '';
                    if (days < 0) return `${Math.abs(days)} hari lalu`;
                    if (days === 0) return 'Jatuh tempo hari ini';
                    if (days === 1) return 'Jatuh tempo besok';
                    return `${days} hari lagi`;
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
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    });
                },

                getStatusText(status) {
                    return {
                        'kosong': 'Kosong',
                        'terisi': 'Terisi',
                        'akan_jatuh_tempo': 'Akan Jatuh Tempo',
                        'lewat_jatuh_tempo': 'Lewat Jatuh Tempo'
                    } [status] || status;
                },
                getStatusHeaderBadgeClass(status) {
                    return {
                        'terisi': 'bg-white/20 text-white',
                        'akan_jatuh_tempo': 'bg-yellow-100/80 text-yellow-700',
                        'lewat_jatuh_tempo': 'bg-red-500/80 text-white'
                    } [status] || 'bg-white/20 text-white';
                },
                getHeaderGradientClass() {
                    if (!this.selectedSdb) return 'from-blue-600 via-blue-700 to-blue-800 text-white';
                    const map = {
                        'akan_jatuh_tempo': 'from-yellow-400 via-yellow-500 to-yellow-600 text-yellow-950',
                        'lewat_jatuh_tempo': 'from-red-500 via-red-600 to-red-700 text-white'
                    };
                    return map[this.selectedSdb.status] || 'from-blue-600 via-blue-700 to-blue-800 text-white';
                },
                getExpiryText(status, days) {
                    if (status === 'kosong' || days === null) return '';
                    if (days < 0) return `(Lewat ${Math.abs(days)} hari)`;
                    if (days === 0) return '(Jatuh tempo hari ini)';
                    return `(${days} hari lagi)`;
                },
                getTotalUnitsByType(type) {
                    if (!this.sdbLayouts[type]) return 0;
                    return this.sdbLayouts[type].grid.flat().length;
                },
                getFilteredUnitsCount() {
                    return this.filteredUnits.length;
                },
                clearFilters() {
                    this.filters = {
                        search: '',
                        status: '',
                        tipe: ''
                    };
                    this.applyFilters();
                }
            };
        }
    </script>
</x-app-layout>
