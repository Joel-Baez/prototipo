// Detect the project base to avoid hardcoding localhost vs 127.0.0.1 and ensure the
// frontend consumes the two microservices under the same root folder (htdocs/prototipo).
const projectRoot = window.location.pathname.includes('/frontend')
    ? window.location.pathname.split('/frontend')[0]
    : '';
const BASE_URL = `${window.location.origin}${projectRoot}`;

// Allow overrides via query params when microservices run on different ports (ej. php -S -t public)
const params = new URLSearchParams(window.location.search);
const usersApiOverride = params.get('usersApi');
const flightsApiOverride = params.get('flightsApi');

// If the frontend is running on a dev port (8000, 3000, 5173), assume microservices on 8001/8002.
const devPorts = ['8000', '3000', '5173'];
const isDevPort = devPorts.includes(window.location.port);
const guessedUsersApi = `${window.location.protocol}//${window.location.hostname}:8001`;
const guessedFlightsApi = `${window.location.protocol}//${window.location.hostname}:8002`;

const USERS_API = (usersApiOverride
    || (isDevPort ? guessedUsersApi : `${BASE_URL}/backend/users_ms/public`)).replace(/\/$/, '');
const FLIGHTS_API = (flightsApiOverride
    || (isDevPort ? guessedFlightsApi : `${BASE_URL}/backend/flights_ms/public`)).replace(/\/$/, '');

const loginForm = document.getElementById('loginForm');
const logoutBtn = document.getElementById('logoutBtn');
const loginSection = document.getElementById('loginSection');
const adminSection = document.getElementById('adminSection');
const gestorSection = document.getElementById('gestorSection');

const token = () => localStorage.getItem('token');
const role = () => localStorage.getItem('role');

const authHeaders = () => ({
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token()}`,
});

const actionButtons = (actions = []) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'actions-cell';
    actions.forEach(({ label, onClick, tone = 'ghost' }) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = label;
        btn.className = `chip ${tone}`;
        btn.addEventListener('click', onClick);
        wrapper.appendChild(btn);
    });
    return wrapper;
};

const renderTable = (tableId, data, columns) => {
    const tbody = document.querySelector(`#${tableId} tbody`);
    if (!tbody) return;
    tbody.innerHTML = '';
    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${columns.length}" class="empty">Sin datos</td></tr>`;
        return;
    }

    data.forEach((item) => {
        const tr = document.createElement('tr');
        columns.forEach((col) => {
            const td = document.createElement('td');
            if (typeof col === 'function') {
                td.textContent = col(item);
            } else if (typeof col === 'object' && col.render) {
                td.appendChild(col.render(item));
            } else {
                td.textContent = item[col] ?? '';
            }
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
};

const handleError = async (res) => {
    const payload = await res.json();
    alert(payload.error || 'Error en la solicitud');
};

loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(loginForm));
    const res = await fetch(`${USERS_API}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });

    if (!res.ok) return handleError(res);
    const data = await res.json();
    localStorage.setItem('token', data.token);
    localStorage.setItem('role', data.role);
    toggleSections();
    if (data.role === 'administrador') {
        loadUsers();
        loadNaves();
        loadFlights();
    }
    if (['gestor', 'administrador'].includes(data.role)) {
        loadReservations();
    }
});

logoutBtn.addEventListener('click', async () => {
    if (!token()) return;
    await fetch(`${USERS_API}/logout`, { method: 'POST', headers: authHeaders() });
    localStorage.clear();
    toggleSections();
});

function toggleSections() {
    const hasToken = !!token();
    loginSection.classList.toggle('hidden', hasToken);
    logoutBtn.classList.toggle('hidden', !hasToken);
    adminSection.classList.toggle('hidden', !(hasToken && role() === 'administrador'));
    gestorSection.classList.toggle('hidden', !(hasToken && (role() === 'gestor' || role() === 'administrador')));
}

toggleSections();

// Admin actions
const userForm = document.getElementById('userForm');
document.getElementById('loadUsers').addEventListener('click', loadUsers);
userForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(userForm));
    const res = await fetch(`${USERS_API}/users`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify(body),
    });
    if (!res.ok) return handleError(res);
    userForm.reset();
    loadUsers();
});

async function loadUsers() {
    const res = await fetch(`${USERS_API}/users`, { headers: authHeaders() });
    if (!res.ok) return handleError(res);
    const data = await res.json();
    renderTable('usersTable', data, [
        'id',
        'name',
        'email',
        'role',
        {
            render: (user) => actionButtons([
                {
                    label: 'Editar',
                    onClick: () => updateUser(user),
                },
                {
                    label: 'Cambiar rol',
                    onClick: () => changeUserRole(user),
                },
            ]),
        },
    ]);
}

async function updateUser(user) {
    const name = prompt('Nombre', user.name);
    const email = prompt('Email', user.email);
    const password = prompt('Contraseña (déjalo vacío para no cambiar)');
    const payload = {};
    if (name && name !== user.name) payload.name = name;
    if (email && email !== user.email) payload.email = email;
    if (password) payload.password = password;
    if (!Object.keys(payload).length) return;
    const res = await fetch(`${USERS_API}/users/${user.id}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: JSON.stringify(payload),
    });
    if (!res.ok) return handleError(res);
    loadUsers();
}

async function changeUserRole(user) {
    const newRole = prompt('Rol (administrador/gestor)', user.role);
    if (!newRole) return;
    const res = await fetch(`${USERS_API}/users/${user.id}/role`, {
        method: 'PUT',
        headers: authHeaders(),
        body: JSON.stringify({ role: newRole }),
    });
    if (!res.ok) return handleError(res);
    loadUsers();
}

