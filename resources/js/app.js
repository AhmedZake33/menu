import './bootstrap';
import * as bootstrap from 'bootstrap';
import L from 'leaflet';
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

const initLocationPicker = (root = document) => {
    const locationPicker = root.querySelector('#restaurant-location-picker:not([data-map-ready])');

    if (!locationPicker) {
        return;
    }

    locationPicker.dataset.mapReady = '1';

    const latitudeInput = root.querySelector('#map_latitude');
    const longitudeInput = root.querySelector('#map_longitude');
    const clearButton = root.querySelector('#clear-location-picker');
    const fallbackPosition = [30.0444, 31.2357];
    const storedLat = parseFloat(locationPicker.dataset.lat);
    const storedLng = parseFloat(locationPicker.dataset.lng);
    const hasStoredPosition = !Number.isNaN(storedLat) && !Number.isNaN(storedLng);
    const initialPosition = hasStoredPosition ? [storedLat, storedLng] : fallbackPosition;
    const map = L.map(locationPicker).setView(initialPosition, hasStoredPosition ? 16 : 12);
    let marker = null;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const updateFields = latlng => {
        latitudeInput.value = latlng.lat.toFixed(7);
        longitudeInput.value = latlng.lng.toFixed(7);
    };

    const setMarker = latlng => {
        if (!marker) {
            marker = L.marker(latlng, { draggable: true }).addTo(map);
            marker.on('dragend', event => updateFields(event.target.getLatLng()));
        } else {
            marker.setLatLng(latlng);
        }

        updateFields(latlng);
    };

    if (hasStoredPosition) {
        setMarker(L.latLng(storedLat, storedLng));
    }

    map.on('click', event => setMarker(event.latlng));

    clearButton?.addEventListener('click', () => {
        latitudeInput.value = '';
        longitudeInput.value = '';

        if (marker) {
            marker.remove();
            marker = null;
        }
    });

    setTimeout(() => map.invalidateSize(), 250);
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

const initDashboardWidgets = (root = document) => {
    initPanelSidebar(root);
    initSortables(root);
    initMenuSearch(root);
    initPasswordToggles(root);
    initLocationPicker(root);
    initPublicMapTabs(root);
    initPublicOrdering(root);
};

initDashboardWidgets();
initPanelAjax();
initPublicMenuAjax();
Alpine.start();
