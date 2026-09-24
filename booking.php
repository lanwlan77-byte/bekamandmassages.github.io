<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Metode request tidak diperbolehkan.']);
}

$input = json_decode(file_get_contents('php://input'), true);
$input = is_array($input) ? $input : $_POST;

$name = trim((string) ($input['customer_name'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$serviceId = filter_var($input['service_id'] ?? null, FILTER_VALIDATE_INT);
$date = trim((string) ($input['booking_date'] ?? ''));
$time = trim((string) ($input['booking_time'] ?? ''));
$notes = trim((string) ($input['notes'] ?? ''));

if ($name === '' || mb_strlen($name) > 120 || $phone === '' || mb_strlen($phone) > 30) {
    respond(422, ['success' => false, 'message' => 'Nama dan nomor WhatsApp wajib diisi dengan benar.']);
}

if (!$serviceId || !validDate($date) || !validTime($time) || $date < date('Y-m-d')) {
    respond(422, ['success' => false, 'message' => 'Layanan, tanggal, dan jam booking belum valid.']);
}

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=bekam_db;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $serviceStatement = $pdo->prepare('SELECT id FROM services WHERE id = :id AND is_active = 1');
    $serviceStatement->execute(['id' => $serviceId]);
    if (!$serviceStatement->fetch()) {
        respond(422, ['success' => false, 'message' => 'Layanan yang dipilih tidak tersedia.']);
    }

    $scheduleStatement = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE booking_date = :booking_date AND booking_time = :booking_time
         AND status IN ('pending', 'confirmed') LIMIT 1"
    );
    $scheduleStatement->execute(['booking_date' => $date, 'booking_time' => $time]);
    if ($scheduleStatement->fetch()) {
        respond(409, ['success' => false, 'message' => 'Jadwal tersebut sudah dipesan. Silakan pilih jam lain.']);
    }

    $bookingCode = 'BK-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $statement = $pdo->prepare(
        'INSERT INTO bookings
        (booking_code, customer_name, phone, service_id, booking_date, booking_time, notes)
        VALUES (:booking_code, :customer_name, :phone, :service_id, :booking_date, :booking_time, :notes)'
    );
    $statement->execute([
        'booking_code' => $bookingCode,
        'customer_name' => $name,
        'phone' => $phone,
        'service_id' => $serviceId,
        'booking_date' => $date,
        'booking_time' => $time,
        'notes' => $notes !== '' ? $notes : null,
    ]);

    respond(201, ['success' => true, 'message' => 'Booking berhasil disimpan.', 'booking_code' => $bookingCode]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    respond(500, ['success' => false, 'message' => 'Database belum terhubung. Pastikan database.sql sudah diimpor.']);
}

function validDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function validTime(string $time): bool
{
    $parsed = DateTime::createFromFormat('H:i', $time);
    return $parsed !== false && $parsed->format('H:i') === $time;
}

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}