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
        columns.forEach((key) => {
            const td = document.createElement('td');
            td.textContent = typeof key === 'function' ? key(item) : (item[key] ?? '');
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
    renderTable('usersTable', data, ['id', 'name', 'email', 'role']);
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
    renderTable('navesTable', data, ['id', 'name', 'capacity', 'model']);
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
    ]);
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
    ]);
}
