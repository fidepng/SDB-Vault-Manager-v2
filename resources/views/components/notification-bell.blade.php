<div x-data="notificationSystem()" x-init="fetchNotifications()" class="relative mr-4">
    <button @click="open = !open"
        class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none transition duration-150 ease-in-out">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <template x-if="count > 0">
            <span
                class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white ring-2 ring-white"
                x-text="count > 9 ? '9+' : count"></span>
        </template>
    </button>

    <div x-show="open" @click.away="open = false"
        class="absolute right-0 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 z-50"
        style="display: none;" x-transition>
        <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500">Pemberitahuan</h3>
            <span class="text-xs font-semibold text-gray-700 bg-gray-200 px-2 py-0.5 rounded-full"
                x-text="count"></span>
        </div>

        <div class="max-h-80 overflow-y-auto">
            <div x-show="loading" class="px-4 py-4 text-center text-xs text-gray-500">
                <svg class="animate-spin h-5 w-5 mx-auto mb-2 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Memeriksa data...
            </div>

            <div x-show="!loading && items.length === 0" class="px-4 py-8 text-center">
                <svg class="h-8 w-8 mx-auto text-green-400 mb-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-gray-500">Semua aman terkendali.</p>
            </div>

            <template x-for="item in items" :key="item.id">
                <a :href="item.link_action"
                    class="block px-4 py-3 hover:bg-blue-50 transition duration-150 border-b border-gray-100 last:border-0 group">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-gray-800" x-text="'SDB ' + item.nomor_sdb"></span>
                            <span class="text-[10px] text-gray-400 ml-1">•</span>
                            <span class="text-[10px] text-gray-500" x-text="item.nama_nasabah"></span>
                        </div>
                        <span class="h-2 w-2 rounded-full mt-1"
                            :class="item.urgensi === 'high' ? 'bg-red-500' : 'bg-yellow-400'"></span>
                    </div>
                    <p class="text-xs mt-1 font-medium"
                        :class="item.urgensi === 'high' ? 'text-red-600' : 'text-yellow-600'" x-text="item.pesan">
                    </p>
                </a>
            </template>
        </div>

    </div>
</div>

<script>
    function notificationSystem() {
        return {
            open: false,
            count: 0,
            items: [],
            loading: false,
            async fetchNotifications() {
                this.loading = true;
                try {
                    const response = await fetch('{{ route('api.notifications') }}');
                    const data = await response.json();
                    this.count = data.count;
                    this.items = data.items;
                } catch (error) {
                    console.error('Err Notif:', error);
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
