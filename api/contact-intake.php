<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: same-origin');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function loadEnvFile(string $path): array
{
    $values = [];
    if (!is_file($path) || !is_readable($path)) {
        return $values;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $values;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '') {
            continue;
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && $value[strlen($value) - 1] === '"') || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $values[$key] = $value;
    }

    return $values;
}

function envValue(array $env, string $key, string $default = ''): string
{
    if (array_key_exists($key, $env)) {
        return trim((string)$env[$key]);
    }
    return $default;
}

function envBool(array $env, string $key, bool $default = false): bool
{
    $raw = envValue($env, $key, $default ? 'true' : 'false');
    $normalized = strtolower(trim($raw));
    if ($normalized === '') {
        return $default;
    }
    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }
    return $default;
}

function sanitizeHeaderText(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', $value));
}

function isAbsolutePath(string $path): bool
{
    if ($path === '') {
        return false;
    }

    if ($path[0] === '/') {
        return true;
    }

    if (strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/')) {
        return true;
    }

    return false;
}

function resolveClientIp(): string
{
    $cfIp = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if ($cfIp !== '' && filter_var($cfIp, FILTER_VALIDATE_IP)) {
        return $cfIp;
    }

    $xff = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($xff !== '') {
        $parts = explode(',', $xff);
        foreach ($parts as $part) {
            $candidate = trim($part);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }

    $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP)) {
        return $remote;
    }

    return '0.0.0.0';
}

/**
 * @return array{allowed: bool, retryAfter: int}
 */
function enforceRateLimit(string $rateFile, string $key, int $windowSec, int $maxRequests): array
{
    if ($windowSec <= 0 || $maxRequests <= 0) {
        return ['allowed' => true, 'retryAfter' => 0];
    }

    $now = time();
    $fp = @fopen($rateFile, 'c+');
    if ($fp === false) {
        return ['allowed' => true, 'retryAfter' => 0];
    }

    $allowed = true;
    $retryAfter = 0;

    if (@flock($fp, LOCK_EX)) {
        rewind($fp);
        $raw = stream_get_contents($fp);
        $state = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
        if (!is_array($state)) {
            $state = [];
        }

        $cutoff = $now - $windowSec;
        foreach ($state as $bucketKey => $timestamps) {
            if (!is_array($timestamps)) {
                unset($state[$bucketKey]);
                continue;
            }

            $filtered = [];
            foreach ($timestamps as $ts) {
                $tsInt = (int)$ts;
                if ($tsInt >= $cutoff) {
                    $filtered[] = $tsInt;
                }
            }

            if ($filtered === []) {
                unset($state[$bucketKey]);
                continue;
            }

            $state[$bucketKey] = $filtered;
        }

        $bucket = [];
        if (isset($state[$key]) && is_array($state[$key])) {
            $bucket = $state[$key];
        }

        if (count($bucket) >= $maxRequests) {
            $allowed = false;
            $oldest = min($bucket);
            $retryAfter = max(1, $windowSec - ($now - (int)$oldest));
        } else {
            $bucket[] = $now;
            $state[$key] = $bucket;

            $encoded = json_encode($state, JSON_UNESCAPED_SLASHES);
            if (is_string($encoded)) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, $encoded);
            }
        }

        @flock($fp, LOCK_UN);
    }

    fclose($fp);
    return ['allowed' => $allowed, 'retryAfter' => $retryAfter];
}

function verifyTurnstileToken(string $secret, string $token, string $clientIp): bool
{
    if ($secret === '' || $token === '') {
        return false;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    if ($ch === false) {
        return false;
    }

    $body = http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $clientIp,
    ]);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    if (!is_string($response) || trim($response) === '') {
        return false;
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) && (($decoded['success'] ?? false) === true);
}

/**
 * @return array{sent: bool, transport: string, error: string}
 */
