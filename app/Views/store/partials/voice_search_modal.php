<!-- <?= e(store_setting('store_name', 'Noir & Co.')) ?> AI Voice Concierge Modal - Compact Middle Screen Popup -->
<div id="ai-voice-modal" role="dialog" aria-modal="true" aria-labelledby="voice-modal-title" style="display: none; position: fixed; inset: 0; z-index: 999999; background: rgba(6, 8, 6, 0.82); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); align-items: center; justify-content: center; padding: 14px; opacity: 0; transition: opacity 0.25s ease;">
    <!-- Backdrop Click to Dismiss -->
    <div onclick="closeVoiceSearchModal()" style="position: absolute; inset: 0; z-index: 1;"></div>

    <!-- Centerpiece Compact Popup Modal Dialog Box -->
    <div id="ai-voice-modal-content" style="position: relative; z-index: 2; width: 100%; max-width: 360px; margin: auto; background: linear-gradient(165deg, rgba(28, 31, 25, 0.98) 0%, rgba(14, 16, 12, 0.99) 100%); border: 1px solid rgba(197, 168, 128, 0.35); border-radius: 20px; box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.95), 0 0 30px rgba(104, 113, 88, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.12); overflow: hidden; padding: 16px 18px 14px; text-align: center; color: #f7f7f2; transform: scale(0.92); transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Top Right Close X Button -->
        <button type="button" onclick="closeVoiceSearchModal()" aria-label="Close Voice Assistant" style="position: absolute; right: 10px; top: 10px; width: 28px; height: 28px; border-radius: 50%; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.12); color: #c4c5be; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; z-index: 10;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <!-- Brand Kicker Badge -->
        <div style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; background: rgba(104, 113, 88, 0.2); border: 1px solid rgba(197, 168, 128, 0.25); border-radius: 12px; margin-bottom: 6px;">
            <span style="display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: #c5a880; box-shadow: 0 0 6px #c5a880; animation: dotPulse 1.8s infinite ease-in-out;"></span>
            <span style="font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.14em; color: #d8c4a4; font-weight: 700;"><?= e(store_setting('store_name', 'Noir & Co.')) ?> Voice</span>
        </div>

        <h3 id="voice-modal-title" style="margin: 0 0 2px; font-size: 16px; font-weight: 700; font-family: 'Cinzel', serif; letter-spacing: 0.02em; color: #ffffff;"><?= e(store_setting('store_name', 'Noir & Co.')) ?> AI Voice Concierge</h3>
        <p style="margin: 0 0 10px; font-size: 11px; color: #a4a89b; font-weight: 400;">Speak naturally to explore or navigate</p>

        <!-- Interactive Glowing Mic Centerpiece -->
        <div style="position: relative; width: 62px; height: 62px; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center;">
            <div id="voice-pulse-1" style="position: absolute; inset: -6px; border-radius: 50%; background: radial-gradient(circle, rgba(197, 168, 128, 0.25) 0%, transparent 70%); animation: voicePulse 2s infinite ease-out;"></div>
            
            <button type="button" id="voice-mic-btn" onclick="requestMicAndListen()" aria-label="Microphone Access" style="position: relative; z-index: 2; width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(145deg, #687158 0%, #2f3426 100%); border: 2px solid rgba(197, 168, 128, 0.6); box-shadow: 0 6px 18px rgba(0, 0, 0, 0.6), inset 0 1px 2px rgba(255, 255, 255, 0.25); display: flex; align-items: center; justify-content: center; color: #fff; cursor: pointer; transition: all 0.25s ease;">
                <svg id="voice-mic-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                    <line x1="12" y1="19" x2="12" y2="22"></line>
                </svg>
            </button>
        </div>

        <!-- Animated Audio Waveform Bars -->
        <div id="voice-wave-container" style="display: flex; align-items: center; justify-content: center; gap: 3px; height: 18px; margin-bottom: 8px;">
            <span class="v-bar" style="width: 2.5px; height: 6px; background: #687158; border-radius: 2px; animation: vWave 1s infinite ease-in-out 0.05s;"></span>
            <span class="v-bar" style="width: 2.5px; height: 14px; background: #c5a880; border-radius: 2px; animation: vWave 1s infinite ease-in-out 0.2s;"></span>
            <span class="v-bar" style="width: 2.5px; height: 10px; background: #85877d; border-radius: 2px; animation: vWave 1s infinite ease-in-out 0.35s;"></span>
            <span class="v-bar" style="width: 2.5px; height: 16px; background: #f0e6d6; border-radius: 2px; animation: vWave 1s infinite ease-in-out 0.5s;"></span>
            <span class="v-bar" style="width: 2.5px; height: 11px; background: #c5a880; border-radius: 2px; animation: vWave 1s infinite ease-in-out 0.65s;"></span>
            <span class="v-bar" style="width: 2.5px; height: 14px; background: #687158; border-radius: 2px; animation: vWave 1s infinite ease-in-out 0.8s;"></span>
            <span class="v-bar" style="width: 2.5px; height: 7px; background: #85877d; border-radius: 2px; animation: vWave 1s infinite ease-in-out 0.95s;"></span>
        </div>

        <!-- Spoken Feedback Transcript & Status -->
        <p id="voice-status-text" style="font-size: 13.5px; font-weight: 600; color: #fff; min-height: 18px; margin: 0 0 2px; font-family: 'Cinzel', serif;">Listening...</p>
        <p id="voice-sub-text" style="font-size: 11px; color: #a2a49b; margin: 0 0 10px;">Try saying "Black leather jacket" or "Track Order"</p>

        <!-- Direct Mic Permission Button if Needed -->
        <div id="voice-mic-permission-box" style="display: none; margin-bottom: 10px;">
            <button type="button" onclick="requestMicAndListen()" style="background: linear-gradient(135deg, #c5a880 0%, #a4875e 100%); color: #161814; font-weight: 700; font-size: 11px; padding: 6px 12px; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>
                <span>Allow Microphone Access</span>
            </button>
        </div>

        <!-- Live Direct Action Card or Results container -->
        <div id="voice-results-area" style="display: none; margin-bottom: 10px; background: rgba(0,0,0,0.4); border: 1px solid rgba(197,168,128,0.2); border-radius: 10px; padding: 8px; text-align: left; max-height: 130px; overflow-y: auto;"></div>

        <!-- Quick Voice Prompts / Chips -->
        <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; margin-bottom: 12px;">
            <button type="button" onclick="simulateVoiceQuery('Black leather jacket')" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(197, 168, 128, 0.2); color: #e5dec9; font-size: 10.5px; padding: 3px 8px; border-radius: 12px; cursor: pointer; transition: all 0.2s; font-weight: 500;">Leather Jacket</button>
            <button type="button" onclick="simulateVoiceQuery('Outfit for dinner date')" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(197, 168, 128, 0.2); color: #e5dec9; font-size: 10.5px; padding: 3px 8px; border-radius: 12px; cursor: pointer; transition: all 0.2s; font-weight: 500;">Dinner Look</button>
            <button type="button" onclick="simulateVoiceQuery('Track my order')" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(197, 168, 128, 0.2); color: #e5dec9; font-size: 10.5px; padding: 3px 8px; border-radius: 12px; cursor: pointer; transition: all 0.2s; font-weight: 500;">Track Order</button>
            <button type="button" onclick="simulateVoiceQuery('Switch currency to EUR')" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(197, 168, 128, 0.2); color: #e5dec9; font-size: 10.5px; padding: 3px 8px; border-radius: 12px; cursor: pointer; transition: all 0.2s; font-weight: 500;">EUR (€)</button>
        </div>

        <!-- Prominent Bottom Cancel Button -->
        <div>
            <button type="button" onclick="closeVoiceSearchModal()" class="voice-cancel-btn" style="padding: 6px 14px; border-radius: 14px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18); color: #e5dec9; font-size: 11.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Cancel</span>
            </button>
        </div>
    </div>
