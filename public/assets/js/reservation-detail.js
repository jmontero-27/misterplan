document.addEventListener('DOMContentLoaded', () => {
    const actions = document.querySelector('#detail-actions');
    
    if (!actions) return;

    const id = actions.dataset.reservaId;

    const status = document.querySelector('#detail-status');
    const timeline = document.querySelector('#reservation-timeline');
    const feedback = document.querySelector('#detail-feedback');
    
    const dateFormatter = new Intl.DateTimeFormat('es-ES', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Madrid',
    });

    let isUpdating = false;

    function showFeedback(message, type = 'error') {
        feedback.classList.toggle('feedback-success', type === 'success');
        feedback.classList.toggle('feedback-loading', type === 'loading');
        
        feedback.textContent = message;
        ç
        feedback.hidden = false;
    }

    function renderReservation(reservation) {
        status.textContent = reservation.estado.charAt(0).toUpperCase() + reservation.estado.slice(1);
        
        status.className = `status status-${reservation.estado}`;
        
        actions.dataset.estado = reservation.estado;

        for (const button of actions.querySelectorAll('[data-action]')) {
            if (reservation.estado === 'cancelada' ||
                (button.dataset.action === 'confirm' && reservation.estado !== 'pendiente')) {
                button.remove();
            }
        }

        const items = reservation.eventos.map(event => {
            const item = document.createElement('div');
            item.className = 'timeline-item';

            const marker = document.createElement('div');
            marker.className = 'timeline-marker';
            
            const content = document.createElement('div');
            content.className = 'timeline-content';
            
            const title = document.createElement('strong');
            title.textContent = event.tipo.charAt(0).toUpperCase() + event.tipo.slice(1);
            
            const description = document.createElement('p');
            description.textContent = event.descripcion;
            
            const time = document.createElement('time');
            const timestamp = event.fecha_creacion.replace(' ', 'T') + 'Z';
            
            time.dateTime = timestamp;
            time.textContent = dateFormatter.format(new Date(timestamp));
            
            content.append(title, description, time);
            
            item.append(marker, content);
            
            return item;
        });
        timeline.replaceChildren(...items);
    }

    actions.addEventListener('click', async event => {
        const button = event.target.closest('[data-action]');
        
        if (!button || isUpdating) return;

        const action = button.dataset.action;

        if (!['confirm', 'cancel'].includes(action)) return;
        
        const message = action === 'confirm' ? '¿Quieres confirmar esta reserva?' : '¿Quieres cancelar esta reserva?';
        
        if (!window.confirm(message)) return;

        isUpdating = true;

        actions.setAttribute('aria-busy', 'true');
        actions.querySelectorAll('button').forEach(item => item.disabled = true);
        
        showFeedback('Actualizando reserva...', 'loading');

        try {
            const response = await fetch(
                `/api/reservations/${encodeURIComponent(id)}/${action}`,
                { method: 'PATCH', headers: { Accept: 'application/json' } }
            );
            
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.error || 'No se pudo actualizar la reserva');
            }

            renderReservation(payload.data);

            showFeedback(
                action === 'confirm' ? 'Reserva confirmada correctamente.' : 'Reserva cancelada correctamente.',
                action === 'confirm' ? 'success' : 'error'
            );
        } catch (error) {
            showFeedback(error.message || 'No se pudo conectar con el servidor.');
        } finally {
            isUpdating = false;

            actions.removeAttribute('aria-busy');
            actions.querySelectorAll('button').forEach(item => item.disabled = false);
        }
    });
});