function sendMessage(array $env, string $subject, string $body, string $replyEmail, string $replyName): array
{
    $transport = strtolower(envValue($env, 'MAIL_TRANSPORT', 'smtp'));
    $recipient = envValue($env, 'MAIL_TO', 'info@etherr.hr');
    $from = envValue($env, 'MAIL_FROM', 'noreply@etherr.hr');
    $fromName = envValue($env, 'MAIL_FROM_NAME', 'Etherr Website');

    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return ['sent' => false, 'transport' => $transport, 'error' => 'MAIL_TO is not a valid email'];
    }
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return ['sent' => false, 'transport' => $transport, 'error' => 'MAIL_FROM is not a valid email'];
    }

    if ($transport === 'mail') {
        $headers = [
            'From: ' . sanitizeHeaderText($fromName) . ' <' . $from . '>',
            'Reply-To: ' . sanitizeHeaderText($replyName) . ' <' . $replyEmail . '>',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $sent = @mail($recipient, $subject, $body, implode("\r\n", $headers));
        return [
            'sent' => $sent,
            'transport' => 'mail',
            'error' => $sent ? '' : 'mail() returned false',
        ];
    }

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        return ['sent' => false, 'transport' => 'smtp', 'error' => 'Composer autoload not found'];
    }
    require_once $autoload;

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return ['sent' => false, 'transport' => 'smtp', 'error' => 'PHPMailer class not found'];
    }

    $host = envValue($env, 'SMTP_HOST', '');
    $username = envValue($env, 'SMTP_USERNAME', '');
    $password = envValue($env, 'SMTP_PASSWORD', '');
    $port = (int)envValue($env, 'SMTP_PORT', '587');
    $encryption = strtolower(envValue($env, 'SMTP_ENCRYPTION', 'tls'));
    $smtpAuth = envBool($env, 'SMTP_AUTH', true);
    $smtpTimeout = (int)envValue($env, 'SMTP_TIMEOUT', '15');
    $smtpDebug = envBool($env, 'SMTP_DEBUG', false);

    if ($host === '') {
        return ['sent' => false, 'transport' => 'smtp', 'error' => 'SMTP_HOST missing'];
    }
    if ($smtpAuth && ($username === '' || $password === '')) {
        return ['sent' => false, 'transport' => 'smtp', 'error' => 'SMTP credentials missing'];
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port > 0 ? $port : 587;
        $mail->Timeout = $smtpTimeout > 0 ? $smtpTimeout : 15;
        $mail->SMTPAuth = $smtpAuth;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPDebug = $smtpDebug ? 2 : 0;

        if ($encryption === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom($from, $fromName);
        $mail->addAddress($recipient);
        $mail->addReplyTo($replyEmail, $replyName);
        $mail->Subject = $subject;
        $mail->isHTML(false);
        $mail->Body = $body;

        $sent = $mail->send();
        return [
            'sent' => $sent,
            'transport' => 'smtp',
            'error' => $sent ? '' : 'SMTP send returned false',
        ];
    } catch (\Throwable $e) {
        return [
            'sent' => false,
            'transport' => 'smtp',
            'error' => $e->getMessage(),
        ];
    }
}

function appendLog(string $logFile, array $entry): void
{
    $encoded = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        return;
    }
    @file_put_contents($logFile, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$rootDir = dirname(__DIR__);
$env = loadEnvFile($rootDir . '/.env');
$timezone = envValue($env, 'APP_TIMEZONE', 'UTC');
if ($timezone !== '') {
    @date_default_timezone_set($timezone);
}

$allowedOriginsRaw = envValue($env, 'ALLOWED_ORIGINS', '');
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $allowedOriginsRaw))));
$origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($origin !== '' && $allowedOrigins !== []) {
    if (!in_array($origin, $allowedOrigins, true)) {
        respond(403, [
            'ok' => false,
            'errorCode' => 'ORIGIN_NOT_ALLOWED',
            'error' => 'Origin is not allowed',
        ]);
    }
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    respond(204, ['ok' => true]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, [
        'ok' => false,
        'errorCode' => 'METHOD_NOT_ALLOWED',
        'error' => 'Method not allowed',
    ]);
}

