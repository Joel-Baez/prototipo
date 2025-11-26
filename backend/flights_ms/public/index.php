<?php
// Microservicio de vuelos/reservas sin dependencias externas.
// Usa PDO directamente y responde en JSON.

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

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

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
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (str_starts_with($auth, 'Bearer ')) {
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
    if (!$user) json_response(['error' => 'Token inválido o ausente'], 401);
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
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDir && str_starts_with($path, $scriptDir)) {
    $path = substr($path, strlen($scriptDir));
    if ($path === '') $path = '/';
}

if ($method === 'GET' && $path === '/') {
    json_response(['service' => 'flights_ms', 'status' => 'ok']);
}

// --- Naves (admin) ---
if (str_starts_with($path, '/naves')) {
    $user = require_auth();
    require_role($user, ['administrador']);

    if ($method === 'GET' && $path === '/naves') {
        $rows = db()->query('SELECT * FROM naves ORDER BY id ASC')->fetchAll();
        json_response($rows);
    }
    if ($method === 'POST' && $path === '/naves') {
        $data = get_json_input();
        $name = trim($data['name'] ?? '');
        $capacity = (int) ($data['capacity'] ?? 0);
        $model = trim($data['model'] ?? '');
        if (!$name || !$capacity || !$model) json_response(['error' => 'Datos incompletos'], 400);
        $stmt = db()->prepare('INSERT INTO naves (name, capacity, model) VALUES (?, ?, ?)');
        $stmt->execute([$name, $capacity, $model]);
        json_response(['id' => (int) db()->lastInsertId(), 'message' => 'Nave creada'], 201);
    }
    if ($method === 'PUT' && preg_match('#^/naves/(\d+)$#', $path, $m)) {
        $id = (int) $m[1];
        $data = get_json_input();
        $sets = [];$params=[];
        foreach ([['name',$data['name']??null],['capacity',$data['capacity']??null],['model',$data['model']??null]] as [$col,$val]){
            if ($val!==null){$sets[]="$col = ?";$params[]=$val;}
        }
        if (!$sets) json_response(['error'=>'Nada para actualizar'],400);
        $params[]=$id;
        $sql='UPDATE naves SET '.implode(', ',$sets).' WHERE id = ?';
        db()->prepare($sql)->execute($params);
        json_response(['message'=>'Nave actualizada']);
    }
    if ($method === 'DELETE' && preg_match('#^/naves/(\d+)$#', $path, $m)) {
        $id = (int)$m[1];
        db()->prepare('DELETE FROM naves WHERE id = ?')->execute([$id]);
        json_response(['message'=>'Nave eliminada']);
    }
}

// --- Flights ---
if (str_starts_with($path, '/flights')) {
    if ($method === 'GET' && $path === '/flights') {
        $origin = $_GET['origin'] ?? null;
        $dest = $_GET['destination'] ?? null;
        $date = $_GET['date'] ?? null;
        $where=[];$params=[];
        if ($origin){$where[]='origin LIKE ?';$params[]="%$origin%";}
        if ($dest){$where[]='destination LIKE ?';$params[]="%$dest%";}
        if ($date){$where[]='DATE(departure) = ?';$params[]=$date;}
        $sql='SELECT f.*, n.name as nave_name FROM flights f JOIN naves n ON f.nave_id=n.id';
        if ($where) $sql.=' WHERE '.implode(' AND ',$where);
        $sql.=' ORDER BY f.departure ASC';
        $stmt=db()->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());
    }

    $user = require_auth();
    require_role($user, ['administrador']);

    if ($method === 'POST' && $path === '/flights') {
        $data = get_json_input();
        $fields=['nave_id','origin','destination','departure','arrival','price'];
        foreach ($fields as $f){ if(!isset($data[$f])||$data[$f]===''){json_response(['error'=>'Datos incompletos'],400);} }
        $stmt=db()->prepare('INSERT INTO flights (nave_id, origin, destination, departure, arrival, price) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$data['nave_id'],$data['origin'],$data['destination'],$data['departure'],$data['arrival'],$data['price']]);
        json_response(['id'=>(int)db()->lastInsertId(),'message'=>'Vuelo creado'],201);
    }
    if ($method === 'PUT' && preg_match('#^/flights/(\d+)$#',$path,$m)){
        $id=(int)$m[1];
        $data=get_json_input();
        $sets=[];$params=[];
        foreach (['nave_id','origin','destination','departure','arrival','price'] as $col){
            if(array_key_exists($col,$data)){$sets[]="$col = ?";$params[]=$data[$col];}
        }
        if(!$sets)json_response(['error'=>'Nada para actualizar'],400);
        $params[]=$id;
        $sql='UPDATE flights SET '.implode(', ',$sets).' WHERE id = ?';
        db()->prepare($sql)->execute($params);
        json_response(['message'=>'Vuelo actualizado']);
    }
    if ($method === 'DELETE' && preg_match('#^/flights/(\d+)$#',$path,$m)){
        $id=(int)$m[1];
        db()->prepare('DELETE FROM flights WHERE id = ?')->execute([$id]);
        json_response(['message'=>'Vuelo eliminado']);
    }
}

// --- Reservations (gestor) ---
if (str_starts_with($path, '/reservations')) {
    $user=require_auth();
    require_role($user,['gestor']);

    if ($method === 'GET' && $path === '/reservations') {
        $userId = $_GET['user_id'] ?? null;
        $where='';$params=[];
        if ($userId){$where=' WHERE r.user_id = ?';$params[]=$userId;}
        $sql='SELECT r.*, f.origin, f.destination, f.departure, f.arrival FROM reservations r JOIN flights f ON r.flight_id = f.id'.$where.' ORDER BY r.id DESC';
        $stmt=db()->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());
    }

    if ($method === 'POST' && $path === '/reservations') {
        $data=get_json_input();
        $flightId=$data['flight_id']??null;
        $userId=$data['user_id']??$user['id'];
        if(!$flightId)json_response(['error'=>'flight_id requerido'],400);
        $flight=db()->prepare('SELECT id FROM flights WHERE id = ?');
        $flight->execute([$flightId]);
        if(!$flight->fetch()) json_response(['error'=>'Vuelo inexistente'],400);
        $stmt=db()->prepare('INSERT INTO reservations (user_id, flight_id, status) VALUES (?, ?, "activa")');
        $stmt->execute([$userId,$flightId]);
        json_response(['id'=>(int)db()->lastInsertId(),'message'=>'Reserva creada'],201);
    }

    if ($method === 'PUT' && preg_match('#^/reservations/(\d+)/cancel$#',$path,$m)){
        $id=(int)$m[1];
        db()->prepare('UPDATE reservations SET status = "cancelada" WHERE id = ?')->execute([$id]);
        json_response(['message'=>'Reserva cancelada']);
    }
}

json_response(['error'=>'Ruta no encontrada','path'=>$path],404);
