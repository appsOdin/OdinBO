<?php
/** @var array<int, array<string, mixed>> $entries */
$entries = $entries ?? [];
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Calendario de Vacaciones</h2>
        <p class="text-muted m-0">Visualizacion mensual de vacaciones y permisos.</p>
    </div>
</section>

<div class="card border-0 shadow-sm vacation-card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="calendarPrevMonth">Anterior</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="calendarNextMonth">Siguiente</button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="calendarCurrentMonth">Hoy</button>
            </div>
            <h5 class="m-0" id="calendarTitle"></h5>
        </div>

        <div class="vacation-calendar" id="vacationCalendar"></div>
    </div>
</div>

<div class="modal fade" id="calendarRequestDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de la Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="calendarRequestDetailBody">
                <div class="text-muted">Seleccione un registro para ver su detalle.</div>
            </div>
        </div>
    </div>
</div>

<style>
.vacation-calendar {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 8px;
}

.vacation-calendar .weekday {
    text-align: center;
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
    padding: 4px;
}

.vacation-calendar .day {
    min-height: 130px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px;
    background: #fff;
}

.vacation-calendar .day.muted {
    background: #f8f9fa;
    color: #adb5bd;
}

.vacation-calendar .day-number {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 6px;
}

.vacation-calendar .event-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.vacation-calendar .event-btn {
    border: 0;
    border-radius: 6px;
    text-align: left;
    font-size: 0.75rem;
    line-height: 1.2;
    padding: 5px 6px;
    background: #e7f1ff;
    color: #0a58ca;
}

.vacation-calendar .event-btn:hover {
    background: #d0e3ff;
}

@media (max-width: 991.98px) {
    .vacation-calendar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vacation-calendar .weekday {
        display: none;
    }
}
</style>

<script>
(() => {
    const entries = <?= json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const parseApiDate = (value) => {
        const text = String(value || '').trim();
        if (!text) {
            return null;
        }

        const datePart = text.slice(0, 10);
        const [year, month, day] = datePart.split('-').map(Number);
        if (!year || !month || !day) {
            return null;
        }

        return new Date(year, month - 1, day);
    };

    const formatDate = (date) => {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${year}-${month}-${day}`;
    };

    const normalizeRows = () => {
        const rows = [];

        entries.forEach((row) => {
            const start = parseApiDate(row.start_date);
            const end = parseApiDate(row.end_date);
            if (!start || !end) {
                return;
            }

            const cursor = new Date(start.getFullYear(), start.getMonth(), start.getDate());
            while (cursor <= end) {
                rows.push({
                    dateKey: formatDate(cursor),
                    identification: row.identification || '',
                    fullName: row.fullName || '',
                    id: Number(row.id || 0),
                    start_date: row.start_date || '',
                    end_date: row.end_date || '',
                    request_type: row.request_type || '',
                    quantity: row.quantity || 0,
                    description: row.description || ''
                });
                cursor.setDate(cursor.getDate() + 1);
            }
        });

        return rows;
    };

    const allEvents = normalizeRows();
    const eventsByDay = allEvents.reduce((acc, item) => {
        if (!acc[item.dateKey]) {
            acc[item.dateKey] = [];
        }
        acc[item.dateKey].push(item);
        return acc;
    }, {});

    let pointer = new Date();
    pointer = new Date(pointer.getFullYear(), pointer.getMonth(), 1);

    const calendarEl = document.getElementById('vacationCalendar');
    const titleEl = document.getElementById('calendarTitle');
    const prevBtn = document.getElementById('calendarPrevMonth');
    const nextBtn = document.getElementById('calendarNextMonth');
    const todayBtn = document.getElementById('calendarCurrentMonth');
    const detailBody = document.getElementById('calendarRequestDetailBody');
    const detailModalEl = document.getElementById('calendarRequestDetailModal');

    const getModalInstance = () => {
        if (!detailModalEl || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }

        return window.bootstrap.Modal.getOrCreateInstance(detailModalEl);
    };

    const weekdays = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    const renderDetail = (eventData) => {
        if (!detailBody) {
            return;
        }

        detailBody.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6"><strong>ID:</strong> ${eventData.id}</div>
                <div class="col-md-6"><strong>Identificacion:</strong> ${eventData.identification || '-'}</div>
                <div class="col-md-12"><strong>Nombre:</strong> ${eventData.fullName || '-'}</div>
                <div class="col-md-6"><strong>Inicio:</strong> ${String(eventData.start_date || '').slice(0, 10)}</div>
                <div class="col-md-6"><strong>Fin:</strong> ${String(eventData.end_date || '').slice(0, 10)}</div>
                <div class="col-md-6"><strong>Tipo:</strong> ${eventData.request_type || '-'}</div>
                <div class="col-md-6"><strong>Cantidad:</strong> ${eventData.quantity || 0}</div>
                <div class="col-12"><strong>Descripcion:</strong><br>${eventData.description || '-'}</div>
            </div>
        `;
    };

    const render = () => {
        if (!calendarEl || !titleEl) {
            return;
        }

        calendarEl.innerHTML = '';
        weekdays.forEach((day) => {
            const el = document.createElement('div');
            el.className = 'weekday';
            el.textContent = day;
            calendarEl.appendChild(el);
        });

        titleEl.textContent = `${monthNames[pointer.getMonth()]} ${pointer.getFullYear()}`;

        const first = new Date(pointer.getFullYear(), pointer.getMonth(), 1);
        const last = new Date(pointer.getFullYear(), pointer.getMonth() + 1, 0);
        const startOffset = first.getDay();
        const totalCells = Math.ceil((startOffset + last.getDate()) / 7) * 7;

        for (let i = 0; i < totalCells; i++) {
            const dayDate = new Date(first.getFullYear(), first.getMonth(), i - startOffset + 1);
            const dateKey = formatDate(dayDate);
            const inMonth = dayDate.getMonth() === pointer.getMonth();
            const events = eventsByDay[dateKey] || [];

            const cell = document.createElement('div');
            cell.className = `day${inMonth ? '' : ' muted'}`;

            const number = document.createElement('div');
            number.className = 'day-number';
            number.textContent = String(dayDate.getDate());
            cell.appendChild(number);

            const eventList = document.createElement('div');
            eventList.className = 'event-list';

            events.forEach((eventData) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'event-btn';
                btn.textContent = eventData.fullName || eventData.identification || 'Sin nombre';
                btn.addEventListener('click', () => {
                    renderDetail(eventData);
                    getModalInstance()?.show();
                });
                eventList.appendChild(btn);
            });

            cell.appendChild(eventList);
            calendarEl.appendChild(cell);
        }
    };

    prevBtn?.addEventListener('click', () => {
        pointer = new Date(pointer.getFullYear(), pointer.getMonth() - 1, 1);
        render();
    });

    nextBtn?.addEventListener('click', () => {
        pointer = new Date(pointer.getFullYear(), pointer.getMonth() + 1, 1);
        render();
    });

    todayBtn?.addEventListener('click', () => {
        const now = new Date();
        pointer = new Date(now.getFullYear(), now.getMonth(), 1);
        render();
    });

    render();
})();
</script>
