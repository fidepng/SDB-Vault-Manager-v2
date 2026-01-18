<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Audit Trail Sistem') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Total {{ number_format($statistics['total_logs']) }} log aktivitas tercatat
                </p>
            </div>

            {{-- Quick Stats --}}
            <div class="flex gap-4">
                <div class="bg-blue-50 px-4 py-2 rounded-lg border border-blue-200">
                    <p class="text-xs text-blue-600 font-semibold">Hari Ini</p>
                    <p class="text-lg font-bold text-blue-700">{{ $statistics['logs_today'] }}</p>
                </div>
                <div class="bg-green-50 px-4 py-2 rounded-lg border border-green-200">
                    <p class="text-xs text-green-600 font-semibold">Minggu Ini</p>
                    <p class="text-lg font-bold text-green-700">{{ $statistics['logs_this_week'] }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- SECTION 1: FILTER & PENCARIAN (IMPROVED) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('audit-logs.index') }}" id="filterForm">
                        <div class="space-y-6">

                            {{-- ROW 1: Search + User Filter --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                {{-- Search Input --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                                        Pencarian
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" name="search" value="{{ request('search') }}"
                                            placeholder="Cari nama user, kegiatan, deskripsi, IP, nomor SDB..."
                                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 pl-10 pr-10">

                                        @if (request('search'))
                                            <button type="button"
                                                onclick="document.querySelector('input[name=search]').value=''; document.getElementById('filterForm').submit();"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- User Filter --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                                        Filter User
                                    </label>
                                    <select name="user_id"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                                        <option value="">Semua User</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- ROW 2: Kegiatan + Date Range --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                {{-- Kegiatan Filter --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                                        Tipe Kegiatan
                                    </label>
                                    <select name="kegiatan"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                                        <option value="">Semua Kegiatan</option>
                                        @foreach ($kegiatanList as $k)
                                            <option value="{{ $k }}"
                                                {{ request('kegiatan') == $k ? 'selected' : '' }}>
                                                {{ $k }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Date Start --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                                        Dari Tanggal
                                    </label>
                                    <input type="date" name="date_start" value="{{ request('date_start') }}"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                                </div>

                                {{-- Date End --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                                        Sampai Tanggal
                                    </label>
                                    <input type="date" name="date_end" value="{{ request('date_end') }}"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                                </div>
                            </div>

                            {{-- ROW 3: Action Buttons --}}
                            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                                            </path>
                                        </svg>
                                        Terapkan Filter
                                    </button>

                                    <a href="{{ route('audit-logs.index') }}"
                                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition-colors flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        Reset
                                    </a>
                                </div>

                                {{-- Export Button (Super Admin Only) --}}
                                {{-- @if (Auth::user()->role === 'super_admin' && request('date_start') && request('date_end'))
                                    <a href="{{ route('audit-logs.export', ['date_start' => request('date_start'), 'date_end' => request('date_end')]) }}"
                                        class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-lg shadow-green-500/30 transition-all flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4">
                                            </path>
                                        </svg>
                                        Export CSV
                                    </a>
                                @endif --}}

                                {{-- Items Per Page --}}
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-semibold text-gray-500">Tampilkan:</label>
                                    <select name="per_page" onchange="this.form.submit()"
                                        class="rounded-lg border-gray-300 text-sm py-1.5 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20
                                        </option>
                                        <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>
                                            50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100
                                        </option>
                                        <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Active Filters Indicator --}}
            @if (request()->hasAny(['search', 'kegiatan', 'user_id', 'date_start', 'date_end']))
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-xl">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-blue-700 mb-2">Filter Aktif:</p>
                            <div class="flex flex-wrap gap-2">
                                @if (request('search'))
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                        Pencarian: "{{ request('search') }}"
                                    </span>
                                @endif
                                @if (request('kegiatan'))
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                        Kegiatan: {{ request('kegiatan') }}
                                    </span>
                                @endif
                                @if (request('user_id'))
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        User: {{ $users->firstWhere('id', request('user_id'))->name ?? 'Unknown' }}
                                    </span>
                                @endif
                                @if (request('date_start'))
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                                        Dari: {{ request('date_start') }}
                                    </span>
                                @endif
                                @if (request('date_end'))
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                                        Sampai: {{ request('date_end') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- SECTION 2: TABEL DATA --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">

                {{-- Card Header --}}
                <div
                    class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div class="bg-blue-100 p-2.5 rounded-xl text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Rekam Jejak Aktivitas</h3>
                            <p class="text-sm text-gray-500">Monitoring seluruh kegiatan user dalam sistem.</p>
                        </div>
                    </div>
                    <div class="text-xs text-gray-400 font-mono">
                        Menampilkan {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} dari
                        {{ number_format($logs->total()) }} log
                    </div>
                </div>

                <div class="p-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Waktu
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Aktor /
                                        User</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Kegiatan
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                        Deskripsi</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">IP
                                        Address</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @forelse ($logs as $log)
                                    <tr class="hover:bg-blue-50/50 transition-colors group">

                                        {{-- Kolom Waktu --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-700">
                                                    {{ $log->timestamp->format('H:i:s') }}
                                                </span>
                                                <span class="text-xs text-gray-400">
                                                    {{ $log->timestamp->format('d M Y') }}
                                                </span>
                                            </div>
                                        </td>

                                        {{-- Kolom Aktor --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">
                                                    {{ $log->user->name ?? 'System / Guest' }}
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    {{ $log->user->email ?? 'Tidak teridentifikasi' }}
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Kolom Kegiatan (IMPROVED BADGES) --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                // COMPREHENSIVE BADGE MAPPING
                                                $badgeClass = match ($log->kegiatan) {
                                                    // AUTH
                                                    'LOGIN' => 'bg-green-100 text-green-700 border-green-200',
                                                    'LOGOUT' => 'bg-gray-100 text-gray-600 border-gray-200',
                                                    // SDB OPERATIONS
                                                    'PENYEWAAN_BARU' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                    'PERPANJANGAN' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                                    'SEWA_BERAKHIR'
                                                        => 'bg-orange-100 text-orange-700 border-orange-200',
                                                    'EDIT_DATA' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                                    // DATA OPERATIONS
                                                    'IMPORT_PREVIEW'
                                                        => 'bg-purple-100 text-purple-700 border-purple-200',
                                                    'IMPORT_EXECUTED'
                                                        => 'bg-purple-100 text-purple-700 border-purple-200',
                                                    'IMPORT_CANCELLED' => 'bg-gray-100 text-gray-600 border-gray-200',
                                                    'EXPORT_DATA' => 'bg-teal-100 text-teal-700 border-teal-200',
                                                    // ADMIN
                                                    'USER_MANAGEMENT' => 'bg-pink-100 text-pink-700 border-pink-200',
                                                    // ERRORS
                                                    'IMPORT_VALIDATION_FAILED',
                                                    'IMPORT_EXECUTION_FAILED'
                                                        => 'bg-red-100 text-red-700 border-red-200',
                                                    // DEFAULT
                                                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                                                };
                                            @endphp
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border {{ $badgeClass }}">
                                                {{ $log->kegiatan }}
                                            </span>
                                        </td>

                                        {{-- Kolom Deskripsi (SMART RENDERER) --}}
                                        <td class="px-6 py-4 w-full">
                                            <div class="text-sm text-gray-600">
                                                @if (Str::startsWith($log->deskripsi, 'JSON_DATA:'))
                                                    @php
                                                        $jsonData = json_decode(
                                                            Str::after($log->deskripsi, 'JSON_DATA:'),
                                                            true,
                                                        );
                                                    @endphp

                                                    @if (is_array($jsonData))
                                                        <div class="flex flex-col gap-2">
                                                            @foreach ($jsonData as $change)
                                                                <div
                                                                    class="flex items-center text-xs bg-gray-50 p-2 rounded border border-gray-100">
                                                                    <span
                                                                        class="font-bold text-gray-700 w-24">{{ $change['field'] }}:</span>
                                                                    <span
                                                                        class="text-red-500 line-through mr-2 bg-red-50 px-1 rounded">
                                                                        {{ $change['old'] }}
                                                                    </span>
                                                                    <svg class="w-3 h-3 text-gray-400 mx-1"
                                                                        fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                                                    </svg>
                                                                    <span
                                                                        class="text-green-600 font-bold bg-green-50 px-1 rounded">
                                                                        {{ $change['new'] }}
                                                                    </span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        {{ str_replace('JSON_DATA:', '', $log->deskripsi) }}
                                                    @endif
                                                @else
                                                    {{ $log->deskripsi }}
                                                @endif

                                                @if ($log->sdb_unit_id)
                                                    <div class="mt-1">
                                                        <a href="{{ route('dashboard', ['open_unit' => $log->sdbUnit->nomor_sdb]) }}"
                                                            class="inline-flex items-center text-blue-500 hover:underline text-xs font-bold bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">
                                                            Ref: SDB {{ $log->sdbUnit->nomor_sdb ?? '?' }}
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Kolom IP --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="text-xs font-mono text-gray-400 bg-gray-50 px-2 py-1 rounded border border-gray-200">
                                                {{ $log->ip_address ?? 'unknown' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center text-gray-400">
                                                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                                <span class="text-base font-medium">Tidak ada log yang sesuai dengan
                                                    filter.</span>
                                                <a href="{{ route('audit-logs.index') }}"
                                                    class="text-sm text-blue-500 hover:underline mt-2">
                                                    Reset filter untuk melihat semua data
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($logs->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
