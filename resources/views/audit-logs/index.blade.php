<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Audit Trail Sistem') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- SECTION 1: FILTER & PENCARIAN --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('audit-logs.index') }}">
                        <div class="flex flex-col md:flex-row gap-4 items-end">

                            {{-- Search Text --}}
                            <div class="flex-1 w-full">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Kata Kunci</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        placeholder="Cari nama user, deskripsi, atau IP..."
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 pl-10">
                                </div>
                            </div>

                            {{-- Filter Kegiatan --}}
                            <div class="w-full md:w-48">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tipe
                                    Kegiatan</label>
                                <select name="kegiatan"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                                    <option value="">Semua</option>
                                    @foreach ($kegiatanList as $k)
                                        <option value="{{ $k }}"
                                            {{ request('kegiatan') == $k ? 'selected' : '' }}>{{ $k }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Date Range --}}
                            <div class="w-full md:w-40">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tanggal</label>
                                <input type="date" name="date_start" value="{{ request('date_start') }}"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                            </div>

                            {{-- Tombol Action --}}
                            <div class="flex gap-2 w-full md:w-auto">
                                <button type="submit"
                                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all flex-1 md:flex-none flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                                        </path>
                                    </svg>
                                    Filter
                                </button>
                                <a href="{{ route('audit-logs.index') }}"
                                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition-colors"
                                    title="Reset Filter">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                        </path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

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
                        {{ $logs->total() }} log
                    </div>
                </div>

                <div class="p-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Waktu
                                        Kejadian</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Aktor /
                                        User</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Kegiatan
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Deskripsi
                                        Detail</th>
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

                                        {{-- Kolom Aktor (Avatar Style) --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-9 w-9 flex-shrink-0">
                                                    <div
                                                        class="h-9 w-9 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center text-gray-600 font-bold text-xs shadow-sm border border-white">
                                                        {{ strtoupper(substr($log->user->name ?? '?', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-bold text-gray-900">
                                                        {{ $log->user->name ?? 'System / Guest' }}
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        {{ $log->user->email ?? 'Tidak teridentifikasi' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Kolom Kegiatan (Badges) --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $badgeClass = match ($log->kegiatan) {
                                                    'LOGIN' => 'bg-green-100 text-green-700 border-green-200',
                                                    'LOGOUT' => 'bg-gray-100 text-gray-600 border-gray-200',
                                                    'PENYEWAAN_BARU',
                                                    'PERPANJANGAN'
                                                        => 'bg-blue-100 text-blue-700 border-blue-200',
                                                    'SEWA_BERAKHIR',
                                                    'DELETE'
                                                        => 'bg-red-100 text-red-700 border-red-200',
                                                    'USER_MANAGEMENT'
                                                        => 'bg-purple-100 text-purple-700 border-purple-200',
                                                    default => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                                };
                                            @endphp
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border {{ $badgeClass }}">
                                                {{ $log->kegiatan }}
                                            </span>
                                        </td>

                                        {{-- Kolom Deskripsi --}}
                                        <td class="px-6 py-4">
                                            <div
                                                class="text-sm text-gray-600 leading-relaxed max-w-xs truncate hover:whitespace-normal hover:overflow-visible hover:relative hover:z-10 hover:bg-white transition-all">
                                                {{ $log->deskripsi }}
                                                @if ($log->sdb_unit_id)
                                                    <a href="#"
                                                        class="inline-flex items-center text-blue-500 hover:underline text-xs font-bold ml-1 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">
                                                        SDB {{ $log->sdbUnit->nomor_sdb ?? '?' }}
                                                    </a>
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
                                                <span class="text-base font-medium">Belum ada log aktivitas yang
                                                    terekam.</span>
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
