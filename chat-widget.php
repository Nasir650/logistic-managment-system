<!-- Chat Widget HTML -->
<div id="chat" class="fixed bottom-4 right-4 w-80 bg-white rounded-lg shadow-lg border hidden z-50">
    <div class="flex items-center justify-between px-3 py-2 bg-blue-600 text-white rounded-t-lg">
        <div class="font-semibold" id="chat-title">Chat</div>
        <button id="chat-close" class="text-white hover:text-gray-200 text-xl leading-none">&times;</button>
    </div>
    <div id="chat-messages" class="h-64 overflow-y-auto p-3 space-y-2 text-sm bg-gray-50"></div>
    <div class="p-2 border-t">
        <input id="chat-input" class="w-full border rounded px-2 py-1 text-sm" 
               placeholder="Type a message and hit Enter" maxlength="2000">
    </div>
    <div id="chat-status" class="px-3 py-1 text-xs text-gray-500 border-t">Offline</div>
</div>

<script>
// SSE + AJAX Chat System
(function() {
    // HTML escaping function
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Chat client using SSE + AJAX
    class SSEChat {
        constructor() {
            this.activeRoom = null;
            this.eventSource = null;
            this.lastMessageId = 0;
            this.isConnected = false;
        }

        async joinRoom(room) {
            this.leaveRoom();
            this.activeRoom = room;
            this.lastMessageId = 0;
            
            // Load chat history first
            await this.loadHistory(room);
            
            // Start SSE connection
            this.connectSSE(room);
        }

        async loadHistory(room) {
            try {
                const response = await fetch(`/logi/chat_history.php?room=${encodeURIComponent(room)}`);
                const data = await response.json();
                
                if (data.success) {
                    messagesList.innerHTML = '';
                    data.messages.forEach(msg => {
                        this.addMessage(msg);
                        this.lastMessageId = Math.max(this.lastMessageId, msg.id);
                    });
                    this.scrollToBottom();
                }
            } catch (error) {
                console.error('Error loading history:', error);
                this.addSystemMessage('Failed to load chat history');
            }
        }

        connectSSE(room) {
            const url = `/logi/chat_stream.php?room=${encodeURIComponent(room)}&last_id=${this.lastMessageId}`;
            this.eventSource = new EventSource(url);
            
            this.eventSource.onopen = () => {
                this.isConnected = true;
                this.updateStatus('Connected');
            };

            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    
                    switch (data.type) {
                        case 'message':
                            this.addMessage(data);
                            this.lastMessageId = Math.max(this.lastMessageId, data.id);
                            this.scrollToBottom();
                            break;
                        case 'connected':
                            this.updateStatus('Connected');
                            break;
                        case 'heartbeat':
                            // Keep connection alive
                            break;
                        case 'error':
                            this.addSystemMessage('Error: ' + data.message);
                            break;
                    }
                } catch (error) {
                    console.error('Error parsing SSE message:', error);
                }
            };

            this.eventSource.onerror = () => {
                this.isConnected = false;
                this.updateStatus('Disconnected - Reconnecting...');
                
                // Auto-reconnect after 3 seconds
                setTimeout(() => {
                    if (this.activeRoom) {
                        this.connectSSE(this.activeRoom);
                    }
                }, 3000);
            };
        }

        leaveRoom() {
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
            this.activeRoom = null;
            this.isConnected = false;
            this.updateStatus('Offline');
        }

        async sendMessage(body) {
            if (!this.activeRoom || !body.trim()) return;

            try {
                const response = await fetch('/logi/chat_send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        room: this.activeRoom,
                        body: body.trim()
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                this.addSystemMessage('Failed to send message: ' + error.message);
            }
        }

        addMessage(message) {
            const sender = escapeHtml(message.sender_role || '');
            const body = escapeHtml(message.body || '');
            const time = escapeHtml(message.created_at || '');
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'bg-white rounded px-3 py-2 shadow-sm border';
            messageDiv.innerHTML = `
                <div class="text-xs text-gray-500 mb-1">${sender} • ${time}</div>
                <div class="text-sm">${body}</div>
            `;
            messagesList.appendChild(messageDiv);
        }

        addSystemMessage(text) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'bg-yellow-50 rounded px-3 py-2 border border-yellow-200';
            messageDiv.innerHTML = `<div class="text-xs text-yellow-700">${escapeHtml(text)}</div>`;
            messagesList.appendChild(messageDiv);
            this.scrollToBottom();
        }

        updateStatus(status) {
            document.getElementById('chat-status').textContent = status;
        }

        scrollToBottom() {
            messagesList.scrollTop = messagesList.scrollHeight;
        }
    }

    // Initialize chat system
    const chat = new SSEChat();

    // UI elements
    const chatBox = document.getElementById('chat');
    const chatTitle = document.getElementById('chat-title');
    const messagesList = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const closeButton = document.getElementById('chat-close');

    // Close button functionality
    closeButton.onclick = () => {
        chatBox.classList.add('hidden');
        chat.leaveRoom();
        messagesList.innerHTML = '';
    };

    // Send message on Enter
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && chatInput.value.trim()) {
            chat.sendMessage(chatInput.value.trim());
            chatInput.value = '';
        }
    });

    // Function to open a chat room
    function openRoom(room, label) {
        chatTitle.textContent = label;
        messagesList.innerHTML = '<div class="text-center text-gray-500 text-sm">Loading chat...</div>';
        chatBox.classList.remove('hidden');
        chat.joinRoom(room);
    }

    // Global functions to open chat rooms
    window.openShipmentChat = function(shipmentId, label = '') {
        openRoom('shipment:' + shipmentId, label || ('Shipment #' + shipmentId));
    };

    window.openAdminDriverChat = function(driverId, label = '') {
        openRoom('admin_driver:' + driverId, label || ('Driver #' + driverId));
    };

    console.log('SSE Chat system initialized successfully');
})();
</script>