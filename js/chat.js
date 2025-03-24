/**
 * Chat-Interaktionsskript für Ikigai Finder.
 *
 * Dieses Skript behandelt die Chat-Funktionalität zwischen Benutzer und Bot.
 *
 * @package Ikigai_Finder
 */

document.addEventListener(
	'DOMContentLoaded',
	function () {
		console.log( 'Ikigai Finder: DOMContentLoaded event triggered' );

		const chatContainer = document.getElementById( 'ikigai-finder-chat' );
		if ( ! chatContainer) {
			console.error( 'Ikigai Finder: Chat container not found' );
			return;
		}
		console.log( 'Ikigai Finder: Chat container found' );

		// Check AJAX configuration
		if ( ! window.ikigaiFinder || ! window.ikigaiFinder.ajaxUrl || ! window.ikigaiFinder.nonce) {
			console.error( 'Ikigai Finder: AJAX configuration missing:', window.ikigaiFinder );
			const errorDiv		   = document.createElement( 'div' );
			errorDiv.className	   = 'ikigai-finder-error';
			errorDiv.style.cssText = 'color: #dc3232; padding: 10px; margin: 10px 0; border: 1px solid #dc3232; border-radius: 4px; background: #fff;';
			errorDiv.textContent   = 'Error: AJAX configuration not found';
			chatContainer.prepend( errorDiv );
			return;
		}
		console.log(
			'Ikigai Finder: AJAX configuration found:',
			{
				ajaxUrl: window.ikigaiFinder.ajaxUrl,
				nonceAvailable: ! ! window.ikigaiFinder.nonce
			}
		);

		// Get all necessary DOM elements
		const messagesContainer = chatContainer.querySelector( '.ikigai-finder-chat-messages' );
		const messageInput		= chatContainer.querySelector( '#ikigai-finder-message' );
		const sendButton		= chatContainer.querySelector( '.ikigai-finder-send' );
		const loadingIndicator	= chatContainer.querySelector( '.ikigai-finder-loading' );
		const copyChat			= chatContainer.querySelector( '.ikigai-finder-copy-chat' );
		const copyIkigai		= chatContainer.querySelector( '.ikigai-finder-copy-ikigai' );
		const resetButton       = document.getElementById( 'ikigai-finder-reset-button' );

		// Check if all elements are present
		if ( ! messagesContainer || ! messageInput || ! sendButton || ! loadingIndicator) {
			console.error( 'Ikigai Finder: Missing chat elements' );
			return;
		}

		let conversation		 = [];
		let isWaitingForResponse = false;
		let currentPhase		 = 1;
		let isProcessing		 = false;

		// Erstelle die Phasen-Anzeige
		const phaseIndicator     = document.createElement( 'div' );
		phaseIndicator.id        = 'ikigai-finder-phase-indicator';
		phaseIndicator.className = 'ikigai-finder-phase-indicator';

		// Füge den Fortschrittsbalken hinzu.
		const progressBar     = document.createElement( 'div' );
		progressBar.className = 'phase-progress';
		phaseIndicator.appendChild( progressBar );

		const phases = [
		{ id: 1, text: 'Was liebst du?' },
		{ id: 2, text: 'Was kannst du gut?' },
		{ id: 3, text: 'Was braucht die Welt?' },
		{ id: 4, text: 'Wofür würden Menschen zahlen?' }
		];

		// Füge die Phasen-Anzeige zum Container hinzu
		phases.forEach(
			phase => {
            const phaseElement	   = document.createElement( 'div' );
            phaseElement.className = `phase-item phase-${phase.id}`;
            phaseElement.innerHTML = `
					<div class="phase-circle">
						<span class="phase-number">${phase.id}</span>
					</div>
					<span class="phase-text">${phase.text}</span>
				`;
            phaseIndicator.appendChild( phaseElement );
			}
		);

		// Füge die Phasen-Anzeige nach dem Chat-Input ein
		const chatInput = chatContainer.querySelector('.ikigai-finder-chat-input');
		chatContainer.insertBefore(phaseIndicator, chatInput.nextSibling);

		function updatePhaseIndicator(phase) {
			console.log('Ikigai Finder: Aktualisiere Phasenindikator auf:', phase);

			// Konvertiere Phase zu Nummer, falls es ein String ist
			let numericPhase = phase;
			if (typeof phase === 'string' && phase !== 'done') {
				numericPhase = parseInt(phase);
			}

			const allPhases = phaseIndicator.querySelectorAll('.phase-item');
			const progressBar = phaseIndicator.querySelector('.phase-progress');

			allPhases.forEach(
				(item, index) => {
				item.classList.remove('active', 'completed');
				if (numericPhase === 'done') {
					item.classList.add('completed');
				} else if (index < numericPhase - 1) {
					item.classList.add('completed');
				} else if (index === numericPhase - 1) {
					item.classList.add('active');
				}
				}
			);

			// Berechne die Breite des Fortschrittsbalkens.
			let progressWidth = '0';
			if (numericPhase === 'done') {
				progressWidth = 'calc(100% - 120px)'; // Volle Breite minus Randabstand.
			} else if (numericPhase > 1 && numericPhase <= 4) {
				const progress = ((numericPhase - 1) / 3) * 100;
				progressWidth = `calc(${progress}% * ((100% - 120px) / 100))`;
			}

			// Setze den Fortschrittsbalken.
			if (progressBar) {
				progressBar.style.width = progressWidth;
			}
		}

		function extractPhase(message) {
			// Versuche, die Phase aus der Nachricht zu extrahieren.
			// Prüfe beide möglichen Formate: [PHASE=n] und [CURRENT_PHASE=n]
			let phaseMatch = message.match(/\[PHASE=(\d+|done)\]/);
			if (!phaseMatch) {
				phaseMatch = message.match(/\[CURRENT_PHASE=(\d+|done)\]/);
			}

			console.log('Ikigai Finder: Extrahierte Phase:', phaseMatch ? phaseMatch[1] : 'keine');
			return phaseMatch ? phaseMatch[1] : null;
		}

		function addMessage(message, isUser = false) {
			console.log('Ikigai Finder: Füge Nachricht hinzu:', { message, isUser });
			const messageElement = document.createElement('div');
			messageElement.classList.add('message', isUser ? 'user-message' : 'bot-message');

			let cleanMessage = message;

			if (!isUser) {
				// Extrahiere und aktualisiere die Phase
				const newPhase = extractPhase(message);
				if (newPhase) {
					currentPhase = newPhase === 'done' ? 'done' : parseInt(newPhase);
					console.log('Ikigai Finder: Aktualisiere auf Phase:', currentPhase);
					updatePhaseIndicator(currentPhase);
					// Entferne das Phase-Tag aus der Nachricht
					cleanMessage = message.replace(/\[PHASE=(\d+|done)\]/, '').trim();
					cleanMessage = cleanMessage.replace(/\[CURRENT_PHASE=(\d+|done)\]/, '').trim();
				}

				// Prüfe, ob ein Phasenwechsel im Text erwähnt wird
				if (cleanMessage.includes('Phase 2') || cleanMessage.includes('nächsten Phase') ||
				    cleanMessage.includes('zweiten Phase')) {
					console.log('Ikigai Finder: Phasenwechsel zu Phase 2 im Text erkannt');
					if (currentPhase === 1 || currentPhase === '1') {
						currentPhase = 2;
						updatePhaseIndicator(currentPhase);
					}
				} else if (cleanMessage.includes('Phase 3') || cleanMessage.includes('dritten Phase') ||
				    (cleanMessage.includes('nächsten Phase') && (currentPhase === 2 || currentPhase === '2'))) {
					console.log('Ikigai Finder: Phasenwechsel zu Phase 3 im Text erkannt');
					if (currentPhase === 2 || currentPhase === '2') {
						currentPhase = 3;
						updatePhaseIndicator(currentPhase);
					}
				} else if (cleanMessage.includes('Phase 4') || cleanMessage.includes('vierten Phase') ||
				    (cleanMessage.includes('nächsten Phase') && (currentPhase === 3 || currentPhase === '3')) ||
				    cleanMessage.includes('letzten Phase')) {
					console.log('Ikigai Finder: Phasenwechsel zu Phase 4 im Text erkannt');
					if (currentPhase === 3 || currentPhase === '3') {
						currentPhase = 4;
						updatePhaseIndicator(currentPhase);
					}
				} else if (cleanMessage.includes('abgeschlossen') && cleanMessage.includes('Ikigai') ||
				    cleanMessage.includes('alle vier Bereiche') || cleanMessage.includes('alle Phasen')) {
					console.log('Ikigai Finder: Phasenwechsel zu "done" im Text erkannt');
					if (currentPhase === 4 || currentPhase === '4') {
						currentPhase = 'done';
						updatePhaseIndicator(currentPhase);
					}
				}

				// Prüfe, ob marked verfügbar ist
				if (typeof marked !== 'undefined') {
					console.log( 'Ikigai Finder: Marked.js verfügbar, parse Markdown' );
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
					console.warn( 'Ikigai Finder: Marked.js nicht verfügbar, verwende Plaintext' );
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
				// Zeige den Loading-Indikator an
				loadingIndicator.style.display = 'flex';
				messagesContainer.classList.add('loading');
				sendButton.disabled = true;
				messageInput.disabled = true;
			} else {
				// Verstecke den Loading-Indikator
				loadingIndicator.style.display = 'none';
				messagesContainer.classList.remove('loading');
				sendButton.disabled = false;
				messageInput.disabled = false;
			}
		}

		async function sendMessage(message = '') {
			if (isProcessing) {
				return;
			}

			// Verhindere, dass leere Nachrichten gesendet werden.
			const msg = message || messageInput.value.trim();
			if ( ! msg && message !== 'start') {
				return;
			}

			isProcessing = true;
			setLoading(true);

			try {
				// Füge die Nachricht zur Konversation hinzu
				if (msg !== 'start') {
					addMessage(msg, true);
					conversation.push({
						role: 'user',
						content: msg
					});
				}

				// Sende die Nachricht an den Server
				const response = await fetch(window.ikigaiFinder.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'ikigai_finder_chat',
						nonce: window.ikigaiFinder.nonce,
						message: msg,
						conversation: JSON.stringify(conversation)
					})
				});

				const data = await response.json();

				if (data.success) {
					// Füge die Antwort zur Konversation hinzu
					addMessage(data.data.message);
					conversation.push({
						role: 'assistant',
						content: data.data.message
					});
				} else {
					// Zeige Fehlermeldung an
					const errorMessage = data.data.message || 'Ein Fehler ist aufgetreten.';
					addMessage(errorMessage);
				}
			} catch (error) {
				console.error('Ikigai Finder: Fehler beim Senden der Nachricht:', error);
				addMessage('Ein Fehler ist aufgetreten. Bitte versuche es später erneut.');
			} finally {
				isProcessing = false;
				setLoading(false);
			}
		}

		// Event Listeners
		sendButton.addEventListener('click', () => sendMessage());
		messageInput.addEventListener('keypress', (e) => {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				sendMessage();
			}
		});

		// Reset-Button
		if (resetButton) {
			resetButton.addEventListener('click', () => {
				conversation = [];
				messagesContainer.innerHTML = '';
				currentPhase = 1;
				updatePhaseIndicator(currentPhase);
				sendMessage('start');
			});
		}

		// Starte den Chat
		sendMessage('start');
	}
);
