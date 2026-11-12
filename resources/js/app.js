import './bootstrap';
import * as bootstrap from 'bootstrap';
import Sortable from 'sortablejs';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const initSortables = (root = document) => {
    root.querySelectorAll('[data-sortable]:not([data-sortable-ready])').forEach(element => {
        new Sortable(element, { animation: 150 });
        element.dataset.sortableReady = '1';
    });
};

const initMenuSearch = (root = document) => {
    root.querySelectorAll('#menu-search:not([data-search-ready])').forEach(input => {
        input.dataset.searchReady = '1';
        input.addEventListener('input', event => {
            const query = event.target.value.trim().toLowerCase();
            document.querySelectorAll('.menu-searchable').forEach(item => {
                item.hidden = !item.dataset.search.toLowerCase().includes(query);
            });
        });
    });
};

const initPasswordToggles = (root = document) => {
    root.querySelectorAll('.password-toggle:not([data-password-ready])').forEach(button => {
        button.dataset.passwordReady = '1';
        button.addEventListener('click', () => {
            const input = button.parentElement.querySelector('input');
            input.type = input.type === 'password' ? 'text' : 'password';
            button.querySelector('i').classList.toggle('bi-eye');
            button.querySelector('i').classList.toggle('bi-eye-slash');
        });
    });
};

const initPanelSidebar = (root = document) => {
    root.querySelectorAll('#panelSidebar:not([data-sidebar-ready])').forEach(sidebar => {
        sidebar.dataset.sidebarReady = '1';
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth >= 992) {
                    return;
                }

                bootstrap.Offcanvas.getInstance(sidebar)?.hide();
            });
        });
    });
};

const playOrderSound = () => {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const context = new AudioContext();
        const gain = context.createGain();
        gain.gain.value = 0.08;
        gain.connect(context.destination);

        [660, 880, 660].forEach((frequency, index) => {
            const oscillator = context.createOscillator();
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            oscillator.connect(gain);
            oscillator.start(context.currentTime + index * 0.16);
            oscillator.stop(context.currentTime + index * 0.16 + 0.12);
        });
    } catch (error) {
        // Browsers may block sound until the user interacts with the page.
    }
};

const initOrderLiveNotifications = () => {
    const restaurantId = document.body.dataset.restaurantChannel;

    if (!restaurantId || document.body.dataset.orderLiveReady === '1' || !window.Echo) {
        return;
    }

    document.body.dataset.orderLiveReady = '1';

    window.Echo.private(`restaurant.${restaurantId}`)
        .listen('.menu-order.created', order => {
            const toast = document.querySelector('[data-order-live-toast]');
            const message = document.querySelector('[data-order-live-message]');

            if (message) {
                const table = order.table_number ? `طاولة ${order.table_number} - ` : '';

                message.textContent = `طلب #${order.id} - ${table}${order.total} ${order.currency}`;
            }

            if (toast) {
                toast.hidden = false;
                toast.classList.remove('is-ringing');
                void toast.offsetWidth;
                toast.classList.add('is-ringing');
                setTimeout(() => {
                    toast.hidden = true;
                }, 9000);
            }

            playOrderSound();

            if (window.location.pathname === '/dashboard/orders') {
                setTimeout(() => window.location.reload(), 700);
            }
        });
};

const initPanelAjax = () => {
    document.addEventListener('submit', async event => {
        const form = event.target;
        const panel = form.closest('[data-panel-content]');

        if (!panel || form.matches('[data-no-ajax]')) {
            return;
        }

        if (form.dataset.ajaxSubmitting === '1') {
            return;
        }

        event.preventDefault();

        const submitter = event.submitter;
        const originalHtml = submitter?.innerHTML;
        form.dataset.ajaxSubmitting = '1';
        form.classList.add('is-ajax-submitting');

        if (submitter) {
            submitter.disabled = true;
            submitter.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        }

        try {
            const response = await fetch(form.action, {
                method: form.method.toUpperCase(),
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html, application/xhtml+xml',
                },
                credentials: 'same-origin',
            });

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextPanel = nextDocument.querySelector('[data-panel-content]');

            if (!nextPanel) {
                window.location.reload();
                return;
            }

            bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            panel.replaceWith(nextPanel);
            initDashboardWidgets(nextPanel);
        } catch (error) {
            form.submit();
        } finally {
            form.dataset.ajaxSubmitting = '0';
            form.classList.remove('is-ajax-submitting');

            if (submitter) {
                submitter.disabled = false;
                submitter.innerHTML = originalHtml;
            }
        }
    });
};