$storageDirRaw = envValue($env, 'INTAKE_STORAGE_DIR', 'var');
$storageDir = isAbsolutePath($storageDirRaw) ? $storageDirRaw : $rootDir . '/' . ltrim($storageDirRaw, '/');
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0700, true);
}

$clientIp = resolveClientIp();
$rateWindowSec = (int)envValue($env, 'RATE_LIMIT_WINDOW_SEC', '300');
$rateMaxRequests = (int)envValue($env, 'RATE_LIMIT_MAX_REQUESTS', '8');
$rateFile = rtrim($storageDir, '/') . '/contact-rate-limit.json';
$rate = enforceRateLimit($rateFile, hash('sha256', $clientIp), $rateWindowSec, $rateMaxRequests);
if ($rate['allowed'] === false) {
    header('Retry-After: ' . (string)$rate['retryAfter']);
    respond(429, [
        'ok' => false,
        'errorCode' => 'RATE_LIMITED',
        'error' => 'Too many requests',
        'retryAfter' => $rate['retryAfter'],
    ]);
}

$raw = file_get_contents('php://input');
if (!is_string($raw) || trim($raw) === '') {
    respond(400, [
        'ok' => false,
        'errorCode' => 'EMPTY_BODY',
        'error' => 'Empty request body',
    ]);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(400, [
        'ok' => false,
        'errorCode' => 'INVALID_JSON',
        'error' => 'Invalid JSON payload',
    ]);
}

try {
    $requestId = bin2hex(random_bytes(8));
} catch (\Throwable $e) {
    $requestId = 'req-' . str_replace('.', '', (string)microtime(true));
}

$storeSubmissions = envBool($env, 'STORE_SUBMISSIONS', true);
$logFile = rtrim($storageDir, '/') . '/contact-intake-log.ndjson';
$logRejectedAttempt = static function (string $reason, array $payload) use ($storeSubmissions, $logFile, $clientIp, $requestId): void {
    if (!$storeSubmissions) {
        return;
    }

    appendLog($logFile, [
        'requestId' => $requestId,
        'storedAt' => gmdate('c'),
        'clientIpHash' => hash('sha256', $clientIp),
        'mailSent' => false,
        'mailTransport' => 'none',
        'mailError' => '',
        'rejected' => true,
        'rejectReason' => $reason,
        'payload' => $payload,
    ]);
};

$honeypot = trim((string)($data['honeypot'] ?? ''));
if ($honeypot !== '') {
    $logRejectedAttempt('honeypot_filled', $data);
    respond(200, ['ok' => true, 'status' => 'ignored']);
}

$formTimeMs = (int)($data['form_time'] ?? ($data['formTime'] ?? 0));
if ($formTimeMs < 3000) {
    $logRejectedAttempt('too_fast', $data);
    respond(200, ['ok' => true, 'status' => 'ignored']);
}

$contact = is_array($data['contact'] ?? null) ? $data['contact'] : [];
$project = is_array($data['project'] ?? null) ? $data['project'] : [];
$services = is_array($project['services'] ?? null) ? $project['services'] : [];
$projectType = is_array($project['projectType'] ?? null) ? $project['projectType'] : [];
$timeline = is_array($project['timeline'] ?? null) ? $project['timeline'] : [];

$consent = ($data['consent'] ?? false) === true;
if (!$consent) {
    respond(422, [
        'ok' => false,
        'errorCode' => 'CONSENT_REQUIRED',
        'error' => 'Consent is required',
    ]);
}

$company = trim((string)($contact['company'] ?? ''));
$name = trim((string)($contact['name'] ?? ''));
$email = trim((string)($contact['email'] ?? ''));
$details = trim((string)($project['details'] ?? ''));
$locale = trim((string)($data['locale'] ?? 'hr'));