</div>

<style>
@keyframes dotPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.4); opacity: 0.6; }
}
@keyframes voicePulse {
    0% { transform: scale(0.85); opacity: 0.9; }
    100% { transform: scale(1.7); opacity: 0; }
}
@keyframes vWave {
    0%, 100% { height: 6px; }
    50% { height: 30px; }
}
#ai-voice-modal-content button:hover {
    filter: brightness(1.18);
    border-color: rgba(197, 168, 128, 0.6) !important;
}
.voice-cancel-btn:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.35) !important;
}
</style>

<script>
let speechRecognitionInstance = null;
let activeMediaStream = null;
let voiceActionTimeout = null;

function speakReply(text) {
    if ('speechSynthesis' in window) {
        try {
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 1.05;
            utterance.pitch = 1.0;
            const voices = window.speechSynthesis.getVoices();
            const preferred = voices.find(v => v.lang.startsWith('en') && (v.name.includes('Natural') || v.name.includes('Premium') || v.name.includes('Google') || v.name.includes('Samantha')));
            if (preferred) utterance.voice = preferred;
            window.speechSynthesis.speak(utterance);
        } catch (e) {
            console.warn('Speech synthesis error:', e);
        }
    }
}

function openVoiceSearchModal() {
    const modal = document.getElementById('ai-voice-modal');
    const content = document.getElementById('ai-voice-modal-content');
    const statusText = document.getElementById('voice-status-text');
    const subText = document.getElementById('voice-sub-text');
    const resultsArea = document.getElementById('voice-results-area');
    const waveContainer = document.getElementById('voice-wave-container');
    const permissionBox = document.getElementById('voice-mic-permission-box');

    statusText.textContent = 'Listening to your request...';
    subText.textContent = 'Speak naturally (e.g. "Silk Dresses", "Track Order", "Switch to USD")...';
    if (resultsArea) {
        resultsArea.style.display = 'none';
        resultsArea.innerHTML = '';
    }
    if (waveContainer) waveContainer.style.display = 'flex';
    if (permissionBox) permissionBox.style.display = 'none';

    // Position directly in the exact middle of the screen
    modal.style.display = 'flex';
    void modal.offsetWidth; // Force Reflow
    modal.style.opacity = '1';
    content.style.transform = 'scale(1) translateY(0)';

    // Request Mic and start listening immediately
    requestMicAndListen();
}

