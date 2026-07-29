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

const initDashboardWidgets = (root = document) => {
    initPanelSidebar(root);
    initSortables(root);
    initMenuSearch(root);
    initPasswordToggles(root);
    initLocationPicker(root);
    initPublicMapTabs(root);
};

initDashboardWidgets();
initPanelAjax();
initPublicMenuAjax();
Alpine.start();
