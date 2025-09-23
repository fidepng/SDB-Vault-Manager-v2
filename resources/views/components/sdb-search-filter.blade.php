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

        <button @click="clearFilters()"
            class="text-gray-500 hover:text-red-600 text-sm font-medium flex items-center transition-colors duration-200"
            :class="(filters.search || filters.status || filters.tipe) ? 'opacity-100' : 'opacity-50 cursor-not-allowed'">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Reset Filter
        </button>
    </div>

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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        </div>


        <div class="bg-gray-50 rounded-2xl p-4  flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-3">
                <label class="text-sm font-semibold text-gray-700 whitespace-nowrap">Status:</label>
                <div class="flex gap-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="" @change="applyFilters()"
                            class="sr-only" name="status_filter">
                        <div class="px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === '' ? 'bg-gray-700 text-white' :
                                'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            Semua
                        </div>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="kosong" @change="applyFilters()"
                            class="sr-only" name="status_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === 'kosong' ? 'bg-gray-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            Kosong
                        </div>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="terisi" @change="applyFilters()"
                            class="sr-only" name="status_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === 'terisi' ? 'bg-blue-500 text-white' :
                                'bg-blue-100 text-blue-700 hover:bg-blue-200'">
                            Terisi
                        </div>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="akan_jatuh_tempo" @change="applyFilters()"
                            class="sr-only" name="status_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === 'akan_jatuh_tempo' ? 'bg-yellow-500 text-white' :
                                'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'">
                            Akan Jatuh Tempo
                        </div>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.status" value="lewat_jatuh_tempo"
                            @change="applyFilters()" class="sr-only" name="status_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.status === 'lewat_jatuh_tempo' ? 'bg-red-500 text-white' :
                                'bg-red-100 text-red-700 hover:bg-red-200'">
                            Lewat Jatuh Tempo
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <label class="text-sm font-semibold text-gray-700 whitespace-nowrap">Tipe:</label>
                <div class="flex gap-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.tipe" value="" @change="applyFilters()"
                            class="sr-only" name="tipe_filter">
                        <div class="px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.tipe === '' ? 'bg-gray-700 text-white' :
                                'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            Semua
                        </div>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.tipe" value="B" @change="applyFilters()"
                            class="sr-only" name="tipe_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.tipe === 'B' ? 'bg-blue-500 text-white' :
                                'bg-blue-100 text-blue-700 hover:bg-blue-200'">
                            Tipe B
                        </div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" x-model="filters.tipe" value="C" @change="applyFilters()"
                            class="sr-only" name="tipe_filter">
                        <div class="flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition-all duration-200"
                            :class="filters.tipe === 'C' ? 'bg-indigo-500 text-white' :
                                'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'">
                            Tipe C
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
