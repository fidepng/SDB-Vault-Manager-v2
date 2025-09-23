<x-app-layout>
    <div class="bg-gradient-to-br from-gray-50 to-gray-100" x-data="sdbManager()"
        @keydown.escape.window="handleEscape()">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"> --}}
            <!-- Main Container with Flexbox -->
            <div class="flex items-stretch gap-6 py-6">
                {{-- Hapus 'sticky', 'top-28', dan 'h-full' dari sini --}}
                <div class="w-96 flex-shrink-0">

                    {{-- Pindahkan 'sticky' dan 'top-28' ke div baru ini --}}
                    <div class="sticky top-28">
                        @include('components.sdb-detail-panel')
                    </div>

                </div>

                <div class="flex-1 flex flex-col space-y-8">
                    @include('components.sdb-search-filter')
                    @include('components.sdb-grid')
                </div>
            </div>

            <div x-show="isExtendModalOpen" @keydown.escape.window="isExtendModalOpen = false"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
                <div @click.outside="isExtendModalOpen = false"
                    class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-bold mb-2">Perpanjang Masa Sewa</h3>
                    <p class="text-sm text-gray-600 mb-6">Konfirmasi data dan pilih tanggal mulai baru untuk SDB
                        <strong x-text="selectedSdb?.nomor_sdb"></strong>.
                    </p>

                    {{-- Bagian Nama Nasabah (Bisa Diedit) --}}
                    <div class="mb-4">
                        <label for="modal_nama_nasabah" class="block text-sm font-medium text-gray-700">Nama
                            Nasabah</label>

                        {{-- Tampilan Nama (Mode Normal) --}}
                        <div x-show="!isEditingNameInModal" class="flex items-center justify-between mt-1">
                            {{-- Hapus ID dari elemen <p> ini --}}
                            <p class="font-semibold text-gray-800" x-text="modalFormData.nama_nasabah"></p>
                            <button @click="isEditingNameInModal = true"
                                class="text-xs font-semibold text-blue-600 hover:underline">Edit</button>
                        </div>

                        {{-- Input Nama (Mode Edit) --}}
                        <div x-show="isEditingNameInModal" class="mt-1">
                            {{-- Tambahkan ID yang cocok dengan 'for' pada label --}}
                            <input type="text" id="modal_nama_nasabah" x-model="modalFormData.nama_nasabah"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Nama lengkap nasabah">
                        </div>
                    </div>

                    {{-- Bagian Tanggal Mulai Baru --}}
                    <div class="mb-6">
                        <label for="new_start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai
                            Baru</label>
                        <input type="date" id="new_start_date" x-model="modalFormData.tanggal_mulai_baru"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="flex" :class="isEditingNameInModal ? 'justify-end' : 'justify-between'">
                        <div x-show="isEditingNameInModal" class="flex justify-end gap-3 w-full">
                            <button @click="isEditingNameInModal = false"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Selesai
                                Edit</button>
                        </div>
                        <div x-show="!isEditingNameInModal" class="flex justify-end gap-3 w-full">
                            <button @click="isExtendModalOpen = false"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Batal</button>
                            <button @click="submitExtendRental()"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Simpan
                                Perpanjangan</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Konfirmasi Akhiri Sewa --}}
            <div x-show="isEndRentalModalOpen" @keydown.escape.window="isEndRentalModalOpen = false"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
                <div @click.outside="isEndRentalModalOpen = false"
                    class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md text-center">

                    {{-- Ikon Peringatan --}}
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>

                    {{-- Judul dan Deskripsi --}}
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Akhiri Sewa</h3>
                    <p class="text-sm text-gray-600 mb-1">
                        Anda akan mengakhiri sewa untuk SDB <strong x-text="selectedSdb?.nomor_sdb"></strong>
                        atas nama <strong x-text="selectedSdb?.nama_nasabah"></strong>.
                    </p>
                    <p class="text-sm font-semibold text-red-600 mb-6">
                        Tindakan ini tidak dapat dibatalkan dan semua data penyewa akan dihapus.
                    </p>

                    {{-- Tombol Aksi --}}
                    <div class="flex justify-center gap-4">
                        <button @click="isEndRentalModalOpen = false"
                            class="w-full px-4 py-2.5 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-semibold">
                            Batal
                        </button>
                        <button @click="submitEndRental()"
                            class="w-full px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                            Ya, Akhiri Sewa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Alpine.js Main Script -->
    <script>
        function sdbManager() {
            return {
                selectedSdb: null,
                editMode: false,
                formData: {
                    nama_nasabah: '',
                    tanggal_sewa: '',
                    tanggal_jatuh_tempo: ''
                },
                filters: {
                    search: '',
                    status: '',
                    tipe: ''
                },

                isExtendModalOpen: false,
                isEditingNameInModal: false,
                isEndRentalModalOpen: false,
                modalFormData: {
                    nama_nasabah: '',
                    tanggal_mulai_baru: ''
                },

                newExtendStartDate: new Date().toISOString().slice(0, 10), // Default hari ini

                sdbLayouts: @json($sdbLayouts ?? []),
                sdbDataMap: @json($sdbDataMap ?? []),
                allUnits: @json($allUnits ?? []),
                filteredUnits: @json(collect($allUnits ?? [])->pluck('id')->toArray()),
                isLoading: false,

                get isFilterActive() {
                    return this.filters.search.trim() !== '' || this.filters.status !== '' || this.filters.tipe !== '';
                },

                handleEscape() {
                    if (this.editMode) {
                        this.cancelEdit();
                    } else if (this.selectedSdb) {
                        this.clearSelection();
                    }
                },


                init() {
                    this.applyFilters();

                    this.$watch('formData.tanggal_sewa', (newDate) => {
                        if (this.editMode && this.selectedSdb?.status !== 'lewat_jatuh_tempo') {
                            if (newDate) {
                                const parts = newDate.split('-').map(Number);
                                const startDate = new Date(parts[0], parts[1] - 1, parts[2]);

                                startDate.setFullYear(startDate.getFullYear() + 1);

                                const year = startDate.getFullYear();
                                const month = String(startDate.getMonth() + 1).padStart(2, '0');
                                const day = String(startDate.getDate()).padStart(2, '0');

                                this.formData.tanggal_jatuh_tempo = `${year}-${month}-${day}`;
                            } else {
                                this.formData.tanggal_jatuh_tempo = '';
                            }
                        }
                    });
                },

                async applyFilters() {
                    if (!this.filters.search) {
                        this.clearSelection();
                    }

                    this.isLoading = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.filters.search) params.append('search', this.filters.search);
                        if (this.filters.status) params.append('status', this.filters.status);
                        if (this.filters.tipe) params.append('tipe', this.filters.tipe);

                        const response = await fetch(`/sdb-filtered?${params.toString()}`);
                        if (!response.ok) throw new Error('Failed to fetch filtered data');

                        const data = await response.json();
                        this.allUnits = data.units;
                        this.filteredUnits = data.units.map(unit => unit.id);
                    } catch (error) {
                        console.error('Error applying filters:', error);
                        this.applyClientSideFilters();
                    } finally {
                        this.isLoading = false;
                    }
                },

                applyClientSideFilters() {
                    let filtered = [...this.allUnits];

                    if (this.filters.search) {
                        const search = this.filters.search.toLowerCase();
                        filtered = filtered.filter(unit =>
                            unit.nomor_sdb.toLowerCase().includes(search) ||
                            (unit.nama_nasabah && unit.nama_nasabah.toLowerCase().includes(search))
                        );
                    }
                    if (this.filters.status) {
                        filtered = filtered.filter(unit => unit.status === this.filters.status);
                    }
                    if (this.filters.tipe) {
                        filtered = filtered.filter(unit => unit.tipe === this.filters.tipe);
                    }

                    this.filteredUnits = filtered.map(unit => unit.id);
                },

                clearFilters() {
                    this.filters = {
                        search: '',
                        status: '',
                        tipe: ''
                    };
                    this.applyFilters();
                },

                async searchAndSelect() {
                    const searchTerm = this.filters.search.trim();
                    if (!searchTerm) {
                        return;
                    }
                    await this.applyFilters();
                    const exactMatch = this.allUnits.find(
                        unit => unit.nomor_sdb.toLowerCase() === searchTerm.toLowerCase()
                    );
                    if (exactMatch) {
                        this.showDetail(exactMatch.id);
                    }
                },

                // // Fungsi untuk mendapatkan total semua unit (tidak berubah saat filter)
                // getTotalAllUnits() {
                //     const originalUnits = @json($sdbUnits ?? $allUnits);
                //     return originalUnits.length;
                // },

                // // Fungsi untuk mendapatkan unit yang difilter berdasarkan tipe
                // getFilteredUnitsByType(type) {
                //     const filteredByType = this.allUnits.filter(unit =>
                //         unit.tipe === type && this.filteredUnits.includes(unit.id)
                //     );
                //     return filteredByType.length;
                // },

                // // Update fungsi getTotalUnitsByType yang sudah ada (jika belum ada, tambahkan)
                // getTotalUnitsByType(type) {
                //     const originalUnits = @json($sdbUnits ?? $allUnits);
                //     return originalUnits.filter(unit => unit.tipe === type).length;
                // }

                async showDetail(unitId) {
                    if (this.selectedSdb && this.selectedSdb.id === unitId) {
                        this.clearSelection();
                        return;
                    }
                    try {
                        const response = await fetch(`/sdb/${unitId}`);
                        // Baris ini sudah cukup untuk menangani error server
                        if (!response.ok) {
                            throw new Error('Gagal mengambil data SDB dari server');
                        }

                        const result = await response.json();

                        // --- PERUBAHAN UTAMA DI SINI ---
                        // Kita tidak lagi memeriksa 'result.success'. Cukup periksa keberadaan 'result.data'.
                        if (result.data) {
                            this.selectedSdb = result.data;
                        } else {
                            // Error ini akan dilempar jika format JSON tidak memiliki key 'data'
                            throw new Error('Format data SDB tidak valid dari server');
                        }
                        // --- BATAS PERUBAHAN ---

                        this.editMode = false;
                    } catch (error) {
                        console.error('Error fetching SDB detail:', error);
                        window.showNotification(error.message || 'Gagal mengambil data SDB', 'error');
                        this.clearSelection();
                    }
                },

                clearSelection() {
                    this.selectedSdb = null;
                    this.editMode = false;
                },

                initFormData() {
                    // Cek apakah ini mode edit berdasarkan keberadaan nama nasabah
                    const isEditing = !!this.selectedSdb?.nama_nasabah;

                    // Jika mode edit, gunakan tanggal sewa yang ada (yang sekarang formatnya sudah benar).
                    // Jika mode tambah baru, gunakan tanggal hari ini.
                    const defaultTanggalSewa = isEditing ?
                        this.selectedSdb.tanggal_sewa :
                        new Date().toISOString().slice(0, 10);

                    this.formData = {
                        nama_nasabah: this.selectedSdb?.nama_nasabah || '',
                        tanggal_sewa: defaultTanggalSewa,
                        // Kita biarkan jatuh tempo dihitung oleh $watch atau backend
                        tanggal_jatuh_tempo: this.selectedSdb?.tanggal_jatuh_tempo || '',
                        tipe: this.selectedSdb?.tipe || ''
                    };
                },

                cancelEdit() {
                    this.editMode = false;
                    this.formData = {
                        nama_nasabah: '',
                        tanggal_sewa: '',
                        tanggal_jatuh_tempo: ''
                    };
                },

                validateForm() {
                    if (!this.formData.nama_nasabah.trim()) {
                        window.showNotification('Nama nasabah harus diisi', 'warning');
                        return false;
                    }
                    if (!this.formData.tanggal_sewa) {
                        window.showNotification('Tanggal sewa harus diisi', 'warning');
                        return false;
                    }
                    if (!this.formData.tanggal_jatuh_tempo) {
                        window.showNotification('Tanggal jatuh tempo harus diisi', 'warning');
                        return false;
                    }
                    return true;
                },

                updateLocalUnitData(updatedUnit) {
                    // --- MATA-MATA ---
                    // console.log("Fungsi updateLocalUnitData dipanggil dengan data:", updatedUnit);
                    // console.log("Nomor SDB yang akan diupdate:", updatedUnit.nomor_sdb);
                    // console.log("Data LAMA di map:", this.sdbDataMap[updatedUnit.nomor_sdb]);
                    // -----------------

                    const index = this.allUnits.findIndex(u => u.id === updatedUnit.id);
                    if (index !== -1) {
                        this.allUnits[index] = {
                            ...this.allUnits[index],
                            ...updatedUnit
                        };
                    }

                    if (this.selectedSdb && this.selectedSdb.id === updatedUnit.id) {
                        this.selectedSdb = {
                            ...this.selectedSdb,
                            ...updatedUnit
                        };
                    }

                    const newMap = {
                        ...this.sdbDataMap
                    };
                    newMap[updatedUnit.nomor_sdb] = {
                        ...newMap[updatedUnit.nomor_sdb],
                        ...updatedUnit
                    };
                    this.sdbDataMap = newMap;

                    // --- MATA-MATA ---
                    // console.log("Data BARU di map:", this.sdbDataMap[updatedUnit.nomor_sdb]);
                    // -----------------
                },

                formatDate(dateString) {
                    if (!dateString || typeof dateString !== 'string') {
                        return '—';
                    }
                    const parts = dateString.split('-');
                    if (parts.length !== 3) {
                        return '—';
                    }
                    const year = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10) - 1;
                    const day = parseInt(parts[2], 10);
                    if (isNaN(year) || isNaN(month) || isNaN(day)) {
                        return '—';
                    }
                    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                        'September', 'Oktober', 'November', 'Desember'
                    ];
                    const date = new Date(year, month, day);
                    return `${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
                },

                getExpiryText(status, days) {
                    // Jangan tampilkan apa-apa jika status kosong atau data tidak valid
                    if (status === 'kosong' || days === null) {
                        return '';
                    }

                    if (days < 0) {
                        return `(Lewat ${Math.abs(days)} hari)`;
                    }
                    if (days === 0) {
                        return '(Jatuh tempo hari ini)';
                    }
                    if (days === 1) {
                        return '(Jatuh tempo besok)';
                    }
                    // Untuk status 'terisi' atau 'akan_jatuh_tempo'
                    return `(${days} hari lagi)`;
                },

                getExpiryTooltipText(status, days) {
                    // Jangan tampilkan apa-apa jika status kosong atau data tidak valid
                    if (status === 'kosong' || days === null) {
                        return '';
                    }

                    if (days < 0) {
                        return `${Math.abs(days)} hari lalu`;
                    }
                    if (days === 0) {
                        return 'Jatuh tempo hari ini';
                    }
                    if (days === 1) {
                        return 'Jatuh tempo besok';
                    }
                    // Untuk status 'terisi' atau 'akan_jatuh_tempo'
                    return `${days} hari lagi`;
                },

                async saveData() {
                    if (!this.validateForm()) {
                        return;
                    }
                    this.isLoading = true;
                    try {
                        const url = `/sdb/${this.selectedSdb.id}`;
                        const response = await fetch(url, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify(this.formData)
                        });

                        const result = await response.json();
                        if (response.ok && result.success) {
                            window.showNotification('Data berhasil disimpan!', 'success');
                            this.updateLocalUnitData(result.data);
                            this.editMode = false;
                        } else {
                            window.showNotification(result.message || 'Gagal menyimpan data', 'error');
                        }
                    } catch (error) {
                        console.error('Error saving data:', error);
                        window.showNotification('Terjadi kesalahan koneksi saat menyimpan data', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                // GANTI FUNGSI LAMA INI...
                endRental() {
                    if (this.selectedSdb?.nama_nasabah) {
                        this.isEndRentalModalOpen = true;
                    }
                },

                // Fungsi ini untuk menjalankan aksi setelah dikonfirmasi di modal
                async submitEndRental() {
                    this.isLoading = true;
                    this.isEndRentalModalOpen = false; // Langsung tutup modal
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        });
                        const result = await response.json();
                        if (response.ok && result.success) {
                            window.showNotification('Sewa berhasil diakhiri!', 'success');
                            // Ganti 'updateLocalUnitData' dengan 'clearSelection' agar panel tertutup
                            this.updateLocalUnitData(result.data);
                            this.clearSelection();
                        } else {
                            window.showNotification(result.message || 'Gagal mengakhiri sewa', 'error');
                        }
                    } catch (error) {
                        console.error('Error ending rental:', error);
                        window.showNotification('Terjadi kesalahan koneksi saat mengakhiri sewa', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                extendRental() {
                    if (this.selectedSdb) {
                        // Isi modalFormData dengan data terbaru saat modal dibuka
                        this.modalFormData.nama_nasabah = this.selectedSdb.nama_nasabah;
                        this.modalFormData.tanggal_mulai_baru = new Date().toISOString().slice(0, 10);

                        this.isEditingNameInModal = false; // Selalu reset ke mode non-edit
                        this.isExtendModalOpen = true;
                    }
                },

                // Ganti fungsi submitExtendRental() yang lama dengan ini
                async submitExtendRental() {
                    // Validasi sederhana di frontend
                    if (!this.modalFormData.tanggal_mulai_baru || !this.modalFormData.nama_nasabah.trim()) {
                        window.showNotification('Nama Nasabah dan Tanggal Mulai Baru harus diisi.', 'warning');
                        return;
                    }

                    this.isLoading = true;
                    try {
                        const response = await fetch(`/sdb/${this.selectedSdb.id}/extend-rental`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            // Kirim seluruh objek modalFormData
                            body: JSON.stringify(this.modalFormData)
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            window.showNotification('Masa sewa berhasil diperpanjang!', 'success');
                            this.updateLocalUnitData(result.data); // Update UI
                            this.isExtendModalOpen = false; // Tutup modal
                        } else {
                            window.showNotification(result.message || 'Gagal memperpanjang sewa', 'error');
                        }

                    } catch (error) {
                        console.error('Error extending rental:', error);
                        window.showNotification('Terjadi kesalahan koneksi.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                getStatusText(status) {
                    const statusMap = {
                        'kosong': 'Kosong',
                        'terisi': 'Terisi',
                        'akan_jatuh_tempo': 'Akan Jatuh Tempo',
                        'lewat_jatuh_tempo': 'Lewat Jatuh Tempo'
                    };
                    return statusMap[status] || 'Unknown';
                },

                getStatusBadgeClass(status) {
                    const classMap = {
                        'kosong': 'bg-gray-100 text-gray-800 border border-gray-300',
                        'terisi': 'bg-blue-100 text-blue-800 border border-blue-300',
                        'akan_jatuh_tempo': 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                        'lewat_jatuh_tempo': 'bg-red-100 text-red-800 border border-red-300'
                    };
                    return classMap[status] || 'bg-gray-100 text-gray-800 border border-gray-300';
                },

                // --- TAMBAHKAN FUNGSI BARU DI BAWAH INI ---
                getStatusHeaderBadgeClass(status) {
                    const classMap = {
                        'terisi': 'bg-white/20 text-white',
                        'akan_jatuh_tempo': 'bg-yellow-100/80 text-yellow-700',
                        'lewat_jatuh_tempo': 'bg-red-500/80 text-white'
                    };
                    return classMap[status] || 'bg-white/20 text-white';
                },

                getHeaderGradientClass() {
                    // Jika tidak ada SDB yang dipilih, gunakan warna biru default
                    if (!this.selectedSdb) {
                        return 'from-blue-600 via-blue-700 to-blue-800 text-white';
                    }

                    const status = this.selectedSdb.status;
                    const classMap = {
                        'akan_jatuh_tempo': 'from-yellow-400 via-yellow-500 to-yellow-600 text-yellow-950',
                        'lewat_jatuh_tempo': 'from-red-500 via-red-600 to-red-700 text-white',
                    };

                    // Untuk status 'kosong' dan 'terisi', kembalikan warna biru default
                    return classMap[status] || 'from-blue-600 via-blue-700 to-blue-800 text-white';
                },

                // getHeaderGradientClass() {
                //     // Jika tidak ada SDB yang dipilih, gunakan warna biru default
                //     if (!this.selectedSdb) {
                //         return 'from-blue-600 via-blue-700 to-blue-800';
                //     }

                //     const status = this.selectedSdb.status;
                //     const classMap = {
                //         'akan_jatuh_tempo': 'from-yellow-500 via-yellow-600 to-yellow-700',
                //         'lewat_jatuh_tempo': 'from-red-600 via-red-700 to-red-800',
                //     };

                //     // Untuk status 'kosong' dan 'terisi', kembalikan warna biru default
                //     return classMap[status] || 'from-blue-600 via-blue-700 to-blue-800';
                // },

                getFilteredUnitsCount() {
                    return this.filteredUnits.length;
                },
                getTotalUnitsByType(type) {
                    // OPSI 1: Perbaikan Langsung (sudah benar)
                    // Menggunakan data 'allUnits' yang sudah ada di properti Alpine.js.
                    // return this.allUnits.filter(unit => unit.tipe === type).length;

                    // OPSI 2: Best Practice (Lebih Cepat & Efisien)
                    // Kita tidak perlu memfilter array besar. Jumlah total unit per tipe
                    // sudah bisa kita ketahui dari data layout yang dikirim controller.
                    // Ini jauh lebih ringan untuk browser.
                    if (!this.sdbLayouts[type]) return 0;
                    return this.sdbLayouts[type].grid.flat().length;
                },

                needsAction() {
                    return this.selectedSdb?.status === 'lewat_jatuh_tempo';
                },
                isApproachingDue() {
                    return this.selectedSdb?.status === 'akan_jatuh_tempo';
                }
            };
        }
    </script>
</x-app-layout>
