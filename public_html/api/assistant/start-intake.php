<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ai_json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

ai_verify_same_origin();
$body = ai_read_json_body();
$locale = ai_normalize_locale((string)($body['locale'] ?? 'hr'));

try {
    ai_json_response(200, ai_start_intake_session($locale));
} catch (Throwable $error) {
    ai_log('start_intake_failed', 'error', ['message' => $error->getMessage()]);
    ai_json_response(503, ['success' => false, 'message' => 'Assistant intake is not available.']);
}
