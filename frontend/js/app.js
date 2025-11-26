// Detect the project base to avoid hardcoding localhost vs 127.0.0.1 and ensure the
// frontend consumes the two microservices under the same root folder (htdocs/prototipo).
const projectRoot = window.location.pathname.includes('/frontend')
    ? window.location.pathname.split('/frontend')[0]
    : '';
const BASE_URL = `${window.location.origin}${projectRoot}`;

const USERS_API = `${BASE_URL}/backend/users_ms/public`;
const FLIGHTS_API = `${BASE_URL}/backend/flights_ms/public`;

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
const usersList = document.getElementById('usersList');
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
    usersList.textContent = JSON.stringify(data.users, null, 2);
}

const naveForm = document.getElementById('naveForm');
const navesList = document.getElementById('navesList');
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
    navesList.textContent = JSON.stringify(data.naves, null, 2);
}

const flightForm = document.getElementById('flightForm');
const flightsList = document.getElementById('flightsList');
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
    flightsList.textContent = JSON.stringify(data.flights, null, 2);
}

// Gestor actions
const searchForm = document.getElementById('searchForm');
const searchResults = document.getElementById('searchResults');
searchForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const params = new URLSearchParams(Object.fromEntries(new FormData(searchForm))).toString();
    const res = await fetch(`${FLIGHTS_API}/flights/search?${params}`, { headers: authHeaders() });
    if (!res.ok) return handleError(res);
    const data = await res.json();
    searchResults.textContent = JSON.stringify(data.flights, null, 2);
});

const reservationForm = document.getElementById('reservationForm');
const reservationsList = document.getElementById('reservationsList');
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
    reservationsList.textContent = JSON.stringify(data.reservations, null, 2);
}
