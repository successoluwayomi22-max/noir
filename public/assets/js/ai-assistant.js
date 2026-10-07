(() => {
    if (document.querySelector('.noir-ai-assistant')) return;
    const script = document.currentScript || document.querySelector('script[data-noir-ai-assistant]');
    const endpoint = script?.dataset.endpoint;
    const csrfToken = script?.dataset.csrfToken;
    const storeName = script?.dataset.storeName || 'Noir & Co.';
    const catalogueUrl = script?.dataset.catalogueUrl || '/store#catalogue';
    const configuredTimeout = Number(script?.dataset.timeoutMs || 30000);
    const requestTimeout = Number.isFinite(configuredTimeout) ? Math.max(15000, Math.min(70000, configuredTimeout)) : 30000;
    const storefront = document.body.classList.contains('store-page')
        || document.body.classList.contains('shop-page')
        || document.body.classList.contains('product-page');
    const excludedRoute = /\/(?:cart|checkout)(?:\/|$)/.test(window.location.pathname);

    if (!endpoint || !csrfToken || !storefront || excludedRoute) return;

    // Currency and Translation settings passed from backend
    const currencySymbol = script?.dataset.currencySymbol || '₦';
    const currencyRate = Number(script?.dataset.currencyRate || 1.0);
    const locale = script?.dataset.locale || 'en';

    // dynamic price formatting in JS
    const formatPriceJS = (amount) => {
        const val = Number(amount || 0) * currencyRate;
        if (currencySymbol === '₦') {
            return currencySymbol + Math.round(val).toLocaleString();
        }
        return currencySymbol + val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const translations = {
        en: {
            stylist: 'AI Stylist',
            stylistDesc: 'AI Stylist & Outfit Decision-Maker',
            voiceInput: 'Voice Input',
            speakToAI: 'Speak to {storeName} AI',
            close: 'Close Stylist',
            open: 'Open {storeName} AI',
            suggestedHeader: 'Suggested styling prompts',
            placeholder: 'Ask or speak to {storeName} AI...',
            send: 'Send',
            disclaimer: 'Live catalog recommendations with real-time stock and color harmony.',
            confidenceMatch: 'Confidence Match',
            total: 'Curated Ensemble Total:',
            explore: 'Explore Capsule',
            helpful: 'Helpful recommendation?',
            yes: 'Yes',
            no: 'Not my style',
            greeting: 'Bonjour! I am your {storeName} Personal Stylist & Outfit Decision-Maker. Tell me what occasion you are dressing for, your preferred colors, or budget, and I will curate a bespoke ensemble.',
            suggest1: 'Dinner tonight under {amount}',
            suggest2: 'Smart casual work outfit',
            suggest3: 'What colors match olive & black?',
            suggest4: 'Which pieces am I missing?',
            thinking: 'Thinking',
            busy: 'I am currently curating wardrobes for multiple clients. Please try again in a moment.',
            error: 'Our styling atelier is momentarily busy. Please try asking again or browse the signature catalog.',
            viewPiece: 'View Piece',
            clientMessage: 'Your message',
            stylistReply: '{storeName} Stylist reply',
            voiceNotSupported: 'Voice input is not supported by your current browser. You can type your request directly here!'
        },
        fr: {
            stylist: 'Styliste IA',
            stylistDesc: 'Styliste IA & Aide à la décision de tenues',
            voiceInput: 'Entrée vocale',
            speakToAI: 'Parler à {storeName} IA',
            close: 'Fermer le styliste',
            open: 'Ouvrir {storeName} IA',
            suggestedHeader: 'Suggestions de questions',
            placeholder: 'Demandez ou parlez à {storeName} IA...',
            send: 'Envoyer',
            disclaimer: 'Recommandations en temps réel avec harmonie des couleurs.',
            confidenceMatch: 'Correspondance de confiance',
            total: 'Total de la tenue sélectionnée:',
            explore: 'Explorer la capsule',
            helpful: 'Recommandation utile?',
            yes: 'Oui',
            no: 'Pas mon style',
            greeting: 'Bonjour! Je suis votre styliste personnel {storeName} IA. Dites-moi pour quelle occasion vous vous habillez, vos couleurs préférées ou votre budget.',
            suggest1: 'Dîner ce soir à moins de {amount}',
            suggest2: 'Tenue de travail décontractée et chic',
            suggest3: 'Quelles couleurs vont avec l\'olive et le noir?',
            suggest4: 'Quelles pièces me manquent?',
            thinking: 'Réflexion',
            busy: 'Je prépare actuellement des tenues pour d\'autres clients. Veuillez réessayer dans un instant.',
            error: 'Notre atelier est momentanément occupé. Veuillez réessayer ou parcourir le catalogue.',
            viewPiece: 'Voir la pièce',
            clientMessage: 'Votre message',
            stylistReply: 'Réponse du styliste {storeName}',
            voiceNotSupported: 'L\'entrée vocale n\'est pas prise en charge par votre navigateur actuel. Vous pouvez saisir votre demande directement ici !'
        },
        es: {
            stylist: 'Estilista IA',
            stylistDesc: 'Estilista IA y Asistente de vestuario',
            voiceInput: 'Entrada de voz',
            speakToAI: 'Hablar con {storeName} IA',
            close: 'Cerrar estilista',
            open: 'Abrir {storeName} IA',
            suggestedHeader: 'Sugerencias de estilismo',
            placeholder: 'Pregunta o habla con {storeName} IA...',
            send: 'Enviar',
            disclaimer: 'Recomendaciones en tiempo real con stock real y armonía de color.',
            confidenceMatch: 'Coincidencia de confianza',
            total: 'Total del conjunto seleccionado:',
            explore: 'Explorar la cápsula',
            helpful: '¿Recomendación útil?',
            yes: 'Sí',
            no: 'No es mi estilo',
            greeting: '¡Hola! Soy tu estilista personal de {storeName} IA. Dime para qué ocasión te estás vistiendo, tus colores favoritos o presupuesto.',
            suggest1: 'Cena de hoy por menos de {amount}',
            suggest2: 'Atuendo informal y elegante',
            suggest3: '¿Qué colores combinan con verde oliva y negro?',
            suggest4: '¿Qué prendas me faltan?',
            thinking: 'Pensando',
            busy: 'Actualmente estoy seleccionando guardarropas para otros clientes. Intente de nuevo en un momento.',
            error: 'Nuestro taller de estilismo está ocupado. Intente de nuevo o explore el catálogo.',
            viewPiece: 'Ver artículo',
            clientMessage: 'Su mensaje',
            stylistReply: 'Respuesta del estilista de {storeName}',
            voiceNotSupported: 'El navegador actual no admite la entrada de voz. ¡Puede escribir su solicitud directamente aquí!'
        },
        de: {
            stylist: 'KI-Stylist',
            stylistDesc: 'KI-Stylist & Outfit-Entscheidungshilfe',
            voiceInput: 'Spracheingabe',
            speakToAI: 'Mit {storeName} KI sprechen',
            close: 'Stylist schließen',
            open: 'Öffnen {storeName} KI',
            suggestedHeader: 'Empfohlene Styling-Fragen',
            placeholder: 'Fragen oder sprechen mit {storeName} KI...',
            send: 'Senden',
            disclaimer: 'Echtzeit-Empfehlungen mit Farbharmonie.',
            confidenceMatch: 'Übereinstimmungsgrad',
            total: 'Gesamtsumme des Outfits:',
            explore: 'Kapsel erkunden',
            helpful: 'Hilfreiche Empfehlung?',
            yes: 'Ja',
            no: 'Nicht mein Stil',
            greeting: 'Hallo! Ich bin Ihr persönlicher KI-Stylist von {storeName}. Sagen Sie mir, für welchen Anlass Sie sich kleiden möchten, Ihre bevorzugten Farben oder Ihr Budget.',
            suggest1: 'Abendessen heute unter {amount}',
            suggest2: 'Smart-Casual-Arbeitsoutfit',
            suggest3: 'Welche Farben passen zu Oliv und Schwarz?',
            suggest4: 'Welche Stücke fehlen mir?',
            thinking: 'Überlegt',
            busy: 'Ich bin gerade beschäftigt. Bitte versuchen Sie es in einem Moment noch einmal.',
            error: 'Unser Atelier ist beschäftigt. Bitte versuchen Sie es erneut oder stöbern Sie im Katalog.',
            viewPiece: 'Artikel ansehen',
            clientMessage: 'Ihre Nachricht',
            stylistReply: 'Antwort von {storeName} Stylist',
            voiceNotSupported: 'Spracheingabe wird von Ihrem aktuellen Browser nicht unterstützt. Sie können Ihre Anfrage direkt hier eingeben!'
        },
        it: {
            stylist: 'Stilista IA',
            stylistDesc: 'Stilista IA & Assistente di abbigliamento',
            voiceInput: 'Ingresso vocale',
            speakToAI: 'Parla con {storeName} IA',
            close: 'Chiudi stilista',
            open: 'Apri {storeName} IA',
            suggestedHeader: 'Suggerimenti di stile',
            placeholder: 'Chiedi o parla con {storeName} IA...',
            send: 'Invia',
            disclaimer: 'Raccomandazioni in tempo reale con armonia dei colori.',
            confidenceMatch: 'Corrispondenza di fiducia',
            total: 'Totale del completo selezionato:',
            explore: 'Esplora la capsule',
            helpful: 'Raccomandazione utile?',
            yes: 'Sì',
            no: 'Non è il mio stile',
            greeting: 'Bonjour! Sono il tuo stilista personale {storeName} IA. Dimmi l\'occasione, i tuoi colori preferiti o il tuo budget.',
            suggest1: 'Cena stasera sotto i {amount}',
            suggest2: 'Outfit da lavoro smart casual',
            suggest3: 'Quali colori si abbinano a oliva e nero?',
            suggest4: 'Quali capi mi mancano?',
            thinking: 'Pensando',
            busy: 'Sto preparando guardaroba per altri clienti. Riprova tra un momento.',
            error: 'Il nostro atelier è occupato. Riprova più tardi o sfoglia il catalogo.',
            viewPiece: 'Visualizza capo',
            clientMessage: 'Il tuo messaggio',
            stylistReply: 'Risposta dello stilista di {storeName}',
            voiceNotSupported: 'L\'input vocale non è supportato dal tuo browser attuale. Puoi digitare la tua richiesta direttamente qui!'
        },
        ar: {
            stylist: 'منسق الأزياء الذكي',
            stylistDesc: 'منسق أزياء بالذكاء الاصطناعي ومساعد اختيار الملابس',
            voiceInput: 'إدخال صوتي',
            speakToAI: 'تحدث إلى ذكاء {storeName}',
            close: 'إغلاق المنسق',
            open: 'افتح ذكاء {storeName}',
            suggestedHeader: 'اقتراحات التنسيق',
            placeholder: 'اسأل أو تحدث إلى ذكاء {storeName}...',
            send: 'إرسال',
            disclaimer: 'توصيات الكتالوج المباشرة مع توافق الألوان في الوقت الفعلي.',
            confidenceMatch: 'نسبة التطابق',
            total: 'إجمالي التنسيق المنسق:',
            explore: 'استكشاف الكبسولة',
            helpful: 'هل التوصية مفيدة؟',
            yes: 'نعم',
            no: 'ليس ذوقي',
            greeting: 'مرحباً! أنا منسق الأزياء الشخصي الخاص بك من {storeName}. أخبرني بالمناسبة التي تستعد لها، أو ألوانك المفضلة، أو ميزانيتك.',
            suggest1: 'عشاء الليلة بأقل من {amount}',
            suggest2: 'ملابس عمل أنيقة وغير رسمية',
            suggest3: 'ما هي الألوان التي تتناسب مع الزيتوني والأسود؟',
            suggest4: 'ما هي القطع الناقصة في خزانتي؟',
            thinking: 'يفكر',
            busy: 'أقوم حالياً بتنسيق ملابس لعملاء آخرين. يرجى المحاولة مرة أخرى بعد قليل.',
            error: 'مشغل التنسيق الخاص بنا مشغول حالياً. يرجى إعادة المحاولة لاحقاً أو تصفح الكتالوج.',
            viewPiece: 'عرض القطعة',
            clientMessage: 'رسالتك',
            stylistReply: 'رد منسق {storeName}',
            voiceNotSupported: 'الإدخال الصوتي غير مدعوم في متصفحك الحالي. يمكنك كتابة طلبك هنا مباشرة!'
        },
        yo: {
            stylist: 'Olùṣàkóso Ṣíkí IA',
            stylistDesc: 'Olùṣàkóso Ṣíkí IA & Aṣayan Aṣọ',
            voiceInput: 'Igbọran ohun',
            speakToAI: 'Sọrọ si {storeName} IA',
            close: 'Pa olùṣàkóso aṣọ tì',
            open: 'Ṣí {storeName} IA',
            suggestedHeader: 'Awọn aba apẹrẹ aṣọ',
            placeholder: 'Bère tabi sọrọ si {storeName} IA...',
            send: 'Firanṣẹ',
            disclaimer: 'Awọn iṣeduro katalogi laaye pẹlu irẹpọ awọ gidi.',
            confidenceMatch: 'Ijọra pipe',
            total: 'Apapọ Iye Aṣọ ti a yàn:',
            explore: 'Ṣawari capsule aṣọ',
            helpful: 'Ṣe iṣeduro yi wulo?',
            yes: 'Bẹẹni',
            no: 'Kii ṣe ara mi',
            greeting: 'Pẹlẹ o! Emi ni Olùṣàkóso Ṣíkí rẹ lati {storeName}. Sọ fun mi iru ayẹyẹ ti o fẹ lọ, awọn awọ ti o fẹ, tabi iye owo ti o ni.',
            suggest1: 'Ounje alẹ labẹ {amount}',
            suggest2: 'Aṣọ iṣẹ ti o wuyi ṣugbọn ti ko lera',
            suggest3: 'Awọn awọ wo ni o ba olive ati dudu mu?',
            suggest4: 'Awọn aṣọ wo ni mi o ni ninu iyẹwu mi?',
            thinking: 'N ronu',
            busy: 'Mo n ṣiṣẹ lọwọ lori awọn aṣọ fun awọn alabara miiran. Jọwọ gbiyanju lẹẹkansii laipẹ.',
            error: 'Atelier wa n ṣiṣẹ lọwọ lọwọlọwọ. Jọwọ gbiyanju lẹẹkan si tabi ṣawari katalogi aṣọ wa.',
            viewPiece: 'Wo aṣọ',
            clientMessage: 'Awọn ọrọ rẹ',
            stylistReply: 'Idahun Olùṣàkóso {storeName}',
            voiceNotSupported: 'Kii ṣe gbogbo aṣàwákiri lo gba igbọran ohun wọle. O le kọ ibeere rẹ taara sibi!'
        },
        ha: {
            stylist: 'Mai Tsara Kayan AI',
            stylistDesc: 'Mai Tsara Kayan AI & Mataimakin Zaɓin Tufafi',
            voiceInput: 'Shigar da murya',
            speakToAI: 'Yi magana da {storeName} AI',
            close: 'Rufe tsarin',
            open: 'Bude {storeName} AI',
            suggestedHeader: 'Shawarwarin salon tufafi',
            placeholder: 'Tambayi ko yi magana da {storeName} AI...',
            send: 'Aika',
            disclaimer: 'Shawarwarin kaya kai tsaye tare da daidaiton launi.',
            confidenceMatch: 'Daidaiton amincewa',
            total: 'Jumillar Kayan da aka zaba:',
            explore: 'Bincika salon ajiya',
            helpful: 'Shawarar ta taimaka?',
            yes: 'Eh',
            no: 'Ba salon na ba ne',
            greeting: 'Sannu! Ni ne mai tsara muku tufafi na kanka na {storeName} AI. Faɗa mini wace hidima kuke shiryawa, launukan da kuka fi so, ko kasafin kuɗi.',
            suggest1: 'Abincin dare na yau ƙasa da {amount}',
            suggest2: 'Kayan aiki na yau da kullum mai kyau',
            suggest3: 'Wadanne launuka ne suka dace da olive da dudu?',
            suggest4: 'Wadanne kaya ne ba su cikin tarina?',
            thinking: 'Tunanin',
            busy: 'Ina tsara wa wasu abokan ciniki kaya a halin yanzu. Don Allah a sake gwadawa nan da wani lokaci.',
            error: 'Wurinmu na tsara tufafi yana da aiki a yanzu. Don Allah a sake gwadawa ko bincika babban shagonmu.',
            viewPiece: 'Duba Tufafin',
            clientMessage: 'Saƙon ku',
            stylistReply: 'Amsar Mai Tsara Kayan {storeName}',
            voiceNotSupported: 'Wannan binciken ba ya goyon bayan shigar da murya. Kuna iya rubuta bukatarku kai tsaye a nan!'
        },
        ig: {
            stylist: 'Onye Ọkachamara Uwe AI',
            stylistDesc: 'Onye Ọkachamara Uwe AI & Onye Enyemaka Nhọrọ Uwe',
            voiceInput: 'Ntinye olu',
            speakToAI: 'Gwa {storeName} AI okwu',
            close: 'Mechie usoro',
            open: 'Meghee {storeName} AI',
            suggestedHeader: 'Ntụnye maka uwe',
            placeholder: 'Jụọ ma ọ bụ gwa {storeName} AI okwu...',
            send: 'Ziga',
            disclaimer: 'Ntụnye uwe ozugbo nwere nkwekọ agba.',
            confidenceMatch: 'Nkwekọrịta zuru oke',
            total: 'Ngụkọta Uwe Nchịkọta ahụ:',
            explore: 'Chọgharịa nchịkọta uwe',
            helpful: 'Ntụnye a ọ bara uru?',
            yes: 'Ee',
            no: 'Ọ bụghị ụdị m',
            greeting: 'Nnọọ! Abụ m onye ọkachamara uwe gị si {storeName}. Gwa m ụdị emume ị na-akwadebe maka ya, agba ndị kacha gị mma, ma ọ bụ ego ole ị nwere.',
            suggest1: 'Nri abalị taa n\'okpuru {amount}',
            suggest2: 'Uwe ọrụ mara mma ma dị mfe',
            suggest3: 'Kedu agba ndị dabara na olive na oji?',
            suggest4: 'Kedu uwe ndị na-efu m na kọbọd m?',
            thinking: 'Na-eche echiche',
            busy: 'M na-emere ndị ọzọ uwe ugbu a. Biko nwaa ọzọ n\'oge na-adịghị anya.',
            error: 'Ụlọ ọrụ anyị nwere nnukwu ọrụ ugbu a. Biko nwaa ọzọ ma ọ bụ chọgharịa katalọgụ anyị.',
            viewPiece: 'Lelee Uwe ahụ',
            clientMessage: 'Ozi gị',
            stylistReply: 'Azịza onye ọkachamara uwe {storeName}',
            voiceNotSupported: 'Olu ntinye adịghị na ihe nchọgharị a ugbu a. I nwere ike pịnye ihe ị chọrọ ozugbo ebe a!'
        },
        vi: {
            stylist: 'Stylist AI',
            stylistDesc: 'Stylist AI & Trợ lý tư vấn trang phục',
            voiceInput: 'Nhập giọng nói',
            speakToAI: 'Nói chuyện với {storeName} AI',
            close: 'Đóng Stylist',
            open: 'Mở {storeName} AI',
            suggestedHeader: 'Gợi ý phối đồ',
            placeholder: 'Hỏi hoặc nói chuyện với {storeName} AI...',
            send: 'Gửi',
            disclaimer: 'Gợi ý sản phẩm trực quan với sự hài hòa màu sắc thời gian thực.',
            confidenceMatch: 'Mức độ phù hợp',
            total: 'Tổng giá trị set đồ:',
            explore: 'Khám phá bộ sưu tập',
            helpful: 'Gợi ý này có hữu ích không?',
            yes: 'Có',
            no: 'Không đúng gu của tôi',
            greeting: 'Xin chào! Tôi là Stylist cá nhân của bạn từ {storeName} AI. Hãy cho tôi biết bạn đang chuẩn bị cho dịp nào, màu sắc yêu thích, hoặc ngân sách.',
            suggest1: 'Bữa tối tối nay dưới {amount}',
            suggest2: 'Trang phục công sở lịch sự năng động',
            suggest3: 'Màu gì hợp với xanh olive và đen?',
            suggest4: 'Tủ đồ của tôi đang thiếu những món gì?',
            thinking: 'Đang suy nghĩ',
            busy: 'Tôi đang chuẩn bị tủ đồ cho khách hàng khác. Vui lòng thử lại sau vài giây.',
            error: 'Atelier thiết kế đang tạm thời bận. Vui lòng thử lại hoặc khám phá catalogue sản phẩm.',
            viewPiece: 'Xem sản phẩm',
            clientMessage: 'Tin nhắn của bạn',
            stylistReply: 'Phản hồi từ Stylist {storeName}',
            voiceNotSupported: 'Tính năng giọng nói không được hỗ trợ bởi trình duyệt này. Bạn có thể nhập trực tiếp yêu cầu tại đây!'
        }
    };

    const activeLocale = translations[locale] ? locale : 'en';
    const t = (key, replacements = {}) => {
        let text = translations[activeLocale][key] || translations['en'][key] || key;
        text = text.replace(/{storeName}/g, storeName);
        for (const [k, v] of Object.entries(replacements)) {
            text = text.replace(new RegExp('{' + k + '}', 'g'), v);
        }
        return text;
    };

    const root = document.createElement('aside');
    root.className = 'noir-ai-assistant';
    root.dataset.open = 'false';
    root.innerHTML = `
        <section id="noir-ai-panel" class="noir-ai-panel" role="dialog" aria-modal="false" aria-labelledby="noir-ai-title" aria-describedby="noir-ai-description" hidden>
            <header class="noir-ai-header">
                <div class="noir-ai-heading">
                    <span class="noir-ai-mark" aria-hidden="true"><i data-lucide="sparkles"></i></span>
                    <div>
                        <p class="noir-ai-kicker">${storeName} Atelier</p>
                        <h2 id="noir-ai-title">${storeName} AI</h2>
                        <p id="noir-ai-description">${t('stylistDesc')}</p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button class="noir-ai-header-voice" id="noir-ai-header-voice" type="button" aria-label="${t('voiceInput')}" title="${t('speakToAI')}"><i data-lucide="mic"></i></button>
                    <button class="noir-ai-close" type="button" aria-label="${t('close')}"><i data-lucide="x"></i></button>
                </div>
            </header>
            <div class="noir-ai-status sr-only" role="status" aria-live="polite" aria-atomic="true"></div>
            <div class="noir-ai-messages" role="log" aria-live="polite" aria-relevant="additions text" aria-label="${t('stylistReply')}"></div>
            <div class="noir-ai-suggestions" aria-label="${t('suggestedHeader')}">
                <button class="noir-ai-suggestion" type="button">${t('suggest1', { amount: formatPriceJS(80000) })}</button>
                <button class="noir-ai-suggestion" type="button">${t('suggest2')}</button>
                <button class="noir-ai-suggestion" type="button">${t('suggest3')}</button>
                <button class="noir-ai-suggestion" type="button">${t('suggest4')}</button>
            </div>
            <form class="noir-ai-form">
                <label class="sr-only" for="noir-ai-input">${t('speakToAI')}</label>
                <input id="noir-ai-input" type="text" maxlength="700" autocomplete="off" placeholder="${t('placeholder')}" aria-label="${t('speakToAI')}">
                <button type="button" class="noir-ai-voice-btn" id="noir-ai-voice-btn" aria-label="${t('voiceInput')}" title="${t('speakToAI')}"><i data-lucide="mic"></i></button>
                <button type="submit" aria-label="${t('send')}"><i data-lucide="arrow-up"></i><span>${t('send')}</span></button>
            </form>
            <p class="noir-ai-disclaimer"><i data-lucide="shield-check"></i> ${t('disclaimer')}</p>
        </section>
        <button class="noir-ai-launcher" type="button" aria-expanded="false" aria-controls="noir-ai-panel" aria-label="${storeName} AI">
            <span class="noir-ai-launcher-text">${storeName} AI</span>
            <span class="noir-ai-launcher-icon" aria-hidden="true"><i data-lucide="sparkles"></i></span>
        </button>`;

    document.body.append(root);

    if (window.lucide?.createIcons) window.lucide.createIcons();

    const panel = root.querySelector('.noir-ai-panel');
    const launcher = root.querySelector('.noir-ai-launcher');
    const close = root.querySelector('.noir-ai-close');
    const form = root.querySelector('.noir-ai-form');
    const input = root.querySelector('#noir-ai-input');
    const voiceBtn = root.querySelector('#noir-ai-voice-btn');
    const send = form.querySelector('button[type="submit"]');
    const messages = root.querySelector('.noir-ai-messages');
    const status = root.querySelector('.noir-ai-status');
    const suggestionsContainer = root.querySelector('.noir-ai-suggestions');
    const mobileQuery = window.matchMedia('(max-width: 640px)');
    let history = [];
    let activeRequest = null;
    let lastFocus = null;

    const announce = (message) => {
        status.textContent = '';
        window.setTimeout(() => {
            status.textContent = message;
        }, 20);
    };

    const appendMessage = (role, text, options = {}) => {
        const { remember = true, fallback = false, payload = null, scroll = 'end' } = options;
        const message = document.createElement('div');
        message.className = 'noir-ai-message';
        message.dataset.role = role;
        message.setAttribute('role', 'article');
        message.setAttribute('aria-label', role === 'user' ? t('clientMessage') : t('stylistReply'));
        if (fallback) message.dataset.fallback = 'true';

        const label = document.createElement('span');
        label.className = 'noir-ai-message-label';
        label.textContent = role === 'user' ? (activeLocale === 'en' ? 'You' : t('clientMessage')) : t('stylist');

        const copy = document.createElement('div');
        copy.className = 'noir-ai-message-copy';

        let displayText = text || '';
        if (role === 'assistant') {
            displayText = displayText.replace(/\*\*([^* \n]+)\*\*/g, '$1').replace(/__([^_ \n]+)__/g, '$1');
        }

        const p = document.createElement('p');
        p.textContent = displayText;
        copy.append(p);

        if (payload && Array.isArray(payload.products) && payload.products.length > 0) {
            const outfitCard = document.createElement('div');
            outfitCard.className = 'noir-ai-decision-card';

            const headerMeta = document.createElement('div');
            headerMeta.className = 'noir-ai-meta-bar';

            const confidenceScore = Math.round((payload.confidence || 0.90) * 100);
            headerMeta.innerHTML = `
                <span class="noir-ai-confidence-badge"><i data-lucide="check-circle"></i> ${confidenceScore}% ${t('confidenceMatch')}</span>
                ${payload.occasion ? `<span class="noir-ai-occasion-badge">${payload.occasion}</span>` : ''}
            `;
            outfitCard.append(headerMeta);

            const grid = document.createElement('div');
            grid.className = 'noir-ai-product-grid';

            payload.products.forEach((prod) => {
                const item = document.createElement('div');
                item.className = 'noir-ai-product-tile';
                const imgSrc = prod.image || '/assets/images/placeholder.png';
                const basePath = catalogueUrl.replace(/\/store.*$/, '');
                const productUrl = `${basePath}/products/${encodeURIComponent(prod.id || prod.slug)}`;
                item.innerHTML = `
                    <div class="noir-ai-tile-img">
                        <img src="${imgSrc}" alt="${prod.name}" loading="lazy">
                    </div>
                    <div class="noir-ai-tile-details">
                        <span class="noir-ai-tile-cat">${prod.category || 'Atelier'}</span>
                        <h4 class="noir-ai-tile-name">${prod.name}</h4>
                        <p class="noir-ai-tile-reason">${prod.styling_rationale || prod.reason || 'Curated staple'}</p>
                        <div class="noir-ai-tile-footer">
                            <strong class="noir-ai-tile-price">${prod.formatted_price || formatPriceJS(prod.price)}</strong>
                            <a href="${productUrl}" class="noir-ai-view-btn">${t('viewPiece')}</a>
                        </div>
                    </div>
                `;
                grid.append(item);
            });
            outfitCard.append(grid);

            if (payload.total_price && payload.total_price > 0) {
                const totalBar = document.createElement('div');
                totalBar.className = 'noir-ai-total-bar';
                totalBar.innerHTML = `
                    <div class="noir-ai-total-info">
                        <span>${t('total')}</span>
                        <strong>${formatPriceJS(payload.total_price)}</strong>
                    </div>
                    <a href="${catalogueUrl}" class="noir-ai-cta-btn">${t('explore')}</a>
                `;
                outfitCard.append(totalBar);
            }

            // Render structured actions (e.g. Navigation, Shop This Look, Save Outfit)
            if (Array.isArray(payload.actions) && payload.actions.length > 0) {
                const actionsBar = document.createElement('div');
                actionsBar.className = 'noir-ai-actions-bar';
                actionsBar.style.cssText = 'display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;';
                payload.actions.forEach((act) => {
                    const actBtn = document.createElement('a');
                    actBtn.className = 'noir-ai-action-btn';
                    actBtn.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:rgba(196,154,69,0.15);border:1px solid #C49A45;color:#E5C378;font-size:11px;font-weight:700;border-radius:8px;text-decoration:none;transition:all 0.2s;';
                    actBtn.textContent = act.label || (act.type === 'navigate' ? 'Open Page' : 'Shop Look');
                    if (act.type === 'navigate' && act.destination) {
                        const basePath = catalogueUrl.replace(/\/store.*$/, '');
                        actBtn.href = `${basePath}${act.destination}`;
                    } else {
                        actBtn.href = catalogueUrl;
                    }
                    actionsBar.append(actBtn);
                });
                outfitCard.append(actionsBar);
            }

            // Interactive feedback actions
            const feedbackBar = document.createElement('div');
            feedbackBar.className = 'noir-ai-feedback-bar';
            feedbackBar.innerHTML = `
                <span class="noir-ai-feedback-label">${t('helpful')}</span>
                <button type="button" class="noir-ai-fb-btn" data-action="like" title="Love this ensemble"><i data-lucide="thumbs-up"></i> ${t('yes')}</button>
                <button type="button" class="noir-ai-fb-btn" data-action="not_my_style" title="Not quite my style"><i data-lucide="thumbs-down"></i> ${t('no')}</button>
            `;

            // Attach feedback listeners
            feedbackBar.querySelectorAll('.noir-ai-fb-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const action = btn.dataset.action;
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    try {
                        const basePath = catalogueUrl.replace(/\/store.*$/, '');
                        await fetch(`${basePath}/api/v1/assistant/feedback`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: action, metadata: { prompt: displayText } })
                        });
                        feedbackBar.innerHTML = `<span style="font-size:11px;color:#C49A45;">Feedback saved. Thank you!</span>`;
                    } catch (_) {}
                });
            });

            outfitCard.append(feedbackBar);
            copy.append(outfitCard);
        }

        message.append(label, copy);

        if (fallback) {
            const browse = document.createElement('a');
            browse.className = 'noir-ai-browse-link';
            browse.href = catalogueUrl;
            browse.textContent = activeLocale === 'en' ? 'Browse the collection' : (activeLocale === 'fr' ? 'Parcourir la collection' : (activeLocale === 'es' ? 'Explorar la colección' : 'Browse the collection'));
            message.append(browse);
        }

        messages.append(message);
        if (window.lucide?.createIcons) window.lucide.createIcons();

        if (scroll === 'start') {
            const offset = message.getBoundingClientRect().top - messages.getBoundingClientRect().top;
            messages.scrollTop += offset - 8;
        } else {
            messages.scrollTop = messages.scrollHeight;
        }

        if (remember) {
            history.push({ role, content: displayText });
            history = history.slice(-8);
        }
        if (role === 'assistant') announce(displayText);

        // Update follow up chips if provided
        if (payload && Array.isArray(payload.follow_up_suggestions) && payload.follow_up_suggestions.length > 0) {
            suggestionsContainer.innerHTML = '';
            payload.follow_up_suggestions.forEach((sug) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'noir-ai-suggestion';
                btn.textContent = sug;
                btn.addEventListener('click', () => {
                    input.value = sug;
                    form.requestSubmit();
                });
                suggestionsContainer.append(btn);
            });
        }
    };

    const setOpen = (open) => {
        root.dataset.open = String(open);
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        launcher.setAttribute('aria-label', open ? t('close') : `${storeName} AI`);
        document.body.classList.toggle('noir-ai-mobile-open', open && mobileQuery.matches);

        if (open) {
            lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            announce(`${storeName} AI opened.`);
            window.setTimeout(() => {
                if (input) input.focus({ preventScroll: true });
            }, 30);
        } else {
            announce(`${storeName} AI closed.`);
            if (lastFocus && document.contains(lastFocus)) {
                lastFocus.focus({ preventScroll: true });
            }
        }
    };

    const fallback = (message) => {
        appendMessage('assistant', message, { remember: false, fallback: true, scroll: 'start' });
    };

    const setSending = (sending) => {
        input.disabled = sending;
        send.disabled = sending;
        send.querySelector('span').textContent = sending ? t('thinking') : t('send');
        send.setAttribute('aria-label', sending ? t('thinking') : t('send'));
        input.setAttribute('aria-busy', String(sending));
        send.classList.toggle('is-loading', sending);
        if (sending) announce(t('thinking'));
    };

    const submitMessage = async (text) => {
        const previousHistory = history.slice(-6);
        appendMessage('user', text);
        input.value = '';
        setSending(true);

        activeRequest = new AbortController();
        const timeout = window.setTimeout(() => activeRequest?.abort(), requestTimeout);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ message: text, history: previousHistory }),
                signal: activeRequest.signal,
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || (typeof payload.reply !== 'string' && typeof payload.message !== 'string')) {
                if (response.status === 429) {
                    throw new Error('busy');
                }
                throw new Error('unavailable');
            }

            const replyText = payload.reply || payload.message || '';
            appendMessage('assistant', replyText, { scroll: 'start', payload: payload });
        } catch (error) {
            history = history.filter((turn, index) => !(index === history.length - 1 && turn.role === 'user' && turn.content === text));
            if (error instanceof Error && error.message === 'busy') {
                fallback(t('busy'));
            } else {
                fallback(t('error'));
            }
        } finally {
            window.clearTimeout(timeout);
            activeRequest = null;
            setSending(false);
            input.focus();
        }
    };

    launcher.addEventListener('click', () => setOpen(panel.hidden));
    close.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) setOpen(false);
    });
    mobileQuery.addEventListener?.('change', () => {
        document.body.classList.toggle('noir-ai-mobile-open', !panel.hidden && mobileQuery.matches);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text || send.disabled) return;
        submitMessage(text);
    });

    root.querySelectorAll('.noir-ai-suggestion').forEach((button) => {
        button.addEventListener('click', () => {
            input.value = button.textContent || '';
            announce('Styling prompt selected: ' + input.value);
            form.requestSubmit();
        });
    });

    const headerVoiceBtn = root.querySelector('#noir-ai-header-voice');

    const handleVoiceRecognition = () => {
        const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRec) {
            appendMessage('assistant', t('voiceNotSupported'), { remember: false });
            return;
        }

        try {
            const recognition = new SpeechRec();
            recognition.lang = activeLocale === 'en' ? 'en-US' : (activeLocale === 'fr' ? 'fr-FR' : (activeLocale === 'es' ? 'es-ES' : 'en-US'));
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            
            if (voiceBtn) voiceBtn.classList.add('is-listening');
            if (headerVoiceBtn) headerVoiceBtn.classList.add('is-listening');
            input.placeholder = activeLocale === 'fr' ? 'Écoute en cours...' : (activeLocale === 'es' ? 'Escuchando...' : 'Listening...');
            announce(input.placeholder);
            
            recognition.onresult = (e) => {
                const transcript = e.results[0][0].transcript;
                input.value = transcript;
                if (voiceBtn) voiceBtn.classList.remove('is-listening');
                if (headerVoiceBtn) headerVoiceBtn.classList.remove('is-listening');
                input.placeholder = t('placeholder');
                form.requestSubmit();
            };
            recognition.onerror = (err) => {
                if (voiceBtn) voiceBtn.classList.remove('is-listening');
                if (headerVoiceBtn) headerVoiceBtn.classList.remove('is-listening');
                input.placeholder = t('placeholder');
                console.warn('Voice recognition notice:', err);
            };
            recognition.onend = () => {
                if (voiceBtn) voiceBtn.classList.remove('is-listening');
                if (headerVoiceBtn) headerVoiceBtn.classList.remove('is-listening');
                input.placeholder = t('placeholder');
            };
            recognition.start();
        } catch (err) {
            if (voiceBtn) voiceBtn.classList.remove('is-listening');
            if (headerVoiceBtn) headerVoiceBtn.classList.remove('is-listening');
            input.placeholder = t('placeholder');
        }
    };

    if (voiceBtn) voiceBtn.addEventListener('click', handleVoiceRecognition);
    if (headerVoiceBtn) headerVoiceBtn.addEventListener('click', handleVoiceRecognition);

    appendMessage('assistant', t('greeting'), { remember: false });
})();
