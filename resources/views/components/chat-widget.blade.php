<div
    x-data="chatWidget()"
    x-init="init()"
    @open-chat.window="openWindow()"
    class="fixed bottom-4 right-4 z-50 flex flex-col items-end"
>
    <!-- Chat Window -->
    <div
        x-show="isOpen"
        x-cloak
        x-transition.origin.bottom.right
        class="mb-3 w-[340px] sm:w-[380px] h-[470px] bg-white border border-slate-200 rounded-2xl shadow-xl flex flex-col overflow-hidden"
    >
        <!-- Header -->
        <div class="px-4 py-3 bg-slate-800 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                <div class="min-w-0">
                    <p class="text-xs font-bold leading-none truncate">Chat OPTIMA 3307</p>
                    <p class="text-[10px] text-slate-300 mt-0.5 truncate" x-text="mode === 'general' ? '# General (Semua Tim)' : (selectedUser ? 'Privat: ' + selectedUser.name : 'Pilih Kontak Privat')"></p>
                </div>
            </div>
            <button @click="isOpen = false" class="p-1 text-slate-300 hover:text-white rounded-lg cursor-pointer" title="Tutup">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Tabs: General / Privat -->
        <div class="grid grid-cols-2 border-b border-slate-200 bg-slate-50 text-xs font-semibold shrink-0">
            <button
                type="button"
                @click="switchMode('general')"
                :class="mode === 'general' ? 'bg-white text-slate-900 border-b-2 border-slate-800' : 'text-slate-500 hover:text-slate-800'"
                class="py-2.5 px-3 text-center transition cursor-pointer"
            >
                # General
            </button>
            <button
                type="button"
                @click="switchMode('private')"
                :class="mode === 'private' ? 'bg-white text-slate-900 border-b-2 border-slate-800' : 'text-slate-500 hover:text-slate-800'"
                class="py-2.5 px-3 text-center transition cursor-pointer flex items-center justify-center gap-1.5"
            >
                <span>Privat</span>
                <span x-show="totalUnread > 0" x-text="totalUnread" class="px-1.5 py-0.5 text-[10px] font-bold bg-rose-500 text-white rounded-full"></span>
            </button>
        </div>

        <!-- Private User Picker (when mode === 'private' && !selectedUser) -->
        <div x-show="mode === 'private' && !selectedUser" class="flex-1 overflow-y-auto divide-y divide-slate-100">
            <div class="p-2.5 bg-slate-50/70">
                <input type="text" x-model="userSearch" placeholder="Cari nama pegawai / mitra..." class="w-full text-xs bg-white border border-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-slate-400">
            </div>
            <template x-for="u in filteredUsers()" :key="u.id">
                <button
                    type="button"
                    @click="selectUser(u)"
                    class="w-full px-4 py-2.5 flex items-center justify-between hover:bg-slate-50 text-left transition cursor-pointer"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-800 truncate" x-text="u.name"></p>
                        <p class="text-[10px] text-slate-400 truncate" x-text="u.role_label"></p>
                    </div>
                    <span x-show="u.unread > 0" x-text="u.unread" class="px-2 py-0.5 text-[10px] font-bold bg-rose-500 text-white rounded-full shrink-0"></span>
                </button>
            </template>
            <p x-show="filteredUsers().length === 0" class="p-6 text-center text-xs text-slate-400">Pengguna tidak ditemukan.</p>
        </div>

        <!-- Conversation Area (General OR Private with selectedUser) -->
        <div x-show="mode === 'general' || (mode === 'private' && selectedUser)" class="flex-1 flex flex-col min-h-0">
            <!-- Back bar for Private -->
            <div x-show="mode === 'private' && selectedUser" class="px-3 py-1.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px] shrink-0">
                <button type="button" @click="selectedUser = null" class="inline-flex items-center gap-1 font-semibold text-slate-600 hover:text-slate-900 cursor-pointer">
                    <i data-lucide="arrow-left" class="w-3 h-3"></i>
                    <span>Daftar Kontak</span>
                </button>
                <span class="font-semibold text-slate-700 truncate max-w-[180px]" x-text="selectedUser?.name"></span>
            </div>

            <!-- Messages -->
            <div x-ref="msgContainer" class="flex-1 overflow-y-auto p-3 space-y-2.5 bg-[#F8F9FA]">
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.is_mine ? 'bg-slate-800 text-white rounded-2xl rounded-br-xs' : 'bg-white border border-slate-200 text-slate-700 rounded-2xl rounded-bl-xs'" class="max-w-[82%] px-3 py-2 shadow-2xs">
                            <div x-show="!msg.is_mine" class="text-[10px] font-bold text-slate-500 mb-0.5" x-text="msg.sender_name + ' (' + msg.sender_role + ')'"></div>
                            <p class="text-xs whitespace-pre-wrap break-words" x-text="msg.message"></p>
                            <div :class="msg.is_mine ? 'text-slate-300' : 'text-slate-400'" class="text-[9px] text-right mt-1" x-text="msg.time"></div>
                        </div>
                    </div>
                </template>
                <p x-show="messages.length === 0" class="text-center text-xs text-slate-400 py-10">
                    Belum ada pesan. Mulai percakapan sekarang!
                </p>
            </div>

            <!-- Input -->
            <form @submit.prevent="sendMessage()" class="p-2.5 bg-white border-t border-slate-200 flex items-center gap-2 shrink-0">
                <input
                    type="text"
                    x-model="draft"
                    placeholder="Tulis pesan..."
                    required
                    class="flex-1 text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:bg-white focus:border-slate-400"
                >
                <button
                    type="submit"
                    :disabled="sending || !draft.trim()"
                    class="p-2 bg-slate-800 hover:bg-slate-900 disabled:opacity-50 text-white rounded-xl transition cursor-pointer shrink-0"
                    title="Kirim"
                >
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Toggle Button -->
    <button
        type="button"
        @click="toggleWindow()"
        class="relative inline-flex items-center gap-2 px-4 py-3 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-full shadow-lg transition cursor-pointer"
    >
        <i data-lucide="message-circle" class="w-4 h-4"></i>
        <span>Chat</span>
        <span
            x-show="totalUnread > 0"
            x-text="totalUnread"
            class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 flex items-center justify-center text-[10px] font-bold bg-rose-500 text-white rounded-full border-2 border-white"
        ></span>
    </button>
