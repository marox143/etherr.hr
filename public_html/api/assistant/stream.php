<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ai_json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

ai_verify_same_origin();
$body = ai_read_json_body();
$locale = ai_normalize_locale((string)($body['locale'] ?? 'hr'));
$message = (string)($body['message'] ?? '');

header('Content-Type: application/x-ndjson; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $payload = ai_send_message($message, $locale);
    if (empty($payload['success'])) {
        http_response_code(503);
        echo json_encode(['type' => 'error', 'message' => $payload['message'] ?? 'Assistant unavailable.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        exit;
    }
    echo json_encode(['type' => 'done', 'payload' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
} catch (Throwable $error) {
    ai_log('stream_endpoint_failed', 'error', ['message' => $error->getMessage()]);
    http_response_code(503);
    echo json_encode(['type' => 'error', 'message' => 'Assistant is not configured yet.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}
