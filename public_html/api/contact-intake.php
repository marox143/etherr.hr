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

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatDisplayDate(string $value, string $timezone): string
{
    if ($value === '') {
        return '-';
    }

    try {
        $date = new DateTimeImmutable($value);
        $displayTimezone = new DateTimeZone($timezone !== '' ? $timezone : 'Europe/Zagreb');
        return $date->setTimezone($displayTimezone)->format('d.m.Y. H:i');
    } catch (\Throwable $e) {
        return $value;
    }
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

function resolveClientCountry(): string
{
    $headers = [
        'HTTP_CF_IPCOUNTRY',
        'HTTP_X_COUNTRY_CODE',
        'GEOIP_COUNTRY_CODE',
        'HTTP_X_APPENGINE_COUNTRY',
    ];

    foreach ($headers as $header) {
        $value = strtoupper(trim((string)($_SERVER[$header] ?? '')));
        if ($value !== '') {
            return $value === 'XX' ? 'Nepoznato' : $value;
        }
    }

    return '-';
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
function sendMessage(
    array $env,
    string $subject,
    string $body,
    string $replyEmail,
    string $replyName,
    string $htmlBody = '',
    string $recipientOverride = ''
): array
{
    $transport = strtolower(envValue($env, 'MAIL_TRANSPORT', 'smtp'));
    $recipient = $recipientOverride !== '' ? $recipientOverride : envValue($env, 'MAIL_TO', 'info@etherr.hr');
    $from = envValue($env, 'MAIL_FROM', 'noreply@etherr.hr');
    $fromName = envValue($env, 'MAIL_FROM_NAME', 'Etherr Website');

    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return ['sent' => false, 'transport' => $transport, 'error' => 'Recipient is not a valid email'];
    }
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return ['sent' => false, 'transport' => $transport, 'error' => 'MAIL_FROM is not a valid email'];
    }

    if ($transport === 'mail') {
        $headers = [
            'From: ' . sanitizeHeaderText($fromName) . ' <' . $from . '>',
            'Reply-To: ' . sanitizeHeaderText($replyName) . ' <' . $replyEmail . '>',
            $htmlBody !== '' ? 'Content-Type: text/html; charset=UTF-8' : 'Content-Type: text/plain; charset=UTF-8',
        ];

        $sent = @mail($recipient, $subject, $htmlBody !== '' ? $htmlBody : $body, implode("\r\n", $headers));
        return [
            'sent' => $sent,
            'transport' => 'mail',
            'error' => $sent ? '' : 'mail() returned false',
        ];
    }

    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
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
        $mail->isHTML($htmlBody !== '');
        $mail->Body = $htmlBody !== '' ? $htmlBody : $body;
        if ($htmlBody !== '') {
            $mail->AltBody = $body;
        }

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

function renderEmailRows(array $rows): string
{
    $html = '';
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $label = escapeHtml((string)($row['label'] ?? ''));
        $value = trim((string)($row['value'] ?? ''));
        $displayValue = $value !== '' ? $value : '-';
        $href = trim((string)($row['href'] ?? ''));
        $valueHtml = escapeHtml($displayValue);
        if ($href !== '' && $value !== '') {
            $valueHtml = '<a href="' . escapeHtml($href) . '" style="color:#0f1720;text-decoration:none;">' . $valueHtml . '</a>';
        }

        $html .= '<tr>'
            . '<td style="padding:9px 12px 9px 0;border-bottom:1px solid #dceaea;color:#118a85;font-size:11px;line-height:16px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;white-space:nowrap;vertical-align:top;">' . $label . '</td>'
            . '<td style="padding:9px 0;border-bottom:1px solid #dceaea;color:#0f1720;font-size:14px;line-height:20px;font-weight:600;vertical-align:top;overflow-wrap:anywhere;word-break:break-word;">' . $valueHtml . '</td>'
            . '</tr>';
    }

    return $html;
}

