<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2 text-gray-800">
            <a href="{{ route('users.index') }}" class="hover:text-blue-600 transition-colors">
                <h2 class="font-semibold text-xl leading-tight">{{ __('Manajemen Pengguna') }}</h2>
            </a>
            <span class="text-gray-400">/</span>
            <span class="font-medium text-gray-500 text-lg">Tambah Baru</span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">

                {{-- Card Header --}}
                <div class="px-8 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Registrasi Petugas Baru</h3>
                        <p class="text-sm text-gray-500">Buat akun untuk memberikan akses sistem.</p>
                    </div>
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                            </path>
                        </svg>
                    </div>
                </div>

                <div class="p-8">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Nama --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                                        Nama Lengkap
                                    </label>
                                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-3 px-4 transition-all"
                                        placeholder="Contoh: Budi Santoso">
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                {{-- Custom Dropdown Role (Alpine.js) --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                                        Role / Jabatan
                                    </label>

                                    {{-- Component Dropdown Modern --}}
                                    <div x-data="{
                                        open: false,
                                        selected: '{{ old('role', 'admin') }}',
                                        get label() {
                                            return this.selected === 'super_admin' ? 'Super Admin' : 'Admin Biasa';
                                        }
                                    }" class="relative">

                                        {{-- Input Tersembunyi (Agar data terkirim ke server) --}}
                                        <input type="hidden" name="role" x-model="selected">

                                        {{-- Trigger Button (Tampilan Box) --}}
                                        <button type="button" @click="open = !open" @click.outside="open = false"
                                            class="w-full bg-white border border-gray-300 text-gray-700 py-3 px-4 rounded-xl shadow-sm text-left flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                            <span x-text="label" class="text-sm"></span>
                                            <svg class="h-4 w-4 text-gray-500 transition-transform duration-200"
                                                :class="{ 'rotate-180': open }" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        {{-- Dropdown Menu Items --}}
                                        <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            class="absolute z-50 mt-2 w-full bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 py-1 focus:outline-none"
                                            style="display: none;">

                                            {{-- Pilihan 1: Admin --}}
                                            <button type="button" @click="selected = 'admin'; open = false"
                                                class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center justify-between group">
                                                <span>Admin Biasa</span>
                                                <span x-show="selected === 'admin'"</span>
                                            </button>

                                            {{-- Pilihan 2: Super Admin --}}
                                            <button type="button" @click="selected = 'super_admin'; open = false"
                                                class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-purple-600 flex items-center justify-between group border-t border-gray-100">
                                                <span>Super Admin</span>
                                                <span x-show="selected === 'super_admin'"</span>
                                            </button>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                </div>
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                                    Alamat Email
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                                            </path>
                                        </svg>
                                    </div>
                                    <input type="email" name="email" value="{{ old('email') }}" required
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-3 pl-10"
                                        placeholder="nama@bank.com">
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <div class="border-t border-gray-100 my-8"></div>

                        {{-- Keamanan --}}
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
                            <h4 class="text-sm font-bold text-gray-900 flex items-center mb-4">
                                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                                Setup Password Awal
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Password</label>
                                    <input type="password" name="password" required autocomplete="new-password"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-3 px-4 bg-white">
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Konfirmasi
                                        Password</label>
                                    <input type="password" name="password_confirmation" required
                                        autocomplete="new-password"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-3 px-4 bg-white">
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="flex items-center justify-end gap-4 mt-8 pt-4">
                            <a href="{{ route('users.index') }}"
                                class="px-6 py-3 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-all">Batal</a>
                            <button type="submit"
                                class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Simpan Petugas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
