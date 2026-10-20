document.addEventListener('DOMContentLoaded', () => {
    const actions = document.querySelector('#detail-actions');

    if (!actions) return;

    const id = actions.dataset.reservaId;

    actions.addEventListener('click', async event => {
        const button = event.target.closest('[data-action]');

        if (!button) return;

        const action = button.dataset.action;

        const message = action === 'confirm'
            ? '¿Quieres confirmar esta reserva?'
            : '¿Quieres cancelar esta reserva?';

        if (!window.confirm(message)) return;

        const buttons = actions.querySelectorAll('button');
        buttons.forEach(item => item.disabled = true);

        try {
            const response = await fetch(
                `/api/reservations/${encodeURIComponent(id)}/${action}`,
                {
                    method: 'PATCH',
                    headers: { Accept: 'application/json' },
                }
            );

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.error || 'No se pudo actualizar la reserva');
            }

            window.location.reload();
        } catch (error) {
            window.alert(error.message);
            buttons.forEach(item => item.disabled = false);
        }
    });
});
