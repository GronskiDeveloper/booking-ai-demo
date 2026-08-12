<?php

declare(strict_types=1);

/**
 * GET /slots.php?date=YYYY-MM-DD
 *
 * Returns the free 30-min slots for the salon on the given date, using
 * grodev/booking-slots as the math engine. Existing bookings are loaded
 * from a plain JSON file — in production that's a database read; here it's
 * kept file-based so the demo runs without any DB setup.
 *
 * Salon hours: 09:00–17:00, Mon–Sat. 10-minute buffer between bookings.
 *
 * Built by GroDev — https://grodev.pl/system-rezerwacji-online
 */

require __DIR__ . '/../vendor/autoload.php';

use GroDev\Booking\SlotGenerator;

// -- CORS + JSON headers ------------------------------------------------------

$allowedOrigin = getenv('BOOKING_ALLOWED_ORIGIN') ?: '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    http_response_code(204);
    exit;
}

// -- Input validation ---------------------------------------------------------

$date = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid ?date=YYYY-MM-DD']);
    exit;
}

try {
    $day = new DateTimeImmutable($date . ' 00:00', new DateTimeZone('Europe/Warsaw'));
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Unparseable date']);
    exit;
}

// Salon closed on Sundays (day-of-week 7)
if ((int) $day->format('N') === 7) {
    echo json_encode(['date' => $date, 'closed' => true, 'reason' => 'Nieczynne w niedziele', 'slots' => []]);
    exit;
}

// -- Load bookings for this date ---------------------------------------------

$bookingsFile = __DIR__ . '/../data/bookings.json';
$all = json_decode(file_get_contents($bookingsFile) ?: '[]', true) ?: [];

$busy = [];
foreach ($all as $b) {
    if (($b['date'] ?? '') !== $date) {
        continue;
    }
    $busy[] = [
        new DateTimeImmutable($date . ' ' . $b['start'], new DateTimeZone('Europe/Warsaw')),
        new DateTimeImmutable($date . ' ' . $b['end'],   new DateTimeZone('Europe/Warsaw')),
    ];
}

// -- Generate free slots ------------------------------------------------------

$opensAt  = $day->setTime(9, 0);
$closesAt = $day->setTime(17, 0);
$notBefore = new DateTimeImmutable('now', new DateTimeZone('Europe/Warsaw'));
$notBefore = $notBefore->modify('+2 hours'); // 2h lead time — no same-hour bookings

$generator = new SlotGenerator(slotMinutes: 30, bufferMinutes: 10);
$slots = $generator->generate($opensAt, $closesAt, $busy, $notBefore);

echo json_encode([
    'date'   => $date,
    'closed' => false,
    'slots'  => array_map(fn ($s) => [
        'label' => $s->label(),
        'start' => $s->start->format(DATE_ATOM),
        'end'   => $s->end->format(DATE_ATOM),
    ], $slots),
], JSON_UNESCAPED_UNICODE);
