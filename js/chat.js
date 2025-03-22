document.addEventListener('DOMContentLoaded', function() {
    console.log('WP Ikigai: DOMContentLoaded event triggered');
    
    const chatContainer = document.getElementById('wp-ikigai-chat');
    if (!chatContainer) {
        console.error('WP Ikigai: Chat container not found');
        return;
    }
    console.log('WP Ikigai: Chat container found');

    // Check AJAX configuration
    if (!window.wpIkigai || !window.wpIkigai.ajaxUrl || !window.wpIkigai.nonce) {
        console.error('WP Ikigai: AJAX configuration missing:', window.wpIkigai);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'wp-ikigai-error';
        errorDiv.style.cssText = 'color: #dc3232; padding: 10px; margin: 10px 0; border: 1px solid #dc3232; border-radius: 4px; background: #fff;';
        errorDiv.textContent = 'Error: AJAX configuration not found';
        chatContainer.prepend(errorDiv);
        return;
    }
    console.log('WP Ikigai: AJAX configuration found:', {
        ajaxUrl: window.wpIkigai.ajaxUrl,
        nonceAvailable: !!window.wpIkigai.nonce
    });

    // Get all necessary DOM elements
    const messagesContainer = chatContainer.querySelector('.wp-ikigai-chat-messages');
    const messageInput = chatContainer.querySelector('#wp-ikigai-message');
    const sendButton = chatContainer.querySelector('.wp-ikigai-send');
    const feedbackContainer = chatContainer.querySelector('.wp-ikigai-feedback');
    const loadingIndicator = chatContainer.querySelector('.wp-ikigai-loading');
    const copyChat = chatContainer.querySelector('.wp-ikigai-copy-chat');
    const copyIkigai = chatContainer.querySelector('.wp-ikigai-copy-ikigai');
    const feedbackButtons = chatContainer.querySelectorAll('.wp-ikigai-feedback-btn');

    // Check if all elements are present
    if (!messagesContainer || !messageInput || !sendButton || !feedbackContainer || !loadingIndicator) {
        console.error('WP Ikigai: Missing chat elements');
        return;
    }

    let conversation = [];
    let isWaitingForResponse = false;
    let currentPhase = 1;
    let isProcessing = false;

    // Erstelle die Phasen-Anzeige
    const phaseIndicator = document.createElement('div');
    phaseIndicator.className = 'wp-ikigai-phase-indicator';
    
    // Füge den Fortschrittsbalken hinzu
    const progressBar = document.createElement('div');
    progressBar.className = 'phase-progress';
    phaseIndicator.appendChild(progressBar);

    const phases = [
        { id: 1, text: 'What do you love?' },
        { id: 2, text: 'What are you good at?' },
        { id: 3, text: 'What does the world need?' },
        { id: 4, text: 'For what would people pay?' }
    ];

    // Füge die Phasen-Anzeige zum Container hinzu
    phases.forEach(phase => {
        const phaseElement = document.createElement('div');
        phaseElement.className = `phase-item phase-${phase.id}`;
        phaseElement.innerHTML = `
            <div class="phase-circle">
                <span class="phase-number">${phase.id}</span>
            </div>
            <span class="phase-text">${phase.text}</span>
        `;
        phaseIndicator.appendChild(phaseElement);
    });

    // Füge die Phasen-Anzeige nach dem Chat-Input ein
    chatContainer.insertBefore(phaseIndicator, feedbackContainer);

    function updatePhaseIndicator(phase) {
        const allPhases = phaseIndicator.querySelectorAll('.phase-item');
        const progressBar = phaseIndicator.querySelector('.phase-progress');
        
        allPhases.forEach((item, index) => {
            item.classList.remove('active', 'completed');
            if (phase === 'done') {
                item.classList.add('completed');
            } else if (index < phase - 1) {
                item.classList.add('completed');
            } else if (index === phase - 1) {
                item.classList.add('active');
            }
        });

        // Berechne die Breite des Fortschrittsbalkens
        let progressWidth = '0';
        if (phase === 'done') {
            progressWidth = 'calc(100% - 120px)'; // Volle Breite minus Randabstand
        } else if (phase > 1 && phase <= 4) {
            const progress = ((phase - 1) / 3) * 100;
            progressWidth = `calc(${progress}% * ((100% - 120px) / 100))`;
        }
        progressBar.style.width = progressWidth;
    }

    function extractPhase(message) {
        const phaseMatch = message.match(/\[PHASE=(\d+|done)\]/);
        if (phaseMatch) {
            return phaseMatch[1] === 'done' ? 'done' : parseInt(phaseMatch[1]);
        }
        return null;
    }

    function addMessage(message, isUser = false) {
        console.log('WP Ikigai: Füge Nachricht hinzu:', { message, isUser });
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${isUser ? 'user-message' : 'bot-message'}`;
        
        let cleanMessage = message;
        
        if (!isUser) {
            // Extrahiere und aktualisiere die Phase
            const newPhase = extractPhase(message);
            if (newPhase) {
                currentPhase = newPhase;
                updatePhaseIndicator(currentPhase);
                // Entferne das Phase-Tag aus der Nachricht
                cleanMessage = message.replace(/\[PHASE=(?:\d+|done)\]/, '').trim();
            }

            // Prüfe, ob marked verfügbar ist
            if (typeof marked !== 'undefined') {
                console.log('WP Ikigai: Marked.js verfügbar, parse Markdown');
                marked.setOptions({
                    breaks: true,
                    gfm: true,
                    headerIds: false,
                    mangle: false,
                    sanitize: true,
                });
                messageDiv.innerHTML = marked.parse(cleanMessage);
            } else {
                console.warn('WP Ikigai: Marked.js nicht verfügbar, verwende Plaintext');
                messageDiv.textContent = cleanMessage;
            }
        } else {
            messageDiv.textContent = cleanMessage;
        }

        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function setLoading(loading) {
        isWaitingForResponse = loading;
        messageInput.disabled = loading;
        sendButton.disabled = loading;
        loadingIndicator.style.display = loading ? 'block' : 'none';
        if (loading) {
            feedbackContainer.style.display = 'none';
        }
    }

    async function sendMessage(message = '') {
        if (isProcessing || isWaitingForResponse) return;
        isProcessing = true;

        try {
            setLoading(true);

            // Füge die aktuelle Phase zur Nachricht hinzu
            const messageWithPhase = `[CURRENT_PHASE=${currentPhase}] ${message}`;

            const formData = new URLSearchParams();
            formData.append('action', 'wp_ikigai_chat');
            formData.append('nonce', window.wpIkigai.nonce);
            formData.append('message', messageWithPhase);
            formData.append('conversation', JSON.stringify(conversation));

            const response = await fetch(window.wpIkigai.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data?.message || 'Unbekannter Fehler');
            }

            // Verarbeite die Bot-Antwort
            let botMessage = data.data.message;
            
            if (message) {
                addMessage(message, true);
            }
            addMessage(botMessage, false);
            conversation = data.data.conversation;

            messageInput.value = '';
            messageInput.focus();

        } catch (error) {
            console.error('WP Ikigai: Fehler:', error);
            addMessage('Entschuldigung, es gab einen technischen Fehler: ' + error.message, false);
        } finally {
            setLoading(false);
            isProcessing = false;
        }
    }

    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (message) {
                sendMessage(message);
            }
        }
    });

    sendButton.addEventListener('click', () => {
        const message = messageInput.value.trim();
        if (message) {
            sendMessage(message);
        }
    });

    // Starte den Chat automatisch
    setTimeout(() => {
        sendMessage();
    }, 500);
}); 