function appendLog(string $logFile, array $entry): void
{
    $encoded = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        return;
    }
    @file_put_contents($logFile, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$rootDir = dirname(__DIR__, 2);
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
    $title = preg_replace('/\s+/', ' ', $title) ?? $title;
    $category = preg_replace('/\s+/', ' ', $category) ?? $category;
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
$timezone = envValue($env, 'APP_TIMEZONE', 'Europe/Zagreb');
$displaySubmittedAt = formatDisplayDate($submittedAt, $timezone);
$servicesValue = !empty($servicesText) ? implode(', ', $servicesText) : '-';
$phoneHref = $safePhone !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $safePhone) : '';
$websiteHref = '';
if ($safeWebsite !== '') {
    if (filter_var($safeWebsite, FILTER_VALIDATE_URL)) {
        $websiteHref = $safeWebsite;
    } elseif (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}/i', $safeWebsite) === 1) {
        $websiteHref = 'https://' . $safeWebsite;
    }
}
$sourceUrlHref = filter_var($sourceUrl, FILTER_VALIDATE_URL) ? $sourceUrl : '';
$clientCountry = resolveClientCountry();

$messageLines = [
    'Etherr - novi upit s web stranice',
    '================================',
    '',
    'ID upita: ' . $requestId,
    'Tvrtka: ' . $company,
    'Kontakt osoba: ' . $name,
    'E-mail: ' . $email,
    'Telefon: ' . ($safePhone !== '' ? $safePhone : '-'),
    'Web stranica: ' . ($safeWebsite !== '' ? $safeWebsite : '-'),
    'Preferirani kontakt: ' . ($contactMethodLabel !== '' ? $contactMethodLabel : '-'),
    'Vrsta projekta: ' . ($projectTypeLabel !== '' ? $projectTypeLabel : '-'),
    'Rok: ' . ($timelineLabel !== '' ? $timelineLabel : '-'),
    'Usluge: ' . $servicesValue,
    '',
    'Detalji:',
    $details,
    '',
    'Meta',
    '----',
    'Jezik: ' . ($locale !== '' ? $locale : '-'),
    'Poslano: ' . $displaySubmittedAt,
    'Stranica: ' . ($sourcePage !== '' ? $sourcePage : '-'),
    'URL: ' . ($sourceUrl !== '' ? $sourceUrl : '-'),
    'Referrer: ' . ($sourceReferrer !== '' ? $sourceReferrer : '-'),
    'IP adresa: ' . $clientIp,
    'Država: ' . $clientCountry,
];

