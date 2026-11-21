<?php
// Parent: JSON list of time slots for one hospital and date, with how many places are left.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('parent');
header('Content-Type: application/json');

$hid  = (int) ($_GET['hospital'] ?? 0);
$date = $_GET['date'] ?? '';
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$hid || !$d || $d->format('Y-m-d') !== $date) {
    echo json_encode(['capacity' => 0, 'slots' => []]);
    exit;
}

$capacity = scalar_count("SELECT slot_capacity FROM hospitals WHERE id = ? AND status = 'active'", [$hid]);

// How many active bookings each slot already has
$q = db()->prepare("SELECT TIME_FORMAT(preferred_time, '%H:%i') AS t, COUNT(*) AS n FROM bookings
    WHERE hospital_id = ? AND preferred_date = ? AND status IN ('pending','approved') GROUP BY t");
$q->execute([$hid, $date]);
$used = array_column($q->fetchAll(), 'n', 't');

$slots = [];
foreach (booking_slots() as $s) {
    $slots[] = ['time' => $s, 'left' => max(0, $capacity - (int) ($used[$s] ?? 0))];
}
echo json_encode(['capacity' => $capacity, 'slots' => $slots]);