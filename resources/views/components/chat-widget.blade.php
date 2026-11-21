<div
    x-data="chatWidget()"
    x-init="init()"
    @open-chat.window="openWindow()"
>
    <!-- Right-Edge Floating Toggle Pill (when drawer closed) -->
    <button
        type="button"
        x-show="!isOpen"
        @click="openWindow()"
        class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-full shadow-lg transition cursor-pointer"
    >
        <span class="w-6 h-6 rounded-full bg-orange-500 text-white flex items-center justify-center">
            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
        </span>
        <span>Chat</span>
        <span
            x-show="totalUnread > 0"
            x-text="totalUnread > 99 ? '99+' : totalUnread"
            class="px-1.5 py-0.5 text-[10px] font-bold bg-rose-500 text-white rounded-full"
        ></span>
    </button>

    <!-- Right-Side Docked Chat Drawer (Shopee-style) -->
    <aside
        x-show="isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 w-full sm:w-[360px] bg-white border-l border-slate-200 shadow-2xl flex flex-col"
    >
        <!-- LIST VIEW -->
        <div x-show="activeRoom === null" class="flex-1 flex flex-col min-h-0">
            <!-- Top Header -->
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="text-base font-bold text-orange-600 tracking-tight">Chat</span>
                    <select x-model="filterCategory" class="text-xs font-medium bg-white border border-slate-200 rounded-lg px-2 py-1 text-slate-700 focus:outline-none">
                        <option value="all">Semua Chat</option>
                        <option value="general">Chat General</option>
                        <option value="team">Grup Tim Kerja</option>
                        <option value="private">Chat Privat</option>
                    </select>
                </div>
                <button
                    type="button"
                    @click="isOpen = false"
                    class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg cursor-pointer"
                    title="Tutup panel chat"
                >
                    <i data-lucide="chevrons-right" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="px-4 py-2.5 shrink-0">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </span>
                    <input
                        type="text"
                        x-model="userSearch"
                        placeholder="Cari nama tim atau pegawai"
                        class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-orange-400"
                    >
                </div>
            </div>

            <!-- Tabs: Chat Hari Ini | Semua Chat -->
            <div class="grid grid-cols-2 border-b border-slate-200 text-xs font-semibold shrink-0">
                <button
                    type="button"
                    @click="timeTab = 'today'"
                    :class="timeTab === 'today' ? 'text-orange-600 border-b-2 border-orange-500' : 'text-slate-600 hover:text-slate-900'"
                    class="py-2.5 text-center transition cursor-pointer"
                >
                    Chat Hari Ini
                </button>
                <button
                    type="button"
                    @click="timeTab = 'all'"
                    :class="timeTab === 'all' ? 'text-orange-600 border-b-2 border-orange-500' : 'text-slate-600 hover:text-slate-900'"
                    class="py-2.5 text-center transition cursor-pointer"
                >
                    Semua Chat
                </button>
            </div>

            <!-- Sub-filter: Semua / Belum Dibaca -->
            <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between text-xs shrink-0">
                <select x-model="readFilter" class="text-xs font-medium text-slate-600 bg-transparent focus:outline-none cursor-pointer">
                    <option value="all">Semua</option>
                    <option value="unread">Belum Dibaca</option>
                </select>
                <span x-show="totalUnread > 0" class="text-[11px] font-semibold text-rose-600" x-text="totalUnread + ' belum dibaca'"></span>
            </div>

            <!-- Conversation List -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                <!-- Pinned # General Channel -->
                <button
                    type="button"
                    x-show="showGeneralRow()"
                    @click="openGeneral()"
                    class="w-full px-4 py-3 flex items-center gap-3 hover:bg-orange-50/40 text-left transition cursor-pointer"
                >
                    <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm shrink-0">
                        #
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-bold text-slate-800 truncate">General (Semua Tim)</p>
                            <span class="text-[10px] text-slate-400 shrink-0" x-text="generalInfo.last_time"></span>
                        </div>
                        <p class="text-[11px] text-slate-500 truncate mt-0.5" x-text="generalInfo.last_message"></p>
                    </div>
                </button>

                <!-- Group Chat per Tim Kerja -->
                <template x-for="t in filteredTeams()" :key="'team-' + t.id">
                    <button
                        type="button"
                        @click="openTeam(t)"
                        class="w-full px-4 py-3 flex items-center gap-3 hover:bg-emerald-50/40 text-left transition cursor-pointer"
                    >
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px] shrink-0" x-text="t.kode.substring(0, 4)"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-bold text-slate-800 truncate" x-text="t.nama"></p>
                                <span class="text-[10px] text-slate-400 shrink-0" x-text="t.last_time"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5" x-text="t.last_message"></p>
                        </div>
                    </button>
                </template>

                <!-- Private Users -->
                <template x-for="u in filteredUsers()" :key="'user-' + u.id">
                    <button
                        type="button"
                        @click="openPrivate(u)"
                        class="w-full px-4 py-3 flex items-center gap-3 hover:bg-slate-50 text-left transition cursor-pointer"
                    >
                        <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0" x-text="u.name.substring(0, 2).toUpperCase()"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-bold text-slate-800 truncate" x-text="u.name"></p>
                                <span class="text-[10px] text-slate-400 shrink-0" x-text="u.last_time"></span>
                            </div>
                            <div class="flex items-center justify-between gap-2 mt-0.5">
                                <p class="text-[11px] text-slate-500 truncate" x-text="u.last_message"></p>
                                <span x-show="u.unread > 0" x-text="u.unread" class="px-1.5 py-0.5 text-[10px] font-bold bg-orange-500 text-white rounded-full shrink-0"></span>
                            </div>
                        </div>
                    </button>
                </template>

                <p x-show="!showGeneralRow() && filteredTeams().length === 0 && filteredUsers().length === 0" class="p-8 text-center text-xs text-slate-400">
                    Tidak ada percakapan yang cocok.
                </p>
            </div>
        </div>

        <!-- CONVERSATION VIEW -->
        <div x-show="activeRoom !== null" class="flex-1 flex flex-col min-h-0">
            <!-- Conversation Header -->
            <div class="px-4 py-3 border-b border-slate-200 bg-white flex items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <button type="button" @click="backToList()" class="p-1 text-slate-500 hover:text-slate-900 rounded-lg hover:bg-slate-100 cursor-pointer" title="Kembali">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    </button>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate" x-text="roomTitle()"></p>
                        <p class="text-[10px] text-slate-400 truncate" x-text="roomSubtitle()"></p>
                    </div>
                </div>
                <button type="button" @click="isOpen = false" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg cursor-pointer">
                    <i data-lucide="chevrons-right" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Messages -->
            <div x-ref="msgContainer" class="flex-1 overflow-y-auto p-3.5 space-y-2.5 bg-[#F8F9FA]">
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.is_mine ? 'bg-orange-500 text-white rounded-2xl rounded-br-xs' : 'bg-white border border-slate-200 text-slate-700 rounded-2xl rounded-bl-xs'" class="max-w-[82%] px-3 py-2 shadow-2xs">
                            <div x-show="!msg.is_mine" class="text-[10px] font-bold text-slate-500 mb-0.5" x-text="msg.sender_name"></div>
                            <p class="text-xs whitespace-pre-wrap break-words" x-text="msg.message"></p>
                            <div :class="msg.is_mine ? 'text-orange-100' : 'text-slate-400'" class="text-[9px] text-right mt-1" x-text="msg.time"></div>
                        </div>
                    </div>
                </template>
                <p x-show="messages.length === 0" class="text-center text-xs text-slate-400 py-10">
                    Belum ada pesan. Mulai percakapan sekarang!
                </p>
            </div>

            <!-- Message Input -->
            <form @submit.prevent="sendMessage()" class="p-3 bg-white border-t border-slate-200 flex items-center gap-2 shrink-0">
                <input
                    type="text"
                    x-model="draft"
                    placeholder="Tulis pesan..."
                    required
                    class="flex-1 text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:bg-white focus:border-orange-400"
                >
                <button
                    type="submit"
                    :disabled="sending || !draft.trim()"
                    class="p-2 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white rounded-xl transition cursor-pointer shrink-0"
                    title="Kirim"
                >
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </aside>
</div>

