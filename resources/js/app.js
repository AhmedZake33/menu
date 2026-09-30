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

const embedUrl = query => `https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed`;

const coordinatePatterns = [
    /!3d(-?[\d.]+)!4d(-?[\d.]+)/,
    /@(-?[\d.]+),(-?[\d.]+)/,
    /[?&](?:q|query|ll)=(-?[\d.]+),(-?[\d.]+)/,
    /[?&]center=(-?[\d.]+),(-?[\d.]+)/,
];

const extractCoordinates = value => {
    // A pair is routinely written as %2C, and both a pasted link and a searched
    // location keep it that way, so it is matched on the decoded form.
    const normalised = value.replace(/%2C/gi, ',');

    for (const pattern of coordinatePatterns) {
        const match = normalised.match(pattern);

        if (!match) {
            continue;
        }

        const lat = parseFloat(match[1]);
        const lng = parseFloat(match[2]);

        if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            return { lat, lng };
        }
    }

    return null;
};

const initMapPreview = (root = document) => {
    const container = root.querySelector('[data-map-preview-container]') || root.querySelector('.restaurant-location-picker');

    if (!container) {
        return;
    }

    const linkInput = root.querySelector('[data-map-link]');
    const latitudeInput = root.querySelector('#map_latitude');
    const longitudeInput = root.querySelector('#map_longitude');
    const clearButton = root.querySelector('#clear-location-picker');
    const initial = latitudeInput && longitudeInput && latitudeInput.value && longitudeInput.value
        ? `${parseFloat(latitudeInput.value)},${parseFloat(longitudeInput.value)}`
        : null;

    const render = query => {
        container.innerHTML = query
            ? `<iframe data-map-preview title="موقع المطعم على Google Maps" src="${embedUrl(query)}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>`
            : '<div class="restaurant-location-picker-fallback"><i class="bi bi-geo-alt"></i><span>الصق رابط المطعم من Google Maps، وهيتعرض الموقع هنا على طول.</span></div>';
    };

    const syncFromLink = () => {
        const value = linkInput.value.trim();

        if (!value) {
            return;
        }

        const coordinates = extractCoordinates(value);

        if (coordinates) {
            latitudeInput.value = coordinates.lat.toFixed(7);
            longitudeInput.value = coordinates.lng.toFixed(7);
        }

        render(coordinates ? `${coordinates.lat},${coordinates.lng}` : value);
    };

    const syncFromCoordinates = () => {
        const lat = parseFloat(latitudeInput.value);
        const lng = parseFloat(longitudeInput.value);

        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            return;
        }

        render(`${lat},${lng}`);
    };

    if (linkInput) {
        linkInput.addEventListener('input', syncFromLink);
        linkInput.addEventListener('change', syncFromLink);
    }

    latitudeInput?.addEventListener('change', syncFromCoordinates);
    longitudeInput?.addEventListener('change', syncFromCoordinates);

    clearButton?.addEventListener('click', () => {
        if (latitudeInput) latitudeInput.value = '';
        if (longitudeInput) longitudeInput.value = '';
        render(null);
    });

    if (initial) {
        render(initial);
    }
};