</div>

<script>
    function chatWidget() {
        return {
            isOpen: false,
            mode: 'general',
            users: [],
            userSearch: '',
            selectedUser: null,
            messages: [],
            draft: '',
            sending: false,
            totalUnread: 0,
            pollTimer: null,

            init() {
                this.fetchUsers();
                setInterval(() => {
                    this.fetchUsers();
                    if (this.isOpen && (this.mode === 'general' || this.selectedUser)) {
                        this.fetchMessages(false);
                    }
                }, 4000);
            },

            openWindow() {
                this.isOpen = true;
                this.fetchUsers();
                if (this.mode === 'general' || this.selectedUser) {
                    this.fetchMessages(true);
                }
                this.$nextTick(() => lucide.createIcons());
            },

            toggleWindow() {
                if (this.isOpen) {
                    this.isOpen = false;
                } else {
                    this.openWindow();
                }
            },

            switchMode(newMode) {
                this.mode = newMode;
                if (newMode === 'general') {
                    this.fetchMessages(true);
                } else {
                    this.fetchUsers();
                    if (this.selectedUser) {
                        this.fetchMessages(true);
                    }
                }
                this.$nextTick(() => lucide.createIcons());
            },

            selectUser(user) {
                this.selectedUser = user;
                user.unread = 0;
                this.fetchMessages(true);
                this.$nextTick(() => lucide.createIcons());
            },

            filteredUsers() {
                const q = this.userSearch.toLowerCase().trim();
                if (!q) return this.users;
                return this.users.filter(u =>
                    u.name.toLowerCase().includes(q) ||
                    u.role_label.toLowerCase().includes(q)
                );
            },

            async fetchUsers() {
                try {
                    const res = await fetch('{{ route('chat.users') }}', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.users = data.users || [];
                    this.totalUnread = data.total_unread || 0;
                } catch (e) {}
            },

            async fetchMessages(scrollBottom = false) {
                const receiverId = this.mode === 'private' && this.selectedUser ? this.selectedUser.id : '';
                const url = '{{ route('chat.messages') }}' + (receiverId ? '?receiver_id=' + receiverId : '');
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
                const receiverId = this.mode === 'private' && this.selectedUser ? this.selectedUser.id : null;
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
                        }),
                    });

                    if (res.ok) {
                        const data = await res.json();
                        this.messages.push(data.message);
                        this.draft = '';
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
