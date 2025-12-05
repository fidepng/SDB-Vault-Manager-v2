<div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
    <div class="flex items-center justify-between mb-8">
        <h3 class="text-xl font-bold text-gray-900 flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                </svg>
            </div>
            <span>Denah Safe Deposit Box</span>
        </h3>
        <div class="text-right">
            <div class="text-sm text-gray-500 mb-1">Total SDB</div>
            <div class="text-lg font-bold text-gray-900" x-text="allUnits.length + ' unit'"></div>
        </div>
    </div>

    <div class="space-y-10">
        {{-- BARU: Loop utama berdasarkan layout dari controller --}}
        <template x-for="(typeData, type) in sdbLayouts" :key="type">
            <div class="sdb-type-section">
                {{-- Header Tipe SDB (logika tetap sama) --}}
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="px-6 py-3 rounded-2xl shadow-sm border"
                            :class="{
                                'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 border-blue-200': type === 'B',
                                'bg-gradient-to-r from-indigo-100 to-indigo-200 text-indigo-800 border-indigo-200': type === 'C'
                            }">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9z">
                                    </path>
                                </svg>
                                <span class="text-sm font-bold" x-text="`Tipe ${type}`"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BARU: Grid Unit SDB dengan Nested Loop --}}
                <div class="grid gap-2"
                    :style="`grid-template-columns: repeat(${typeData.dimensions.columns}, minmax(0, 1fr));`">

                    <template x-for="unitNumber in typeData.grid.flat()" :key="unitNumber">
                        {{-- HAPUS: x-data yang mengisolasi 'unit' dihapus dari div ini --}}
                        <div>
                            {{-- UBAH: Semua referensi 'unit.' diubah menjadi 'sdbDataMap[unitNumber].' --}}
                            <div :id="'unit-' + sdbDataMap[unitNumber].id"
                                @click="showDetail(sdbDataMap[unitNumber].id)"
                                class="sdb-unit cursor-pointer text-white font-semibold rounded-lg text-center transition-all duration-200 transform hover:scale-105 hover:shadow-lg relative group"
                                :class="{
                                    'bg-gray-500 hover:bg-gray-600': sdbDataMap[unitNumber].status === 'kosong',
                                    'bg-blue-500 hover:bg-blue-600': sdbDataMap[unitNumber].status === 'terisi',
                                    'bg-yellow-500 hover:bg-yellow-600': sdbDataMap[unitNumber]
                                        .status === 'akan_jatuh_tempo',
                                    'bg-red-500 hover:bg-red-600': sdbDataMap[unitNumber]
                                        .status === 'lewat_jatuh_tempo',
                                    'opacity-30 pointer-events-none': !filteredUnits.includes(sdbDataMap[unitNumber]
                                        .id) && isFilterActive,
                                    'ring-2 ring-blue-400 ring-opacity-75 scale-105 shadow-xl': selectedSdb?.id ===
                                        sdbDataMap[unitNumber].id
                                }">

                                <div class="p-2 h-12 flex flex-col justify-center items-center">
                                    <div class="font-bold text-xs leading-tight"
                                        x-text="sdbDataMap[unitNumber].nomor_sdb"></div>
                                    <div x-show="sdbDataMap[unitNumber].nama_nasabah"
                                        class="w-1.5 h-1.5 bg-white rounded-full mt-1 opacity-90"></div>
                                </div>

                                <template
                                    x-if="sdbDataMap[unitNumber].status === 'lewat_jatuh_tempo' || sdbDataMap[unitNumber].status === 'akan_jatuh_tempo'">
                                    <div class="absolute -top-1 -right-1">
                                        <div
                                            class="w-3 h-3 bg-white rounded-full flex items-center justify-center shadow-sm">
                                            <div class="w-2 h-2 rounded-full"
                                                :class="{
                                                    'bg-red-600': sdbDataMap[unitNumber].status === 'lewat_jatuh_tempo',
                                                    'bg-yellow-600': sdbDataMap[unitNumber]
                                                        .status === 'akan_jatuh_tempo'
                                                }">
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div
                                    class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-20">
                                    <div x-show="sdbDataMap[unitNumber].status === 'kosong'" class="text-gray-400">
                                        Kosong - Tersedia
                                    </div>

                                    <div x-show="sdbDataMap[unitNumber].nama_nasabah" class="text-gray-300">
                                        <span class="font-semibold"
                                            x-text="{
                                                'terisi': '✅ Aktif',
                                                'akan_jatuh_tempo': '⚠️ Akan Jatuh Tempo',
                                                'lewat_jatuh_tempo': '🚨 LEWAT JATUH TEMPO!'
                                            }[sdbDataMap[unitNumber].status]"
                                            :class="{
                                                'text-green-300': sdbDataMap[unitNumber].status === 'terisi',
                                                'text-yellow-300': sdbDataMap[unitNumber].status === 'akan_jatuh_tempo',
                                                'text-red-300': sdbDataMap[unitNumber].status === 'lewat_jatuh_tempo'
                                            }"></span>
                                        <br>
                                        <span class="text-sm"
                                            x-text="sdbDataMap[unitNumber].nama_nasabah?.substring(0, 20)"></span>
                                        <br>
                                        <span class="text-xs"
                                            x-text="getExpiryTooltipText(sdbDataMap[unitNumber].status, sdbDataMap[unitNumber].days_until_expiry)"></span>
                                    </div>

                                    <div
                                        class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