$message = implode("\n", $messageLines);
$contactRowsHtml = renderEmailRows([
    ['label' => 'Tvrtka', 'value' => $company],
    ['label' => 'Kontakt osoba', 'value' => $name],
    ['label' => 'E-mail', 'value' => $email, 'href' => 'mailto:' . $email],
    ['label' => 'Telefon', 'value' => $safePhone, 'href' => $phoneHref],
    ['label' => 'Web stranica', 'value' => $safeWebsite, 'href' => $websiteHref],
    ['label' => 'Preferirani kontakt', 'value' => $contactMethodLabel],
]);
$projectRowsHtml = renderEmailRows([
    ['label' => 'Vrsta projekta', 'value' => $projectTypeLabel],
    ['label' => 'Rok', 'value' => $timelineLabel],
    ['label' => 'Usluge', 'value' => $servicesValue],
]);
$metaRowsHtml = renderEmailRows([
    ['label' => 'ID upita', 'value' => $requestId],
    ['label' => 'Jezik', 'value' => $locale],
    ['label' => 'Poslano', 'value' => $displaySubmittedAt],
    ['label' => 'Stranica', 'value' => $sourcePage],
    ['label' => 'URL', 'value' => $sourceUrl, 'href' => $sourceUrlHref],
    ['label' => 'Referrer', 'value' => $sourceReferrer],
    ['label' => 'IP adresa', 'value' => $clientIp],
    ['label' => 'Država', 'value' => $clientCountry],
]);
$detailsHtml = nl2br(escapeHtml($details));
$clientLocale = strtolower(substr($locale !== '' ? $locale : 'hr', 0, 2));
if (!in_array($clientLocale, ['hr', 'en', 'de'], true)) {
    $clientLocale = 'hr';
}
$clientCopy = [
    'hr' => [
        'subject' => 'Zaprimili smo vaš upit',
        'message' => 'Zaprimili smo vaš upit. Javit ćemo vam se u najkraćem roku.',
        'summaryTitle' => 'Kratki sažetak',
        'detailsTitle' => 'Detalji upita',
        'company' => 'Tvrtka',
        'name' => 'Kontakt osoba',
        'email' => 'E-mail',
        'phone' => 'Telefon',
        'website' => 'Web stranica',
        'preferredContact' => 'Preferirani kontakt',
        'projectType' => 'Vrsta projekta',
        'timeline' => 'Rok',
        'services' => 'Usluge',
        'slogan' => 'Sva rješenja na jednom mjestu',
        'footerLine' => 'Gradimo i povezujemo sve dijelove vašeg digitalnog sustava.',
    ],
    'en' => [
        'subject' => 'We received your inquiry',
        'message' => 'We have received your inquiry. We will get back to you as soon as possible.',
        'summaryTitle' => 'Short summary',
        'detailsTitle' => 'Inquiry details',
        'company' => 'Company',
        'name' => 'Contact person',
        'email' => 'Email',
        'phone' => 'Phone',
        'website' => 'Website',
        'preferredContact' => 'Preferred contact',
        'projectType' => 'Project type',
        'timeline' => 'Timeline',
        'services' => 'Services',
        'slogan' => 'All solutions in one place',
        'footerLine' => 'We build and connect all parts of your digital setup.',
    ],
    'de' => [
        'subject' => 'Wir haben Ihre Anfrage erhalten',
        'message' => 'Wir haben Ihre Anfrage erhalten. Wir melden uns so schnell wie möglich bei Ihnen.',
        'summaryTitle' => 'Kurze Zusammenfassung',
        'detailsTitle' => 'Details der Anfrage',
        'company' => 'Unternehmen',
        'name' => 'Kontaktperson',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'website' => 'Webseite',
        'preferredContact' => 'Bevorzugter Kontakt',
        'projectType' => 'Projekttyp',
        'timeline' => 'Zeitrahmen',
        'services' => 'Leistungen',
        'slogan' => 'Alle Lösungen an einem Ort',
        'footerLine' => 'Wir entwickeln und verbinden Ihr gesamtes digitales Setup.',
    ],
][$clientLocale];

