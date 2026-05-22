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