async function requestMicAndListen() {
    const statusText = document.getElementById('voice-status-text');
    const subText = document.getElementById('voice-sub-text');
    const waveContainer = document.getElementById('voice-wave-container');
    const permissionBox = document.getElementById('voice-mic-permission-box');
    const micBtn = document.getElementById('voice-mic-btn');

    // 1. Request microphone access via getUserMedia if supported
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        try {
            activeMediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            if (permissionBox) permissionBox.style.display = 'none';
        } catch (err) {
            console.warn('Microphone permission request error:', err);
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                statusText.textContent = 'Microphone Access Required';
                subText.textContent = 'Please tap "Allow Microphone Access" below to speak.';
                if (permissionBox) permissionBox.style.display = 'block';
                if (waveContainer) waveContainer.style.display = 'none';
                return;
            }
        }
    }

    // 2. Initialize Speech Recognition
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
        try {
            if (speechRecognitionInstance) {
                try { speechRecognitionInstance.abort(); } catch (_) {}
            }
            speechRecognitionInstance = new SpeechRecognition();
            speechRecognitionInstance.lang = '<?= current_locale() === "fr" ? "fr-FR" : (current_locale() === "es" ? "es-ES" : (current_locale() === "de" ? "de-DE" : (current_locale() === "ar" ? "ar-SA" : "en-US"))) ?>';
            speechRecognitionInstance.interimResults = true;
            speechRecognitionInstance.maxAlternatives = 1;

            speechRecognitionInstance.onstart = function() {
                statusText.textContent = 'Listening to your request...';
                subText.textContent = 'Listening to your voice... speak now';
                if (waveContainer) waveContainer.style.display = 'flex';
                if (micBtn) micBtn.style.transform = 'scale(1.08)';
            };

            speechRecognitionInstance.onresult = function(event) {
                const transcript = Array.from(event.results).map(r => r[0].transcript).join('');
                statusText.textContent = `"${transcript}"`;

                if (event.results[0].isFinal) {
                    processVoiceIntent(transcript);
                }
            };

            speechRecognitionInstance.onerror = function(event) {
                if (micBtn) micBtn.style.transform = 'scale(1)';
                if (event.error === 'not-allowed') {
                    statusText.textContent = 'Microphone Access Required';
                    subText.textContent = 'Please allow microphone access to use voice search.';
                    if (permissionBox) permissionBox.style.display = 'block';
                    if (waveContainer) waveContainer.style.display = 'none';
                } else if (event.error === 'no-speech') {
                    statusText.textContent = 'No voice detected';
                    subText.textContent = 'Tap the microphone icon or prompt chips below.';
                }
            };

            speechRecognitionInstance.onend = function() {
                if (micBtn) micBtn.style.transform = 'scale(1)';
            };

            speechRecognitionInstance.start();
        } catch (e) {
            console.error('Speech recognition start error:', e);
        }
    } else {
        statusText.textContent = 'Voice Ready';
        subText.textContent = 'Tap any prompt below to explore.';
    }
}

