<?php
// Microservicio de usuarios sin dependencias externas.
// Usa PDO directamente y responde en JSON.

// Cabeceras CORS/JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(json_encode(['status' => 'ok']));
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function starts_with(string $haystack, string $needle): bool
{
    return substr($haystack, 0, strlen($needle)) === $needle;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $db = getenv('DB_NAME') ?: 'vuelos_app';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        json_response(['error' => 'DB connection failed', 'details' => $e->getMessage()], 500);
    }

    return $pdo;
}

function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function bearer_token(): ?string
{
    // Compatibilidad con PHP built-in server, Apache y Nginx
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? '');
    if (!$auth && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $auth = $value;
                break;
            }
        }
    }

    if (starts_with($auth, 'Bearer ')) {
        return substr($auth, 7);
    }
    return null;
}

function current_user(): ?array
{
    $token = bearer_token();
    if (!$token) return null;
    $stmt = db()->prepare('SELECT * FROM users WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Token inválido o ausente'], 401);
    }
    return $user;
}

function require_role(array $user, array $roles): void
{
    if (!in_array($user['role'], $roles, true)) {
        json_response(['error' => 'No autorizado'], 403);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// Normalizar base path cuando se sirve desde subcarpetas y permitir la barra final
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDir && starts_with($path, $scriptDir)) {
    $path = substr($path, strlen($scriptDir));
    if ($path === '') $path = '/';
}
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// Rutas
if ($method === 'GET' && $path === '/') {
    json_response(['service' => 'users_ms', 'status' => 'ok']);
}

if ($method === 'POST' && $path === '/login') {
    $data = get_json_input();
    $email = $data['email'] ?? ($data['user'] ?? '');
    $password = $data['password'] ?? ($data['pwd'] ?? '');
    if (!$email || !$password) {
        json_response(['error' => 'Credenciales incompletas'], 400);
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || $user['password'] !== $password) {
        json_response(['error' => 'Credenciales inválidas'], 401);
    }

    $token = bin2hex(random_bytes(24));
    $upd = db()->prepare('UPDATE users SET token = ? WHERE id = ?');
    $upd->execute([$token, $user['id']]);
    $user['token'] = $token;

    json_response([
        'token' => $token,
        'userId' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ]);
}

if ($method === 'POST' && $path === '/logout') {
    $user = require_auth();
    $upd = db()->prepare('UPDATE users SET token = NULL WHERE id = ?');
    $upd->execute([$user['id']]);
    json_response(['message' => 'Sesión cerrada']);
}

if ($method === 'GET' && $path === '/me') {
    $user = require_auth();
    unset($user['password'], $user['token']);
    json_response($user);
}

// Rutas de administración
if (starts_with($path, '/users')) {
    $user = require_auth();
    require_role($user, ['administrador']);

    if ($method === 'GET' && $path === '/users') {
        $rows = db()->query('SELECT id, name, email, role FROM users ORDER BY id ASC')->fetchAll();
        json_response($rows);
    }

    if ($method === 'POST' && $path === '/users') {
        $data = get_json_input();
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'gestor';
        if (!$name || !$email || !$password) {
            json_response(['error' => 'Faltan campos obligatorios'], 400);
        }
        $stmt = db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        try {
            $stmt->execute([$name, $email, $password, $role]);
        } catch (Throwable $e) {
            json_response(['error' => 'No se pudo crear usuario', 'details' => $e->getMessage()], 400);
        }
        json_response(['id' => (int) db()->lastInsertId(), 'message' => 'Usuario creado'], 201);
    }

    if ($method === 'PUT' && preg_match('#^/users/(\d+)$#', $path, $m)) {
        $id = (int) $m[1];
        $data = get_json_input();
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $sets = [];
        $params = [];
        foreach ([['name',$name],['email',$email],['password',$password]] as [$col, $val]) {
            if ($val !== null) { $sets[] = "$col = ?"; $params[] = $val; }
        }
        if (!$sets) json_response(['error' => 'Nada para actualizar'], 400);
        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
        db()->prepare($sql)->execute($params);
        json_response(['message' => 'Usuario actualizado']);
    }

    if ($method === 'PUT' && preg_match('#^/users/(\d+)/role$#', $path, $m)) {
        $id = (int) $m[1];
        $data = get_json_input();
        $role = $data['role'] ?? null;
        if (!$role) json_response(['error' => 'Rol requerido'], 400);
        db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        json_response(['message' => 'Rol actualizado']);
    }
}

json_response(['error' => 'Ruta no encontrada', 'path' => $path], 404);
