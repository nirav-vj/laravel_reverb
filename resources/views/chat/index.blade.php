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
                            <div @click.stop="pinnedListModalOpen = true" 
                                 class="text-[10px] text-teal-400 hover:text-teal-300 font-bold px-2 py-1 bg-slate-950 hover:bg-slate-900/60 border border-slate-800 rounded-md shrink-0 cursor-pointer transition select-none shadow-lg active:scale-95" 
                                 title="View All Pinned"
                                 x-text="pinnedCount + ' pinned'"></div>
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
                                        <div @dblclick="setReplyingTo(msg)"
                                             class="max-w-[75%] sm:max-w-[65%] rounded-2xl p-3 relative shadow-md group/bubble cursor-pointer select-none"
                                             x-data="{ menuOpen: false, emojiPickerOpen: false }"
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

                                            <!-- Nested Reply Quote Bubble -->
                                            <template x-if="msg.parent">
                                                <div @click.stop="scrollToMessage(msg.parent_id)"
                                                     class="mb-2 p-2.5 rounded-xl border-l-4 cursor-pointer select-none transition duration-150 flex flex-col gap-0.5 text-left min-w-[140px]"
                                                     :class="msg.sender_id === authUserId 
                                                        ? 'bg-black/20 hover:bg-black/30 border-teal-300' 
                                                        : 'bg-slate-950/60 hover:bg-slate-950/80 border-teal-500'">
                                                    <span class="text-[10px] font-bold text-teal-400 leading-tight" 
                                                          x-text="msg.parent.sender_id === authUserId ? 'You' : (msg.parent.sender ? msg.parent.sender.name : activeContact.name)"></span>
                                                    
                                                    <template x-if="msg.parent.attachment_path">
                                                        <span class="text-[10px] text-teal-300/80 font-semibold truncate mt-0.5" 
                                                              x-text="isImageAttachment(msg.parent) ? '📷 Image' : (isAudioAttachment(msg.parent) ? '🎤 Voice Note' : '📁 Document')"></span>
                                                    </template>
                                                    
                                                    <p class="text-xs text-slate-300 line-clamp-1 leading-snug mt-0.5 break-words" x-text="msg.parent.message || 'Attachment'"></p>
                                                </div>
                                            </template>

                                            <!-- Actions Row: Emoji Picker + Reply + 3-Dot Menu -->
                                            <div class="absolute top-2 opacity-0 group-hover/bubble:opacity-100 transition-opacity duration-200 z-20 flex items-center gap-1.5"
                                                 :class="msg.sender_id === authUserId ? '-left-[104px]' : '-right-[104px]'">

                                                <!-- Emoji Reaction Trigger -->
                                                <div class="relative"
                                                     x-data="{
                                                         fullPickerOpen: false,
                                                         emojiCategory: 0,
                                                         emojiSearch: '',
                                                         allCategories: [
                                                             { label: '😊', name: 'Smileys', emojis: ['😀','😁','😂','🤣','😃','😄','😅','😆','😇','😉','😊','😋','😍','🥰','😘','😗','😙','😚','🙂','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😒','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🥵','🥶','🥴','😵','🤯','🤠','🥳','😎','🤓','🧐','😕','😟','🙁','☹️','😮','😯','😲','😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠','🤬','😈','👿'] },
                                                             { label: '👋', name: 'Gestures', emojis: ['👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏','🙌','🫶','👐','🤲','🙏','✍️','💅','🤳','💪','🦵','🦶','👂','🦻','👃','🫀','🫁','🧠','🦷','🦴','👀','👁','👅','👄'] },
                                                             { label: '❤️', name: 'Hearts', emojis: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❤️‍🔥','❤️‍🩹','💕','💞','💓','💗','💖','💘','💝','💟','☮️','✝️','☪️','🕉','✡️','🔯','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'] },
                                                             { label: '⚽', name: 'Activities', emojis: ['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🏓','🏸','🏒','🥊','🥋','🎽','⛷','🏂','🏋️','🤼','🤸','⛹️','🤺','🏇','🧘','🏄','🏊','🤽','🚣','🧗','🚵','🚴','🏆','🥇','🥈','🥉','🏅','🎖','🎗','🎪','🎭','🎨','🎬','🎤','🎧','🎵','🎶','🎷','🎸','🎹','🎺','🎻','🥁','🎮','🎲'] },
                                                             { label: '🐶', name: 'Animals', emojis: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🐔','🐧','🐦','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋','🐌','🐞','🐜','🦟','🦂','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🦍','🐘','🦛','🦏','🐪','🦒','🦘','🐃','🐄'] },
                                                             { label: '🍎', name: 'Food', emojis: ['🍎','🍊','🍋','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🥑','🍆','🥔','🥕','🌽','🌶','🫑','🥦','🧄','🧅','🍄','🥜','🌰','🍞','🥐','🥖','🥨','🧀','🥚','🍳','🧈','🥞','🧇','🥓','🌮','🌯','🥙','🧆','🥚','🍣','🍤','🍜','🍝','🍛','🍲','🍱','🍚','🍙','🍘','🍥','🥮','🧁','🎂','🍰','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','☕','🍵','🧋','🥤','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🧃'] },
                                                             { label: '💡', name: 'Objects', emojis: ['💡','🔦','🕯','🪔','📱','💻','🖥','⌨️','🖨','🖱','🖲','💾','💿','📀','📷','📸','📹','🎥','📽','🎞','📞','☎️','📟','📠','📺','📻','🎙','🎚','🎛','⏱','⏲','⏰','🕰','⌚','📡','🔋','🔌','💰','💴','💵','💸','💳','🪙','📈','📉','📊','📦','📧','📨','📩','📪','📫','📬','📭','📮','🗳','✏️','📝','📁','📂','📅','📆','🗓','📇','📋','📌','📍','🗺','🔍','🔎','🔏','🔐','🔑','🗝','🔨','⛏','🪓','🔧','🪛','🔩','⚙️','🗜','🪝','🧲','🪜','⚖️','🪣','🪤','🧰','🔬','🔭','💊','💉','🩺','🩸','🪤','🧸','🎁','🎀','🎊','🎉','🎈'] },
                                                             { label: '🔣', name: 'Symbols', emojis: ['✅','❌','❎','🆗','🆙','🆒','🆕','🆓','0️⃣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟','💯','🔢','🔡','🔠','▶️','⏩','⏭','⏯','◀️','⏪','⏮','🔼','⏫','🔽','⏬','⏸','⏹','⏺','🎦','🔅','🔆','📶','📳','📴','📵','📲','🔇','🔕','🔉','🔊','📣','📢','🔔','🔕','💬','💭','🗯','♻️','🚮','🚰','♿','🚹','🚺','🚻','🅿️','🈳','🈹','🈲','🆘','🆔','🔞','⛔','🚫','🔇','🔕','🔛','🔜','🔝'] }
                                                         ],
                                                         get filteredEmojis() {
                                                             const cat = this.allCategories[this.emojiCategory];
                                                             if (!this.emojiSearch.trim()) return cat ? cat.emojis : [];
                                                             const q = this.emojiSearch.trim().toLowerCase();
                                                             return this.allCategories.flatMap(c => c.emojis).filter(e => e.includes(q));
                                                         }
                                                     }">
                                                    <button @click.stop="emojiPickerOpen = !emojiPickerOpen; fullPickerOpen = false"
                                                            class="p-1 rounded-full bg-slate-900/80 border border-slate-800 text-slate-400 hover:text-yellow-400 shadow-lg backdrop-blur-sm transition text-sm leading-none"
                                                            title="React">
                                                        😊
                                                    </button>

                                                    <!-- Quick 6 + Full Picker Toggle -->
                                                    <div x-show="emojiPickerOpen" x-transition.opacity.duration.150ms style="display:none;"
                                                         @click.away="emojiPickerOpen = false; fullPickerOpen = false"
                                                         class="absolute z-50 bottom-9 bg-slate-900/98 border border-slate-700 rounded-2xl shadow-2xl backdrop-blur-md"
                                                         :class="msg.sender_id === authUserId ? 'right-0' : 'left-0'">

                                                        <!-- Quick row: 6 emojis + + button -->
                                                        <div class="flex items-center gap-0.5 px-2 py-1.5">
                                                            <template x-for="emoji in ['👍','❤️','😂','😮','😢','🙏']" :key="emoji">
                                                                <button @click.stop="sendReaction(msg.id, emoji); emojiPickerOpen = false; fullPickerOpen = false"
                                                                        class="text-xl hover:scale-125 transition-transform duration-100 px-1 py-0.5 rounded-xl hover:bg-slate-800 active:scale-110"
                                                                        x-text="emoji">
                                                                </button>
                                                            </template>
                                                            <!-- Plus button -->
                                                            <button @click.stop="fullPickerOpen = !fullPickerOpen; emojiSearch = ''"
                                                                    class="w-7 h-7 rounded-full bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-300 hover:text-white flex items-center justify-center ml-1 transition font-bold text-sm">
                                                                +
                                                            </button>
                                                        </div>

                                                        <!-- Full Picker Panel -->
                                                        <div x-show="fullPickerOpen" x-transition.opacity.duration.100ms style="display:none;"
                                                             class="border-t border-slate-800 w-72">
                                                            <!-- Search bar -->
                                                            <div class="px-2 pt-2 pb-1">
                                                                <input x-model="emojiSearch" type="text" placeholder="Search emoji..."
                                                                       class="w-full px-3 py-1.5 text-xs bg-slate-950 border border-slate-700 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:border-teal-500/60 transition">
                                                            </div>

                                                            <!-- Category tabs -->
                                                            <template x-if="!emojiSearch.trim()">
                                                                <div class="flex gap-0.5 px-2 pb-1 overflow-x-auto scrollbar-hide">
                                                                    <template x-for="(cat, idx) in allCategories" :key="idx">
                                                                        <button @click.stop="emojiCategory = idx"
                                                                                class="flex-shrink-0 px-2 py-1 rounded-lg text-base transition-all"
                                                                                :class="emojiCategory === idx ? 'bg-teal-500/20 scale-110' : 'hover:bg-slate-800'"
                                                                                x-text="cat.label">
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                            </template>

                                                            <!-- Emoji grid -->
                                                            <div class="grid grid-cols-8 gap-0 px-2 pb-2 max-h-44 overflow-y-auto">
                                                                <template x-for="emoji in filteredEmojis" :key="emoji">
                                                                    <button @click.stop="sendReaction(msg.id, emoji); emojiPickerOpen = false; fullPickerOpen = false"
                                                                            class="text-xl p-1 rounded-lg hover:bg-slate-800 hover:scale-125 transition-all duration-75 active:scale-110"
                                                                            x-text="emoji">
                                                                    </button>
                                                                </template>
                                                                <template x-if="filteredEmojis.length === 0">
                                                                    <div class="col-span-8 text-center text-slate-500 text-xs py-4">No emoji found</div>
                                                                </template>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>

                                                <!-- Reply Button -->
                                                <button @click.stop="setReplyingTo(msg)"
                                                        class="p-1 rounded-full bg-slate-900/80 border border-slate-800 text-slate-400 hover:text-teal-400 shadow-lg backdrop-blur-sm transition"
                                                        title="Reply">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                    </svg>
                                                </button>

                                                <!-- 3-Dot Options -->
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
                                                    <template x-if="isImageAttachment(msg)">
                                                        <a :href="msg.attachment_url" target="_blank" class="block cursor-zoom-in group/img relative">
                                                            <img :src="msg.attachment_url" class="w-full max-h-60 object-cover hover:scale-[1.02] transition-transform duration-200">
                                                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition-opacity duration-150">
                                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                                </svg>
                                                            </div>
                                                        </a>
                                                    </template>
                                                    <!-- Audio/Voice Note Player -->
                                                    <template x-if="isAudioAttachment(msg)">
                                                        <div class="p-3 rounded-lg bg-black/20 hover:bg-black/30 transition-colors duration-150 flex flex-col gap-2 min-w-[240px]"
                                                             x-data="{
                                                                 audio: null,
                                                                 isPlaying: false,
                                                                 currentTime: 0,
                                                                 duration: 0,
                                                                 playbackSpeed: 1,
                                                                 initAudio(url) {
                                                                     if (!this.audio) {
                                                                         const a = new Audio();
                                                                         a.addEventListener('timeupdate', () => {
                                                                             this.currentTime = a.currentTime;
                                                                             if (this.duration === Infinity || !this.duration || this.duration === 0) {
                                                                                 if (a.duration && a.duration !== Infinity) {
                                                                                     this.duration = a.duration;
                                                                                 } else if (a.buffered && a.buffered.length > 0) {
                                                                                     this.duration = a.buffered.end(0);
                                                                                 }
                                                                             }
                                                                         });
                                                                         const setDur = () => {
                                                                             if (a.duration && a.duration !== Infinity) {
                                                                                 this.duration = a.duration;
                                                                             } else if (a.buffered && a.buffered.length > 0) {
                                                                                 this.duration = a.buffered.end(0);
                                                                             }
                                                                         };
                                                                         a.addEventListener('loadedmetadata', setDur);
                                                                         a.addEventListener('durationchange', setDur);
                                                                         a.addEventListener('progress', setDur);
                                                                         a.addEventListener('ended', () => {
                                                                             this.isPlaying = false;
                                                                             this.currentTime = 0;
                                                                         });
                                                                         a.src = url;
                                                                         this.audio = a;
                                                                     }
                                                                 },
                                                                 togglePlay(url) {
                                                                     this.initAudio(url);
                                                                     if (this.isPlaying) {
                                                                         this.audio.pause();
                                                                         this.isPlaying = false;
                                                                     } else {
                                                                         this.audio.play().catch(e => console.error('Audio play failed:', e));
                                                                         this.isPlaying = true;
                                                                     }
                                                                 },
                                                                 cycleSpeed() {
                                                                     if (this.audio) {
                                                                         if (this.playbackSpeed === 1) {
                                                                             this.playbackSpeed = 1.5;
                                                                         } else if (this.playbackSpeed === 1.5) {
                                                                             this.playbackSpeed = 2;
                                                                         } else {
                                                                             this.playbackSpeed = 1;
                                                                         }
                                                                         this.audio.playbackRate = this.playbackSpeed;
                                                                     }
                                                                 },
                                                                 formatTime(seconds) {
                                                                     if (isNaN(seconds) || seconds === Infinity || !seconds) return '0:00';
                                                                     const m = Math.floor(seconds / 60);
                                                                     const s = Math.floor(seconds % 60).toString().padStart(2, '0');
                                                                     return `${m}:${s}`;
                                                                 },
                                                                 seek(e, url) {
                                                                     this.initAudio(url);
                                                                     if (this.audio) {
                                                                         const rect = e.currentTarget.getBoundingClientRect();
                                                                         const clickX = e.clientX - rect.left;
                                                                         const percentage = clickX / rect.width;
                                                                         if (this.duration && this.duration !== Infinity) {
                                                                             this.audio.currentTime = percentage * this.duration;
                                                                         }
                                                                     }
                                                                 },
                                                                 getProgressPercent() {
                                                                     if (this.duration > 0) {
                                                                         const pct = (this.currentTime / this.duration) * 100;
                                                                         return Math.min(100, Math.max(0, pct));
                                                                     }
                                                                     return 0;
                                                                 }
                                                             }">
                                                            
                                                            <div class="flex items-center gap-3">
                                                                <!-- Play/Pause Button -->
                                                                <button @click="togglePlay(msg.attachment_url)" class="p-2.5 rounded-full bg-teal-500 hover:bg-teal-400 text-slate-950 flex items-center justify-center shrink-0 shadow transition transform active:scale-95">
                                                                    <!-- Play Icon -->
                                                                    <svg x-show="!isPlaying" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                                        <path d="M8 5v14l11-7z"></path>
                                                                    </svg>
                                                                    <!-- Pause Icon -->
                                                                    <svg x-show="isPlaying" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" style="display:none;">
                                                                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"></path>
                                                                    </svg>
                                                                </button>
                                                                
                                                                <!-- Progress Slider -->
                                                                <div class="flex-1 flex flex-col gap-1">
                                                                    <div class="w-full bg-slate-800/80 rounded-full h-1.5 cursor-pointer relative group/timeline" @click="seek($event, msg.attachment_url)">
                                                                        <div class="bg-teal-400 h-full rounded-full transition-all duration-75"
                                                                             :style="'width: ' + getProgressPercent() + '%'"></div>
                                                                        <div class="absolute w-2.5 h-2.5 bg-teal-300 rounded-full top-1/2 -translate-y-1/2 opacity-0 group-hover/timeline:opacity-100 transition-opacity"
                                                                             :style="'left: ' + getProgressPercent() + '%'"></div>
                                                                    </div>
                                                                    <div class="flex items-center justify-between text-[9px] text-slate-400 font-semibold tracking-wider">
                                                                        <span x-text="formatTime(currentTime)"></span>
                                                                        <span x-text="formatTime(duration)"></span>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Playback Speed Control -->
                                                                <button @click="cycleSpeed()" class="text-[10px] font-bold px-2 py-1 bg-slate-950/80 hover:bg-slate-900 border border-slate-800 rounded-md text-teal-400 transition hover:text-teal-300 shrink-0 select-none shadow">
                                                                    <span x-text="playbackSpeed + 'x'"></span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    
                                                    <!-- File/Document -->
                                                    <template x-if="!isImageAttachment(msg) && !isAudioAttachment(msg)">
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
                                                                <span class="flex items-center">
                                                                    <template x-if="activeContact && activeContact.is_online">
                                                                        <!-- Delivered/Online: Double Grey Ticks -->
                                                                        <svg class="w-4 h-4 text-teal-100/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                                            <polyline points="22 10 13.5 18.5 11 16" class="opacity-80"></polyline>
                                                                        </svg>
                                                                    </template>
                                                                    <template x-if="!activeContact || !activeContact.is_online">
                                                                        <!-- Sent/Offline: Single Grey Tick -->
                                                                        <svg class="w-4 h-4 text-teal-100/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                                        </svg>
                                                                    </template>
                                                                </span>
                                                            </template>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Reaction Pills -->
                                            <template x-if="msg.reactions_data && msg.reactions_data.length > 0">
                                                <div class="flex flex-wrap gap-1 mt-2 -mb-1">
                                                    <template x-for="group in getReactionGroups(msg.reactions_data)" :key="group.emoji">
                                                        <button @click="sendReaction(msg.id, group.emoji)"
                                                                :title="group.names.join(', ')"
                                                                class="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold transition-all duration-150 hover:scale-105 active:scale-95 select-none"
                                                                :class="group.reacted 
                                                                    ? 'bg-teal-500/30 border border-teal-400/60 text-teal-200' 
                                                                    : 'bg-slate-800/80 border border-slate-700 text-slate-300 hover:border-teal-500/50'">
                                                            <span x-text="group.emoji"></span>
                                                            <span x-text="group.count" class="text-[10px]"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </template>

                                        </div>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Replying to Message Preview Banner -->
                    <div x-show="replyingTo" class="px-4 py-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between relative z-10 shrink-0" x-transition>
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="text-teal-400 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                </svg>
                            </div>
                            <div class="flex flex-col min-w-0 border-l-2 border-teal-500 pl-2 flex-1">
                                <span class="text-[11px] font-bold text-teal-500 leading-tight" x-text="replyingTo && (replyingTo.sender_id === authUserId ? 'You' : activeContact.name)"></span>
                                <template x-if="replyingTo && replyingTo.attachment_path">
                                    <div class="text-[11px] text-teal-400/80 flex items-center gap-1 font-semibold">
                                        <span x-text="isImageAttachment(replyingTo) ? '📷 Image' : (isAudioAttachment(replyingTo) ? '🎤 Voice Note' : '📁 Document')"></span>
                                        <span class="text-slate-500 font-normal truncate max-w-[150px]" x-text="'(' + replyingTo.attachment_name + ')'"></span>
                                    </div>
                                </template>
                                <p class="text-[13px] text-slate-300 truncate leading-tight mt-0.5" x-text="replyingTo && (replyingTo.message || 'Attachment')"></p>
                            </div>
                        </div>
                        <button @click="clearReplyingTo()" class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
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
                        <div x-show="!isRecordingAudio">
                            <button @click="$refs.attachmentInput.click()" 
                                    class="p-2.5 rounded-xl text-slate-400 hover:text-teal-400 hover:bg-slate-800 transition duration-150"
                                    title="Add Attachment">
                                <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                            </button>
                            <input type="file" x-ref="attachmentInput" class="hidden" @change="handleFileChange">
                        </div>

                        <!-- Microphone Icon -->
                        <div x-show="!isRecordingAudio">
                            <button @click="startAudioRecording()" 
                                    class="p-2.5 rounded-xl text-slate-400 hover:text-teal-400 hover:bg-slate-800 transition duration-150"
                                    title="Record Voice Note">
                                <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Glowing Recording Indicator -->
                        <div x-show="isRecordingAudio" style="display: none;" class="flex-1 flex items-center justify-between bg-slate-950/80 border border-rose-500/20 px-4 py-2.5 rounded-2xl">
                            <div class="flex items-center gap-2">
                                <span class="flex h-2.5 w-2.5 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                                </span>
                                <span class="text-xs text-rose-400 font-bold uppercase tracking-wider animate-pulse">Recording Voice Note</span>
                                <span class="text-xs text-slate-300 font-bold ml-2 border-l border-slate-800 pl-3" x-text="formatAudioTimer(audioDuration)">0:00</span>
                            </div>
                            
                            <button @click="cancelAudioRecording()" 
                                    class="p-1.5 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                    title="Cancel & Discard">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Typing input field -->
                        <div class="flex-1" x-show="!isRecordingAudio">
                            <input type="text" x-model="newMessageText" 
                                   @keydown="handleKeyDown" 
                                   @input="notifyTyping"
                                   class="block w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800/80 rounded-2xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-teal-500/50 focus:border-teal-500/50 transition duration-150"
                                   placeholder="Type a message...">
                        </div>

                        <!-- Send Button -->
                        <div>
                            <button @click="isRecordingAudio ? stopAndSendAudioRecording() : sendCurrentMessage()" 
                                    class="p-3 rounded-2xl bg-gradient-to-r transition duration-150 transform hover:scale-105 active:scale-95 flex items-center justify-center"
                                    :class="isRecordingAudio 
                                        ? 'from-rose-500 to-red-500 hover:from-rose-600 hover:to-red-600 text-white shadow-lg shadow-rose-500/10' 
                                        : 'from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-slate-950 shadow-lg shadow-teal-500/10'">
                                <template x-if="isRecordingAudio">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </template>
                                <template x-if="!isRecordingAudio">
                                    <svg class="w-5 h-5 text-slate-950 transform rotate-45 -translate-x-0.5 translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </template>
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

    <!-- Profile Edit Modal -->
    <div x-show="profileModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="profileModalOpen = false" x-transition.opacity></div>
        
        <!-- Modal Content -->
        <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm m-4 transform transition-all"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
             
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-200">Edit Profile</h3>
                <button @click="profileModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="saveProfile()" class="space-y-4">
                <!-- Avatar Upload with Preview -->
                <div class="flex flex-col items-center gap-3">
                    <div class="relative group/avatar cursor-pointer" @click="$refs.profileAvatarInput.click()">
                        <img :src="profileAvatarPreview || authUserAvatar" class="w-24 h-24 rounded-full object-cover border-2 border-teal-500/50 shadow-md">
                        <div class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover/avatar:opacity-100 flex items-center justify-center transition-opacity duration-150">
                            <span class="text-[10px] text-white font-medium uppercase">Change</span>
                        </div>
                    </div>
                    <input type="file" x-ref="profileAvatarInput" class="hidden" accept="image/*" @change="handleProfileAvatarChange">
                    <span class="text-[10px] text-slate-500">Click to upload a new profile photo (max 2MB)</span>
                </div>

                <!-- Display Name Field -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Display Name</label>
                    <input type="text" x-model="profileName" 
                           class="block w-full px-3 py-2 bg-slate-950/80 border border-slate-800/80 rounded-xl text-sm text-white focus:outline-none focus:ring-1 focus:ring-teal-500/50 focus:border-teal-500/50 transition duration-150">
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="profileModalOpen = false" 
                            class="flex-1 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-slate-700 transition transform active:scale-95">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSavingProfile"
                            class="flex-1 py-2 rounded-xl bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-slate-950 text-xs font-bold shadow-lg shadow-teal-500/10 transition transform active:scale-95 disabled:opacity-50 disabled:pointer-events-none">
                        <span x-show="!isSavingProfile">Save Changes</span>
                        <span x-show="isSavingProfile" class="flex items-center justify-center gap-1">
                            <svg class="animate-spin h-4 w-4 text-slate-950" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
        </div>
    </div>

    <!-- All Pinned Messages Modal -->
    <div x-show="pinnedListModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="pinnedListModalOpen = false" x-transition.opacity></div>
        
        <!-- Modal Content -->
        <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-md m-4 transform transition-all flex flex-col max-h-[80vh]"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
             
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                    </svg>
                    <h3 class="text-lg font-bold text-slate-200">Pinned Messages</h3>
                    <span class="px-2 py-0.5 text-xs font-semibold bg-teal-500/10 text-teal-400 rounded-full" x-text="pinnedCount"></span>
                </div>
                <button @click="pinnedListModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- List Container -->
            <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                <template x-for="msg in pinnedMessagesList" :key="msg.id">
                    <div class="p-3 bg-slate-950/40 hover:bg-slate-950/80 border border-slate-800/80 rounded-xl transition duration-150 flex items-start justify-between gap-3 group/pin-item">
                        <div class="min-w-0 flex-1 cursor-pointer" @click="scrollToMessage(msg.id); pinnedListModalOpen = false">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-bold text-teal-400" x-text="msg.sender_id === authUserId ? 'You' : activeContact.name"></span>
                                <span class="text-[9px] text-slate-500 font-medium" x-text="formatTime(msg.created_at)"></span>
                            </div>
                            
                            <!-- Attachment preview if present -->
                            <template x-if="msg.attachment_path">
                                <div class="mb-1 text-[11px] text-teal-400/80 flex items-center gap-1 font-semibold">
                                    <span x-text="isImageAttachment(msg) ? '📷 Image' : (isAudioAttachment(msg) ? '🎤 Voice Note' : '📁 Document')"></span>
                                    <span class="text-slate-500 font-normal truncate max-w-[150px]" x-text="'(' + msg.attachment_name + ')'"></span>
                                </div>
                            </template>
                            
                            <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed" x-text="msg.message || 'Attachment'"></p>
                        </div>
                        
                        <div class="flex items-center gap-1.5 self-center">
                            <!-- Scroll/Jump Button -->
                            <button @click="scrollToMessage(msg.id); pinnedListModalOpen = false" 
                                    class="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-teal-400 hover:bg-slate-700 transition"
                                    title="Jump to Message">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <!-- Unpin Button -->
                            <button @click="pinMessageApi(msg.id)" 
                                    class="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                    title="Unpin Message">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
                
                <template x-if="pinnedMessagesList.length === 0">
                    <div class="text-center py-8 text-slate-500 text-xs">
                        No pinned messages in this chat.
                    </div>
                </template>
            </div>
            
            <div class="mt-4 pt-3 border-t border-slate-800 flex justify-end">
                <button @click="pinnedListModalOpen = false" 
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition active:scale-95">
                    Close
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
            replyingTo: null,
            
            // Attachments
            attachmentFile: null,
            attachmentPreviewUrl: null,
            attachmentName: '',
            attachmentProgress: 0,
            isUploading: false,
            
            // Voice Notes
            isRecordingAudio: false,
            audioRecorder: null,
            audioChunks: [],
            audioDuration: 0,
            audioTimerInterval: null,
            
            // Delete Modal State
            deleteModalOpen: false,
            messageToDelete: null,

            // Profile Modal State
            profileModalOpen: false,
            profileName: '',
            profileAvatarFile: null,
            profileAvatarPreview: null,
            isSavingProfile: false,
            pinnedListModalOpen: false,
            
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

            get pinnedMessagesList() {
                if (!this.messages) return [];
                return this.messages.filter(m => m.is_pinned);
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

            setReplyingTo(msg) {
                this.replyingTo = msg;
                this.$nextTick(() => {
                    const inputEl = document.querySelector('input[placeholder="Type a message..."]');
                    if (inputEl) inputEl.focus();
                });
            },

            clearReplyingTo() {
                this.replyingTo = null;
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

                            // Smart merge: keep any optimistic (temp) messages that
                            // haven't been confirmed by the server yet, so they don't flicker.
                            const tempMessages = this.messages.filter(m => m._tempId);
                            const serverIds = new Set(data.messages.map(m => m.id));

                            // Start from server messages
                            const merged = [...data.messages];

                            // Append temp messages that the server hasn't confirmed yet
                            tempMessages.forEach(tmp => {
                                // Once server has an id matching its content, drop the temp
                                if (!serverIds.has(tmp.id)) {
                                    merged.push(tmp);
                                }
                            });

                            const hadNewMessages = merged.length > this.messages.length;
                            this.messages = merged;

                            if (isAtBottom && hadNewMessages) {
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
                
                // Clear any unsent attachment preview and reply context
                this.clearAttachment();
                this.clearReplyingTo();
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
                this.clearReplyingTo();
                this.messages = [];
            },

            // Send message execution (handles files via Axios to fetch progress)
            sendCurrentMessage() {
                if (!this.newMessageText.trim() && !this.attachmentFile) return;

                const contactId = this.activeContact.id;
                const formData = new FormData();

                // Capture and clear input IMMEDIATELY so rapid Enter presses stay separate
                const messageText = this.newMessageText.trim();
                this.newMessageText = '';

                if (messageText) {
                    formData.append('message', messageText);
                }

                if (this.replyingTo) {
                    formData.append('parent_id', this.replyingTo.id);
                }

                if (this.attachmentFile) {
                    formData.append('attachment', this.attachmentFile);
                    this.isUploading = true;
                    this.attachmentProgress = 10;
                }

                // ⚡ OPTIMISTIC UI — push message to screen in 0ms, no waiting for server
                const tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).slice(2);
                const optimisticMsg = {
                    _tempId: tempId,          // marker to identify this as a local temp message
                    id: tempId,               // used by Alpine :key, replaced when server responds
                    sender_id: this.authUserId,
                    receiver_id: contactId,
                    message: messageText,
                    parent_id: this.replyingTo ? this.replyingTo.id : null,
                    parent: this.replyingTo ? {
                        id: this.replyingTo.id,
                        sender_id: this.replyingTo.sender_id,
                        message: this.replyingTo.message,
                        attachment_path: this.replyingTo.attachment_path,
                        attachment_name: this.replyingTo.attachment_name,
                        attachment_type: this.replyingTo.attachment_type,
                        sender: this.replyingTo.sender || { name: this.replyingTo.sender_id === this.authUserId ? 'You' : this.activeContact.name }
                    } : null,
                    attachment_path: null,
                    attachment_name: null,
                    attachment_type: null,
                    attachment_url: null,
                    is_seen: false,
                    is_pinned: false,
                    is_deleted_for_everyone: false,
                    deleted_by: [],
                    reactions_data: [],
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString(),
                    _sending: true,           // show subtle sending indicator
                };
                this.messages.push(optimisticMsg);
                this.updateContactLastMessage(contactId, optimisticMsg);
                this.scrollToBottom();

                // Clear replying status
                this.clearReplyingTo();

                // Stop typing notifications
                this.whisperTyping(false);

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                window.axios.post(`/messages/${contactId}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    onUploadProgress: (progressEvent) => {
                        if (this.attachmentFile) {
                            const pct = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            this.attachmentProgress = pct;
                        }
                    }
                })
                .then(response => {
                    if (response.data.success) {
                        const newMsg = response.data.message;

                        // Swap the optimistic placeholder with the real server message
                        const idx = this.messages.findIndex(m => m._tempId === tempId);
                        if (idx !== -1) {
                            this.messages.splice(idx, 1, newMsg);
                        } else {
                            // Already replaced by polling — nothing to do
                        }

                        this.updateContactLastMessage(contactId, newMsg);
                        this.clearAttachment();
                    }
                })
                .catch(err => {
                    console.error('Error sending message:', err);
                    // Remove the optimistic message on failure
                    this.messages = this.messages.filter(m => m._tempId !== tempId);
                    // Restore text so user doesn't lose it
                    if (!this.newMessageText.trim()) {
                        this.newMessageText = messageText;
                    }
                    alert('Failed to send message. Please try again.');
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

            isAudioAttachment(msg) {
                if (!msg.attachment_path) return false;
                if (msg.attachment_type === 'audio') return true;
                const path = (msg.attachment_path || '').toLowerCase();
                const name = (msg.attachment_name || '').toLowerCase();
                return path.endsWith('.webm') || 
                       path.endsWith('.mp3') || 
                       path.endsWith('.wav') || 
                       path.endsWith('.m4a') || 
                       name.includes('voice_note');
            },

            isImageAttachment(msg) {
                if (!msg.attachment_path) return false;
                if (msg.attachment_type === 'image') return true;
                const path = (msg.attachment_path || '').toLowerCase();
                return path.endsWith('.jpg') || 
                       path.endsWith('.jpeg') || 
                       path.endsWith('.png') || 
                       path.endsWith('.gif') || 
                       path.endsWith('.webp');
            },

            formatSnippet(msg) {
                if (!msg) return 'No messages yet';
                if (msg.attachment_path) {
                    if (this.isImageAttachment(msg)) return '📷 Image';
                    if (this.isAudioAttachment(msg)) return '🎤 Voice Note';
                    return '📁 Document';
                }
                return msg.message || '';
            },

            openProfileModal() {
                this.profileName = this.authUserName;
                this.profileAvatarFile = null;
                this.profileAvatarPreview = null;
                this.profileModalOpen = true;
            },

            handleProfileAvatarChange(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                this.profileAvatarFile = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.profileAvatarPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            },

            saveProfile() {
                if (!this.profileName.trim()) return;
                
                this.isSavingProfile = true;
                const formData = new FormData();
                formData.append('name', this.profileName);
                if (this.profileAvatarFile) {
                    formData.append('avatar', this.profileAvatarFile);
                }
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                fetch('/profile/update', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    this.isSavingProfile = false;
                    if (data.success) {
                        // Update state locally
                        this.authUserName = data.name;
                        this.authUserAvatar = data.avatar_url;
                        this.profileModalOpen = false;
                    } else {
                        alert(data.error || 'Failed to update profile.');
                    }
                })
                .catch(err => {
                    this.isSavingProfile = false;
                    console.error("Error saving profile:", err);
                    alert('An error occurred. Make sure the image is valid and under 2MB.');
                });
            },

            startAudioRecording() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Audio recording is not supported in this browser.');
                    return;
                }

                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(stream => {
                        this.audioChunks = [];
                        this.audioDuration = 0;
                        this.isRecordingAudio = true;
                        
                        this.audioRecorder = new MediaRecorder(stream);
                        this.audioRecorder.addEventListener('dataavailable', e => {
                            if (e.data.size > 0) {
                                this.audioChunks.push(e.data);
                            }
                        });

                        this.audioRecorder.start(100);

                        this.audioTimerInterval = setInterval(() => {
                            this.audioDuration++;
                        }, 1000);
                    })
                    .catch(err => {
                        console.error('Error starting audio recording:', err);
                        alert('Could not access microphone. Please grant microphone permissions.');
                    });
            },

            cancelAudioRecording() {
                if (this.audioRecorder && this.audioRecorder.state !== 'inactive') {
                    this.audioRecorder.stop();
                    this.audioRecorder.stream.getTracks().forEach(track => track.stop());
                }
                
                clearInterval(this.audioTimerInterval);
                this.isRecordingAudio = false;
                this.audioChunks = [];
                this.audioDuration = 0;
            },

            stopAndSendAudioRecording() {
                if (!this.audioRecorder || this.audioRecorder.state === 'inactive') return;

                this.audioRecorder.addEventListener('stop', () => {
                    const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    this.sendVoiceNoteBlob(audioBlob);
                }, { once: true });

                this.audioRecorder.stop();
                this.audioRecorder.stream.getTracks().forEach(track => track.stop());
                
                clearInterval(this.audioTimerInterval);
                this.isRecordingAudio = false;
            },

            sendVoiceNoteBlob(blob) {
                const contactId = this.activeContact.id;
                const formData = new FormData();
                
                const filename = 'voice_note_' + Date.now() + '.webm';
                formData.append('attachment', blob, filename);
                formData.append('message', '');

                if (this.replyingTo) {
                    formData.append('parent_id', this.replyingTo.id);
                }

                // Clear replying status
                this.clearReplyingTo();

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                this.isUploading = true;
                this.attachmentProgress = 10;

                window.axios.post(`/messages/${contactId}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        this.attachmentProgress = percentCompleted;
                    }
                })
                .then(response => {
                    this.isUploading = false;
                    if (response.data.success) {
                        const newMsg = response.data.message;
                        this.messages.push(newMsg);
                        this.updateContactLastMessage(contactId, newMsg);
                        this.scrollToBottom();
                    }
                })
                .catch(err => {
                    console.error("Error uploading voice note:", err);
                    alert("Failed to upload voice note.");
                    this.isUploading = false;
                });
            },

            formatAudioTimer(seconds) {
                const m = Math.floor(seconds / 60);
                const s = (seconds % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            },

            // ================== EMOJI REACTIONS ==================

            sendReaction(messageId, emoji) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch(`/messages/${messageId}/react`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ reaction: emoji }),
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Patch the local message reactions immediately
                        const msg = this.messages.find(m => m.id === data.message_id);
                        if (msg) {
                            msg.reactions_data = data.reactions;
                        }
                    }
                })
                .catch(err => console.error('Reaction error:', err));
            },

            /**
             * Groups raw reactions array into deduplicated emoji → {emoji, count, reacted, names}
             */
            getReactionGroups(reactionsData) {
                if (!reactionsData || !reactionsData.length) return [];
                const groups = {};
                reactionsData.forEach(r => {
                    if (!groups[r.reaction]) {
                        groups[r.reaction] = {
                            emoji: r.reaction,
                            count: 0,
                            reacted: false,
                            names: [],
                        };
                    }
                    groups[r.reaction].count++;
                    groups[r.reaction].names.push(r.user_name);
                    if (r.user_id === this.authUserId) {
                        groups[r.reaction].reacted = true;
                    }
                });
                return Object.values(groups);
            }
        };
    }
</script>
@endsection