<script>
    function chatWidget() {
        return {
            isOpen: false,
            activeRoom: null, // null | 'general' | 'team' | 'private'
            timeTab: 'all',   // 'today' | 'all'
            filterCategory: 'all', // 'all' | 'general' | 'team' | 'private'
            readFilter: 'all',     // 'all' | 'unread'
            userSearch: '',
            generalInfo: { last_message: 'Grup diskusi seluruh tim & mitra', last_time: '', is_today: false },
            teams: [],
            users: [],
            selectedTeam: null,
            selectedUser: null,
            messages: [],
            draft: '',
            sending: false,
            totalUnread: 0,

            init() {
                this.fetchUsers();
                setInterval(() => {
                    this.fetchUsers();
                    if (this.isOpen && this.activeRoom !== null) {
                        this.fetchMessages(false);
                    }
                }, 4000);
            },

            roomTitle() {
                if (this.activeRoom === 'general') return '# General (Semua Tim)';
                if (this.activeRoom === 'team') return this.selectedTeam?.nama || 'Grup Tim';
                return this.selectedUser?.name || '';
            },

            roomSubtitle() {
                if (this.activeRoom === 'general') return 'Grup diskusi seluruh pegawai & mitra';
                if (this.activeRoom === 'team') return 'Grup diskusi internal ' + (this.selectedTeam?.nama || '');
                return this.selectedUser?.role_label || '';
            },

            openWindow() {
                this.isOpen = true;
                this.fetchUsers();
                if (this.activeRoom !== null) {
                    this.fetchMessages(true);
                }
                this.$nextTick(() => lucide.createIcons());
            },

            openGeneral() {
                this.activeRoom = 'general';
                this.selectedTeam = null;
                this.selectedUser = null;
                this.fetchMessages(true);
                this.$nextTick(() => lucide.createIcons());
            },

            openTeam(team) {
                this.activeRoom = 'team';
                this.selectedTeam = team;
                this.selectedUser = null;
                this.fetchMessages(true);
                this.$nextTick(() => lucide.createIcons());
            },

            openPrivate(user) {
                this.activeRoom = 'private';
                this.selectedUser = user;
                this.selectedTeam = null;
                user.unread = 0;
                this.fetchMessages(true);
                this.$nextTick(() => lucide.createIcons());
            },

            backToList() {
                this.activeRoom = null;
                this.fetchUsers();
                this.$nextTick(() => lucide.createIcons());
            },

            showGeneralRow() {
                if (!['all', 'general'].includes(this.filterCategory)) return false;
                if (this.readFilter === 'unread') return false;
                if (this.timeTab === 'today' && !this.generalInfo.is_today) return false;
                const q = this.userSearch.toLowerCase().trim();
                if (q && !'general semua tim'.includes(q)) return false;
                return true;
            },

            filteredTeams() {
                if (!['all', 'team'].includes(this.filterCategory)) return [];
                if (this.readFilter === 'unread') return [];
                const q = this.userSearch.toLowerCase().trim();
                return this.teams.filter(t => {
                    if (this.timeTab === 'today' && !t.is_today) return false;
                    if (q && !t.nama.toLowerCase().includes(q) && !t.kode.toLowerCase().includes(q)) return false;
                    return true;
                });
            },

            filteredUsers() {
                if (!['all', 'private'].includes(this.filterCategory)) return [];
                const q = this.userSearch.toLowerCase().trim();
                return this.users.filter(u => {
                    if (this.timeTab === 'today' && !u.is_today) return false;
                    if (this.readFilter === 'unread' && u.unread <= 0) return false;
                    if (q && !u.name.toLowerCase().includes(q) && !u.role_label.toLowerCase().includes(q)) return false;
                    return true;
                });
            },

            async fetchUsers() {
                try {
                    const res = await fetch('{{ route('chat.users') }}', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.general) this.generalInfo = data.general;
                    this.teams = data.teams || [];
                    this.users = data.users || [];
                    this.totalUnread = data.total_unread || 0;
                } catch (e) {}
            },

            async fetchMessages(scrollBottom = false) {
                let query = '';
                if (this.activeRoom === 'private' && this.selectedUser) {
                    query = '?receiver_id=' + this.selectedUser.id;
                } else if (this.activeRoom === 'team' && this.selectedTeam) {
                    query = '?tim_kerja_id=' + this.selectedTeam.id;
                }
                const url = '{{ route('chat.messages') }}' + query;
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    const prevCount = this.messages.length;
                    this.messages = data.messages || [];
                    if (scrollBottom || this.messages.length > prevCount) {
                        this.$nextTick(() => {
                            const el = this.$refs.msgContainer;
                            if (el) el.scrollTop = el.scrollHeight;
                        });
                    }
                } catch (e) {}
            },

            async sendMessage() {
                if (!this.draft.trim() || this.sending) return;
                this.sending = true;
                const receiverId = this.activeRoom === 'private' && this.selectedUser ? this.selectedUser.id : null;
                const timKerjaId = this.activeRoom === 'team' && this.selectedTeam ? this.selectedTeam.id : null;
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const res = await fetch('{{ route('chat.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            message: this.draft,
                            receiver_id: receiverId,
                            tim_kerja_id: timKerjaId,
                        }),
                    });

                    if (res.ok) {
                        const data = await res.json();
                        this.messages.push(data.message);
                        this.draft = '';
                        this.fetchUsers();
                        this.$nextTick(() => {
                            const el = this.$refs.msgContainer;
                            if (el) el.scrollTop = el.scrollHeight;
                        });
                    }
                } finally {
                    this.sending = false;
                }
            },
        };
    }
</script>