function processVoiceIntent(query) {
    const statusText = document.getElementById('voice-status-text');
    const subText = document.getElementById('voice-sub-text');
    const resultsArea = document.getElementById('voice-results-area');
    const waveContainer = document.getElementById('voice-wave-container');
    if (waveContainer) waveContainer.style.display = 'none';

    const lower = query.toLowerCase().trim();

    // 1. Order Tracking intent
    if (lower.includes('track') || lower.includes('order status') || lower.includes('where is my order')) {
        statusText.textContent = 'Tracking Orders';
        subText.textContent = 'Opening your live order tracking portal...';
        speakReply('Opening order tracking.');
        setTimeout(() => {
            window.location.href = '<?= $publicPath ?>/track-order';
        }, 1100);
        return;
    }

    // 2. Currency Switch intent
    if (lower.includes('usd') || lower.includes('dollar')) {
        statusText.textContent = 'Currency: US Dollar ($)';
        subText.textContent = 'Converting store currency to USD...';
        speakReply('Switching currency to US Dollars.');
        submitCurrencyChange('USD');
        return;
    }
    if (lower.includes('naira') || lower.includes('ngn')) {
        statusText.textContent = 'Currency: Nigerian Naira (₦)';
        subText.textContent = 'Converting store currency to Naira...';
        speakReply('Switching currency to Nigerian Naira.');
        submitCurrencyChange('NGN');
        return;
    }
    if (lower.includes('euro') || lower.includes('eur')) {
        statusText.textContent = 'Currency: Euro (€)';
        subText.textContent = 'Converting store currency to Euro...';
        speakReply('Switching currency to Euros.');
        submitCurrencyChange('EUR');
        return;
    }
    if (lower.includes('pound') || lower.includes('gbp')) {
        statusText.textContent = 'Currency: British Pound (£)';
        subText.textContent = 'Converting store currency to Pounds...';
        speakReply('Switching currency to British Pounds.');
        submitCurrencyChange('GBP');
        return;
    }

    // 3. AI Stylist / Outfit Query
    if (lower.includes('wear') || lower.includes('outfit') || lower.includes('style') || lower.includes('recommend') || lower.includes('match') || lower.includes('date') || lower.includes('wedding') || lower.includes('gala')) {
        statusText.textContent = 'Consulting AI Stylist...';
        subText.textContent = 'Curating personalized luxury recommendations...';
        
        fetch('<?= $publicPath ?>/api/v1/ai/stylist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ message: query, history: [] })
        })
        .then(res => res.json())
        .then(data => {
            const reply = data.message || data.reply || 'Here is an exquisite curated selection from our collection.';
            statusText.textContent = 'Stylist Recommendation';
            subText.textContent = reply;
            speakReply(reply.slice(0, 140));

            const products = data.products || data.recommendations || [];
            if (products.length > 0 && resultsArea) {
                resultsArea.style.display = 'block';
                resultsArea.innerHTML = '<div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#85877d;margin-bottom:8px;">Curated Pieces:</div>' +
                    products.slice(0, 3).map(p => `
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;padding:8px;border-radius:10px;background:rgba(255,255,255,0.06);">
                            <img src="${p.image || p.featured_image || ''}" style="width:40px;height:40px;object-fit:cover;border-radius:8px;" onerror="this.style.display='none'">
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.name}</div>
                                <div style="font-size:11px;color:#c5a880;font-weight:600;">${'<?= e(currency_symbol()) ?>' + (<?= (float)get_exchange_rate() ?> * Number(p.price || 0)).toLocaleString(undefined, {minimumFractionDigits: '<?= e(active_currency()) ?>' === 'NGN' ? 0 : 2, maximumFractionDigits: '<?= e(active_currency()) ?>' === 'NGN' ? 0 : 2})}</div>
                            </div>
                            <a href="<?= $publicPath ?>/store/product/${p.slug || p.id}" style="font-size:11px;padding:5px 10px;background:#687158;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">View</a>
                        </div>
                    `).join('');
            }
        })
        .catch(err => {
            statusText.textContent = 'Searching Store: ' + query;
            speakReply('Searching catalogue for ' + query);
            setTimeout(() => {
                window.location.href = '<?= $publicPath ?>/store?search=' + encodeURIComponent(query);
            }, 800);
        });
        return;
    }

    // 4. Default Search Catalogue
    statusText.textContent = 'Searching: ' + query;
    subText.textContent = 'Finding items in catalogue...';
    speakReply('Finding ' + query);
    setTimeout(() => {
        window.location.href = '<?= $publicPath ?>/store?search=' + encodeURIComponent(query);
    }, 900);
}