const initPlaceSearch = (root = document) => {
    const wrapper = root.querySelector('[data-place-search]');

    if (!wrapper || wrapper.dataset.placeSearchReady === '1') {
        return;
    }

    wrapper.dataset.placeSearchReady = '1';

    const input = wrapper.querySelector('[data-place-search-input]');
    const results = wrapper.querySelector('[data-place-search-results]');
    const submitButton = wrapper.querySelector('[data-place-search-submit]');
    const status = wrapper.querySelector('[data-place-search-status]');
    const latitudeInput = root.querySelector('#map_latitude');
    const longitudeInput = root.querySelector('#map_longitude');
    const linkInput = root.querySelector('[data-map-link]');
    const addressInput = root.querySelector('[name="address"]');

    const idle = 'اكتب اسم المطعم واختار المكان الصح من القائمة، أو الصق رابط Google Maps تحت.';
    const noResults = 'مفيش نتايج للبحث ده. جرّب اسم أطول أو اسم الشارع.';
    const noPlaceId = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> المكان اتحدد والخريطة جاهزة، بس Place ID (كود التقييم) مش متأكد منه. الصق رابط المطعم من Google Maps لو عايزه.</span>';

    let timer = null;
    let request = 0;

    const say = message => {
        status.innerHTML = message;
    };

    const close = () => {
        results.innerHTML = '';
        results.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    };

    // Reuses the handlers already bound to the link and coordinate inputs, so a picked
    // place and a pasted link both end up in exactly the same state.
    const applyLink = value => {
        linkInput.value = value;
        linkInput.dispatchEvent(new Event('input', { bubbles: true }));
        linkInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const applyCoordinates = (lat, lng) => {
        latitudeInput.value = lat.toFixed(7);
        longitudeInput.value = lng.toFixed(7);
        latitudeInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const pick = async place => {
        const coordinates = `${place.lat.toFixed(7)},${place.lng.toFixed(7)}`;

        close();
        applyCoordinates(place.lat, place.lng);
        applyLink(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(coordinates)}`);

        if (addressInput && !addressInput.value.trim()) {
            addressInput.value = place.address;
        }

        say('<span class="text-muted"><span class="spinner-border spinner-border-sm"></span> جاري التأكد من بيانات المكان على Google…</span>');

        try {
            const response = await fetch(wrapper.dataset.placeIdUrl, {
                method: 'POST',
                body: JSON.stringify({ name: place.name, address: place.address }),
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const { place_id: placeId } = await response.json();

            if (placeId) {
                applyLink(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(place.label)}&query_place_id=${placeId}`);
                say('<span class="text-success"><i class="bi bi-check-circle"></i> المكان اتحدد وكود التقييم جاهز. دوس حفظ في الأسفل.</span>');

                return;
            }

            say(noPlaceId);
        } catch (error) {
            say(noPlaceId);
        }
    };

    const render = places => {
        results.innerHTML = '';

        if (places.length === 0) {
            close();
            say(noResults);

            return;
        }

        places.forEach(place => {
            const option = document.createElement('button');
            const name = document.createElement('span');
            const address = document.createElement('span');

            option.type = 'button';
            option.className = 'place-search-result';
            option.setAttribute('role', 'option');
            name.className = 'place-search-result-name';
            name.textContent = place.name;
            address.className = 'place-search-result-address';
            address.textContent = place.address;
            option.append(name, address);
            option.addEventListener('click', () => pick(place));
            results.append(option);
        });

        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const run = async () => {
        const query = input.value.trim();

        if (query.length < 3) {
            close();

            return;
        }

        // A link pasted here is the other half of this box: hand it to the map input
        // instead of searching for a place called "https".
        if (/^https?:\/\//i.test(query)) {
            input.value = '';
            say(idle);
            applyLink(query);

            return;
        }

        const current = ++request;
        results.hidden = false;
        say('<span class="text-muted"><span class="spinner-border spinner-border-sm"></span> جاري البحث…</span>');

        try {
            const response = await fetch(`${wrapper.dataset.placeSearchUrl}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('search failed');
            }

            const { places } = await response.json();

            if (current !== request) {
                return;
            }

            render(places ?? []);
        } catch (error) {
            if (current === request) {
                close();
                say('<span class="text-danger">مقدرناش نعمل البحث دلوقتي. جرّب تاني أو الصق رابط Google Maps.</span>');
            }
        }
    };

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(run, 350);
    });

    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            close();

            return;
        }

        // The whole settings form wraps this box, so Enter has to search rather than
        // post every restaurant field.
        if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(timer);
            run();
        }
    });

    submitButton?.addEventListener('click', () => {
        window.clearTimeout(timer);
        run();
    });

    document.addEventListener('click', event => {
        if (!wrapper.contains(event.target)) {
            close();
        }
    });

    say(idle);
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
                message.textContent = `طلب #${order.id} - طاولة ${order.table_number} - ${order.total} ${order.currency}`;
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

    const status = root.querySelector('[data-google-place-status]');
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
            status.innerHTML = missing;

            return;
        }

        const placeId = extract(value);

        if (placeId) {
            target.value = placeId;
            status.innerHTML = ready;

            return;
        }

        // Keep any Place ID already saved; it must not be wiped by a link we cannot parse.
        status.innerHTML = shortLink.test(value) ? unresolvable : missing;
    };

    source.dataset.placeIdReady = '1';
    source.addEventListener('input', sync);
    source.addEventListener('change', sync);

    if (!target.value.trim()) {
        sync();
    }
};

const initDashboardWidgets = (root = document) => {
    initPanelSidebar(root);
    initSortables(root);
    initMenuSearch(root);
    initPasswordToggles(root);
    initMapPreview(root);
    initPlaceSearch(root);
    initGooglePlaceId(root);
    initPublicMapTabs(root);
    initPublicOrdering(root);
};

initDashboardWidgets();
initOrderLiveNotifications();
initPanelAjax();
initPublicMenuAjax();
Alpine.start();