if ($company === '' || $name === '' || $details === '') {
    respond(422, [
        'ok' => false,
        'errorCode' => 'MISSING_FIELDS',
        'error' => 'Missing required fields',
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, [
        'ok' => false,
        'errorCode' => 'INVALID_EMAIL',
        'error' => 'Invalid email',
    ]);
}

$turnstileSecret = envValue($env, 'TURNSTILE_SECRET_KEY', '');
$turnstileRequired = ($turnstileSecret !== '') && envBool($env, 'TURNSTILE_ENFORCED', true);
if ($turnstileRequired) {
    $turnstileToken = trim((string)($data['turnstileToken'] ?? ''));
    if ($turnstileToken === '') {
        respond(422, [
            'ok' => false,
            'errorCode' => 'TURNSTILE_REQUIRED',
            'error' => 'Security verification is required',
        ]);
    }

    $verified = verifyTurnstileToken($turnstileSecret, $turnstileToken, $clientIp);
    if (!$verified) {
        respond(422, [
            'ok' => false,
            'errorCode' => 'TURNSTILE_FAILED',
            'error' => 'Security verification failed',
        ]);
    }
}

$safeWebsite = trim((string)($contact['website'] ?? ''));
$safePhone = trim((string)($contact['phone'] ?? ''));
$contactMethodLabel = trim((string)($contact['preferredContact']['label'] ?? ($contact['preferredContact']['value'] ?? '')));
$timelineLabel = trim((string)($timeline['label'] ?? ($timeline['value'] ?? '')));
$projectTypeLabel = trim((string)($projectType['label'] ?? ($projectType['value'] ?? '')));

$servicesText = [];
foreach ($services as $entry) {
    if (!is_array($entry)) {
        continue;
    }

    $title = trim((string)($entry['title'] ?? ''));
    $category = trim((string)($entry['category'] ?? ''));
    if ($title === '') {
        continue;
    }

    $servicesText[] = $category !== '' ? sprintf('%s (%s)', $title, $category) : $title;
}

$submittedAt = trim((string)($data['submittedAt'] ?? ''));
$source = is_array($data['source'] ?? null) ? $data['source'] : [];
$sourceUrl = trim((string)($source['url'] ?? ''));
$sourcePage = trim((string)($source['page'] ?? ''));
$sourceReferrer = trim((string)($source['referrer'] ?? ''));

$messageLines = [
    'Etherr contact intake',
    '====================',
    '',
    'Request ID: ' . $requestId,
    'Company: ' . $company,
    'Contact: ' . $name,
    'Email: ' . $email,
    'Phone: ' . ($safePhone !== '' ? $safePhone : '-'),
    'Website: ' . ($safeWebsite !== '' ? $safeWebsite : '-'),
    'Preferred contact: ' . ($contactMethodLabel !== '' ? $contactMethodLabel : '-'),
    'Project type: ' . ($projectTypeLabel !== '' ? $projectTypeLabel : '-'),
    'Timeline: ' . ($timelineLabel !== '' ? $timelineLabel : '-'),
    'Services: ' . (!empty($servicesText) ? implode(', ', $servicesText) : '-'),
    '',
    'Details:',
    $details,
    '',
    'Meta',
    '----',
    'Locale: ' . ($locale !== '' ? $locale : '-'),
    'Submitted at: ' . ($submittedAt !== '' ? $submittedAt : '-'),
    'Page: ' . ($sourcePage !== '' ? $sourcePage : '-'),
    'URL: ' . ($sourceUrl !== '' ? $sourceUrl : '-'),
    'Referrer: ' . ($sourceReferrer !== '' ? $sourceReferrer : '-'),
    'Client IP: ' . $clientIp,
];

$message = implode("\n", $messageLines);
$subject = sprintf('[Etherr] Intake: %s', $company !== '' ? $company : $name);
$mailResult = sendMessage(
    $env,
    $subject,
    $message,
    $email,
    $name
);

if ($storeSubmissions) {
    appendLog($logFile, [
        'requestId' => $requestId,
        'storedAt' => gmdate('c'),
        'clientIpHash' => hash('sha256', $clientIp),
        'mailSent' => $mailResult['sent'],
        'mailTransport' => $mailResult['transport'],
        'mailError' => $mailResult['error'],
        'payload' => $data,
    ]);
}

respond(200, [
    'ok' => true,
    'status' => $mailResult['sent'] ? 'sent' : 'queued',
    'requestId' => $requestId,
]);
