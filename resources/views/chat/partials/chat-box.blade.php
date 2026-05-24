<!-- Chat Screen Layout -->
<div class="flex-1 flex flex-col h-full overflow-hidden">
    
    <!-- Chat Header -->
    <div class="h-16 px-4 border-b border-slate-800/80 bg-slate-900/40 flex items-center justify-between flex-shrink-0">
        <div @click="if(activeContact.is_group) groupInfoSidebarOpen = !groupInfoSidebarOpen" class="flex items-center gap-3 min-w-0 cursor-pointer group/header">
            <!-- Back Button (Mobile) -->
            <button @click.stop="closeActiveChat()" class="md:hidden p-2 -ml-2 rounded-xl text-slate-400 hover:text-white transition duration-150">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>

            <img :src="activeContact.avatar_url" class="w-10 h-10 rounded-full object-cover border border-slate-800 group-hover/header:scale-105 transition duration-200">
            <div class="min-w-0">
                <h3 class="font-semibold text-sm leading-tight truncate text-slate-200 group-hover/header:text-teal-400 transition duration-150" x-text="activeContact.name"></h3>
                <div class="text-[10px] leading-none mt-1">
                    <template x-if="activeContact.typing">
                        <span class="text-teal-400 font-medium italic animate-pulse" x-text="activeContact.typing === true ? 'typing...' : activeContact.typing"></span>
                    </template>
                    <template x-if="!activeContact.typing">
                        <span :class="activeContact.is_group ? 'text-teal-400/80 font-medium' : (activeContact.is_online ? 'text-teal-400 font-medium' : 'text-slate-500')" 
                              x-text="activeContact.is_group ? (activeContact.members ? activeContact.members.length + ' members • Click for info' : 'Group • Click for info') : (activeContact.is_online ? 'Online' : 'Offline')"></span>
                    </template>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Info Button -->
            <template x-if="activeContact.is_group">
                <button @click="groupInfoSidebarOpen = !groupInfoSidebarOpen" 
                        class="p-2 rounded-xl text-slate-400 hover:text-teal-400 hover:bg-teal-500/10 transition duration-150" 
                        :class="groupInfoSidebarOpen ? 'text-teal-400 bg-teal-500/10' : ''"
                        title="Group Info">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>
            </template>
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
                            
                            <!-- Sender Name (Only in Group Chats for other members) -->
                            <template x-if="activeContact.is_group && msg.sender_id !== authUserId && msg.sender">
                                <div class="text-[11px] font-bold text-teal-400 mb-1 leading-none hover:underline"
                                     x-text="msg.sender.name"></div>
                            </template>

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
                                                <!-- Read: Double Blue Ticks (WhatsApp Style) -->
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
        </form>
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
