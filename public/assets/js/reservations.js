document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.querySelector('#reservas-tbody');
    const feedback = document.querySelector('#reservas-feedback');

    const filters = {
        huesped: document.querySelector('#filter-huesped'),
        estado: document.querySelector('#filter-estado'),
        desde: document.querySelector('#filter-desde'),
        hasta: document.querySelector('#filter-hasta'),
    };

    const dateFormatter = new Intl.DateTimeFormat('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'UTC',
    });

    const moneyFormatter = new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
    });

    let currentRequest = 0;
    let searchTimer;

    function formatDate(value) {
        if (!value) return '—';

        const date = new Date(
            `${String(value).slice(0, 10)}T00:00:00Z`
        );

        return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date);
    }

    function formatMoney(value) {
        const amount = Number(value);

        return value === null ||
            value === undefined ||
            value === '' ||
            !Number.isFinite(amount)
            ? '—'
            : moneyFormatter.format(amount);
    }

    function cell(row, value, className = '') {
        const td = document.createElement('td');
        td.textContent = value ?? '—';

        if (className) td.className = className;

        row.appendChild(td);
        return td;
    }

    function updateStats(reservas) {
        const count = estado => reservas.filter(r => r.estado === estado).length;

        document.querySelector('#stat-total').textContent = reservas.length;

        document.querySelector('#stat-confirmadas').textContent = count('confirmada');

        document.querySelector('#stat-pendientes').textContent = count('pendiente');

        document.querySelector('#stat-canceladas').textContent = count('cancelada');
    }

    function renderTable(reservas) {
        tbody.replaceChildren();

        document.querySelector('#reservas-count').textContent = `(${reservas.length})`;

        document.querySelector('#reservas-footer').textContent = `Mostrando ${reservas.length} reservas`;

        if (reservas.length === 0) {
            const row = document.createElement('tr');
            const td = cell(
                row,
                'No se encontraron reservas.',
                'empty-state'
            );

            td.colSpan = 8;
            tbody.appendChild(row);
            return;
        }

        for (const reserva of reservas) {
            const row = document.createElement('tr');

            cell(row, reserva.id);
            cell(row, reserva.nombre_huesped, 'guest-name');
            cell(row, reserva.nombre_alojamiento);
            cell(row, formatDate(reserva.fecha_entrada));
            cell(row, formatDate(reserva.fecha_salida));
            cell(row, formatMoney(reserva.importe));

            const statusCell = document.createElement('td');
            const badge = document.createElement('span');

            const estados = [
                'pendiente',
                'confirmada',
                'cancelada',
            ];

            const estado = String(reserva.estado ?? '');

            badge.className = 'status status-' + (estados.includes(estado) ? estado : 'unknown');

            badge.textContent = estado || 'Desconocido';
            statusCell.appendChild(badge);
            row.appendChild(statusCell);

            const actionCell = document.createElement('td');
            const detail = document.createElement('a');

            detail.className = 'table-action';
            detail.href = `/reservas/${encodeURIComponent(reserva.id)}`;
            detail.textContent = 'Ver detalle';

            actionCell.appendChild(detail);
            row.appendChild(actionCell);

            tbody.appendChild(row);
        }
    }

    async function loadReservations() {
        const requestId = ++currentRequest;
        const params = new URLSearchParams();

        if (filters.huesped.value.trim()) {
            params.set('huesped', filters.huesped.value.trim());
        }

        if (filters.estado.value) {
            params.set('estado', filters.estado.value);
        }

        if (filters.desde.value) {
            params.set('fecha_desde', filters.desde.value);
        }

        if (filters.hasta.value) {
            params.set('fecha_hasta', filters.hasta.value);
        }

        feedback.hidden = true;

        try {
            const url = '/api/reservations' + (params.size ? `?${params.toString()}` : '');

            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.error || 'Error al cargar reservas');
            }

            const reservas = Array.isArray(payload) ? payload : payload.data;

            if (!Array.isArray(reservas)) {
                throw new Error('Formato de respuesta inesperado');
            }

            if (requestId !== currentRequest) return;

            renderTable(reservas);
            updateStats(reservas);
        } catch (error) {
            if (requestId !== currentRequest) return;

            feedback.textContent = error.message;
            feedback.hidden = false;

            renderTable([]);
            updateStats([]);
        }
    }

    filters.huesped.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadReservations, 300);
    });

    for (const key of ['estado', 'desde', 'hasta']) {
        filters[key].addEventListener('change', loadReservations);
    }

    document.querySelector('#btn-limpiar').addEventListener('click', () => {
        clearTimeout(searchTimer);

        Object.values(filters).forEach(field => {
            field.value = '';
        });

        loadReservations();
    });

    document.querySelector('#btn-actualizar').addEventListener('click', loadReservations);

    const drawer = document.querySelector('#reserva-drawer');
    const backdrop = document.querySelector('#drawer-backdrop');
    const form = document.querySelector('#form-reserva');
    const formError = document.querySelector('#form-error');
    const submitButton = document.querySelector('#btn-guardar-reserva');
    const openButton = document.querySelector('#btn-nueva-reserva');

    let lastFocusedElement = null;

    let isSubmitting = false;

    function openDrawer() {
        lastFocusedElement = document.activeElement;
        backdrop.hidden = false;
        drawer.classList.add('open');
        drawer.inert = false;
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        form.elements.nombre_huesped.focus();
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        drawer.inert = true;
        drawer.setAttribute('aria-hidden', 'true');
        backdrop.hidden = true;
        document.body.style.overflow = '';
        lastFocusedElement?.focus();
    }

    openButton.addEventListener('click', openDrawer);

    document.querySelector('#drawer-close').addEventListener('click', closeDrawer);

    document.querySelector('#drawer-cancel').addEventListener('click', closeDrawer);

    backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && drawer.classList.contains('open')) {
            closeDrawer();
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        formError.hidden = true;

        const data = Object.fromEntries(new FormData(form));

        if (data.fecha_salida <= data.fecha_entrada) {
            formError.textContent = 'La fecha de salida debe ser posterior a la entrada.';
            formError.hidden = false;
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.textContent = 'Guardando...';

        try {
            const response = await fetch('/api/reservations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });

            const payload = await response.json();

            if (!response.ok) {
                const details = payload.details ? Object.values(payload.details).flat().join('\n') : '';

                throw new Error(details || payload.error || 'No se pudo crear la reserva');
            }

            form.reset();
            closeDrawer();

            await loadReservations();
        } catch (error) {
            formError.textContent = error.message;
            formError.hidden = false;
        } finally {
            isSubmitting = false;
            submitButton.disabled = false;
            submitButton.textContent = 'Crear reserva';
        }
    });

    lucide.createIcons();
});