$clientSummaryRowsHtml = renderEmailRows([
    ['label' => $clientCopy['company'], 'value' => $company],
    ['label' => $clientCopy['name'], 'value' => $name],
    ['label' => $clientCopy['email'], 'value' => $email, 'href' => 'mailto:' . $email],
    ['label' => $clientCopy['phone'], 'value' => $safePhone, 'href' => $phoneHref],
    ['label' => $clientCopy['website'], 'value' => $safeWebsite, 'href' => $websiteHref],
    ['label' => $clientCopy['preferredContact'], 'value' => $contactMethodLabel],
    ['label' => $clientCopy['projectType'], 'value' => $projectTypeLabel],
    ['label' => $clientCopy['timeline'], 'value' => $timelineLabel],
    ['label' => $clientCopy['services'], 'value' => $servicesValue],
]);
$clientPlainLines = [
    $clientCopy['subject'],
    str_repeat('=', strlen($clientCopy['subject'])),
    '',
    $clientCopy['message'],
    '',
    $clientCopy['summaryTitle'],
    '----',
    $clientCopy['company'] . ': ' . ($company !== '' ? $company : '-'),
    $clientCopy['name'] . ': ' . ($name !== '' ? $name : '-'),
    $clientCopy['email'] . ': ' . $email,
    $clientCopy['phone'] . ': ' . ($safePhone !== '' ? $safePhone : '-'),
    $clientCopy['website'] . ': ' . ($safeWebsite !== '' ? $safeWebsite : '-'),
    $clientCopy['preferredContact'] . ': ' . ($contactMethodLabel !== '' ? $contactMethodLabel : '-'),
    $clientCopy['projectType'] . ': ' . ($projectTypeLabel !== '' ? $projectTypeLabel : '-'),
    $clientCopy['timeline'] . ': ' . ($timelineLabel !== '' ? $timelineLabel : '-'),
    $clientCopy['services'] . ': ' . $servicesValue,
    '',
    $clientCopy['detailsTitle'] . ':',
    $details,
];
$clientMessage = implode("\n", $clientPlainLines);
$emailSignatureFooter = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="left" style="border-collapse:collapse;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;color:#0f1720;margin:0;">
  <tr>
    <td valign="middle" style="vertical-align:middle;padding:0 18px 0 0;">
      <a href="https://etherr.hr" style="text-decoration:none;border:0;">
        <img src="https://etherr.hr/assets/images/logo.png" width="128" alt="Etherr" style="display:block;width:128px;height:auto;border:0;outline:none;text-decoration:none;">
      </a>
    </td>
    <td valign="middle" style="vertical-align:middle;padding:0 0 0 18px;border-left:1px solid #dceaea;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;">
        <tr>
          <td style="padding:0 0 6px 0;font-size:10px;line-height:13px;mso-line-height-rule:exactly;font-weight:700;letter-spacing:1.7px;color:#118a85;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;">' . escapeHtml($clientCopy['slogan']) . '</td>
        </tr>
        <tr>
          <td style="padding:16px 0 16px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;">
              <tr>
                <td valign="middle" style="vertical-align:middle;padding:0 0 2px 0;font-size:13px;line-height:17px;mso-line-height-rule:exactly;font-weight:700;color:#14313a;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;">
                  <a href="tel:+385916309013" style="color:#14313a;text-decoration:none;">+385 91 6309 013</a>
                </td>
              </tr>
              <tr>
                <td valign="middle" style="vertical-align:middle;padding:0 0 2px 0;font-size:13px;line-height:17px;mso-line-height-rule:exactly;font-weight:700;color:#14313a;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;">
                  <a href="mailto:info@etherr.hr" style="color:#14313a;text-decoration:none;">info@etherr.hr</a>
                </td>
              </tr>
              <tr>
                <td valign="middle" style="vertical-align:middle;padding:0;font-size:13px;line-height:17px;mso-line-height-rule:exactly;font-weight:700;color:#14313a;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;">
                  <a href="https://www.etherr.hr" style="color:#14313a;text-decoration:none;">www.etherr.hr</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:0;font-size:11px;line-height:15px;mso-line-height-rule:exactly;font-weight:500;letter-spacing:0;color:#118a85;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;">
            ' . escapeHtml($clientCopy['footerLine']) . '
          </td>
        </tr>
      </table>
    </td>
    <td valign="middle" style="vertical-align:middle;padding:0 0 0 18px;">
      <img src="https://etherr.hr/signature/nodes.png" width="160" height="140" alt="" style="display:block;width:160px;height:140px;border:0;outline:none;text-decoration:none;">
    </td>
  </tr>