const replacePublicMenuFrom = nextDocument => {
    const currentShell = document.querySelector('[data-public-menu-shell]');
    const nextShell = nextDocument.querySelector('[data-public-menu-shell]');

    if (!currentShell || !nextShell) {
        window.location.reload();
        return;
    }

    document.title = nextDocument.title;
    currentShell.replaceWith(nextShell);
    initDashboardWidgets(nextShell);
};

const initPublicMenuAjax = () => {
    document.addEventListener('click', async event => {
        const link = event.target.closest('[data-public-menu-link]');

        if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();

        const shell = document.querySelector('[data-public-menu-shell]');
        shell?.classList.add('is-public-menu-loading');

        try {
            const response = await fetch(link.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html, application/xhtml+xml',
                },
                credentials: 'same-origin',
            });
            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');

            replacePublicMenuFrom(nextDocument);
            history.pushState({}, nextDocument.title, link.href);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (error) {
            window.location.href = link.href;
        } finally {
            document.querySelector('[data-public-menu-shell]')?.classList.remove('is-public-menu-loading');
        }
    });

    window.addEventListener('popstate', async () => {
        const response = await fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const html = await response.text();
        replacePublicMenuFrom(new DOMParser().parseFromString(html, 'text/html'));
    });
};

const initPublicMapTabs = (root = document) => {
    root.querySelectorAll('[data-public-map-toggle]:not([data-map-toggle-ready])').forEach(button => {
        button.dataset.mapToggleReady = '1';
        button.addEventListener('click', () => {
            const panel = document.querySelector('#menu-map-panel');
            const shouldShow = panel?.classList.contains('d-none');

            panel?.classList.toggle('d-none', !shouldShow);
            button.classList.toggle('active', shouldShow);
            button.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');

            if (shouldShow) {
                panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
};

const initPublicOrdering = (root = document) => {
    const shell = root.querySelector?.('[data-public-menu-shell]:not([data-ordering-ready])')
        ?? (root.matches?.('[data-public-menu-shell]:not([data-ordering-ready])') ? root : null);

    if (!shell) {
        return;
    }

    shell.dataset.orderingReady = '1';

    const cart = new Map();
    const bar = shell.querySelector('[data-order-bar]');
    const itemsBox = shell.querySelector('[data-order-items]');
    const inputsBox = shell.querySelector('[data-order-inputs]');
    const submit = shell.querySelector('[data-order-submit]');
    const form = shell.querySelector('[data-public-order-form]');
    const statusBox = shell.querySelector('[data-order-status]');
    const detailsStep = shell.querySelector('[data-order-details-step]');
    const verificationStep = shell.querySelector('[data-order-verification-step]');
    const tokenInput = shell.querySelector('[data-verification-token]');
    const currency = shell.querySelector('.public-order-bar span')?.textContent?.split(' ').pop() ?? '';
    let verificationRequested = false;

    const money = value => Number(value || 0).toFixed(2);
    const setStatus = (message, type = 'info') => {
        if (!statusBox) {
            return;
        }

        statusBox.className = `alert alert-${type}`;
        statusBox.textContent = message;
    };

    const setSubmitting = isSubmitting => {
        if (!submit) {
            return;
        }

        submit.disabled = isSubmitting || cart.size === 0;
        submit.innerHTML = isSubmitting
            ? '<span class="spinner-border spinner-border-sm"></span>'
            : (verificationRequested ? 'تأكيد الطلب' : 'إرسال كود التأكيد');
    };
    const resetVerification = () => {
        verificationRequested = false;
        if (tokenInput) {
            tokenInput.value = '';
        }
        detailsStep?.classList.remove('d-none');
        verificationStep?.classList.add('d-none');
        statusBox?.classList.add('d-none');
    };

    const render = () => {
        const items = [...cart.values()];
        const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
        const total = items.reduce((sum, item) => sum + item.quantity * item.price, 0);

        shell.querySelectorAll('[data-order-count]').forEach(element => {
            element.textContent = totalQuantity;
        });
        shell.querySelectorAll('[data-order-total]').forEach(element => {
            element.textContent = money(total);
        });

        if (bar) {
            bar.hidden = items.length === 0;
        }

        if (submit) {
            submit.disabled = items.length === 0;
            submit.textContent = verificationRequested ? 'تأكيد الطلب' : 'إرسال كود التأكيد';
        }

        if (itemsBox) {
            itemsBox.innerHTML = items.length
                ? items.map(item => `
                    <div class="public-order-line">
                        <div>
                            <strong>${item.name}</strong>
                            <small>${money(item.price)} ${currency}</small>
                        </div>
                        <div class="public-order-qty">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-order-dec="${item.id}">-</button>
                            <span>${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-order-inc="${item.id}">+</button>
                            <button class="btn btn-sm btn-outline-danger" type="button" data-order-remove="${item.id}"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                `).join('')
                : '<p class="text-muted mb-0">لم يتم اختيار أصناف بعد.</p>';
        }

        if (inputsBox) {
            inputsBox.innerHTML = items.map((item, index) => `
                <input type="hidden" name="items[${index}][id]" value="${item.id}">
                <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
            `).join('');
        }
    };

    shell.addEventListener('click', event => {
        const addButton = event.target.closest('[data-order-add]');
        const increment = event.target.closest('[data-order-inc]');
        const decrement = event.target.closest('[data-order-dec]');
        const remove = event.target.closest('[data-order-remove]');

        if (addButton) {
            const id = addButton.dataset.id;
            const current = cart.get(id) ?? {
                id,
                name: addButton.dataset.name,
                price: Number(addButton.dataset.price),
                quantity: 0,
            };

            current.quantity += 1;
            cart.set(id, current);
            resetVerification();
            render();
            return;
        }

        if (increment) {
            const item = cart.get(increment.dataset.orderInc);
            if (item) {
                item.quantity += 1;
                resetVerification();
                render();
            }
            return;
        }

        if (decrement) {
            const item = cart.get(decrement.dataset.orderDec);
            if (item) {
                item.quantity -= 1;
                if (item.quantity <= 0) {
                    cart.delete(item.id);
                }
                resetVerification();
                render();
            }
            return;
        }

        if (remove) {
            cart.delete(remove.dataset.orderRemove);
            resetVerification();
            render();
        }
    });

    form?.addEventListener('submit', async event => {
        if (cart.size === 0) {
            event.preventDefault();
            render();
            return;
        }

        event.preventDefault();
        setSubmitting(true);

        try {
            const response = await fetch(verificationRequested ? form.dataset.confirmAction : form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const errors = data.errors ? Object.values(data.errors).flat() : [];
                throw new Error(errors[0] ?? data.message ?? 'حدث خطأ، حاول مرة أخرى.');
            }

            if (!verificationRequested) {
                verificationRequested = true;
                tokenInput.value = data.token;
                detailsStep?.classList.add('d-none');
                verificationStep?.classList.remove('d-none');
                verificationStep?.querySelector('input')?.focus();
                setStatus(data.message ?? 'تم إرسال كود التأكيد إلى الإيميل.', 'success');
                render();
                return;
            }

            cart.clear();
            render();
            setStatus(data.message ?? 'تم تأكيد الطلب بنجاح.', 'success');
            setTimeout(() => window.location.reload(), 1200);
        } catch (error) {
            setStatus(error.message, 'danger');
        } finally {
            setSubmitting(false);
        }
    });

    render();
};

const initGooglePlaceId = (root = document) => {
    const source = root.querySelector('[data-google-place-source]');
    const target = root.querySelector('[data-google-place-id]');

    if (!source || !target) {
        return;
    }

    // The link card and the review card both report the same state.
    const statuses = [...root.querySelectorAll('[data-google-place-status]')];
    const paint = message => statuses.forEach(status => {
        status.innerHTML = message;
    });
    const patterns = [
        /(?:placeid|place_id|ftid)=([^&\s]+)/i,
        /!1s([\w:-]+)/,
        /\/maps\/place\/([\w:-]+)/,
        /\/maps\/place\/([^/@?]+)/,
    ];
    const shortLink = /^(https?:\/\/)?(maps\.app\.goo\.gl|goo\.gl)\//i;
    const ready = '<span class="text-success"><i class="bi bi-check-circle"></i> جاهز — الكود بيفتح صفحة التقييم مباشرة.</span>';
    const missing = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> محتاجين رابط المطعم على Google Maps الأول، من غيره مش هينفع نفتح صفحة التقييم (النجوم والتعليق).</span>';
    const unresolvable = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> ده لينك مختصر مفيهوش بيانات المكان. افتح المطعم في المتصفح على الكمبيوتر، دوس <b>مشاركة ← نسخ الرابط</b>، والصق الرابط الطويل هنا.</span>';

    const extract = value => {
        for (const pattern of patterns) {
            const match = value.match(pattern);

            if (match) {
                return decodeURIComponent(match[1]);
            }
        }

        return '';
    };

    const sync = () => {
        const value = source.value.trim();

        if (!value) {
            target.value = '';
            paint(missing);

            return;
        }

        const placeId = extract(value);

        if (placeId) {
            target.value = placeId;
            paint(ready);

            return;
        }

        // Keep any Place ID already saved; it must not be wiped by a link we cannot parse.
        paint(shortLink.test(value) ? unresolvable : missing);
    };

    source.dataset.placeIdReady = '1';
    source.addEventListener('input', sync);
    source.addEventListener('change', sync);

    if (!target.value.trim()) {
        sync();
    }
};

const initCashier = (root = document) => {
    root.querySelectorAll('[data-pos]:not([data-pos-ready])').forEach(pos => {
        pos.dataset.posReady = '1';

        const form = pos.querySelector('[data-pos-form]');
        const linesBox = pos.querySelector('[data-pos-lines]');
        const emptyNote = pos.querySelector('[data-pos-empty]');
        const submit = pos.querySelector('[data-pos-submit]');
        const discountInput = pos.querySelector('[data-pos-discount]');
        const paidInput = pos.querySelector('[data-pos-paid]');
        const taxRate = parseFloat(pos.dataset.tax) || 0;
        const serviceRate = parseFloat(pos.dataset.service) || 0;
        const round = value => Math.round((value + Number.EPSILON) * 100) / 100;
        const money = value => round(value).toFixed(2);
        const out = (name, value) => {
            const element = pos.querySelector(`[data-pos-${name}]`);

            if (element) {
                element.textContent = money(value);
            }
        };

        // id => { name, price, quantity }, in the order items were added.
        const cart = new Map();

        const element = (tag, className, text) => {
            const node = document.createElement(tag);

            if (className) {
                node.className = className;
            }

            if (text !== undefined) {
                node.textContent = text;
            }

            return node;
        };

        const hidden = (name, value) => {
            const input = element('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;

            return input;
        };

        const totals = () => {
            let subtotal = 0;
            cart.forEach(line => {
                subtotal += line.price * line.quantity;
            });
            subtotal = round(subtotal);

            const discount = Math.min(subtotal, Math.max(0, parseFloat(discountInput?.value) || 0));
            const net = subtotal - discount;
            const service = round(net * serviceRate / 100);
            const tax = round((net + service) * taxRate / 100);

            return { subtotal, discount, service, tax, total: round(net + service + tax) };
        };

        const renderTotals = () => {
            const sums = totals();
            out('subtotal', sums.subtotal);
            out('discount-out', sums.discount);
            out('service', sums.service);
            out('tax', sums.tax);
            out('total', sums.total);

            const discountRow = pos.querySelector('[data-pos-discount-row]');

            if (discountRow) {
                discountRow.hidden = sums.discount <= 0;
            }

            const paid = parseFloat(paidInput?.value) || 0;
            out('change', Math.max(0, paid - sums.total));
            submit.disabled = cart.size === 0;
        };

        const render = () => {
            linesBox.querySelectorAll('.pos-line').forEach(line => line.remove());
            emptyNote.hidden = cart.size > 0;

            let index = 0;
            cart.forEach((line, id) => {
                const row = element('div', 'pos-line');
                const name = element('div', 'pos-line-name', line.name);
                name.append(element('small', '', money(line.price)));

                const quantity = element('div', 'pos-qty');
                const minus = element('button', '', '−');
                const plus = element('button', '', '+');
                minus.type = 'button';
                plus.type = 'button';
                minus.setAttribute('aria-label', `إنقاص ${line.name}`);
                plus.setAttribute('aria-label', `زيادة ${line.name}`);
                minus.addEventListener('click', () => change(id, -1));
                plus.addEventListener('click', () => change(id, 1));
                quantity.append(plus, element('b', '', String(line.quantity)), minus);

                row.append(
                    name,
                    quantity,
                    element('div', 'pos-line-total', money(line.price * line.quantity)),
                    hidden(`items[${index}][id]`, id),
                    hidden(`items[${index}][quantity]`, line.quantity),
                );
                linesBox.append(row);
                index += 1;
            });

            renderTotals();
        };

        const change = (id, delta) => {
            const line = cart.get(id);

            if (!line) {
                return;
            }

            line.quantity = Math.min(999, line.quantity + delta);

            if (line.quantity <= 0) {
                cart.delete(id);
            }

            render();
        };

        pos.querySelectorAll('[data-pos-item]').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;

                if (cart.has(id)) {
                    change(id, 1);

                    return;
                }

                cart.set(id, { name: button.dataset.name, price: parseFloat(button.dataset.price) || 0, quantity: 1 });
                render();
            });
        });

        // Search and category chips narrow the same grid together.
        let activeCategory = '';
        const search = pos.querySelector('[data-pos-search]');
        const filter = () => {
            const query = (search?.value || '').trim().toLowerCase();
            let visible = 0;

            pos.querySelectorAll('[data-pos-item]').forEach(button => {
                const match = (!activeCategory || button.dataset.cat === activeCategory)
                    && (!query || button.dataset.name.toLowerCase().includes(query));
                button.hidden = !match;
                visible += match ? 1 : 0;
            });

            pos.querySelector('[data-pos-no-results]').hidden = visible > 0;
        };

        search?.addEventListener('input', filter);
        pos.querySelectorAll('[data-pos-cat]').forEach(chip => {
            chip.addEventListener('click', () => {
                activeCategory = chip.dataset.posCat;
                pos.querySelectorAll('[data-pos-cat]').forEach(other => other.classList.toggle('active', other === chip));
                filter();
            });
        });

        const syncOrderType = () => {
            const table = pos.querySelector('[data-pos-table]');

            if (table) {
                table.hidden = form.querySelector('[data-pos-type]:checked')?.value !== 'dine_in';
            }
        };

        const syncMethod = () => {
            const cash = pos.querySelector('[data-pos-cash]');

            if (cash) {
                cash.hidden = form.querySelector('[data-pos-method]:checked')?.value !== 'cash';
            }
        };

        form.querySelectorAll('[data-pos-type]').forEach(input => input.addEventListener('change', syncOrderType));
        form.querySelectorAll('[data-pos-method]').forEach(input => input.addEventListener('change', syncMethod));
        discountInput?.addEventListener('input', renderTotals);
        paidInput?.addEventListener('input', renderTotals);
        pos.querySelector('[data-pos-clear]')?.addEventListener('click', () => {
            cart.clear();
            render();
        });
        form.addEventListener('submit', event => {
            if (cart.size === 0) {
                event.preventDefault();

                return;
            }

            // One click, one invoice.
            submit.disabled = true;
        });

        syncOrderType();
        syncMethod();
        render();
    });
};

const initColorSwatches = (root = document) => {
    root.querySelectorAll('[data-color-swatches]:not([data-swatches-ready])').forEach(box => {
        box.dataset.swatchesReady = '1';
        const custom = box.querySelector('input[type="color"]');

        // The colour input is what gets saved; a preset only fills it in.
        box.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                custom.value = radio.value;
            });
        });
        custom.addEventListener('input', () => {
            box.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.checked = radio.value === custom.value;
            });
        });
    });
};

const initDashboardWidgets = (root = document) => {
    initColorSwatches(root);
    initCashier(root);
    initPanelSidebar(root);
    initSortables(root);
    initMenuSearch(root);
    initPasswordToggles(root);
    initGooglePlaceId(root);
    initPublicMapTabs(root);
    initPublicOrdering(root);
};

initDashboardWidgets();
initOrderLiveNotifications();
initPanelAjax();
initPublicMenuAjax();
Alpine.start();
