/**
 * Chat-Interaktionsskript für WP-Ikigai.
 *
 * Dieses Skript behandelt die Chat-Funktionalität zwischen Benutzer und Bot.
 *
 * @package WP_Ikigai
 */

document.addEventListener(
	'DOMContentLoaded',
	function () {
		console.log( 'WP Ikigai: DOMContentLoaded event triggered' );

		const chatContainer = document.getElementById( 'ikigai-chat-container' );
		if ( ! chatContainer) {
			console.error( 'WP Ikigai: Chat container not found' );
			return;
		}
		console.log( 'WP Ikigai: Chat container found' );

		// Check AJAX configuration
		if ( ! window.wpIkigai || ! window.wpIkigai.ajaxUrl || ! window.wpIkigai.nonce) {
			console.error( 'WP Ikigai: AJAX configuration missing:', window.wpIkigai );
			const errorDiv		   = document.createElement( 'div' );
			errorDiv.className	   = 'wp-ikigai-error';
			errorDiv.style.cssText = 'color: #dc3232; padding: 10px; margin: 10px 0; border: 1px solid #dc3232; border-radius: 4px; background: #fff;';
			errorDiv.textContent   = 'Error: AJAX configuration not found';
			chatContainer.prepend( errorDiv );
			return;
		}
		console.log(
			'WP Ikigai: AJAX configuration found:',
			{
				ajaxUrl: window.wpIkigai.ajaxUrl,
				nonceAvailable: ! ! window.wpIkigai.nonce
			}
		);

		// Get all necessary DOM elements
		const messagesContainer = chatContainer.querySelector( '.wp-ikigai-chat-messages' );
		const messageInput		= chatContainer.querySelector( '#ikigai-message-input' );
		const sendButton		= chatContainer.querySelector( '#ikigai-send-button' );
		const feedbackContainer = chatContainer.querySelector( '#ikigai-feedback-container' );
		const loadingIndicator	= chatContainer.querySelector( '.wp-ikigai-loading' );
		const copyChat			= chatContainer.querySelector( '.wp-ikigai-copy-chat' );
		const copyIkigai		= chatContainer.querySelector( '.wp-ikigai-copy-ikigai' );
		const feedbackButtons	= chatContainer.querySelectorAll( '.wp-ikigai-feedback-btn' );
		const resetButton       = document.getElementById( 'ikigai-reset-button' );

		// Check if all elements are present
		if ( ! messagesContainer || ! messageInput || ! sendButton || ! feedbackContainer || ! loadingIndicator) {
			console.error( 'WP Ikigai: Missing chat elements' );
			return;
		}

		let conversation		 = [];
		let isWaitingForResponse = false;
		let currentPhase		 = 1;
		let isProcessing		 = false;

		// Erstelle die Phasen-Anzeige
		const phaseIndicator     = document.createElement( 'div' );
		phaseIndicator.id        = 'ikigai-phase-indicator';
		phaseIndicator.className = 'ikigai-phase-indicator';

		// Füge den Fortschrittsbalken hinzu.
		const progressBar     = document.createElement( 'div' );
		progressBar.className = 'phase-progress';
		phaseIndicator.appendChild( progressBar );

		const phases = [
		{ id: 1, text: 'What do you love?' },
		{ id: 2, text: 'What are you good at?' },
		{ id: 3, text: 'What does the world need?' },
		{ id: 4, text: 'For what would people pay?' }
		];

		// Füge die Phasen-Anzeige zum Container hinzu
		phases.forEach(
			phase => {
            const phaseElement	   = document.createElement( 'div' );
            phaseElement.className = `phase - item phase - ${phase.id}`;
            phaseElement.innerHTML = `
					< div class        = "phase-circle" >
						< span class = "phase-number" > ${phase.id} < / span >
					< / div >
					< span class = "phase-text" > ${phase.text} < / span >
				`;
            phaseIndicator.appendChild( phaseElement );
			}
		);

		// Füge die Phasen-Anzeige nach dem Chat-Input ein
		chatContainer.insertBefore( phaseIndicator, feedbackContainer );

		function updatePhaseIndicator(phase) {
			const allPhases   = phaseIndicator.querySelectorAll( '.phase-item' );
			const progressBar = phaseIndicator.querySelector( '.phase-progress' );

			allPhases.forEach(
				(item, index) => {
                item.classList.remove( 'active', 'completed' );
                if (phase === 'done') {
                    item.classList.add( 'completed' );
                } else if (index < phase - 1) {
                item.classList.add( 'completed' );
                } else if (index === phase - 1) {
						item.classList.add( 'active' );
					}
				}
			);

			// Berechne die Breite des Fortschrittsbalkens.
			let progressWidth = '0';
			if (phase === 'done') {
				progressWidth = 'calc(100% - 120px)'; // Volle Breite minus Randabstand.
			} else if (phase > 1 && phase <= 4) {
				const progress = ((phase - 1) / 3) * 100;
				progressWidth  = `calc( ${progress} % * ((100 % - 120px) / 100) )`;
			}

			// Setze den Fortschrittsbalken.
			if (progressBar) {
				progressBar.style.width = progressWidth;
			}
		}

		function extractPhase(message) {
			// Versuche, die Phase aus der Nachricht zu extrahieren.
			const phaseMatch = message.match( /\[Phase: (\d+|done)\]/ );
			return phaseMatch ? phaseMatch[1] : null;
		}

		function addMessage(message, isUser = false) {
			console.log( 'WP Ikigai: Füge Nachricht hinzu:', { message, isUser } );
			const messageElement = document.createElement( 'div' );
			messageElement.classList.add( 'message', isUser ? 'user-message' : 'bot-message' );

			let cleanMessage = message;

			if ( ! isUser) {
				// Extrahiere und aktualisiere die Phase
				const newPhase = extractPhase( message );
				if (newPhase) {
					currentPhase = newPhase;
					updatePhaseIndicator( currentPhase );
					// Entferne das Phase-Tag aus der Nachricht
					cleanMessage = message.replace( /\[Phase: (\d+|done)\]/, '' ).trim();
				}

				// Prüfe, ob marked verfügbar ist
				if (typeof marked !== 'undefined') {
					console.log( 'WP Ikigai: Marked.js verfügbar, parse Markdown' );
					marked.setOptions(
						{
							breaks: true,
							gfm: true,
							headerIds: false,
							mangle: false,
							sanitize: true,
						}
					);
					messageElement.innerHTML = marked.parse( cleanMessage );
				} else {
					console.warn( 'WP Ikigai: Marked.js nicht verfügbar, verwende Plaintext' );
					messageElement.textContent = cleanMessage;
				}
			} else {
				messageElement.textContent = cleanMessage;
			}

			messagesContainer.appendChild( messageElement );
			messagesContainer.scrollTop = messagesContainer.scrollHeight;
		}

		function setLoading(loading) {
			if (loading) {
				// Füge eine Klasse zum messagesContainer hinzu, während der Bot antwortet.
				messagesContainer.classList.add( 'loading' );
				sendButton.disabled = true;
			} else {
				// Entferne die Klasse, wenn die Antwort eingegangen ist.
				messagesContainer.classList.remove( 'loading' );
				sendButton.disabled = false;
			}
		}

		async function sendMessage(message = '') {
			if (isProcessing) {
				return;
			}

			// Verhindere, dass leere Nachrichten gesendet werden.
			const msg = message || messageInput.value.trim();
			if ( ! msg) {
				return;
			}

			isProcessing = true;
			setLoading( true );

			try {
				// Sende die Nachricht an das Backend.
				// Füge die aktuelle Phase zur Nachricht hinzu.
				const messageWithPhase = `[CURRENT_PHASE = ${currentPhase}] ${msg}`;

				const formData = new URLSearchParams();
				formData.append( 'action', 'wp_ikigai_chat' );
				formData.append( 'message', messageWithPhase );
				formData.append( 'conversation', conversation );
				formData.append( '_wpnonce', window.wpIkigai.nonce );

				const response = await fetch(
					window.wpIkigai.ajaxUrl,
					{
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: formData
					}
				);

				const data = await response.json();

				if ( ! data.success) {
					throw new Error( data.data ? .message || 'Unbekannter Fehler' );
				}

				// Verarbeite die Bot-Antwort
				let botMessage = data.data.message;

				if (msg) {
					addMessage( msg, true );
				}
				addMessage( botMessage, false );
				conversation = data.data.conversation;

				messageInput.value = '';
				messageInput.focus();

			} catch (error) {
				console.error( 'WP Ikigai: Fehler:', error );
				addMessage( 'Entschuldigung, es gab einen technischen Fehler: ' + error.message, false );
			} finally {
				setLoading( false );
				isProcessing = false;
			}
		}

		messageInput.addEventListener(
			'keypress',
			(e) => {
				if (e.key === 'Enter' && ! e.shiftKey) {
					e.preventDefault();
					const message = messageInput.value.trim();
					if (message) {
						sendMessage( message );
					}
				}
			}
		);

		sendButton.addEventListener(
			'click',
			() => {
				const message = messageInput.value.trim();
				if (message) {
					sendMessage( message );
				}
			}
		);

		// Starte den Chat automatisch
		setTimeout(
			() => {
				sendMessage();
			},
			500
		);
	}
);