</table>';
$clientEmailSignatureFooter = $emailSignatureFooter;
$internalEmailSignatureFooter = str_replace(
    [escapeHtml($clientCopy['slogan']), escapeHtml($clientCopy['footerLine'])],
    ['Sva rješenja na jednom mjestu', 'Gradimo i povezujemo sve dijelove vašeg digitalnog sustava.'],
    $emailSignatureFooter
);
$clientHtmlMessage = '<!doctype html>
<html lang="' . escapeHtml($clientLocale) . '">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&amp;display=swap" rel="stylesheet">
  </head>
  <body style="margin:0;padding:0;background:#f7fbfb;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;color:#0f1720;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;background:#f7fbfb;">
      <tr>
        <td align="center" style="padding:28px 14px;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" style="width:100%;max-width:680px;border-collapse:separate;border-spacing:0;background:transparent;border:0;">
            <tr>
              <td style="padding:26px 28px 22px 28px;background:#0f2a2c;border-radius:22px 22px 0 0;">
                <div style="font-size:16px;line-height:24px;font-weight:600;color:#ecf8f8;">' . escapeHtml($clientCopy['message']) . '</div>
              </td>
            </tr>
            <tr>
              <td style="padding:22px 28px 8px 28px;background:#ffffff;">
                <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">' . escapeHtml($clientCopy['summaryTitle']) . '</div>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:8px;">' . $clientSummaryRowsHtml . '</table>
              </td>
            </tr>
            <tr>
              <td style="padding:18px 28px 22px 28px;background:#ffffff;">
                <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">' . escapeHtml($clientCopy['detailsTitle']) . '</div>
                <div style="margin-top:10px;padding:15px 16px;background:#f1f7f7;border:1px solid #dceaea;border-radius:14px;color:#14313a;font-size:14px;line-height:22px;font-weight:500;overflow-wrap:anywhere;word-break:break-word;">' . $detailsHtml . '</div>
              </td>
            </tr>
            <tr>
              <td align="left" style="padding:22px 28px 24px 28px;background:#f1f7f7;border-top:1px solid #dceaea;border-radius:0 0 22px 22px;">
                ' . $clientEmailSignatureFooter . '
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>';
$htmlMessage = '<!doctype html>
<html lang="hr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&amp;display=swap" rel="stylesheet">
  </head>
  <body style="margin:0;padding:0;background:#f7fbfb;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;color:#0f1720;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;background:#f7fbfb;">
      <tr>
        <td align="center" style="padding:28px 14px;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" style="width:100%;max-width:680px;border-collapse:separate;border-spacing:0;background:transparent;border:0;">
            <tr>
              <td style="padding:22px 28px 8px 28px;background:#ffffff;border-radius:22px 22px 0 0;">
                <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">Kontakt podaci</div>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:8px;">' . $contactRowsHtml . '</table>
              </td>
            </tr>
            <tr>
              <td style="padding:18px 28px 8px 28px;background:#ffffff;">
                <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">Projekt</div>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:8px;">' . $projectRowsHtml . '</table>
              </td>
            </tr>
            <tr>
              <td style="padding:18px 28px 8px 28px;background:#ffffff;">
                <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">Detalji upita</div>
                <div style="margin-top:10px;padding:15px 16px;background:#f1f7f7;border:1px solid #dceaea;border-radius:14px;color:#14313a;font-size:14px;line-height:22px;font-weight:500;overflow-wrap:anywhere;word-break:break-word;">' . $detailsHtml . '</div>
              </td>
            </tr>
            <tr>
              <td style="padding:18px 28px 22px 28px;background:#ffffff;">
                <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">Tehnički podaci</div>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:8px;">' . $metaRowsHtml . '</table>
              </td>
            </tr>
            <tr>
              <td align="left" style="padding:22px 28px 24px 28px;background:#f1f7f7;border-top:1px solid #dceaea;border-radius:0 0 22px 22px;">
                ' . $internalEmailSignatureFooter . '
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>';
$subject = sprintf('[Etherr] Upit: %s', sanitizeHeaderText($company !== '' ? $company : $name));
$mailResult = sendMessage(
    $env,
    $subject,
    $message,
    $email,
    $name,
    $htmlMessage
);
$clientMailResult = sendMessage(
    $env,
    '[Etherr] ' . $clientCopy['subject'],
    $clientMessage,
    envValue($env, 'MAIL_TO', 'info@etherr.hr'),
    envValue($env, 'MAIL_FROM_NAME', 'Etherr'),
    $clientHtmlMessage,
    $email
);

if ($storeSubmissions) {
    appendLog($logFile, [
        'requestId' => $requestId,
        'storedAt' => gmdate('c'),
        'clientIpHash' => hash('sha256', $clientIp),
        'mailSent' => $mailResult['sent'],
        'mailTransport' => $mailResult['transport'],
        'mailError' => $mailResult['error'],
        'clientMailSent' => $clientMailResult['sent'],
        'clientMailTransport' => $clientMailResult['transport'],
        'clientMailError' => $clientMailResult['error'],
        'payload' => $data,
    ]);
}

respond(200, [
    'ok' => true,
    'status' => $mailResult['sent'] ? 'sent' : 'queued',
    'requestId' => $requestId,
]);