function simulateVoiceQuery(text) {
    document.getElementById('voice-status-text').textContent = text;
    processVoiceIntent(text);
}

function submitCurrencyChange(code) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= $publicPath ?>/set-currency';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_csrf';
    csrf.value = '<?= csrf_token() ?>';
    const curr = document.createElement('input');
    curr.type = 'hidden';
    curr.name = 'currency';
    curr.value = code;
    form.appendChild(csrf);
    form.appendChild(curr);
    document.body.appendChild(form);
    form.submit();
}

function closeVoiceSearchModal() {
    const modal = document.getElementById('ai-voice-modal');
    const content = document.getElementById('ai-voice-modal-content');
    
    // Stop Speech Recognition
    if (speechRecognitionInstance) {
        try { speechRecognitionInstance.abort(); } catch (e) {}
    }
    // Stop Media Audio Stream tracks
    if (activeMediaStream) {
        try {
            activeMediaStream.getTracks().forEach(track => track.stop());
            activeMediaStream = null;
        } catch (e) {}
    }
    // Cancel Voice Speech
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
    clearTimeout(voiceActionTimeout);

    if (modal && content) {
        modal.style.opacity = '0';
        content.style.transform = 'scale(0.9) translateY(0)';
        
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

// Global Escape Key Listener to dismiss modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('ai-voice-modal');
        if (modal && modal.style.display === 'flex') {
            closeVoiceSearchModal();
        }
    }
});
</script>
