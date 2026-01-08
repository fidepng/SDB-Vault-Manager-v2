<div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-900 flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <span>Pencarian & Filter</span>
        </h3>

        {{-- ACTION BUTTONS ROW --}}
        <div class="flex items-center gap-3">
            {{-- 
                CRITICAL FIX: Export button now accesses Alpine data directly
                Uses @click with Alpine's scope to get current filter state
            --}}
            <button @click="exportWithCurrentFilters()"
                class="group flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition-all shadow-lg shadow-green-500/30"
                title="Export data ke Excel">
                <svg class="w-5 h-5 group-hover:animate-bounce" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span class="hidden sm:inline">Export</span>
            </button>

            {{-- IMPORT BUTTON (Super Admin Only) --}}
            @if (Auth::user() && Auth::user()->role === 'super_admin')
                <div x-data="{
                    isUploading: false,
                    fileName: '',
                    handleFileSelect(event) {
                        const file = event.target.files[0];
                        if (file) {
                            this.fileName = file.name;
                            this.isUploading = true;
                
                            if (file.size > 5242880) {
                                alert('File terlalu besar! Maksimal 5MB.');
                                event.target.value = '';
                                this.isUploading = false;
                                this.fileName = '';
                                return;
                            }
                
                            const validTypes = ['.xlsx', '.xls', '.csv'];
                            const fileExt = '.' + file.name.split('.').pop().toLowerCase();
                            if (!validTypes.includes(fileExt)) {
                                alert('Format file tidak valid! Gunakan .xlsx, .xls, atau .csv');
                                event.target.value = '';
                                this.isUploading = false;
                                this.fileName = '';
                                return;
                            }
                
                            $refs.importForm.submit();
                        }
                    }
                }">
                    <button @click="$refs.importFileInput.click()" :disabled="isUploading"
                        class="group flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="!isUploading" class="w-5 h-5 group-hover:animate-bounce" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L9 8m4-4v12"></path>
                        </svg>
                        <svg x-show="isUploading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"
                            x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span class="hidden sm:inline" x-text="isUploading ? 'Mengupload...' : 'Import'"></span>
                    </button>

                    <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data"
                        id="importForm" x-ref="importForm">
                        @csrf
                        <input type="file" name="file" x-ref="importFileInput" accept=".xlsx,.xls,.csv"
                            @change="handleFileSelect($event)" class="hidden">
                    </form>
                </div>
            @endif

            {{-- RESET BUTTON --}}
            <button @click="clearFilters()"
                class="text-gray-500 hover:text-red-600 text-sm font-medium flex items-center transition-colors duration-200"
                :class="(filters.search || filters.status || filters.tipe) ? 'opacity-100' : 'opacity-50 cursor-not-allowed'">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                <span class="hidden sm:inline">Reset</span>
            </button>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- ERROR & SUCCESS MESSAGES SECTION            --}}
    {{-- ============================================ --}}

    {{-- Import Success Message --}}
    @if (session('success') && session('import_success_details'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm animate-fade-in">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-green-700 font-bold">{{ session('success') }}</p>
                    <div class="mt-2 text-sm text-green-600">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Total diproses: <strong>{{ session('import_success_details')['total'] }}</strong></li>
                            <li>Sewa baru: <strong>{{ session('import_success_details')['new'] }}</strong></li>
                            <li>Data dikoreksi: <strong>{{ session('import_success_details')['updated'] }}</strong></li>
                        </ul>
                    </div>
                </div>
                <div class="ml-3">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()"
                        class="text-green-500 hover:text-green-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import Error Message (Enhanced) --}}
    @if (session('import_error'))
        @php
            $errorType = session('import_error_type', 'validation');
            $errorColors = [
                'validation' => [
                    'bg' => 'bg-red-50',
                    'border' => 'border-red-500',
                    'text' => 'text-red-700',
                    'icon' => 'text-red-400',
                ],
                'structure' => [
                    'bg' => 'bg-orange-50',
                    'border' => 'border-orange-500',
                    'text' => 'text-orange-700',
                    'icon' => 'text-orange-400',
                ],
                'system' => [
                    'bg' => 'bg-yellow-50',
                    'border' => 'border-yellow-500',
                    'text' => 'text-yellow-700',
                    'icon' => 'text-yellow-400',
                ],
                'expired' => [
                    'bg' => 'bg-blue-50',
                    'border' => 'border-blue-500',
                    'text' => 'text-blue-700',
                    'icon' => 'text-blue-400',
                ],
                'execution' => [
                    'bg' => 'bg-purple-50',
                    'border' => 'border-purple-500',
                    'text' => 'text-purple-700',
                    'icon' => 'text-purple-400',
                ],
            ];
            $colors = $errorColors[$errorType] ?? $errorColors['validation'];
        @endphp

        <div class="mb-6 {{ $colors['bg'] }} border-l-4 {{ $colors['border'] }} p-5 rounded-r shadow-md animate-shake">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 {{ $colors['icon'] }}" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="{{ $colors['text'] }} font-bold text-base mb-1">
                        @switch($errorType)
                            @case('validation')
                                ❌ Validasi File Gagal
                            @break

                            @case('structure')
                                ⚠️ Struktur File Tidak Valid
                            @break

                            @case('system')
                                🔧 Kesalahan Sistem
                            @break

                            @case('expired')
                                ⏱️ Sesi Kadaluarsa
                            @break

                            @case('execution')
                                ⛔ Eksekusi Import Gagal
                            @break

                            @default
                                ❌ Import Gagal
                        @endswitch
                    </h3>
                    <p class="{{ $colors['text'] }} text-sm leading-relaxed">{{ session('import_error') }}</p>

                    {{-- Execution Errors Detail --}}
                    @if ($errorType === 'execution' && session('import_execution_errors'))
                        <div class="mt-3 space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                            <p class="{{ $colors['text'] }} text-xs font-semibold">Detail Error:</p>
                            @foreach (session('import_execution_errors') as $execError)
                                <div class="bg-white p-2 rounded border {{ $colors['border'] }} border-opacity-30">
                                    <p class="text-xs font-mono {{ $colors['text'] }}">
                                        <strong>Baris {{ $execError['row'] }}</strong> (SDB:
                                        {{ $execError['nomor_sdb'] }}):
                                        {{ $execError['error'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Helpful Tips --}}
                    <div
                        class="mt-3 p-3 bg-white bg-opacity-60 rounded border {{ $colors['border'] }} border-opacity-30">
                        <p class="{{ $colors['text'] }} text-xs font-semibold mb-2">💡 Solusi:</p>
                        <ul class="{{ $colors['text'] }} text-xs space-y-1 list-disc list-inside">
                            @if ($errorType === 'validation')
                                <li>Pastikan file berformat .xlsx, .xls, atau .csv</li>
                                <li>Ukuran file maksimal 5MB</li>
                                <li>Periksa struktur kolom sesuai template</li>
                            @elseif($errorType === 'structure')
                                <li>Download template Excel dari sistem</li>
                                <li>Pastikan header kolom: NOMOR_SDB, TIPE, NAMA_NASABAH, dll.</li>
                                <li>Jangan ubah nama kolom header</li>
                            @elseif($errorType === 'expired')
                                <li>Session import maksimal 30 menit</li>
                                <li>Silakan upload file kembali</li>
                            @else
                                <li>Periksa kembali data di file Excel</li>
                                <li>Pastikan format tanggal: YYYY-MM-DD atau DD/MM/YYYY</li>
                                <li>Hubungi administrator jika masalah berlanjut</li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="ml-3">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()"
                        class="{{ $colors['text'] }} hover:opacity-70 transition-opacity">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add this section AFTER the success/error messages in sdb-search-filter.blade.php --}}

    {{-- Import Info Message (No Changes Detected) --}}
    @if (session('import_info'))
        <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-5 rounded-r shadow-md">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-blue-700 font-bold mb-2">{{ session('import_info') }}</p>

                    @if (session('import_info_details'))
                        <div class="mt-3 p-3 bg-white bg-opacity-60 rounded border border-blue-200">
                            <p class="text-xs text-blue-700 font-semibold mb-2">📊 Statistik File:</p>
                            <ul class="text-xs text-blue-600 space-y-1">
                                <li>• Total baris diproses:
                                    <strong>{{ session('import_info_details')['total_rows'] }}</strong>
                                </li>
                                <li>• Data yang di-skip:
                                    <strong>{{ session('import_info_details')['skipped'] }}</strong>
                                </li>
                                <li class="mt-2 text-blue-700 italic">{{ session('import_info_details')['message'] }}
                                </li>
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4 p-3 bg-blue-100 rounded border border-blue-300">
                        <p class="text-xs text-blue-800 font-semibold mb-1">💡 Apa yang harus dilakukan?</p>
                        <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside">
                            <li>Jika ingin menambah data baru, tambahkan baris dengan <strong>nama nasabah
                                    terisi</strong></li>
                            <li>Jika ingin mengubah data, pastikan ada <strong>perbedaan</strong> dengan database</li>
                            <li>File Excel Anda sudah benar, hanya tidak ada yang perlu di-update saat ini</li>
                        </ul>
                    </div>
                </div>
                <div class="ml-3">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()"
                        class="text-blue-500 hover:text-blue-700 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- SEARCH INPUT & FILTERS --}}
    <div class="space-y-6">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="search" x-model="filters.search" @input.debounce.300ms="applyFilters()"
                @change="applyFilters()" @keydown.enter.prevent="searchAndSelect()"
                placeholder="Cari berdasarkan nama atau nomor SDB (Enter untuk memilih)"
                class="block w-full pl-12 pr-12 py-3 text-base border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-gray-50 focus:bg-white">

            <div x-show="filters.search" x-transition class="absolute inset-y-0 right-0 pr-4 flex items-center">
                <button @click="filters.search = ''; applyFilters()"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="bg-gray-50 rounded-2xl p-4 flex flex-wrap items-center gap-6">
            {{-- Status Filter --}}
            <div class="flex items-center gap-3">
                <label class="text-sm font-semibold text-gray-700 whitespace-nowrap">Status:</label>
                <div class="flex gap-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="" @change="applyFilters()"
                            class="sr-only" name="status_filter">
                        <div class="px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === '' ? 'bg-gray-700 text-white' :
                                'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            Semua</div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="kosong" @change="applyFilters()"
                            class="sr-only" name="status_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === 'kosong' ? 'bg-gray-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            Kosong</div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="terisi" @change="applyFilters()"
                            class="sr-only" name="status_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === 'terisi' ? 'bg-blue-500 text-white' :
                                'bg-blue-100 text-blue-700 hover:bg-blue-200'">
                            Terisi</div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="akan_jatuh_tempo"
                            @change="applyFilters()" class="sr-only" name="status_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === 'akan_jatuh_tempo' ? 'bg-yellow-500 text-white' :
                                'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'">
                            Akan Jatuh Tempo</div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="lewat_jatuh_tempo"
                            @change="applyFilters()" class="sr-only" name="status_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === 'lewat_jatuh_tempo' ? 'bg-red-500 text-white' :
                                'bg-red-100 text-red-700 hover:bg-red-200'">
                            Lewat Jatuh Tempo</div>
                    </label>
                </div>
            </div>

            {{-- Tipe Filter --}}
            <div class="flex items-center gap-3">
                <label class="text-sm font-semibold text-gray-700 whitespace-nowrap">Tipe:</label>
                <div class="flex gap-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.tipe" value="" @change="applyFilters()"
                            class="sr-only" name="tipe_filter">
                        <div class="px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.tipe === '' ? 'bg-gray-700 text-white' :
                                'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            Semua</div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.tipe" value="B" @change="applyFilters()"
                            class="sr-only" name="tipe_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.tipe === 'B' ? 'bg-blue-500 text-white' :
                                'bg-blue-100 text-blue-700 hover:bg-blue-200'">
                            Tipe B</div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.tipe" value="C" @change="applyFilters()"
                            class="sr-only" name="tipe_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.tipe === 'C' ? 'bg-indigo-500 text-white' :
                                'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'">
                            Tipe C</div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        10%,
        30%,
        50%,
        70%,
        90% {
            transform: translateX(-5px);
        }

        20%,
        40%,
        60%,
        80% {
            transform: translateX(5px);
        }
    }

    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-shake {
        animation: shake 0.5s ease-in-out;
    }

    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }

    /* Custom scrollbar */
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.5);
        border-radius: 20px;
    }
</style>
