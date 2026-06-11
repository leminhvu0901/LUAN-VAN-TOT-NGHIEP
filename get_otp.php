<?php
$sessions = DB::table('sessions')->orderBy('last_activity', 'desc')->limit(20)->get();
foreach($sessions as $session) {
    $payload = unserialize(base64_decode($session->payload));
    if (is_array($payload) && isset($payload['verify_otp'])) {
        echo "Email: " . ($payload['verify_email'] ?? 'unknown') . " - OTP: " . $payload['verify_otp'] . "\n";
    }
}
