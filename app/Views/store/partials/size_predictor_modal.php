<!-- AI Size Predictor Modal -->
<style>
#ai-size-modal {
    position: fixed !important;
    inset: 0 !important;
    z-index: 99999 !important;
    display: none;
    align-items: center !important;
    justify-content: center !important;
    background: rgba(37, 40, 32, 0.6) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    padding: 16px !important;
    box-sizing: border-box !important;
    opacity: 0;
    transition: opacity 0.25s ease !important;
}
#ai-size-modal.is-open {
    display: flex !important;
    opacity: 1 !important;
}
#ai-size-modal.hidden {
    display: none !important;
}
#ai-size-modal-content {
    position: relative !important;
    background: #ffffff !important;
    border-radius: 24px !important;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3) !important;
    max-width: 440px !important;
    width: 100% !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    padding: 32px 28px !important;
    box-sizing: border-box !important;
    transform: translateY(12px) scale(0.96);
    transition: transform 0.25s ease !important;
}
#ai-size-modal.is-open #ai-size-modal-content {
    transform: translateY(0) scale(1) !important;
}
.ai-modal-close {
    position: absolute !important;
    top: 16px !important;
    right: 16px !important;
    width: 32px !important;
    height: 32px !important;
    border-radius: 50% !important;
    border: 0 !important;
    background: #f0f1eb !important;
    color: #85877d !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
}
.ai-modal-close:hover {
    background: #e2e2da !important;
    color: #252820 !important;
}
.ai-modal-badge {
    width: 48px !important;
    height: 48px !important;
    margin: 0 auto 16px !important;
    border-radius: 16px !important;
    background: linear-gradient(135deg, #252820, #687158) !important;
    color: #ffffff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 8px 20px rgba(37,40,32,0.18) !important;
}
.ai-modal-badge svg {
    width: 24px !important;
    height: 24px !important;
}
.ai-form-grid-2 {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 12px !important;
    margin-bottom: 12px !important;
}
.ai-form-grid-3 {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 10px !important;
    margin-bottom: 16px !important;
}
.ai-input-group label {
    display: block !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #687158 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    margin-bottom: 6px !important;
}
.ai-input-group input {
    width: 100% !important;
    border: 1px solid #e2e2da !important;
    border-radius: 10px !important;
    background: #fbfbf7 !important;
    padding: 10px 12px !important;
    font-size: 13px !important;
    box-sizing: border-box !important;
    outline: none !important;
    transition: border-color 0.2s ease !important;
}
.ai-input-group input:focus {
    border-color: #687158 !important;
    box-shadow: 0 0 0 3px rgba(104,113,88,0.15) !important;
}
.ai-fit-segmented {
    display: flex !important;
    background: #f0f1eb !important;
    border-radius: 12px !important;
    padding: 4px !important;
    gap: 4px !important;
    margin-top: 6px !important;
}
.ai-fit-segmented label {
    flex: 1 !important;
    text-align: center !important;
    cursor: pointer !important;
    margin: 0 !important;
}
.ai-fit-segmented input {
    display: none !important;
}
.ai-fit-segmented span {
    display: block !important;
    padding: 8px 12px !important;
    border-radius: 8px !important;
    font-size: 12px !important;
    color: #85877d !important;
    transition: all 0.2s ease !important;
}
.ai-fit-segmented input:checked + span {
    background: #ffffff !important;
    color: #252820 !important;
    font-weight: 600 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
}
.ai-btn-submit {
    width: 100% !important;
    border: 0 !important;
    border-radius: 12px !important;
    background: #252820 !important;
    color: #ffffff !important;
    padding: 14px 20px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: background 0.2s ease, transform 0.2s ease !important;
    margin-top: 20px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.ai-btn-submit:hover {
    background: #687158 !important;
    transform: translateY(-1px) !important;
}
#ai-loading-state {
    display: none;
    padding: 32px 0;
    text-align: center;
}
#ai-loading-state.active {
    display: block !important;
}
#ai-result-state {
    display: none;
    text-align: center;
}
#ai-result-state.active {
    display: block !important;
}
#ai-size-form.hidden {
    display: none !important;
}
.ai-spinner {
    width: 40px;
    height: 40px;
    margin: 0 auto 16px;
    border-radius: 50%;
    border: 3px solid #e2e2da;
    border-top-color: #687158;
    animation: aiSpin 0.8s linear infinite;
}
@keyframes aiSpin {
    to { transform: rotate(360deg); }
}
</style>

