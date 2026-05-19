document.addEventListener('DOMContentLoaded', function () {

    const chatMessages = document.getElementById('chat-messages');
    const chatInput    = document.getElementById('chat-input');
    const chatSend     = document.getElementById('chat-send');
    const clearBtn     = document.getElementById('clear-btn');

    if (!chatMessages || !chatInput || !chatSend || !clearBtn) return;

    // BFCache: coming back via Back/Forward can restore the page without a full reload; re-fetch history.
    window.addEventListener('pageshow', function (ev) {
        if (ev.persisted) {
            loadHistory();
        }
    });

    loadHistory();
    async function loadHistory() {
        try {
            const res = await fetch('../backend/api/chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'load' }),
                credentials: 'same-origin'
            });

            let data;
            try {
                data = await res.json();
            } catch (e) {
                throw new Error('Server did not return JSON (check PHP errors / chatbot_api.php).');
            }

            if (!res.ok) {
                throw new Error(data.error || data.reply || ('Request failed (HTTP ' + res.status + ')'));
            }

            chatMessages.innerHTML = '';

            if (!Array.isArray(data.history)) {
                if (data.reply && String(data.reply).indexOf('Message cannot be empty') !== -1) {
                    appendMessage('Could not restore chat. Please refresh the page.', 'bot');
                    return;
                }
                appendMessage(data.reply || data.error || 'Unexpected response from server.', 'bot');
                return;
            }

            if (data.history.length > 0) {
                data.history.forEach(function (msg) {
                    appendMessage(msg.message, msg.role === 'user' ? 'user' : 'bot');
                });
            } else {
                const who = (chatMessages.dataset.displayName || 'there').trim() || 'there';
                appendMessage("Hi " + who + "! Ask me about your wallet or spending.", 'bot');
            }
        } catch (error) {
            console.log(error);
            chatMessages.innerHTML = '';
            appendMessage(error.message || 'Could not load chat.', 'bot');
        }
    }

    async function clearHistory() {
        var confirmClear = typeof window.appConfirm === 'function'
            ? window.appConfirm
            : function (msg, opts) { return Promise.resolve(window.confirm(msg)); };

        if (!(await confirmClear('Clear all messages in this chat?', { title: 'Clear chat', okText: 'Clear' }))) return;

        try {
            const res = await fetch('../backend/api/chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'clear' }),
                credentials: 'same-origin'
            });

            let data;
            try {
                data = await res.json();
            } catch (e) {
                throw new Error('Bad response from server.');
            }

            if (data.success) {
                chatMessages.innerHTML = '';
                appendMessage('Chat cleared.', 'bot');
            } else {
                appendMessage(data.error || 'Clear was not completed.', 'bot');
            }
        } catch (error) {
            console.log(error);
            appendMessage('Could not clear chat.', 'bot');
        }
    }

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        appendMessage(message, 'user');
        chatInput.value = '';

        appendMessage('Thinking…', 'typing', 'typing');

        try {
            const res = await fetch('../backend/api/chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message }),
                credentials: 'same-origin'
            });

            let data;
            try {
                data = await res.json();
            } catch (e) {
                removeTyping();
                appendMessage('Invalid response from server. Check chatbot_api.php for PHP errors.', 'bot');
                return;
            }

            if (!res.ok && data.error && !data.reply) {
                removeTyping();
                appendMessage(data.error || ('Request failed (' + res.status + ')'), 'bot');
                return;
            }

            removeTyping();
            appendMessage(data.reply || data.error || 'No reply.', 'bot');
        } catch (err) {
            removeTyping();
            appendMessage('Could not reach server.', 'bot');
        }
    }

    function removeTyping() {
        const el = document.getElementById('typing');
        if (el) el.remove();
    }

    function appendMessage(text, type, id) {
        const div = document.createElement('div');
        div.className = 'chat-bubble ' + type;
        if (id) div.id = id;
        div.textContent = text;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    chatSend.addEventListener('click', sendMessage);
    clearBtn.addEventListener('click', clearHistory);
    chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') sendMessage();
    });
});
