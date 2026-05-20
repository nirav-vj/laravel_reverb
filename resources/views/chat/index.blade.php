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
        
        <!-- ==================== LEFT SIDEBAR: CONTACTS ==================== -->
        <aside class="w-full md:w-[380px] lg:w-[420px] flex-shrink-0 border-r border-slate-800/80 bg-slate-950/60 backdrop-blur-xl flex flex-col h-full"
               :class="{'hidden md:flex': activeContact !== null, 'flex': activeContact === null}">
            
            <!-- Sidebar Header -->
            <div class="h-16 px-4 flex items-center justify-between border-b border-slate-800/80 bg-slate-900/40">
                <div class="flex items-center gap-3">
                    <img :src="authUserAvatar" class="w-10 h-10 rounded-full border-2 border-teal-500/30 object-cover">
                    <div>
                        <h3 class="font-semibold text-sm leading-none" x-text="authUserName"></h3>
                        <span class="text-[10px] text-teal-400 font-medium tracking-wide uppercase">My Profile</span>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
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

            <!-- Contacts List -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-800/30">
                <template x-for="user in filteredUsers" :key="user.id">
                    <div @click="selectContact(user)" 
                         class="px-4 py-3 flex items-center gap-3 hover:bg-slate-900/40 cursor-pointer transition-colors duration-150 relative group"
                         :class="activeContact && activeContact.id === user.id ? 'bg-slate-900/60 border-l-4 border-teal-500' : 'border-l-4 border-transparent'">
                        
                        <!-- Contact Avatar with Real-time Online Indicator -->
                        <div class="relative flex-shrink-0">
                            <img :src="user.avatar_url" class="w-12 h-12 rounded-full object-cover border-2 border-slate-800"
                                 :class="user.is_online ? 'border-teal-500' : 'border-slate-800'">
                            <span x-show="user.is_online" 
                                  class="absolute bottom-0 right-0 block h-3.5 w-3.5 rounded-full bg-teal-400 border-2 border-slate-950 shadow-[0_0_10px_rgba(45,212,191,0.5)]"></span>
                        </div>

                        <!-- Info details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-sm text-slate-200 group-hover:text-white truncate" x-text="user.name"></h4>
                                <span class="text-[10px] text-slate-500 flex-shrink-0" x-text="formatTime(user.last_message ? user.last_message.created_at : null)"></span>
                            </div>
                            
                            <div class="flex items-center justify-between mt-1">
                                <!-- Last message or Typing state indicator -->
                                <div class="text-xs truncate max-w-[80%]" :class="user.typing ? 'text-teal-400 italic' : 'text-slate-400'">
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
                                        <span x-text="formatSnippet(user.last_message)"></span>
                                    </template>
                                </div>
                                
                                <!-- Unread messages count badge -->
                                <span x-show="user.unread_count > 0" 
                                      x-text="user.unread_count" 
                                      class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 text-[10px] font-bold text-slate-950 min-w-[18px] animate-pulse">
                                </span>
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

        <!-- ==================== RIGHT PANEL: ACTIVE CHAT ==================== -->
        <main class="flex-1 flex flex-col bg-slate-950/20 backdrop-blur-sm h-full"
              :class="{'hidden': activeContact === null, 'flex': activeContact !== null}">
            
            <template x-if="activeContact === null">
                <!-- Welcome Screen -->
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center select-none relative">
                    <div class="max-w-md space-y-6">
                        <div class="inline-flex p-6 rounded-full bg-slate-900/50 border border-slate-800 text-slate-500 mb-2">
                            <!-- Premium icon -->
                            <svg class="w-16 h-16 text-teal-500/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-200">ReverbChat Web</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Send and receive real-time secure messages. Select a contact from the sidebar or share an update with your friends instantly.
                        </p>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900/40 border border-slate-800/80 text-[10px] text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            End-to-End Encrypted. Broadcast via Laravel Reverb.
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="activeContact !== null">
                <!-- Chat Screen Layout -->
                <div class="flex-1 flex flex-col h-full overflow-hidden">
                    
                    <!-- Chat Header -->
                    <div class="h-16 px-4 border-b border-slate-800/80 bg-slate-900/40 flex items-center justify-between flex-shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Back Button (Mobile) -->
                            <button @click="closeActiveChat()" class="md:hidden p-2 -ml-2 rounded-xl text-slate-400 hover:text-white transition duration-150">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <img :src="activeContact.avatar_url" class="w-10 h-10 rounded-full object-cover border border-slate-800">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-sm leading-tight truncate text-slate-200" x-text="activeContact.name"></h3>
                                <div class="text-[10px] leading-none mt-1">
                                    <template x-if="activeContact.typing">
                                        <span class="text-teal-400 font-medium italic animate-pulse">typing...</span>
                                    </template>
                                    <template x-if="!activeContact.typing">
                                        <span :class="activeContact.is_online ? 'text-teal-400 font-medium' : 'text-slate-500'" 
                                              x-text="activeContact.is_online ? 'Online' : 'Offline'"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- WhatsApp-Style Pinned Message Banner -->
                <template x-if="pinnedMessage">
                    <div class="px-4 py-2.5 bg-[#202c33] border-b border-[#111b21]/50 flex items-center justify-between cursor-pointer hover:bg-[#2a3942] transition shadow-sm z-10 shrink-0"
                         @click="scrollToMessage(pinnedMessage.id)">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="text-slate-400 shrink-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 11V5.5C16 4.67 15.33 4 14.5 4h-5C8.67 4 8 4.67 8 5.5V11l-2 3v2h5.5v5l.5.5.5-.5v-5H18v-2l-2-3z"></path>
                                </svg>
                            </div>
                            <div class="flex flex-col min-w-0 border-l-2 border-teal-500 pl-2">
                                <span class="text-[11px] font-bold text-teal-500 leading-tight" x-text="pinnedMessage.sender_id === authUserId ? 'You' : activeContact.name"></span>
                                <p class="text-[13px] text-slate-300 truncate leading-tight mt-0.5" x-text="pinnedMessage.message || 'Attachment'"></p>
                            </div>
                        </div>
                        <template x-if="pinnedCount > 1">
                            <div class="text-[10px] text-slate-400 font-medium px-2 py-1 bg-[#111b21] rounded-md shrink-0 border border-slate-800" x-text="pinnedCount + ' pinned'"></div>
                        </template>
                    </div>
                </template>

                    <!-- Chat Message Area -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-950/40 relative" 
                         id="message-container"
                         @scroll="handleScroll">
                        
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom,rgba(15,23,42,0.6),rgba(9,13,22,0.4))] pointer-events-none"></div>

                        <div class="space-y-4 relative z-10">
                            <template x-for="(msg, index) in messages" :key="msg.id">
                                <div class="flex flex-col w-full">
                                    
                                    <!-- Day Separator (Optional / Dynamic) -->
                                    <template x-if="shouldShowDaySeparator(index)">
                                        <div class="flex justify-center my-3">
                                            <span class="px-3 py-1 rounded-full bg-slate-900/60 border border-slate-800 text-[10px] text-slate-500 font-semibold tracking-wide uppercase" 
                                                  x-text="formatDaySeparator(msg.created_at)"></span>
                                        </div>
                                    </template>

                                    <!-- Message Bubble -->
                                    <div :id="'msg-' + msg.id" class="flex w-full transition-colors duration-500 rounded-lg" :class="msg.sender_id === authUserId ? 'justify-end' : 'justify-start'">
                                        <div class="max-w-[75%] sm:max-w-[65%] rounded-2xl p-3 relative shadow-md group/bubble"
                                             x-data="{ menuOpen: false }"
                                             :class="msg.sender_id === authUserId 
                                                ? 'bg-gradient-to-br from-teal-600 to-teal-700 text-slate-100 rounded-tr-none' 
                                                : 'bg-slate-900/80 border border-slate-800 text-slate-200 rounded-tl-none'">
                                            
                                            <!-- Pinned Indicator -->
                                            <template x-if="msg.is_pinned">
                                                <div class="flex items-center gap-1 mb-1.5 text-[10px] uppercase tracking-wider font-bold"
                                                     :class="msg.sender_id === authUserId ? 'text-teal-200' : 'text-teal-500'">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                                                    </svg>
                                                    
                                                </div>
                                            </template>

                                            <!-- 3-Dot Options Menu -->
                                            <div class="absolute top-2 opacity-0 group-hover/bubble:opacity-100 transition-opacity duration-200 z-20"
                                                 :class="msg.sender_id === authUserId ? '-left-10' : '-right-10'">
                                                
                                                <button @click.stop="menuOpen = !menuOpen" @click.away="menuOpen = false"
                                                        class="p-1 rounded-full bg-slate-900/80 border border-slate-800 text-slate-400 hover:text-white shadow-lg backdrop-blur-sm transition">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Dropdown Menu -->
                                                <div x-show="menuOpen" x-transition.opacity.duration.200ms style="display: none;"
                                                     class="absolute top-8 w-36 rounded-xl bg-slate-900 border border-slate-800 shadow-xl overflow-hidden py-1"
                                                     :class="msg.sender_id === authUserId ? 'left-0' : 'right-0'">
                                                    <button @click="pinMessageApi(msg.id); menuOpen = false" 
                                                            class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-slate-800 hover:text-white transition flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                                        </svg>
                                                        <span x-text="msg.is_pinned ? 'Unpin Message' : 'Pin Message'"></span>
                                                    </button>
                                                    <button @click="openDeleteModal(msg); menuOpen = false" 
                                                            class="w-full text-left px-4 py-2 text-xs text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <!-- Attachment rendering -->
                                            <template x-if="msg.attachment_path">
                                                <div class="mb-2 rounded-lg overflow-hidden border border-black/10">
                                                    <!-- Image -->
                                                    <template x-if="msg.attachment_type === 'image'">
                                                        <a :href="msg.attachment_url" target="_blank" class="block cursor-zoom-in group/img relative">
                                                            <img :src="msg.attachment_url" class="w-full max-h-60 object-cover hover:scale-[1.02] transition-transform duration-200">
                                                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition-opacity duration-150">
                                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                                </svg>
                                                            </div>
                                                        </a>
                                                    </template>
                                                    <!-- File/Document -->
                                                    <template x-if="msg.attachment_type !== 'image'">
                                                        <a :href="msg.attachment_url" download 
                                                           class="flex items-center gap-3 p-3 rounded-lg bg-black/20 hover:bg-black/30 transition-colors duration-150">
                                                            <div class="p-2.5 rounded-xl bg-slate-950/80 text-teal-400">
                                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                </svg>
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <p class="text-xs font-semibold truncate text-slate-200" x-text="msg.attachment_name"></p>
                                                                <span class="text-[9px] text-slate-400 font-medium tracking-wider uppercase">Download File</span>
                                                            </div>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>

                                            <!-- Text Message & Inline Time -->
                                            <div class="relative min-w-[65px] pt-0.5 pb-1">
                                                <p class="text-[14.5px] leading-snug whitespace-pre-wrap select-text break-words"><span x-text="msg.message"></span><span class="inline-block w-[80px] h-3 shrink-0"></span></p>
                                                
                                                <!-- Absolute Bottom-Right Info -->
                                                <div class="absolute bottom-0 right-0 flex items-center justify-end gap-1 text-[10px] font-medium"
                                                     :class="msg.sender_id === authUserId ? 'text-teal-100/80' : 'text-slate-500'">
                                                    <span x-text="formatMessageTime(msg.created_at)"></span>
                                                    
                                                    <!-- Delivery status double ticks -->
                                                    <template x-if="msg.sender_id === authUserId">
                                                        <span class="flex items-center">
                                                            <template x-if="msg.is_seen">
                                                                <!-- Read: Blue Ticks (WhatsApp Style) -->
                                                                <svg class="w-4 h-4 text-[#53bdeb]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                                    <polyline points="22 10 13.5 18.5 11 16" class="opacity-80"></polyline>
                                                                </svg>
                                                            </template>
                                                            <template x-if="!msg.is_seen">
                                                                <!-- Sent but not read: Grey Ticks -->
                                                                <svg class="w-4 h-4 text-teal-100/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                                    <polyline points="22 10 13.5 18.5 11 16" class="opacity-80"></polyline>
                                                                </svg>
                                                            </template>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Selected Attachment Banner -->
                    <div x-show="attachmentFile" class="px-4 py-3 bg-slate-900 border-t border-slate-800 flex items-center gap-3 relative" x-transition>
                        <div class="relative w-12 h-12 rounded-lg overflow-hidden bg-slate-950 flex-shrink-0 border border-slate-800 flex items-center justify-center">
                            <template x-if="attachmentPreviewUrl">
                                <img :src="attachmentPreviewUrl" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!attachmentPreviewUrl">
                                <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold truncate text-slate-200" x-text="attachmentName"></p>
                            <span class="text-[10px] text-slate-500 uppercase font-medium">Ready to send</span>
                        </div>
                        <button @click="clearAttachment()" class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Uploading Progress Bar -->
                    <div x-show="isUploading" class="h-1 bg-slate-900 overflow-hidden relative flex-shrink-0">
                        <div class="h-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-150"
                             :style="'width: ' + attachmentProgress + '%'"></div>
                    </div>

                    <!-- Chat Input Panel -->
                    <div class="p-3 bg-slate-900/60 border-t border-slate-800/80 flex items-center gap-2 flex-shrink-0 relative">
                        <!-- Paperclip Attachment Trigger -->
                        <div>
                            <button @click="$refs.attachmentInput.click()" 
                                    class="p-2.5 rounded-xl text-slate-400 hover:text-teal-400 hover:bg-slate-800 transition duration-150"
                                    title="Add Attachment">
                                <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                            </button>
                            <input type="file" x-ref="attachmentInput" class="hidden" @change="handleFileChange">
                        </div>

                        <!-- Typing input field -->
                        <div class="flex-1">
                            <input type="text" x-model="newMessageText" 
                                   @keydown="handleKeyDown" 
                                   @input="notifyTyping"
                                   class="block w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800/80 rounded-2xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-teal-500/50 focus:border-teal-500/50 transition duration-150"
                                   placeholder="Type a message...">
                        </div>

                        <!-- Send Button -->
                        <div>
                            <button @click="sendCurrentMessage()" 
                                    class="p-3 rounded-2xl bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-slate-950 shadow-lg shadow-teal-500/10 transition duration-150 transform hover:scale-105 active:scale-95 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-950 transform rotate-45 -translate-x-0.5 translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>
            </template>
        </main>

    </div>

    <!-- Global Delete Modal -->
    <div x-show="deleteModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="deleteModalOpen = false" x-transition.opacity></div>
        
        <!-- Modal Content -->
        <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm m-4 transform transition-all"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
             
            <div class="flex items-center gap-4 mb-6">
                <div class="p-3 rounded-full bg-rose-500/20 text-rose-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-200">Delete message?</h3>
                    <p class="text-xs text-slate-400">This action cannot be undone.</p>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <template x-if="messageToDelete && messageToDelete.sender_id === authUserId">
                    <button @click="executeDelete('everyone')" 
                            class="w-full py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-medium shadow-lg shadow-rose-500/20 transition transform active:scale-95">
                        Delete for everyone
                    </button>
                </template>
                <button @click="executeDelete('me')" 
                        class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium border border-slate-700 transition transform active:scale-95">
                    Delete for me
                </button>
                <button @click="deleteModalOpen = false" 
                        class="w-full py-2.5 mt-2 rounded-xl bg-transparent hover:bg-slate-800 text-slate-400 font-medium transition transform active:scale-95">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function chatApp(config) {
        return {
            users: config.initialUsers,
            authUserId: config.authUserId,
            authUserName: config.authUserName,
            authUserAvatar: config.authUserAvatar,
            
            // App state
            activeContact: null,
            messages: [],
            newMessageText: '',
            searchQuery: '',
            
            // Attachments
            attachmentFile: null,
            attachmentPreviewUrl: null,
            attachmentName: '',
            attachmentProgress: 0,
            isUploading: false,
            
            // Delete Modal State
            deleteModalOpen: false,
            messageToDelete: null,
            
            // Heartbeat Typing
            typingTimeout: null,
            isLocallyTyping: false,

            get pinnedMessage() {
                if (!this.messages) return null;
                for (let i = this.messages.length - 1; i >= 0; i--) {
                    if (this.messages[i].is_pinned) return this.messages[i];
                }
                return null;
            },

            get pinnedCount() {
                if (!this.messages) return 0;
                return this.messages.filter(m => m.is_pinned).length;
            },

            scrollToMessage(id) {
                const el = document.getElementById('msg-' + id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Briefly highlight the message block
                    el.classList.add('bg-teal-900/40');
                    setTimeout(() => el.classList.remove('bg-teal-900/40'), 1500);
                }
            },

            init() {
                // Echo is disabled because it blocks the single-threaded PHP server on Windows
                // Bulletproof Fallback: Poll for new messages every 2 seconds
                this.startPolling();
            },

            // Auto-polling fallback logic
            startPolling() {
                setInterval(() => {
                    this.pollUpdates();
                }, 2000);
            },

            pollUpdates() {
                // Fetch the latest unread counts and sidebar updates
                fetch('/chat/updates')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            data.users.forEach(updatedUser => {
                                const user = this.users.find(u => u.id === updatedUser.id);
                                if (user) {
                                    user.unread_count = updatedUser.unread_count;
                                    user.is_online = updatedUser.is_online;
                                    user.typing = updatedUser.is_typing;
                                    
                                    if (this.activeContact && this.activeContact.id === user.id) {
                                        this.activeContact.typing = updatedUser.is_typing;
                                    }

                                    if (updatedUser.last_message) {
                                        this.updateContactLastMessage(user.id, updatedUser.last_message);
                                    }
                                }
                            });

                            // If a contact is active, fetch new messages in that chat
                            if (this.activeContact) {
                                this.pollActiveChat();
                            }
                        }
                    })
                    .catch(err => console.error("Polling error:", err));
            },

            pollActiveChat() {
                fetch(`/messages/${this.activeContact.id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const container = document.getElementById('message-container');
                            const isAtBottom = container ? (container.scrollHeight - container.scrollTop <= container.clientHeight + 100) : true;
                            
                            const hasNewMessages = data.messages.length > this.messages.length;
                            
                            this.messages = data.messages;
                            
                            if (isAtBottom && hasNewMessages) {
                                this.scrollToBottom();
                            }
                        }
                    })
                    .catch(err => {});
            },

            // ================== MESSAGE ACTIONS (PIN & DELETE) ==================
            pinMessageApi(messageId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch(`/messages/${messageId}/pin`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Optimistic UI update
                        const msg = this.messages.find(m => m.id === messageId);
                        if (msg) msg.is_pinned = !msg.is_pinned;
                    }
                });
            },

            openDeleteModal(msg) {
                this.messageToDelete = msg;
                this.deleteModalOpen = true;
            },

            executeDelete(type) {
                if (!this.messageToDelete) return;
                
                const messageId = this.messageToDelete.id;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Optimistic UI update
                if (type === 'everyone') {
                    const msg = this.messages.find(m => m.id === messageId);
                    if (msg) {
                        msg.message = '🚫 This message was deleted';
                        msg.attachment_path = null;
                        msg.attachment_name = null;
                    }
                } else if (type === 'me') {
                    this.messages = this.messages.filter(m => m.id !== messageId);
                }
                
                this.deleteModalOpen = false;
                this.messageToDelete = null;

                fetch(`/messages/${messageId}`, {
                    method: 'DELETE',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken 
                    },
                    body: JSON.stringify({ type: type })
                });
            },
            // =====================================================================

            // Filter users list based on sidebar search input
            get filteredUsers() {
                if (!this.searchQuery.trim()) {
                    return this.users;
                }
                const query = this.searchQuery.toLowerCase();
                return this.users.filter(u => u.name.toLowerCase().includes(query));
            },

            // Select and open contact messaging thread
            selectContact(contact) {
                if (this.activeContact && this.activeContact.id === contact.id) return;
                
                // Clear any unsent attachment preview in new context
                this.clearAttachment();
                this.messages = [];
                
                // Set sidebar badge to 0 immediately for premium responsive feeling
                contact.unread_count = 0;
                this.activeContact = contact;

                // Load messages from endpoint
                fetch(`/messages/${contact.id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.messages = data.messages;
                            
                            // Merge contact status details from endpoint
                            this.activeContact.is_online = data.contact.is_online;
                            this.activeContact.last_seen = data.contact.last_seen;
                            
                            this.scrollToBottom();
                        }
                    })
                    .catch(err => console.error("Error fetching messages:", err));
            },

            closeActiveChat() {
                this.activeContact = null;
                this.messages = [];
            },

            // Send message execution (handles files via Axios to fetch progress)
            sendCurrentMessage() {
                if (!this.newMessageText.trim() && !this.attachmentFile) return;

                const contactId = this.activeContact.id;
                const formData = new FormData();
                
                if (this.newMessageText.trim()) {
                    formData.append('message', this.newMessageText);
                }
                
                if (this.attachmentFile) {
                    formData.append('attachment', this.attachmentFile);
                    this.isUploading = true;
                    this.attachmentProgress = 10;
                }

                // Stop typing notifications before sending
                this.whisperTyping(false);

                // Fetch CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Send request using standard window.axios to track precise file upload percentages
                window.axios.post(`/messages/${contactId}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    onUploadProgress: (progressEvent) => {
                        if (this.attachmentFile) {
                            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            this.attachmentProgress = percentCompleted;
                        }
                    }
                })
                .then(response => {
                    if (response.data.success) {
                        const newMsg = response.data.message;
                        
                        // Push into conversation log
                        this.messages.push(newMsg);
                        
                        // Update active contact last message snippet
                        this.updateContactLastMessage(contactId, newMsg);
                        
                        // Clear input elements
                        this.newMessageText = '';
                        this.clearAttachment();
                        this.scrollToBottom();
                    }
                })
                .catch(err => {
                    console.error("Error sending message:", err);
                    alert("Failed to send message/file. Verify file is valid (under 10MB).");
                    this.isUploading = false;
                });
            },

            // Handle carriage return keyboard submission
            handleKeyDown(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendCurrentMessage();
                }
            },

            // Trigger real-time Seen API call
            markAsSeenApi(contactId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch(`/messages/${contactId}/seen`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                }).catch(err => console.error("Error setting seen status:", err));
            },

            // Real-time whispering typing indicators
            notifyTyping() {
                if (!this.isLocallyTyping) {
                    this.isLocallyTyping = true;
                    this.whisperTyping(true);
                }

                clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => {
                    this.isLocallyTyping = false;
                    this.whisperTyping(false);
                }, 2000);
            },

            whisperTyping(isTyping) {
                if (isTyping && this.activeContact) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch('/chat/typing', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ receiver_id: this.activeContact.id })
                    }).catch(err => {});
                }
            },

            // Attachment selection handler
            handleFileChange(e) {
                const file = e.target.files[0];
                if (!file) return;

                this.attachmentFile = file;
                this.attachmentName = file.name;
                
                // Read visual thumbnails if images are selected
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.attachmentPreviewUrl = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    this.attachmentPreviewUrl = null;
                }
            },

            clearAttachment() {
                this.attachmentFile = null;
                this.attachmentName = '';
                this.attachmentPreviewUrl = null;
                this.isUploading = false;
                this.attachmentProgress = 0;
                if (this.$refs.attachmentInput) {
                    this.$refs.attachmentInput.value = '';
                }
            },

            // State mutations for online/offline flags
            updateUserOnlineStatus(userId, isOnline, lastSeenTime = null) {
                const user = this.users.find(u => u.id === userId);
                if (user) {
                    user.is_online = isOnline;
                    if (!isOnline && lastSeenTime) {
                        user.last_seen = 'offline'; // Will reload relative on active click
                    }
                    
                    // If this is our active contact, update header bar instantly
                    if (this.activeContact && this.activeContact.id === userId) {
                        this.activeContact.is_online = isOnline;
                    }
                }
            },

            updateUserTypingStatus(userId, isTyping) {
                const user = this.users.find(u => u.id === userId);
                if (user) {
                    user.typing = isTyping;
                }
                if (this.activeContact && this.activeContact.id === userId) {
                    this.activeContact.typing = isTyping;
                }
            },

            incrementUnread(userId, message) {
                const user = this.users.find(u => u.id === userId);
                if (user) {
                    user.unread_count = (user.unread_count || 0) + 1;
                }
            },

            updateContactLastMessage(userId, message) {
                const userIndex = this.users.findIndex(u => u.id === userId);
                if (userIndex !== -1) {
                    // Update user's message object reference
                    this.users[userIndex].last_message = message;
                    
                    // Extract the contact object
                    const contact = this.users[userIndex];
                    
                    // Pull contact from current place and push to top of list
                    this.users.splice(userIndex, 1);
                    this.users.unshift(contact);
                }
            },

            // Auto Scroll logic
            scrollToBottom() {
                this.$nextTick(() => {
                    const container = document.getElementById('message-container');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });
            },

            handleScroll(e) {
                // Future expansion: Paginated message history pulls
            },

            // Formatting helpers
            formatTime(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },

            formatMessageTime(dateString) {
                return this.formatTime(dateString);
            },

            formatDaySeparator(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                const today = new Date();
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);

                if (date.toDateString() === today.toDateString()) {
                    return 'Today';
                } else if (date.toDateString() === yesterday.toDateString()) {
                    return 'Yesterday';
                } else {
                    return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                }
            },

            shouldShowDaySeparator(index) {
                if (index === 0) return true;
                const current = new Date(this.messages[index].created_at).toDateString();
                const previous = new Date(this.messages[index - 1].created_at).toDateString();
                return current !== previous;
            },

            formatSnippet(msg) {
                if (!msg) return 'No messages yet';
                if (msg.attachment_path) {
                    return msg.attachment_type === 'image' ? '📷 Image' : '📁 Document';
                }
                return msg.message || '';
            }
        };
    }
</script>
@endsection
