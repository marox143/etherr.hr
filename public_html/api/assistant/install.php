<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    ai_start_admin_session();
    if (!ai_admin_is_authenticated()) {
        ai_json_response(403, ['success' => false, 'message' => 'Admin login required.']);
    }
}

try {
    ai_ensure_schema();
    $payload = [
        'success' => true,
        'message' => 'Assistant tables are ready and defaults are seeded.',
        'tables' => [
            ETHERR_AI_PREFIX . 'settings',
            ETHERR_AI_PREFIX . 'sessions',
            ETHERR_AI_PREFIX . 'conversations',
            ETHERR_AI_PREFIX . 'messages',
            ETHERR_AI_PREFIX . 'logs',
        ],
    ];
    if ($isCli) {
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        exit;
    }
    ai_json_response(200, $payload);
} catch (Throwable $error) {
    if ($isCli) {
        fwrite(STDERR, $error->getMessage() . PHP_EOL);
        exit(1);
    }
    ai_json_response(503, ['success' => false, 'message' => $error->getMessage()]);
}