const naveForm = document.getElementById('naveForm');
document.getElementById('loadNaves').addEventListener('click', loadNaves);
naveForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(naveForm));
    body.capacity = parseInt(body.capacity, 10);
    const res = await fetch(`${FLIGHTS_API}/naves`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify(body),
    });
    if (!res.ok) return handleError(res);
    naveForm.reset();
    loadNaves();
});

async function loadNaves() {
    const res = await fetch(`${FLIGHTS_API}/naves`, { headers: authHeaders() });
    if (!res.ok) return handleError(res);
    const data = await res.json();
    renderTable('navesTable', data, [
        'id',
        'name',
        'capacity',
        'model',
        {
            render: (nave) => actionButtons([
                {
                    label: 'Editar',
                    onClick: () => updateNave(nave),
                },
                {
                    label: 'Eliminar',
                    tone: 'danger',
                    onClick: () => deleteNave(nave.id),
                },
            ]),
        },
    ]);
}

async function updateNave(nave) {
    const name = prompt('Nombre', nave.name);
    const capacity = prompt('Capacidad', nave.capacity);
    const model = prompt('Modelo', nave.model);
    const payload = {};
    if (name && name !== nave.name) payload.name = name;
    if (capacity) payload.capacity = Number(capacity);
    if (model && model !== nave.model) payload.model = model;
    if (!Object.keys(payload).length) return;
    const res = await fetch(`${FLIGHTS_API}/naves/${nave.id}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: JSON.stringify(payload),
    });
    if (!res.ok) return handleError(res);
    loadNaves();
}

async function deleteNave(id) {
    if (!confirm('¿Eliminar esta nave?')) return;
    const res = await fetch(`${FLIGHTS_API}/naves/${id}`, {
        method: 'DELETE',
        headers: authHeaders(),
    });
    if (!res.ok) return handleError(res);
    loadNaves();
}

const flightForm = document.getElementById('flightForm');
document.getElementById('loadFlights').addEventListener('click', loadFlights);
flightForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(flightForm));
    const res = await fetch(`${FLIGHTS_API}/flights`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify(body),
    });
    if (!res.ok) return handleError(res);
    flightForm.reset();
    loadFlights();
});

async function loadFlights() {
    const res = await fetch(`${FLIGHTS_API}/flights`, { headers: authHeaders() });
    if (!res.ok) return handleError(res);
    const data = await res.json();
    renderTable('flightsTable', data, [
        'id',
        'origin',
        'destination',
        (row) => row.departure?.replace('T', ' ') ?? row.departure,
        (row) => row.arrival?.replace('T', ' ') ?? row.arrival,
        'nave_name',
        (row) => row.price,
        {
            render: (flight) => actionButtons([
                {
                    label: 'Editar',
                    onClick: () => updateFlight(flight),
                },
                {
                    label: 'Eliminar',
                    tone: 'danger',
                    onClick: () => deleteFlight(flight.id),
                },
            ]),
        },
    ]);
}

async function updateFlight(flight) {
    const payload = {};
    const fields = [
        ['nave_id', flight.nave_id],
        ['origin', flight.origin],
        ['destination', flight.destination],
        ['departure', flight.departure?.replace(' ', 'T')],
        ['arrival', flight.arrival?.replace(' ', 'T')],
        ['price', flight.price],
    ];
    fields.forEach(([key, current]) => {
        const value = prompt(key, current);
        if (value !== null && value !== '') payload[key] = value;
    });
    if (!Object.keys(payload).length) return;
    const res = await fetch(`${FLIGHTS_API}/flights/${flight.id}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: JSON.stringify(payload),
    });
    if (!res.ok) return handleError(res);
    loadFlights();
}

async function deleteFlight(id) {
    if (!confirm('¿Eliminar este vuelo?')) return;
    const res = await fetch(`${FLIGHTS_API}/flights/${id}`, {
        method: 'DELETE',
        headers: authHeaders(),
    });
    if (!res.ok) return handleError(res);
    loadFlights();
}

// Gestor actions
const searchForm = document.getElementById('searchForm');
searchForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const params = new URLSearchParams(Object.fromEntries(new FormData(searchForm))).toString();
    const res = await fetch(`${FLIGHTS_API}/flights?${params}`, { headers: authHeaders() });
    if (!res.ok) return handleError(res);
    const data = await res.json();
    renderTable('searchTable', data, [
        'id',
        'origin',
        'destination',
        (row) => row.departure?.replace('T', ' ') ?? row.departure,
        (row) => row.arrival?.replace('T', ' ') ?? row.arrival,
        'nave_name',
        (row) => row.price,
    ]);
});

const reservationForm = document.getElementById('reservationForm');
document.getElementById('loadReservations').addEventListener('click', loadReservations);
reservationForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(reservationForm));
    const res = await fetch(`${FLIGHTS_API}/reservations`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify(body),
    });
    if (!res.ok) return handleError(res);
    reservationForm.reset();
    loadReservations();
});

async function loadReservations() {
    const res = await fetch(`${FLIGHTS_API}/reservations`, { headers: authHeaders() });
    if (!res.ok) return handleError(res);
    const data = await res.json();
    renderTable('reservationsTable', data, [
        'id',
        'flight_id',
        'origin',
        'destination',
        (row) => row.departure?.replace('T', ' ') ?? row.departure,
        'status',
        {
            render: (reservation) => actionButtons([
                {
                    label: 'Cancelar',
                    tone: 'danger',
                    onClick: () => cancelReservation(reservation.id),
                },
            ]),
        },
    ]);
}

async function cancelReservation(id) {
    if (!confirm('¿Cancelar esta reserva?')) return;
    const res = await fetch(`${FLIGHTS_API}/reservations/${id}/cancel`, {
        method: 'PUT',
        headers: authHeaders(),
    });
    if (!res.ok) return handleError(res);
    loadReservations();
}
