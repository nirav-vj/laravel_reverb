<!-- ==================== LEFT SIDEBAR: CONTACTS ==================== -->
<aside class="w-full md:w-[380px] lg:w-[420px] flex-shrink-0 border-r border-slate-800/80 bg-slate-950/60 backdrop-blur-xl flex flex-col h-full"
       :class="{'hidden md:flex': activeContact !== null, 'flex': activeContact === null}">
    
    <!-- Sidebar Header -->
    <div class="h-16 px-4 flex items-center justify-between border-b border-slate-800/80 bg-slate-900/40">
        <div @click="openProfileModal()" class="flex items-center gap-3 cursor-pointer group">
            <div class="relative">
                <img :src="authUserAvatar" class="w-10 h-10 rounded-full border-2 border-teal-500/30 object-cover group-hover:border-teal-400/60 transition duration-150">
                <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition duration-150">
                    <!-- Edit icon overlay -->
                    <svg class="w-4 h-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
            <div>
                <h3 class="font-semibold text-sm leading-none group-hover:text-teal-400 transition duration-150" x-text="authUserName"></h3>
                <span class="text-[10px] text-teal-400/80 group-hover:text-teal-400 font-medium tracking-wide uppercase transition duration-150">Edit Profile</span>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <!-- New Group Button -->
            <button @click="createGroupModalOpen = true" type="button" class="p-2 rounded-xl text-slate-400 hover:text-teal-400 hover:bg-teal-500/10 transition duration-150" title="Create New Group">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </button>
            
            <!-- Logout Button -->
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition duration-150" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Contacts Search Bar -->
    <div class="p-3 border-b border-slate-800/60 bg-slate-950/20">
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </span>
            <input type="text" x-model="searchQuery" 
                   class="block w-full pl-9 pr-4 py-2 bg-slate-900/60 border border-slate-800/80 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-teal-500/50 focus:border-teal-500/50 transition duration-150"
                   placeholder="Search conversations...">
        </div>
    </div>

    <!-- Tabs: Chats & Groups -->
    <div class="flex border-b border-slate-800/80 bg-slate-900/10 shrink-0">
        <!-- Chats Tab -->
        <button @click="activeTab = 'chats'" 
                class="flex-1 py-3 text-center text-xs font-semibold relative transition-all duration-200 flex items-center justify-center gap-2 group/tab"
                :class="activeTab === 'chats' ? 'text-teal-400 font-bold' : 'text-slate-400 hover:text-slate-200'">
            <span>Chats</span>
            
            <!-- Unread Badge -->
            <span x-show="totalChatsUnread > 0" 
                  x-text="totalChatsUnread"
                  class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 text-[10px] font-black text-slate-950 min-w-[18px]">
            </span>

            <!-- Glowing Active Underline -->
            <div class="absolute bottom-0 inset-x-4 h-[3px] rounded-t-full transition-all duration-300"
                 :class="activeTab === 'chats' ? 'bg-gradient-to-r from-teal-400 to-emerald-400 shadow-[0_-2px_10px_rgba(45,212,191,0.4)]' : 'bg-transparent group-hover/tab:bg-slate-700/50'"></div>
        </button>

        <!-- Groups Tab -->
        <button @click="activeTab = 'groups'" 
                class="flex-1 py-3 text-center text-xs font-semibold relative transition-all duration-200 flex items-center justify-center gap-2 group/tab"
                :class="activeTab === 'groups' ? 'text-teal-400 font-bold' : 'text-slate-400 hover:text-slate-200'">
            <span>Groups</span>

            <!-- Unread Badge -->
            <span x-show="totalGroupsUnread > 0" 
                  x-text="totalGroupsUnread"
                  class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 text-[10px] font-black text-slate-950 min-w-[18px]">
            </span>

            <!-- Glowing Active Underline -->
            <div class="absolute bottom-0 inset-x-4 h-[3px] rounded-t-full transition-all duration-300"
                 :class="activeTab === 'groups' ? 'bg-gradient-to-r from-teal-400 to-emerald-400 shadow-[0_-2px_10px_rgba(45,212,191,0.4)]' : 'bg-transparent group-hover/tab:bg-slate-700/50'"></div>
        </button>
    </div>

    <!-- Contacts List -->
    <div class="flex-1 overflow-y-auto divide-y divide-slate-800/30">
        <template x-for="user in filteredUsers" :key="user.is_group ? 'g_' + user.id : 'u_' + user.id">
            <div @click="selectContact(user)" 
                 class="px-4 py-3 flex items-center gap-3 hover:bg-slate-900/40 cursor-pointer transition-colors duration-150 relative group"
                 :class="activeContact && activeContact.id === user.id && !!activeContact.is_group === !!user.is_group ? 'bg-slate-900/60 border-l-4 border-teal-500' : 'border-l-4 border-transparent'">
                
                <!-- Contact Avatar with Real-time Online Indicator -->
                <div class="relative flex-shrink-0">
                    <img :src="user.avatar_url" class="w-12 h-12 rounded-full object-cover border-2 border-slate-800"
                         :class="user.is_group ? 'border-teal-500/40' : (user.is_online ? 'border-teal-500' : 'border-slate-800')">
                    <template x-if="!user.is_group">
                        <span x-show="user.is_online" 
                              class="absolute bottom-0 right-0 block h-3.5 w-3.5 rounded-full bg-teal-400 border-2 border-slate-950 shadow-[0_0_10px_rgba(45,212,191,0.5)]"></span>
                    </template>
                    <template x-if="user.is_group">
                        <span class="absolute bottom-0 right-0 block h-4.5 w-4.5 rounded-full bg-teal-500 text-slate-950 flex items-center justify-center border border-slate-950 p-[2.5px] shadow-[0_0_8px_rgba(45,212,191,0.4)]">
                            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </span>
                    </template>
                </div>

                <!-- Info details -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h4 class="font-medium text-sm text-slate-200 group-hover:text-white truncate" x-text="user.name"></h4>
                        <span class="text-[10px] text-slate-500 flex-shrink-0" x-text="formatTime(user.last_message ? user.last_message.created_at : null)"></span>
                    </div>
                    
                    <div class="flex items-center justify-between mt-1 gap-2">
                        <!-- Last message or Typing state indicator -->
                        <div class="text-xs truncate flex-1" :class="user.typing ? 'text-teal-400 italic' : 'text-slate-400'">
                            <template x-if="user.typing">
                                <span class="flex items-center gap-1">
                                    <span class="flex h-1.5 w-1.5 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-teal-500"></span>
                                    </span>
                                    typing...
                                </span>
                            </template>
                            <template x-if="!user.typing">
                                <span class="truncate block" x-text="formatSnippet(user.last_message)"></span>
                            </template>
                        </div>
                        
                        <!-- Right-side Status: Unread count OR Ticks below the time -->
                        <div class="flex items-center justify-end flex-shrink-0 min-w-[20px]">
                            <!-- Unread messages count badge -->
                            <span x-show="user.unread_count > 0" 
                                  x-text="user.unread_count" 
                                  class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 text-[10px] font-bold text-slate-950 min-w-[18px] animate-pulse">
                            </span>
                            
                            <!-- Status ticks (Only if no unread count and I sent the last message) -->
                            <template x-if="user.unread_count === 0 && user.last_message && user.last_message.sender_id === authUserId">
                                <span class="flex items-center">
                                    <template x-if="user.last_message.is_seen">
                                        <!-- Read: Double Blue Ticks -->
                                        <svg class="w-4 h-4 text-[#53bdeb]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                            <polyline points="22 10 13.5 18.5 11 16" class="opacity-80"></polyline>
                                        </svg>
                                    </template>
                                    <template x-if="!user.last_message.is_seen">
                                        <span class="flex items-center">
                                            <template x-if="user.is_online">
                                                <!-- Delivered/Online: Double Grey Ticks -->
                                                <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                    <polyline points="22 10 13.5 18.5 11 16" class="opacity-80"></polyline>
                                                </svg>
                                            </template>
                                            <template x-if="!user.is_online">
                                                <!-- Sent/Offline: Single Grey Tick -->
                                                <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </template>
                                        </span>
                                    </template>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <template x-if="filteredUsers.length === 0">
            <div class="p-8 text-center text-xs text-slate-500">
                No active conversations found.
            </div>
        </template>
    </div>
</aside>
