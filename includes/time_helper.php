<?php
function time_elapsed_string($datetime, $full = false)
{
    if ($datetime == '0000-00-00 00:00:00' || empty($datetime)) {
        return "N/A";
    }

    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks manually since DateInterval doesn't support it directly in all versions properties
    $weeks = floor($diff->days / 7);
    $days = $diff->days - ($weeks * 7);

    // We can't easily modify $diff->d strictly for logic if we use $diff->format later,
    // but here we are building a string manually.
    // However, the standard diff properties (y, m, d) are calendar based.
    // If we want "2 weeks ago", we should probably just use approximate larger units logic
    // OR just use standard y/m/d/h/i/s.

    // Let's keep it simple and safe:
    $string = array(
        'y' => 'año',
        'm' => 'mes',
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );

    // Convert to readable
    $parts = [];
    foreach ($string as $k => $v) {
        if ($diff->$k) {
            $parts[$k] = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        }
    }

    // Handle weeks special case roughly? Or just ignore weeks and show days.
    // If we really want weeks:
    if ($diff->y == 0 && $diff->m == 0 && $diff->d >= 7) {
        $w = floor($diff->d / 7);
        $d = $diff->d % 7;
        // This is tricky because d is days within month.
        // Let's stick to standard PHP diff output which is correct.
    }

    if (!$full)
        $parts = array_slice($parts, 0, 1);
    return $parts ? 'hace ' . implode(', ', $parts) : 'justo ahora';
}
?>