<div id="ai-size-modal" class="hidden">
    <div id="ai-size-modal-content">
        <!-- Close Button -->
        <button type="button" onclick="closeSizePredictorModal()" class="ai-modal-close" aria-label="Close size predictor">
            <i data-lucide="x" style="width:16px;height:16px;"></i>
        </button>

        <div style="text-align:center;margin-bottom:24px;">
            <div class="ai-modal-badge">
                <i data-lucide="sparkles"></i>
            </div>
            <h3 style="margin:0 0 8px;font-size:20px;font-weight:600;color:#252820;">Bespoke AI Fitting</h3>
            <p style="margin:0;font-size:13px;color:#85877d;line-height:1.5;">Tell us your measurements. Our AI will recommend your perfect fit.</p>
        </div>

        <form id="ai-size-form" onsubmit="submitSizePredictor(event)">
            <input type="hidden" name="product_id" id="ai-product-id" value="<?= (int)($product['id'] ?? 0) ?>">
            
            <div class="ai-form-grid-2">
                <div class="ai-input-group">
                    <label>Height (cm)</label>
                    <input type="number" name="height" required min="100" max="250" placeholder="e.g. 175">
                </div>
                <div class="ai-input-group">
                    <label>Weight (kg)</label>
                    <input type="number" name="weight" required min="30" max="200" placeholder="e.g. 70">
                </div>
            </div>
            
            <div class="ai-form-grid-3">
                <div class="ai-input-group">
                    <label>Chest (cm)</label>
                    <input type="number" name="chest" placeholder="Optional">
                </div>
                <div class="ai-input-group">
                    <label>Waist (cm)</label>
                    <input type="number" name="waist" placeholder="Optional">
                </div>
                <div class="ai-input-group">
                    <label>Hip (cm)</label>
                    <input type="number" name="hip" placeholder="Optional">
                </div>
            </div>

            <div class="ai-input-group">
                <label>Fit Preference</label>
                <div class="ai-fit-segmented">
                    <label>
                        <input type="radio" name="fit_preference" value="slim">
                        <span>Slim</span>
                    </label>
                    <label>
                        <input type="radio" name="fit_preference" value="regular" checked>
                        <span>Regular</span>
                    </label>
                    <label>
                        <input type="radio" name="fit_preference" value="relaxed">
                        <span>Relaxed</span>
                    </label>
                </div>
            </div>

            <button type="submit" id="ai-submit-btn" class="ai-btn-submit">
                Predict My Size
            </button>
        </form>

        <!-- Loading State -->
        <div id="ai-loading-state">
            <div class="ai-spinner"></div>
            <p style="margin:0;font-size:13px;font-weight:600;color:#687158;">Analyzing measurements & bespoke draping...</p>
        </div>

        <!-- Result State -->
        <div id="ai-result-state">
            <div style="width:54px;height:54px;margin:0 auto 14px;border-radius:50%;background:#eef0e7;color:#687158;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="check" style="width:28px;height:28px;"></i>
            </div>
            <p style="margin:0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#85877d;">Recommended Size</p>
            <div style="margin:6px 0 16px;font-size:44px;font-weight:700;color:#252820;" id="ai-recommended-size">L</div>
            
            <div style="background:#fbfbf7;border:1px solid #e2e2da;border-radius:14px;padding:16px;text-align:left;margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:700;color:#687158;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">
                    <i data-lucide="info" style="width:14px;height:14px;"></i> AI Bespoke Notes
                </div>
                <p style="margin:0;font-size:13px;color:#77796f;line-height:1.6;" id="ai-bespoke-notes">Based on your measurements, this size provides a regular fit.</p>
            </div>

            <button type="button" onclick="applyRecommendedSize()" class="ai-btn-submit" style="margin-top:0;">
                Select This Size
            </button>
            <button type="button" onclick="resetSizePredictor()" style="width:100%;border:1px solid #d8d8cf;border-radius:12px;background:transparent;padding:12px;font-size:13px;font-weight:500;color:#687158;cursor:pointer;margin-top:10px;">
                Try different measurements
            </button>
        </div>
    </div>
</div>

<script>
let recommendedSizeToApply = null;

function openSizePredictorModal() {
    const modal = document.getElementById('ai-size-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    void modal.offsetWidth;
    modal.classList.add('is-open');
}

function closeSizePredictorModal() {
    const modal = document.getElementById('ai-size-modal');
    if (!modal) return;
    modal.classList.remove('is-open');
    setTimeout(() => {
        modal.style.display = 'none';
        modal.classList.add('hidden');
        resetSizePredictor();
    }, 250);
}

function resetSizePredictor() {
    const form = document.getElementById('ai-size-form');
    const loading = document.getElementById('ai-loading-state');
    const result = document.getElementById('ai-result-state');
    if (form) { form.style.display = 'block'; form.classList.remove('hidden'); }
    if (loading) { loading.style.display = 'none'; loading.classList.remove('active'); }
    if (result) { result.style.display = 'none'; result.classList.remove('active'); }
    recommendedSizeToApply = null;
}

async function submitSizePredictor(event) {
    event.preventDefault();
    const form = event.target;
    const loading = document.getElementById('ai-loading-state');
    const result = document.getElementById('ai-result-state');
    
    form.style.display = 'none';
    form.classList.add('hidden');
    loading.style.display = 'block';
    loading.classList.add('active');
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('<?= $publicPath ?>/api/v1/mobile/ai/size-predictor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '<?= $csrf ?>'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) throw new Error('Prediction failed');
        
        const res = await response.json();
        
        loading.style.display = 'none';
        loading.classList.remove('active');
        result.style.display = 'block';
        result.classList.add('active');
        
        document.getElementById('ai-recommended-size').textContent = res.recommended_size;
        document.getElementById('ai-bespoke-notes').textContent = res.bespoke_notes;
        
        recommendedSizeToApply = res.recommended_size;
        
        if (window.lucide) {
            lucide.createIcons();
        }
    } catch (error) {
        console.error(error);
        alert('Could not predict size right now. Please try again.');
        resetSizePredictor();
    }
}

function applyRecommendedSize() {
    if (recommendedSizeToApply) {
        const sizeButtons = document.querySelectorAll('.variant-option');
        for (const btn of sizeButtons) {
            if (btn.textContent.trim().toUpperCase() === recommendedSizeToApply.toUpperCase()) {
                btn.click();
                break;
            }
        }
    }
    closeSizePredictorModal();
}
</script>
