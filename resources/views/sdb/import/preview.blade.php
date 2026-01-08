<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {{-- HEADER WITH FILE INFO --}}
        <div class="mb-8">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-3xl font-extrabold text-gray-900 flex items-center">
                        <div class="bg-yellow-100 p-3 rounded-xl mr-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <span>Preview Import Data</span>
                            @if (isset($results['metadata']))
                                <p class="text-sm text-gray-500 font-normal mt-1">
                                    File: <span
                                        class="font-mono font-semibold">{{ $results['metadata']['filename'] }}</span>
                                </p>
                            @endif
                        </div>
                    </h1>
                    <p class="text-gray-500 mt-2">Sistem telah menganalisis file Anda. Periksa ringkasan sebelum
                        eksekusi.</p>

                    @if (isset($results['metadata']))
                        <div class="mt-3 flex items-center gap-4 text-xs text-gray-600">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $results['metadata']['uploaded_at'] }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ $results['metadata']['uploaded_by'] }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                {{ $results['metadata']['filesize'] }}
                            </span>
                        </div>
                    @endif
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('dashboard') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div
                class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl shadow-lg border border-blue-200 p-6 transform hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-blue-600 uppercase tracking-wide">Total Baris</p>
                        <p class="text-4xl font-bold text-blue-900 mt-2">{{ $results['total'] }}</p>
                    </div>
                    <div class="bg-blue-500 p-3 rounded-full shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl shadow-lg border border-green-200 p-6 transform hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-green-600 uppercase tracking-wide">Sewa Baru</p>
                        <p class="text-4xl font-bold text-green-900 mt-2">{{ count($results['new']) }}</p>
                    </div>
                    <div class="bg-green-500 p-3 rounded-full shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-2xl shadow-lg border border-yellow-200 p-6 transform hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-yellow-600 uppercase tracking-wide">Koreksi Data</p>
                        <p class="text-4xl font-bold text-yellow-900 mt-2">{{ count($results['update']) }}</p>
                    </div>
                    <div class="bg-yellow-500 p-3 rounded-full shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-red-50 to-red-100 rounded-2xl shadow-lg border border-red-200 p-6 transform hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-red-600 uppercase tracking-wide">Error</p>
                        <p class="text-4xl font-bold text-red-900 mt-2">{{ count($results['errors']) }}</p>
                    </div>
                    <div class="bg-red-500 p-3 rounded-full shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ERROR SECTION --}}
        @if (count($results['errors']) > 0)
            <div
                class="bg-gradient-to-r from-red-50 to-red-100 border-2 border-red-300 rounded-2xl p-6 mb-8 shadow-xl">
                <div class="flex items-start">
                    <div class="bg-red-500 p-3 rounded-full mr-4 shadow-lg animate-pulse">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-red-900 font-extrabold text-xl mb-2">⛔ File Mengandung Error</h3>
                        <p class="text-red-700 text-sm mb-4">
                            Ditemukan <strong>{{ count($results['errors']) }} error</strong> yang harus diperbaiki
                            sebelum import dapat dilanjutkan.
                        </p>

                        <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                            @foreach ($results['errors'] as $index => $error)
                                <div
                                    class="bg-white rounded-lg border-l-4 border-red-500 p-4 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex items-start">
                                        <div
                                            class="flex-shrink-0 bg-red-100 rounded-full w-8 h-8 flex items-center justify-center mr-3">
                                            <span class="text-red-700 font-bold text-sm">{{ $index + 1 }}</span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs text-gray-500 font-semibold mb-1">
                                                📍 Baris {{ $error['row'] }}
                                                @if ($error['nomor_sdb'] !== 'N/A')
                                                    • SDB: <span
                                                        class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ $error['nomor_sdb'] }}</span>
                                                @endif
                                            </p>
                                            <p class="text-sm text-red-800 leading-relaxed">{{ $error['error'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex gap-3">
                            <a href="{{ route('dashboard') }}"
                                class="flex-1 px-6 py-3 bg-white border-2 border-red-300 text-red-700 rounded-xl font-bold text-center hover:bg-red-50 transition-colors shadow-sm">
                                🔙 Kembali ke Dashboard
                            </a>
                            <button onclick="window.print()"
                                class="px-6 py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition-colors shadow-lg flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak Error
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- SUCCESS: Show Details & Confirmation ONLY IF CHANGES EXIST --}}

            {{-- NEW RENTALS TABLE --}}
            @if (count($results['new']) > 0)
                <div class="bg-white rounded-2xl shadow-xl border-2 border-green-200 p-6 mb-8">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <span class="bg-green-500 p-2 rounded-xl mr-3 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </span>
                            <span>Sewa Baru ({{ count($results['new']) }} unit)</span>
                        </h3>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">✨ Data
                            Baru</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-green-500 to-green-600">
                                <tr>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        No</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Nomor SDB</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Tipe</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Nama Nasabah</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Tanggal Sewa</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Jatuh Tempo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach ($results['new'] as $index => $item)
                                    <tr class="hover:bg-green-50 transition-colors">
                                        <td class="px-5 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-5 py-4">
                                            <span
                                                class="text-sm font-mono font-bold text-green-700 bg-green-50 px-2 py-1 rounded">
                                                {{ $item['data']['nomor_sdb'] }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span
                                                class="px-2 py-1 bg-blue-100 text-blue-700 rounded font-semibold text-xs">
                                                {{ $item['data']['tipe'] }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-gray-900 font-medium">
                                            {{ $item['data']['nama_nasabah'] }}</td>
                                        <td class="px-5 py-4 text-sm text-gray-600">
                                            {{ \Carbon\Carbon::parse($item['data']['tanggal_sewa'])->format('d/m/Y') }}
                                        </td>
                                        <td class="px-5 py-4 text-sm text-gray-600">
                                            {{ \Carbon\Carbon::parse($item['data']['tanggal_jatuh_tempo'])->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- CORRECTIONS TABLE --}}
            @if (count($results['update']) > 0)
                <div class="bg-white rounded-2xl shadow-xl border-2 border-yellow-200 p-6 mb-8">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <span class="bg-yellow-500 p-2 rounded-xl mr-3 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </span>
                            <span>Koreksi Data ({{ count($results['update']) }} unit)</span>
                        </h3>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-semibold">📝
                            Update</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-yellow-500 to-yellow-600">
                                <tr>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        No</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Nomor SDB</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Field</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Nilai Lama</th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Nilai Baru</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach ($results['update'] as $itemIndex => $item)
                                    @foreach ($item['changes'] as $field => $change)
                                        <tr class="hover:bg-yellow-50 transition-colors">
                                            <td class="px-5 py-4 text-sm text-gray-500">
                                                @if ($loop->first)
                                                    {{ $itemIndex + 1 }}
                                                @endif
                                            </td>
                                            <td class="px-5 py-4">
                                                @if ($loop->first)
                                                    <span
                                                        class="text-sm font-mono font-bold text-yellow-700 bg-yellow-50 px-2 py-1 rounded">
                                                        {{ $item['data']['nomor_sdb'] }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-700 font-medium capitalize">
                                                {{ str_replace('_', ' ', $field) }}
                                            </td>
                                            <td class="px-5 py-4 text-sm">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 rounded-lg font-mono text-xs">
                                                    {{ $change['old'] }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-sm">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-lg font-mono text-xs">
                                                    {{ $change['new'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- CONFIRMATION SECTION --}}
            <div class="bg-gradient-to-br from-red-50 to-red-100 border-4 border-red-500 rounded-3xl p-8 mb-8 shadow-2xl"
                x-data="{
                    confirmation: '',
                    isValid: false,
                    sessionExpiry: {{ session('import_timestamp') ? session('import_timestamp')->addMinutes(30)->timestamp * 1000 : 'null' }},
                    timeRemaining: '',
                    updateTimer() {
                        if (!this.sessionExpiry) return;
                        const now = Date.now();
                        const diff = this.sessionExpiry - now;
                        if (diff <= 0) {
                            this.timeRemaining = 'Session Kadaluarsa!';
                            return;
                        }
                        const minutes = Math.floor(diff / 60000);
                        const seconds = Math.floor((diff % 60000) / 1000);
                        this.timeRemaining = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    }
                }" x-init="$watch('confirmation', value => isValid = value === 'SAYA YAKIN');
                updateTimer();
                setInterval(() => updateTimer(), 1000);">

                <div class="flex items-start mb-6">
                    <div class="bg-red-600 p-4 rounded-2xl mr-5 shadow-lg animate-pulse">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-2xl font-extrabold text-red-900 mb-2">⚠️ Peringatan: Aksi Tidak Dapat
                            Dibatalkan</h3>
                        <p class="text-red-800 text-base leading-relaxed">
                            Data yang sudah diimport akan <strong>langsung mengubah database</strong>.
                            Pastikan Anda telah memeriksa semua detail dengan <strong>sangat teliti</strong>.
                        </p>
                        <div class="mt-3 bg-white bg-opacity-50 rounded-lg p-3">
                            <p class="text-sm text-red-700 font-semibold">
                                ⏱️ Session akan kadaluarsa dalam:
                                <span x-text="timeRemaining" class="font-mono text-red-900"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('import.execute') }}" method="POST">
                    @csrf
                    <div class="bg-white rounded-2xl p-6 mb-6 border-3 border-red-400 shadow-inner">
                        <label class="block text-sm font-bold text-gray-800 mb-3">
                            Untuk melanjutkan, ketik: <span class="text-red-600 font-mono text-lg">"SAYA YAKIN"</span>
                        </label>
                        <input type="text" name="confirmation" x-model="confirmation"
                            class="w-full px-5 py-4 border-3 border-gray-300 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-200 font-mono text-lg transition-all"
                            placeholder="Ketik: SAYA YAKIN" autocomplete="off" autofocus>
                        <p class="text-xs text-gray-500 mt-2">* Huruf kapital semua, tanpa tanda kutip</p>
                        @error('confirmation')
                            <p class="text-red-600 text-sm mt-2 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-4">
                        <a href="{{ route('import.cancel') }}"
                            class="flex-1 px-8 py-4 bg-white border-3 border-gray-300 text-gray-700 rounded-xl font-bold text-center hover:bg-gray-100 transition-colors shadow-lg text-lg">
                            ❌ Batalkan Import
                        </a>
                        <button type="submit" :disabled="!isValid"
                            class="flex-1 px-8 py-4 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-2xl shadow-red-500/50 transition-all disabled:opacity-40 disabled:cursor-not-allowed text-lg"
                            :class="{ 'opacity-40 cursor-not-allowed': !isValid, 'animate-pulse': isValid }">
                            <span x-show="!isValid">🔒 Konfirmasi Diperlukan</span>
                            <span x-show="isValid">✅ Eksekusi Import Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
