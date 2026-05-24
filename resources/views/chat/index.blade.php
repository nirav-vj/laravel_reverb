@extends('layouts.app')

@section('content')
<div class="h-screen flex flex-col overflow-hidden text-slate-100" 
     x-data="chatApp({
         initialUsers: {{ json_encode($users) }},
         authUserId: {{ auth()->id() }},
         authUserName: '{{ auth()->user()->name }}',
         authUserAvatar: '{{ auth()->user()->avatar_url }}'
     })">

    <!-- Ambient Grid & Glows -->
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(13,148,136,0.08),rgba(255,255,255,0))] pointer-events-none"></div>

    <div class="flex-1 flex overflow-hidden relative z-10">
        
        <!-- Left Sidebar: Contacts & Search -->
        @include('chat.partials.sidebar')

        <!-- Right Panel: Active Chat Thread -->
        <main class="flex-1 flex flex-col bg-slate-950/20 backdrop-blur-sm h-full"
              :class="{'hidden': activeContact === null, 'flex': activeContact !== null}">
            
            <template x-if="activeContact === null">
                @include('chat.partials.welcome')
            </template>

            <template x-if="activeContact !== null">
                @include('chat.partials.chat-box')
            </template>
            
        </main>

        <!-- Right Sidebar: Group Details Drawer (WhatsApp-style) -->
        <template x-if="activeContact !== null && activeContact.is_group">
            <aside class="w-full md:w-[320px] lg:w-[360px] flex-shrink-0 border-l border-slate-800/80 bg-slate-950/60 backdrop-blur-xl flex flex-col h-full transition duration-300 relative z-20 shrink-0"
                   x-show="groupInfoSidebarOpen"
                   x-transition:enter="transition ease-in-out duration-300 transform"
                   x-transition:enter-start="translate-x-full"
                   x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition ease-in-out duration-200 transform"
                   x-transition:leave-start="translate-x-0"
                   x-transition:leave-end="translate-x-full"
                   style="display: none;">
                
                <!-- Glow effects inside panel -->
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-teal-500/5 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Sidebar Header -->
                <div class="h-16 px-4 border-b border-slate-800/80 bg-slate-900/40 flex items-center justify-between flex-shrink-0">
                    <h3 class="font-bold text-sm text-slate-200">Group Info</h3>
                    <button @click="groupInfoSidebarOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition duration-150">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Sidebar Scrollable Content -->
                <div class="flex-1 overflow-y-auto py-6 space-y-6">
                    <!-- Avatar & Banner Details -->
                    <div class="text-center px-4">
                        <div class="relative w-28 h-28 mx-auto">
                            <img :src="activeContact.avatar_url" class="w-28 h-28 rounded-full border-4 border-slate-800/80 object-cover shadow-2xl">
                            <span class="absolute bottom-1 right-1 block h-6 w-6 rounded-full bg-teal-500 text-slate-950 flex items-center justify-center border-2 border-slate-950 p-[3px] shadow-[0_0_10px_rgba(45,212,191,0.5)]">
                                <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </span>
                        </div>
                        <h4 class="font-bold text-base text-slate-100 mt-4 truncate" x-text="activeContact.name"></h4>
                        <p class="text-xs text-slate-400 mt-1.5 px-4 italic break-words leading-relaxed" 
                           x-text="activeContact.description || 'No description provided.'"></p>
                    </div>

                    <!-- Divider -->
                    <div class="border-b border-slate-800/60 mx-4"></div>

                    <!-- Members List -->
                    <div>
                        <div class="px-4 mb-3 flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-widest text-teal-400">Group Members</span>
                            <span class="text-[10px] text-slate-500 font-bold" x-text="(activeContact.members ? activeContact.members.length : 0) + ' members'"></span>
                        </div>

                        <!-- Add Member Trigger Button (Admins only) -->
                        <div class="px-4 mb-4" x-show="activeContact.members && activeContact.members.find(m => m.id === authUserId)?.role === 'admin'">
                            <button @click="showAddMembersForm = !showAddMembersForm" 
                                    class="w-full py-2 bg-teal-500/10 hover:bg-teal-500/20 text-teal-400 border border-teal-500/20 rounded-xl text-xs font-bold transition duration-150 flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add Member
                            </button>
                        </div>

                        <!-- Add Members Collapsible Form -->
                        <div x-show="showAddMembersForm" style="display: none;" class="px-4 pb-4 space-y-3 bg-slate-900/30 border-y border-slate-800/40 py-3">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block">Select Contacts to Add</span>
                            <div class="border border-slate-800 bg-slate-950/40 rounded-xl max-h-36 overflow-y-auto divide-y divide-slate-800/20">
                                <template x-for="contact in users.filter(u => !u.is_group && !activeContact.members.some(m => m.id === u.id))" :key="contact.id">
                                    <div @click="toggleAddMemberSelection(contact.id)"
                                         class="px-3 py-1.5 flex items-center justify-between hover:bg-slate-900/40 cursor-pointer transition">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <img :src="contact.avatar_url" class="w-6 h-6 rounded-full object-cover border border-slate-800 shrink-0">
                                            <span class="text-xs text-slate-300 truncate" x-text="contact.name"></span>
                                        </div>
                                        <input type="checkbox" :checked="addMemberSelection.includes(contact.id)" 
                                               class="rounded bg-slate-900 border-slate-800 text-teal-500 focus:ring-teal-500/30 focus:ring-offset-slate-900 w-3.5 h-3.5 pointer-events-none">
                                    </div>
                                </template>
                                <template x-if="users.filter(u => !u.is_group && !activeContact.members.some(m => m.id === u.id)).length === 0">
                                    <div class="p-3 text-[10px] text-slate-500 text-center italic">All contacts are in this group.</div>
                                </template>
                            </div>
                            <div class="flex justify-end gap-2 pt-1">
                                <button @click="showAddMembersForm = false; addMemberSelection = []" 
                                        class="px-2.5 py-1.5 rounded-lg border border-slate-800 hover:bg-slate-800 text-[10px] font-bold text-slate-400">
                                    Cancel
                                </button>
                                <button @click="submitAddMembers()" :disabled="isAddingMembers || addMemberSelection.length === 0"
                                        class="px-3 py-1.5 rounded-lg bg-teal-500 text-slate-950 text-[10px] font-bold transition hover:bg-teal-400 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                    <template x-if="isAddingMembers">
                                        <span class="w-3 h-3 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                                    </template>
                                    Add Selected
                                </button>
                            </div>
                        </div>

                        <!-- Members Loop -->
                        <div class="divide-y divide-slate-800/30">
                            <template x-for="member in activeContact.members" :key="member.id">
                                <div @click="selectGroupMember(member)"
                                     class="flex items-center justify-between px-4 py-2.5 transition duration-150 group/member"
                                     :class="member.id === authUserId 
                                        ? 'bg-slate-900/10' 
                                        : 'hover:bg-slate-900/40 cursor-pointer'">
                                    
                                    <div class="flex items-center gap-3 min-w-0">
                                        <img :src="member.avatar_url" class="w-9 h-9 rounded-full object-cover border border-slate-800 shrink-0">
                                        <div class="min-w-0">
                                            <span class="text-xs font-semibold text-slate-200 truncate block group-hover/member:text-teal-400 transition"
                                                  x-text="member.id === authUserId ? member.name + ' (You)' : member.name"></span>
                                            <span class="text-[9px] text-slate-500 block leading-none mt-0.5" 
                                                  x-text="member.role === 'admin' ? 'Group Creator / Admin' : 'Member'"></span>
                                        </div>
                                    </div>

                                    <div class="shrink-0 flex items-center gap-1.5 pl-2" @click.stopPropagation>
                                        <!-- If user is admin -->
                                        <template x-if="member.role === 'admin'">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-teal-500/10 text-teal-400 border border-teal-500/20">
                                                Admin
                                            </span>
                                        </template>

                                        <!-- If current user is Group Admin, and member is NOT self and is NOT already admin -->
                                        <template x-if="activeContact.members && activeContact.members.find(m => m.id === authUserId)?.role === 'admin' && member.id !== authUserId && member.role !== 'admin'">
                                            <div class="flex items-center gap-1 opacity-0 group-hover/member:opacity-100 transition duration-150">
                                                <!-- Make Admin Button -->
                                                <button @click.stop="makeAdmin(member)" 
                                                        class="p-1 rounded bg-teal-500/10 hover:bg-teal-500/20 text-teal-400 border border-teal-500/20 transition" 
                                                        title="Promote to Admin">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                    </svg>
                                                </button>
                                                <!-- Remove Button -->
                                                <button @click.stop="removeMember(member)" 
                                                        class="p-1 rounded bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition" 
                                                        title="Remove Member">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>

                                        <!-- If current user is NOT admin, and member is NOT self and is NOT already admin, show standard DM icon -->
                                        <template x-if="activeContact.members && activeContact.members.find(m => m.id === authUserId)?.role !== 'admin' && member.id !== authUserId && member.role !== 'admin'">
                                            <span class="opacity-0 group-hover/member:opacity-100 text-teal-400 transition duration-150" title="Direct Chat">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                </svg>
                                            </span>
                                        </template>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </aside>
        </template>

    </div>

    <!-- Beautiful Premium Create Group Modal -->
    <div x-show="createGroupModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" @click="createGroupModalOpen = false" x-transition.opacity></div>

        <!-- Modal Box -->
        <div class="relative w-full max-w-lg bg-slate-900/90 border border-slate-800/80 rounded-3xl overflow-hidden shadow-2xl z-10 transition duration-300"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Glow effect -->
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-800/60 flex items-center justify-between bg-slate-950/40">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-teal-500/10 text-teal-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-slate-100">Create New Group</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Start a fresh conversation with multiple contacts</p>
                    </div>
                </div>
                <button @click="createGroupModalOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitCreateGroup()" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <!-- Group Profile Photo Upload -->
                <div class="flex flex-col items-center justify-center gap-2 pb-2">
                    <label class="relative group cursor-pointer">
                        <input type="file" @change="handleNewGroupAvatarChange" accept="image/*" class="hidden">
                        <div class="w-24 h-24 rounded-full border-2 border-dashed border-slate-700 group-hover:border-teal-500/80 overflow-hidden flex items-center justify-center bg-slate-900/60 transition duration-150">
                            <template x-if="newGroupAvatarPreview">
                                <img :src="newGroupAvatarPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!newGroupAvatarPreview">
                                <div class="flex flex-col items-center justify-center text-center p-2">
                                    <svg class="w-6 h-6 text-slate-400 group-hover:text-teal-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="text-[9px] text-slate-500 mt-1">Upload Photo</span>
                                </div>
                            </template>
                        </div>
                    </label>
                </div>

                <!-- Group Details -->
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Group Name</label>
                        <input type="text" x-model="newGroupName" required
                               class="block w-full px-4 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-teal-500/50 focus:border-teal-500/50 transition duration-150"
                               placeholder="e.g. Project Developers">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Description (Optional)</label>
                        <textarea x-model="newGroupDescription" rows="2"
                                  class="block w-full px-4 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-teal-500/50 focus:border-teal-500/50 transition duration-150 resize-none"
                                  placeholder="What's this group about?"></textarea>
                    </div>
                </div>

                <!-- Select Members Section -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Select Members</label>
                    <div class="border border-slate-850 bg-slate-950/30 rounded-2xl overflow-hidden max-h-48 overflow-y-auto divide-y divide-slate-800/30">
                        <template x-for="contact in users.filter(u => !u.is_group)" :key="contact.id">
                            <div @click="toggleGroupMemberSelection(contact.id)"
                                 class="px-4 py-2.5 flex items-center justify-between hover:bg-slate-900/40 cursor-pointer transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img :src="contact.avatar_url" class="w-8 h-8 rounded-full object-cover border border-slate-800 shrink-0">
                                    <span class="text-xs font-medium text-slate-200 truncate" x-text="contact.name"></span>
                                </div>
                                <div class="flex items-center shrink-0 pl-2">
                                    <input type="checkbox" :checked="newGroupMembers.includes(contact.id)" 
                                           class="rounded bg-slate-900 border-slate-800 text-teal-500 focus:ring-teal-500/30 focus:ring-offset-slate-900 w-4 h-4 pointer-events-none">
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="text-[10px] text-slate-500 mt-1.5 flex justify-between px-1">
                        <span x-text="newGroupMembers.length + ' contacts selected'"></span>
                        <span class="text-teal-400/80">At least 1 required</span>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800/60">
                    <button type="button" @click="createGroupModalOpen = false" 
                            class="px-4 py-2.5 rounded-xl border border-slate-850 hover:bg-slate-800 text-xs font-semibold text-slate-300 transition duration-150">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isCreatingGroup || !newGroupName.trim() || newGroupMembers.length === 0"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-teal-500 to-emerald-500 text-slate-950 text-xs font-bold transition duration-150 hover:from-teal-400 hover:to-emerald-400 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <template x-if="isCreatingGroup">
                            <span class="w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                        </template>
                        Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Load modular Alpine.js Application logic -->
<script src="{{ asset('js/chatApp.js') }}"></script>
@endsection
