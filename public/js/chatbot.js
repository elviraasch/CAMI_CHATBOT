'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.getElementById('cami-toggle-btn');
    const closeButton = document.getElementById('cami-close-btn');
    const historyButton = document.getElementById('cami-history-btn');
    const historyPanel = document.getElementById('cami-history-panel');
    const historyList = document.getElementById('cami-history-list');
    const historyOverview = document.getElementById('cami-history-overview');
    const historyDetail = document.getElementById('cami-history-detail');
    const historyDetailTitle = document.getElementById('cami-history-detail-title');
    const historyMessages = document.getElementById('cami-history-messages');
    const historyBackButton = document.getElementById('cami-history-back-btn');
    const chatWidget = document.getElementById('cami-chatbot');
    const panel = document.getElementById('cami-panel');
    const form = document.getElementById('cami-input-form');
    const input = document.getElementById('cami-input');
    const sendButton = document.getElementById('cami-send-btn');
    const messages = document.getElementById('cami-messages');
    const typing = document.getElementById('cami-typing');

    if (!toggleButton || !closeButton || !historyButton || !historyPanel || !historyList
        || !historyOverview || !historyDetail || !historyDetailTitle || !historyMessages || !historyBackButton
        || !chatWidget || !panel || !form || !input || !messages || !typing) {
        return;
    }
    const welcomeMessage = messages.firstElementChild;

    const storageOwner = chatWidget.dataset.camiStorageOwner || 'guest';
    const storageKey = `cami:chat:v2:${storageOwner}`;
    const openKey = `${storageKey}:open`;
    const activeKey = `${storageKey}:active`;
    const archiveKey = `${storageKey}:archive`;
    const maxHistorySessions = 30;
    const maxSessionMessages = 100;
    const browserStorage = (persistent) => {
        try {
            return persistent ? window.localStorage : window.sessionStorage;
        } catch (_error) {
            return null;
        }
    };
    const historyStorage = browserStorage(storageOwner !== 'guest');
    const panelStorage = browserStorage(false);
    const readItem = (storage, key) => {
        try {
            return storage?.getItem(key) ?? null;
        } catch (_error) {
            return null;
        }
    };
    const writeItem = (storage, key, value) => {
        try {
            storage?.setItem(key, value);
        } catch (_error) {
            // Chat tetap dapat dipakai jika penyimpanan browser dinonaktifkan atau penuh.
        }
    };
    const removeItem = (storage, key) => {
        try {
            storage?.removeItem(key);
        } catch (_error) {
            // Penyimpanan browser mungkin dinonaktifkan.
        }
    };
    const readJson = (storage, key, fallback) => {
        try {
            return JSON.parse(readItem(storage, key) || 'null') ?? fallback;
        } catch (_error) {
            return fallback;
        }
    };
    const makeConversation = () => ({
        id: `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`,
        started_at: new Date().toISOString(),
        records: [],
    });
    const storedArchive = readJson(historyStorage, archiveKey, []);
    const archivedSessions = Array.isArray(storedArchive)
        ? storedArchive.filter((session) => session && typeof session.id === 'string'
            && Array.isArray(session.records)).slice(-maxHistorySessions) : [];
    const storedActive = readJson(panelStorage, activeKey, null);
    let activeConversation = storedActive && typeof storedActive.id === 'string'
        && Array.isArray(storedActive.records) ? storedActive : null;

    // Riwayat versi lama digabung menjadi satu sesi, bukan satu entri per pesan.
    if (!activeConversation && readItem(historyStorage, archiveKey) === null) {
        const legacyKey = `cami:chat:v1:${storageOwner}`;
        const oldRecords = readJson(historyStorage, legacyKey, []);
        if (Array.isArray(oldRecords) && oldRecords.length > 0) {
            const oldConversation = makeConversation();
            oldConversation.started_at = oldRecords[0]?.at || oldConversation.started_at;
            oldConversation.records = oldRecords.filter((record) => record
                && (record.kind === 'user' || record.kind === 'bot'));
            if (readItem(panelStorage, `${legacyKey}:open`) === '1') {
                activeConversation = oldConversation;
                writeItem(panelStorage, activeKey, JSON.stringify(activeConversation));
                writeItem(panelStorage, openKey, '1');
            } else {
                oldConversation.closed_at = oldRecords[oldRecords.length - 1]?.at || new Date().toISOString();
                archivedSessions.push(oldConversation);
                writeItem(historyStorage, archiveKey, JSON.stringify(archivedSessions));
            }
        }
    }

    const ensureActiveConversation = () => {
        if (!activeConversation) {
            activeConversation = makeConversation();
            writeItem(panelStorage, activeKey, JSON.stringify(activeConversation));
        }
        return activeConversation;
    };
    let historyOpen = false;
    let sendingConversationId = null;

    const setHistoryOpen = (isOpen) => {
        historyOpen = isOpen;
        if (isOpen) {
            historyOverview.classList.remove('cami-hidden');
            historyDetail.classList.add('cami-hidden');
        }
        historyPanel.classList.toggle('cami-hidden', !isOpen);
        messages.classList.toggle('cami-hidden', isOpen);
        form.classList.toggle('cami-hidden', isOpen);
        typing.classList.toggle('cami-hidden', isOpen || !input.disabled);
        historyButton.setAttribute('aria-expanded', String(isOpen));
        historyButton.setAttribute('aria-label', isOpen ? 'Kembali ke percakapan' : 'Lihat riwayat percakapan');
        if (!isOpen && !panel.classList.contains('cami-hidden')) input.focus();
    };

    const setPanelOpen = (isOpen) => {
        if (isOpen) ensureActiveConversation();
        panel.classList.toggle('cami-hidden', !isOpen);
        toggleButton.setAttribute('aria-expanded', String(isOpen));
        writeItem(panelStorage, openKey, isOpen ? '1' : '0');
        if (isOpen) {
            if (!historyOpen) input.focus();
            messages.scrollTop = messages.scrollHeight;
        }
    };

    const addMessage = (text, sender, extraClass = '', recordId = null, container = messages) => {
        const row = document.createElement('div');
        row.className = `cami-msg cami-msg-${sender}${extraClass ? ` ${extraClass}` : ''}`;
        if (recordId) row.dataset.camiRecordId = recordId;

        if (sender === 'bot') {
            const avatar = document.createElement('img');
            avatar.src = window.CAMI_AVATAR;
            avatar.alt = 'Cami';
            avatar.className = 'cami-msg-avatar';
            row.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = 'cami-msg-bubble';
        bubble.textContent = text;
        row.appendChild(bubble);
        container.appendChild(row);
        container.scrollTop = container.scrollHeight;

        return bubble;
    };

    const renderHistoryList = () => {
        historyList.replaceChildren();
        if (archivedSessions.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'cami-history-empty';
            empty.textContent = 'Belum ada sesi yang ditutup. Tekan X setelah mengobrol untuk menyimpannya di sini.';
            historyList.appendChild(empty);
            return;
        }

        [...archivedSessions].reverse().forEach((session) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'cami-history-item';

            const title = document.createElement('strong');
            title.textContent = session.records.find((record) => record.kind === 'user')?.text
                || 'Percakapan Cami';
            item.appendChild(title);

            const date = new Date(session.closed_at || session.started_at);
            if (!Number.isNaN(date.getTime())) {
                const time = document.createElement('small');
                time.textContent = `${date.toLocaleString('id-ID', {
                    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
                })} · ${session.records.length} pesan`;
                item.appendChild(time);
            }

            const lastReply = [...session.records].reverse().find((record) => record.kind === 'bot');
            if (typeof lastReply?.message?.text === 'string') {
                const preview = document.createElement('span');
                preview.textContent = lastReply.message.text;
                item.appendChild(preview);
            }

            item.addEventListener('click', () => renderArchivedConversation(session));
            historyList.appendChild(item);
        });
    };

    const saveRecord = (data, conversation = ensureActiveConversation()) => {
        const record = {
            ...data,
            id: `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`,
            at: new Date().toISOString(),
        };
        conversation.records.push(record);
        if (conversation.records.length > maxSessionMessages) {
            conversation.records.splice(0, conversation.records.length - maxSessionMessages);
        }
        if (conversation === activeConversation) {
            writeItem(panelStorage, activeKey, JSON.stringify(conversation));
        } else if (archivedSessions.includes(conversation)) {
            writeItem(historyStorage, archiveKey, JSON.stringify(archivedSessions));
            renderHistoryList();
        }
        return record;
    };

    const archiveCurrentConversation = () => {
        if (activeConversation?.records.length) {
            activeConversation.closed_at = new Date().toISOString();
            archivedSessions.push(activeConversation);
            if (archivedSessions.length > maxHistorySessions) {
                archivedSessions.splice(0, archivedSessions.length - maxHistorySessions);
            }
            writeItem(historyStorage, archiveKey, JSON.stringify(archivedSessions));
        }
        activeConversation = null;
        removeItem(panelStorage, activeKey);
        renderHistoryList();
    };

    const isSafeMediaUrl = (value) => {
        try {
            const url = new URL(value, window.location.origin);
            return ['http:', 'https:'].includes(url.protocol);
        } catch (_error) {
            return false;
        }
    };

    const formatKg = (value) => new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));

    const formatRupiah = (value) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));

    const renderCalculationResult = (bubble, result, updatePageSummary = true) => {
        bubble.classList.add('cami-result-bubble');

        const summary = document.createElement('div');
        summary.className = 'cami-result-summary';

        const title = document.createElement('strong');
        title.textContent = 'Total emisi tahunan';
        summary.appendChild(title);

        const totalTon = Number(result.total_kg) / 1000;
        const total = document.createElement('div');
        total.className = 'cami-result-total';
        // Tampilkan dalam format ton jika lebih dari 1 ton, agar sinkron dengan web
        total.textContent = totalTon >= 1 
            ? `${totalTon.toFixed(3)} ton CO₂e/tahun` 
            : `${formatKg(result.total_kg)} kg CO2e`;
        summary.appendChild(total);

        const scopes = document.createElement('div');
        scopes.className = 'cami-result-scopes';
        [1, 2, 3].forEach((scope) => {
            const scopeKg = Number(result[`scope${scope}_kg`] || 0);
            const scopeTon = scopeKg / 1000;
            const item = document.createElement('span');
            item.textContent = `Scope ${scope}: ${scopeTon >= 1 ? scopeTon.toFixed(3) + ' ton' : formatKg(scopeKg) + ' kg'}`;
            scopes.appendChild(item);
        });
        summary.appendChild(scopes);

        const cost = document.createElement('span');
        cost.className = 'cami-result-cost';
        cost.textContent = `Estimasi biaya offset: ${formatRupiah(result.estimated_cost)}`;
        summary.appendChild(cost);
        bubble.appendChild(summary);

        const links = document.createElement('div');
        links.className = 'cami-result-links';
        [
            [window.CAMI_PROJECTS_URL, 'Lihat Total Emisi Anda'],
            [window.CAMI_DASHBOARD_URL, 'Buka dashboard'],
        ].forEach(([url, label]) => {
            if (!url || !isSafeMediaUrl(url)) return;
            const link = document.createElement('a');
            link.href = url;
            link.textContent = label;
            links.appendChild(link);
        });
        bubble.appendChild(links);

        if (!updatePageSummary) return;

        const totalKg = Number(result.total_kg);
        const totalEmissions = document.getElementById('total-emissions');
        const treeEquivalent = document.getElementById('tree-equivalent');
        const offsetNeeded = document.getElementById('offset-needed');
        if (totalEmissions) {
            totalEmissions.textContent = totalKg.toLocaleString('id-ID', {
                maximumFractionDigits: 3,
            });
        }
        if (treeEquivalent) treeEquivalent.textContent = Math.ceil(totalKg / 22).toLocaleString('id-ID');
        if (offsetNeeded) offsetNeeded.textContent = (totalKg / 1000).toFixed(2);
    };

    const appendBalanceNumber = (container, label, value) => {
        const item = document.createElement('div');
        item.className = 'cami-balance-number';
        const caption = document.createElement('small');
        caption.textContent = label;
        const number = document.createElement('strong');
        number.textContent = value;
        item.append(caption, number);
        container.appendChild(item);
    };

    const renderCarbonBalance = (bubble, result) => {
        bubble.classList.add('cami-result-bubble');
        const card = document.createElement('section');
        card.className = 'cami-balance-card';

        const title = document.createElement('strong');
        title.className = 'cami-balance-title';
        title.textContent = result.type === 'offset_simulation_result'
            ? 'Ringkasan simulasi'
            : 'Saldo karbon';
        card.appendChild(title);

        const numbers = document.createElement('div');
        numbers.className = 'cami-balance-numbers';
        if (result.type === 'offset_simulation_result') {
            appendBalanceNumber(numbers, 'Sebelum', `${formatKg(result.remaining_before_kg)} kg`);
            appendBalanceNumber(numbers, 'Rencana', `${Number(result.planned_ton).toLocaleString('id-ID')} ton`);
            appendBalanceNumber(numbers, 'Sesudah', `${formatKg(result.remaining_after_kg)} kg`);
        } else {
            appendBalanceNumber(numbers, 'Total emisi', `${formatKg(result.total_kg)} kg`);
            appendBalanceNumber(numbers, 'Sudah offset', `${formatKg(result.offset_kg)} kg`);
            appendBalanceNumber(numbers, 'Sisa emisi', `${formatKg(result.remaining_kg)} kg`);
        }
        card.appendChild(numbers);

        if (result.type === 'carbon_balance_result') {
            const progress = document.createElement('div');
            progress.className = 'cami-balance-progress';
            const progressValue = document.createElement('span');
            progressValue.style.width = `${Math.min(100, Math.max(0, Number(result.offset_percentage) || 0))}%`;
            progress.appendChild(progressValue);
            card.appendChild(progress);

            const note = document.createElement('small');
            note.className = 'cami-balance-note';
            note.textContent = `${Number(result.offset_percentage || 0).toLocaleString('id-ID')}% tertutup · kebutuhan minimum ${Number(result.required_ton || 0).toLocaleString('id-ID')} ton`;
            card.appendChild(note);
        } else if (Number.isFinite(Number(result.estimated_total_cost))) {
            const note = document.createElement('small');
            note.className = 'cami-balance-note';
            note.textContent = `Estimasi biaya ${formatRupiah(result.estimated_total_cost)} · belum membuat pesanan`;
            card.appendChild(note);
        }

        bubble.appendChild(card);
    };

    const renderActionPlan = (bubble, result) => {
        if (!Array.isArray(result.steps) || result.steps.length === 0) return;
        bubble.classList.add('cami-result-bubble');
        const plan = document.createElement('section');
        plan.className = 'cami-action-plan';
        const title = document.createElement('strong');
        title.className = 'cami-action-title';
        title.textContent = 'Prioritas Carbon Coach';
        plan.appendChild(title);

        result.steps.forEach((step, index) => {
            const item = document.createElement('div');
            item.className = 'cami-action-step';
            const rank = document.createElement('span');
            rank.className = 'cami-action-rank';
            rank.textContent = String(step.rank || index + 1);
            const copy = document.createElement('span');
            copy.className = 'cami-action-copy';
            const heading = document.createElement('strong');
            heading.textContent = `${step.label || 'Aktivitas'} · ${formatKg(step.emission_kg || 0)} kg`;
            const advice = document.createElement('small');
            advice.textContent = step.advice || '';
            copy.append(heading, advice);
            item.append(rank, copy);
            plan.appendChild(item);
        });

        bubble.appendChild(plan);
    };

    const renderBotMessage = (message, recordId = null, restoring = false,
        allowButtons = true, container = messages) => {
        const text = typeof message?.text === 'string' ? message.text : '';
        const result = message?.custom?.type === 'calculation_result'
            && Number.isFinite(message.custom.total_kg)
            ? message.custom : null;
        
        const bubble = addMessage(
            result 
                ? (storageOwner === 'guest' 
                    ? 'Hasil estimasi emisi sementara berhasil dihitung.' 
                    : 'Hasil emisi berhasil dihitung dan disimpan di akunmu.')
                : (text || 'Cami mengirimkan balasan tanpa teks.'),
            'bot',
            '',
            recordId,
            container
        );

        if (result) renderCalculationResult(bubble, result, !restoring);

        const customType = message?.custom?.type;
        if (['carbon_balance_result', 'offset_simulation_result'].includes(customType)) {
            renderCarbonBalance(bubble, message.custom);
        }
        if (customType === 'personal_action_plan_result') {
            renderActionPlan(bubble, message.custom);
        }

        if (message?.image && isSafeMediaUrl(message.image)) {
            const image = document.createElement('img');
            image.src = message.image;
            image.alt = 'Gambar dari Cami';
            image.loading = 'lazy';
            bubble.appendChild(image);
        }

        if (Array.isArray(message?.buttons) && message.buttons.length > 0) {
            const buttonGroup = document.createElement('div');
            buttonGroup.className = 'cami-response-buttons';

            message.buttons.forEach((button) => {
                if (!button?.title || !button?.payload) return;

                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'cami-response-btn';
                option.textContent = button.title;
                option.disabled = !allowButtons;
                if (allowButtons) {
                    option.addEventListener('click', () => sendMessage(button.payload, button.title));
                }
                buttonGroup.appendChild(option);
            });

            bubble.appendChild(buttonGroup);
        }

        const recommendations = message?.custom?.recommendations;
        if (result && Array.isArray(recommendations) && recommendations.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'cami-result-empty';
            empty.textContent = 'Belum ada proyek offset yang tersedia untuk direkomendasikan.';
            bubble.appendChild(empty);
        }

        if (Array.isArray(recommendations) && recommendations.length > 0) {
            bubble.classList.add('cami-result-bubble');

            const heading = document.createElement('strong');
            heading.className = 'cami-recommendation-heading';
            heading.textContent = message.custom.heading || (message.custom.source === 'profile'
                ? 'Pilihan proyek awal'
                : 'Proyek yang direkomendasikan');
            bubble.appendChild(heading);

            const list = document.createElement('div');
            list.className = 'cami-recommendations';

            recommendations.forEach((project) => {
                if (!project?.name) return;

                const card = document.createElement('article');
                card.className = 'cami-project-card';

                const name = document.createElement('strong');
                name.className = 'cami-project-name';
                name.textContent = project.name;
                card.appendChild(name);

                const score = Number(project.score);
                if (project.score != null && Number.isFinite(score)) {
                    const scoreText = document.createElement('span');
                    scoreText.className = 'cami-project-score';
                    scoreText.textContent = `Cocok ${Math.round(score)}%`;
                    card.appendChild(scoreText);
                }

                if (project.location) {
                    const location = document.createElement('span');
                    location.className = 'cami-project-meta';
                    location.textContent = project.location;
                    card.appendChild(location);
                }

                if (project.category) {
                    const category = document.createElement('span');
                    category.className = 'cami-project-meta';
                    category.textContent = project.category;
                    card.appendChild(category);
                }

                if (project.company_name) {
                    const company = document.createElement('span');
                    company.className = 'cami-project-meta';
                    company.textContent = `Pengelola: ${project.company_name}`;
                    card.appendChild(company);
                }

                const projectFacts = [
                    project.standard ? `Standar: ${project.standard}${project.verified_year ? ` (${project.verified_year})` : ''}` : null,
                    Number(project.duration_months) > 0 ? `Durasi: ${Number(project.duration_months).toLocaleString('id-ID')} bulan` : null,
                    Number(project.area_ha) > 0 ? `Area: ${Number(project.area_ha).toLocaleString('id-ID')} ha` : null,
                    Number(project.co2_per_year) > 0 ? `Dampak: ${Number(project.co2_per_year).toLocaleString('id-ID')} ton CO2e/tahun` : null,
                    Number(project.families_impacted) > 0 ? `${Number(project.families_impacted).toLocaleString('id-ID')} keluarga terdampak` : null,
                ].filter(Boolean);
                if (projectFacts.length > 0) {
                    const facts = document.createElement('small');
                    facts.className = 'cami-project-detail';
                    facts.textContent = projectFacts.join(' · ');
                    card.appendChild(facts);
                }

                const price = Number(project.price_per_ton);
                if (Number.isFinite(price)) {
                    const priceText = document.createElement('span');
                    priceText.className = 'cami-project-price';
                    priceText.textContent = `${new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }).format(price)} / ton`;
                    card.appendChild(priceText);
                }

                if (Number.isFinite(project.stock_available)) {
                    const stockText = document.createElement('span');
                    stockText.className = 'cami-project-meta';
                    stockText.textContent = `Stok: ${new Intl.NumberFormat('id-ID').format(project.stock_available)} ton`;
                    card.appendChild(stockText);
                }

                if (Number.isFinite(project.required_ton) && Number.isFinite(project.total_offset_cost)) {
                    const offsetCost = document.createElement('span');
                    offsetCost.className = 'cami-project-price';
                    offsetCost.textContent = `Perkiraan ${project.required_ton} ton: ${formatRupiah(project.total_offset_cost)} sebelum pajak`;
                    card.appendChild(offsetCost);
                }

                if (Number.isFinite(project.simulated_ton) && Number.isFinite(project.remaining_after_kg)) {
                    const simulation = document.createElement('span');
                    simulation.className = 'cami-project-price';
                    simulation.textContent = `Sesudah ${project.simulated_ton} ton: sisa ${formatKg(project.remaining_after_kg)} kg CO2e`;
                    card.appendChild(simulation);
                }

                if (Number.isFinite(project.purchased_ton) && Number.isFinite(project.purchase_count)) {
                    const soldText = document.createElement('span');
                    soldText.className = 'cami-project-meta';
                    soldText.textContent = `Terjual: ${new Intl.NumberFormat('id-ID').format(project.purchased_ton)} ton dari ${new Intl.NumberFormat('id-ID').format(project.purchase_count)} pesanan`;
                    card.appendChild(soldText);
                }

                if (Array.isArray(project.reasons) && project.reasons.length > 0) {
                    const reasonTitle = document.createElement('strong');
                    reasonTitle.className = 'cami-project-section-title';
                    reasonTitle.textContent = 'Mengapa proyek ini ditampilkan';
                    card.appendChild(reasonTitle);

                    const reason = document.createElement('small');
                    reason.className = 'cami-project-reason';
                    reason.textContent = project.reasons.join(' · ');
                    card.appendChild(reason);
                }

                const scoreBreakdown = project.recommendation_breakdown;
                if (scoreBreakdown && typeof scoreBreakdown === 'object') {
                    const criteria = [
                        ['Sumber emisi', scoreBreakdown.emission_match, 35],
                        ['Harga', scoreBreakdown.budget_match, 25],
                        ['Kecukupan stok', scoreBreakdown.stock_coverage, 20],
                        ['Lokasi', scoreBreakdown.location_match, 10],
                        ['Standar tercatat', scoreBreakdown.verification, 10],
                    ].filter((item) => Number.isFinite(Number(item[1])));

                    if (criteria.length > 0) {
                        const breakdown = document.createElement('div');
                        breakdown.className = 'cami-score-breakdown';
                        criteria.forEach(([label, earned, maximum]) => {
                            const criterion = document.createElement('span');
                            criterion.textContent = `${label}: ${Number(earned).toLocaleString('id-ID')}/${maximum}`;
                            breakdown.appendChild(criterion);
                        });
                        card.appendChild(breakdown);
                    }
                }

                const disclosure = project.purchase_disclosure;
                if (disclosure && typeof disclosure === 'object') {
                    const details = document.createElement('details');
                    details.className = 'cami-purchase-disclosure';

                    const summary = document.createElement('summary');
                    summary.textContent = 'Periksa biaya, risiko & ketentuan';
                    details.appendChild(summary);

                    const amountGrid = document.createElement('div');
                    amountGrid.className = 'cami-disclosure-amounts';
                    [
                        ['Jumlah', `${Number(disclosure.quantity_ton || 1).toLocaleString('id-ID')} ton`],
                        ['Subtotal', formatRupiah(Number(disclosure.subtotal || 0))],
                        [`PPN ${Number(disclosure.tax_rate_percentage || 0).toLocaleString('id-ID')}%`, formatRupiah(Number(disclosure.tax || 0))],
                        ['Biaya lain', formatRupiah(Number(disclosure.other_fees || 0))],
                        ['Estimasi total', formatRupiah(Number(disclosure.estimated_total || 0))],
                    ].forEach(([label, value]) => {
                        const row = document.createElement('span');
                        const key = document.createElement('small');
                        const amount = document.createElement('strong');
                        key.textContent = label;
                        amount.textContent = value;
                        row.append(key, amount);
                        amountGrid.appendChild(row);
                    });
                    details.appendChild(amountGrid);

                    const appendDisclosureList = (title, items) => {
                        if (!Array.isArray(items) || items.length === 0) return;
                        const block = document.createElement('div');
                        block.className = 'cami-disclosure-list';
                        const label = document.createElement('strong');
                        label.textContent = title;
                        const listElement = document.createElement('ul');
                        items.forEach((item) => {
                            const listItem = document.createElement('li');
                            listItem.textContent = item;
                            listElement.appendChild(listItem);
                        });
                        block.append(label, listElement);
                        details.appendChild(block);
                    };

                    appendDisclosureList('Risiko yang perlu dipahami', disclosure.risks);
                    appendDisclosureList('Ketentuan transaksi', disclosure.terms);

                    const finalNote = document.createElement('small');
                    finalNote.className = 'cami-disclosure-note';
                    finalNote.textContent = 'Estimasi ini memakai data saat ini. Harga, stok, dan total final mengikuti halaman checkout.';
                    details.appendChild(finalNote);
                    card.appendChild(details);
                }

                const actions = document.createElement('div');
                actions.className = 'cami-project-actions';

                if (allowButtons) {
                    const simulate = document.createElement('button');
                    simulate.type = 'button';
                    simulate.className = 'cami-project-simulate';
                    simulate.textContent = 'Simulasi 1 ton';
                    simulate.addEventListener('click', () => sendMessage(
                        `simulasikan pembelian 1 ton proyek ${project.name}`,
                        `Simulasi 1 ton · ${project.name}`
                    ));
                    actions.appendChild(simulate);

                    const disclosureButton = document.createElement('button');
                    disclosureButton.type = 'button';
                    disclosureButton.className = 'cami-project-disclosure-btn';
                    disclosureButton.textContent = 'Info sebelum beli';
                    disclosureButton.addEventListener('click', () => sendMessage(
                        `jelaskan biaya risiko dan ketentuan sebelum membeli proyek ${project.name}`,
                        `Info sebelum beli · ${project.name}`
                    ));
                    actions.appendChild(disclosureButton);
                }

                if (project.url && isSafeMediaUrl(project.url)) {
                    const link = document.createElement('a');
                    link.className = 'cami-project-link';
                    link.href = project.url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.textContent = 'Tinjau & beli';
                    actions.appendChild(link);
                }

                if (actions.childElementCount > 0) card.appendChild(actions);

                list.appendChild(card);
            });

            bubble.appendChild(list);
        }
    };

    const renderArchivedConversation = (conversation) => {
        historyMessages.replaceChildren();
        historyDetailTitle.textContent = conversation.records.find((record) => record.kind === 'user')?.text
            || 'Percakapan Cami';
        conversation.records.forEach((record) => {
            if (record.kind === 'user' && typeof record.text === 'string') {
                addMessage(record.text, 'user', '', record.id, historyMessages);
            } else if (record.kind === 'bot' && record.message && typeof record.message === 'object') {
                if (record.error) {
                    addMessage(record.message.text || '', 'bot', 'cami-msg-error', record.id, historyMessages);
                } else {
                    renderBotMessage(record.message, record.id, true, false, historyMessages);
                }
            }
        });
        historyOverview.classList.add('cami-hidden');
        historyDetail.classList.remove('cami-hidden');
        historyMessages.scrollTop = 0;
    };

    const setSending = (isSending) => {
        input.disabled = isSending;
        if (sendButton) sendButton.disabled = isSending;
        typing.classList.toggle('cami-hidden', !isSending || historyOpen);
        messages.scrollTop = messages.scrollHeight;
    };

    const saveBotMessage = (message, conversation, isError = false) => {
        const record = saveRecord({ kind: 'bot', message, error: isError }, conversation);
        if (activeConversation !== conversation) return;
        if (isError) {
            addMessage(message.text, 'bot', 'cami-msg-error', record.id);
        } else {
            renderBotMessage(message, record.id);
        }
    };

    const sendMessage = async (message, visibleMessage = message) => {
        const cleanMessage = String(message).trim();
        if (!cleanMessage || input.disabled) return;
        const requestConversation = ensureActiveConversation();

        messages.querySelectorAll('.cami-response-btn:not(:disabled)').forEach((button) => {
            button.disabled = true;
        });

        const visibleText = String(visibleMessage).trim();
        const userRecord = saveRecord({ kind: 'user', text: visibleText }, requestConversation);
        addMessage(visibleText, 'user', '', userRecord.id);
        sendingConversationId = requestConversation.id;
        setSending(true);

        try {
            const response = await fetch(window.CAMI_CHAT_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.CAMI_CSRF_TOKEN,
                },
                body: JSON.stringify({ message: cleanMessage, conversation_id: requestConversation.id }),
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Pesan tidak dapat dikirim.');
            }

            if (!Array.isArray(payload.data) || payload.data.length === 0) {
                saveBotMessage({ text: 'Aku belum memahami pertanyaan itu. Contoh: "proyek apa yang paling murah?", "rekomendasi proyek", atau "hitung emisi".' }, requestConversation);
                return;
            }

            payload.data.forEach((botMessage) => saveBotMessage(botMessage, requestConversation));
        } catch (error) {
            saveBotMessage({ text: error.message || 'Chatbot sedang tidak tersedia. Silakan coba lagi.' }, requestConversation, true);
        } finally {
            if (activeConversation === requestConversation
                && sendingConversationId === requestConversation.id) {
                sendingConversationId = null;
                setSending(false);
                input.value = '';
                input.focus();
            }
        }
    };

    renderHistoryList();
    const activeRecords = activeConversation?.records || [];
    const lastRecord = activeRecords[activeRecords.length - 1];
    activeRecords.forEach((record) => {
        if (record.kind === 'user' && typeof record.text === 'string') {
            addMessage(record.text, 'user', '', record.id);
        } else if (record.kind === 'bot' && record.message && typeof record.message === 'object') {
            if (record.error) {
                addMessage(record.message.text || '', 'bot', 'cami-msg-error', record.id);
            } else {
                renderBotMessage(record.message, record.id, true, record === lastRecord);
            }
        }
    });

    toggleButton.setAttribute('aria-controls', 'cami-panel');
    toggleButton.setAttribute('aria-expanded', 'false');
    toggleButton.addEventListener('click', () => setPanelOpen(true));
    historyButton.addEventListener('click', () => {
        setHistoryOpen(!historyOpen);
        if (!historyOpen) messages.scrollTop = messages.scrollHeight;
    });
    historyBackButton.addEventListener('click', () => {
        historyDetail.classList.add('cami-hidden');
        historyOverview.classList.remove('cami-hidden');
    });
    closeButton.addEventListener('click', () => {
        setPanelOpen(false);
        setHistoryOpen(false);
        sendingConversationId = null;
        setSending(false);
        archiveCurrentConversation();
        if (welcomeMessage) {
            messages.replaceChildren(welcomeMessage);
            welcomeMessage.querySelectorAll('.cami-response-btn').forEach((button) => {
                button.disabled = false;
            });
        }
        input.value = '';
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage(input.value);
    });

    document.querySelectorAll('[data-cami-payload]').forEach((button) => {
        button.addEventListener('click', () => {
            sendMessage(button.dataset.camiPayload, button.textContent);
        });
    });

    if (readItem(panelStorage, openKey) === '1') setPanelOpen(true);
});