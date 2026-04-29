<?php

declare(strict_types=1);

const ETHERR_AI_PREFIX = 'etherr_ai_';
const ETHERR_AI_COOKIE = 'etherr_ai_session';
const ETHERR_AI_ADMIN_COOKIE = 'etherr_ai_admin';

function ai_root_dir(): string
{
    return dirname(__DIR__, 3);
}

function ai_load_env(): array
{
    static $env = null;
    if (is_array($env)) {
        return $env;
    }

    $env = [];
    $path = ai_root_dir() . '/.env';
    if (!is_readable($path)) {
        return $env;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $env;
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

        $env[$key] = $value;
    }

    return $env;
}

function ai_env(string $key, string $default = ''): string
{
    $env = ai_load_env();
    if (array_key_exists($key, $env)) {
        return trim((string)$env[$key]);
    }
    $value = getenv($key);
    return is_string($value) && trim($value) !== '' ? trim($value) : $default;
}

function ai_json_response(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ai_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function ai_client_ip(): string
{
    $candidates = [
        (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
        (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
        (string)($_SERVER['REMOTE_ADDR'] ?? ''),
    ];
    foreach ($candidates as $candidate) {
        foreach (explode(',', $candidate) as $part) {
            $ip = trim($part);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function ai_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function ai_cookie_options(int $expires = 0): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function ai_session_cookie_options(int $lifetime = 0): array
{
    return [
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function ai_get_db(): mysqli
{
    static $db = null;
    if ($db instanceof mysqli) {
        return $db;
    }

    $host = ai_env('DB_HOST', ai_env('MYSQL_HOST', 'localhost'));
    $name = ai_env('DB_NAME', ai_env('MYSQL_DATABASE'));
    $user = ai_env('DB_USER', ai_env('MYSQL_USER'));
    $pass = ai_env('DB_PASSWORD', ai_env('MYSQL_PASSWORD'));
    $port = (int)ai_env('DB_PORT', '3306');
    $socket = ai_env('DB_SOCKET');

    if ($name === '' || $user === '') {
        throw new RuntimeException('Assistant database credentials are not configured.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = $socket !== ''
        ? new mysqli($host, $user, $pass, $name, $port, $socket)
        : new mysqli($host, $user, $pass, $name, $port);
    $db->set_charset('utf8mb4');
    return $db;
}

function ai_now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function ai_ensure_schema(): void
{
    $db = ai_get_db();
    $charset = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $db->query('CREATE TABLE IF NOT EXISTS ' . ETHERR_AI_PREFIX . "settings (
        setting_key varchar(100) NOT NULL,
        setting_json longtext NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (setting_key)
    ) $charset");

    $db->query('CREATE TABLE IF NOT EXISTS ' . ETHERR_AI_PREFIX . "sessions (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        session_uuid char(36) NOT NULL,
        current_conversation_id bigint unsigned DEFAULT NULL,
        client_ip_hash char(64) DEFAULT NULL,
        user_agent_hash char(64) DEFAULT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        last_activity_at datetime NOT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY session_uuid (session_uuid),
        KEY is_active (is_active),
        KEY last_activity_at (last_activity_at)
    ) $charset");

    $db->query('CREATE TABLE IF NOT EXISTS ' . ETHERR_AI_PREFIX . "conversations (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        session_uuid char(36) NOT NULL,
        status varchar(32) NOT NULL DEFAULT 'active',
        locale varchar(8) NOT NULL DEFAULT 'hr',
        started_at datetime NOT NULL,
        ended_at datetime DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY session_uuid (session_uuid),
        KEY status (status),
        KEY started_at (started_at)
    ) $charset");

    $db->query('CREATE TABLE IF NOT EXISTS ' . ETHERR_AI_PREFIX . "messages (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        conversation_id bigint unsigned NOT NULL,
        role varchar(32) NOT NULL,
        message_text longtext NOT NULL,
        token_estimate int DEFAULT NULL,
        metadata_json longtext DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY conversation_id (conversation_id),
        KEY role (role),
        KEY created_at (created_at)
    ) $charset");

    $db->query('CREATE TABLE IF NOT EXISTS ' . ETHERR_AI_PREFIX . "intakes (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        conversation_id bigint unsigned NOT NULL,
        status varchar(32) NOT NULL DEFAULT 'active',
        locale varchar(8) NOT NULL DEFAULT 'hr',
        state_json longtext NOT NULL,
        request_id varchar(64) DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        submitted_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY conversation_id (conversation_id),
        KEY status (status),
        KEY updated_at (updated_at)
    ) $charset");

    $db->query('CREATE TABLE IF NOT EXISTS ' . ETHERR_AI_PREFIX . "logs (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        event_type varchar(100) NOT NULL,
        severity varchar(20) NOT NULL DEFAULT 'info',
        payload_json longtext DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY event_type (event_type),
        KEY severity (severity),
        KEY created_at (created_at)
    ) $charset");

    ai_seed_defaults();
}

function ai_default_settings(string $key): array
{
    $businessContext = "Etherr je tehnički digitalni studio za web stranice, sustave, automatizaciju, AI/LLM integracije, marketing, analitiku i konzalting.\n\nGrupe usluga:\n1. Digitalne platforme: izrada web stranica, digitalna rješenja.\n2. Marketing i rast: digitalni marketing, sadržaj i kreativa, SEO i AI optimizacija.\n3. Automatizacija i AI: automatizacija procesa, AI i LLM integracije.\n4. Podaci i konzalting: podaci i izvještavanje, IT konzalting.\n\nKoristi ovaj kontekst za odgovaranje o Etherr uslugama, kvalificiranje projektnih potreba i usmjeravanje korisnika prema kontakt formi. Ne izmišljaj cijene, rokove, garancije, veličinu tima, privatne podatke klijenata ili nedostupne studije slučaja.";

    $defaults = [
        'chat' => [
            'assistant_display_name' => 'Etherr AI',
            'default_language' => 'hr',
            'welcome_message' => [
                'hr' => 'Bok, ja sam Etherr AI asistent. Mogu vam pomoći razjasniti ideju i vidjeti što ima najviše smisla za vas. Ako još niste sigurni, vodim vas kroz to korak po korak. Imate li već nešto ili krećemo od nule?',
                'en' => "Hi, I'm the Etherr AI assistant. I can help you clarify your idea and see what makes the most sense for you. If you're not sure yet, I'll guide you through it step by step. Do you already have something in mind, or shall we start from scratch?",
                'de' => 'Hallo, ich bin der Etherr KI-Assistent. Ich kann Ihnen helfen, Ihre Idee zu konkretisieren und zu sehen, was für Sie am meisten Sinn ergibt. Wenn Sie noch nicht sicher sind, führe ich Sie Schritt für Schritt durch den Prozess. Haben Sie schon etwas Konkretes oder fangen wir bei null an?',
            ],
            'input_placeholder' => [
                'hr' => 'Opišite projekt ili pitanje...',
                'en' => 'Describe your project or question...',
                'de' => 'Beschreiben Sie Ihr Projekt oder Ihre Frage...',
            ],
            'unavailable_message' => [
                'hr' => 'AI asistent trenutno nije dostupan. Pošaljite upit kroz kontakt formu i javit ćemo se.',
                'en' => 'The AI assistant is currently unavailable. Please use the contact form and we will get back to you.',
                'de' => 'Der KI-Assistent ist derzeit nicht verfügbar. Bitte nutzen Sie das Kontaktformular, wir melden uns.',
            ],
            'max_history_window' => 10,
            'anonymous_session_ttl' => 86400,
        ],
        'prompt' => [
            'system_prompt' => "You are Etherr AI, a concise technical sales and consulting assistant for etherr.hr.\n\nGoals:\n- Explain Etherr services clearly and practically.\n- Help users understand what a technical solution is, how it works at a high level, and why it may matter for their business.\n- Ask useful qualifying questions about business goals, current setup, timeline, integrations, budget sensitivity and success criteria.\n- Recommend the most relevant Etherr service category when enough information is available.\n- When the user is ready to take the next step, always present both contact options with a clear explanation of each.\n\nContact handoff — TWO OPTIONS:\nWhen the user wants to get in touch, request a quote, book a call, speak to a human, or take any next step, always present both buttons together with a clear explanation:\n1. 'Pošaljite upit kroz chat' — the user can fill in their details directly in this chat. Explain that this is a quick guided form inside the chat.\n2. 'Pošaljite upit kroz kontakt formu' — opens the full contact form on the website.\n\nDATA COLLECTION RULES — STRICTLY ENFORCED:\n- NEVER start asking for any personal data (name, email, phone, company, project details for submission) unless the user has explicitly clicked the 'Pošaljite upit kroz chat' button.\n- If the user asks you to collect their data, take their details, or says something like 'you can take my data' or 'go ahead' WITHOUT having clicked the button, you must explain that due to data protection regulations (GDPR) you need their explicit confirmation by clicking the 'Pošaljite upit kroz chat' button first. Then show the button.\n- Only after the button click may you proceed with the guided intake steps.\n- Asking qualifying questions about the project (what they need, timeline, budget) is fine at any time — that is not data collection. Data collection means asking for name, email, phone, company name for the purpose of submitting an inquiry.\n\nRules:\n- Match the user's language when possible: Croatian, English or German.\n- Be practical, calm and specific.\n- Keep answers under 140 words unless the user asks for detail.\n- Ask at most one or two questions per answer.\n- Do not claim exact prices, deadlines or availability.\n- Do not provide technical tutorials, code, setup steps, configuration instructions, deployment recipes or implementation checklists for work that Etherr provides as a service.\n- If the user asks how to implement something in Etherr's service scope, give a brief conceptual explanation, mention key considerations, and suggest using the contact form so Etherr can review the situation and propose the right solution.\n- If asked for topics unrelated to Etherr services, briefly set a boundary and return to the user's project or business need.\n- NEVER use markdown formatting: no bold (**), no italic (*), no headers (#), no bullet points (- or *), no numbered lists. Write everything in plain conversational sentences and paragraphs only.\n- Always respond entirely in the same language the user is writing in. If the user writes in Croatian, respond fully in Croatian including all service names and technical terms. Never mix languages in a single response.",
            'business_context' => $businessContext,
        ],
        'model' => [
            'model_name' => 'gpt-5.4-mini',
            'timeout' => 45,
            'retry_count' => 1,
            'retry_backoff_ms' => 700,
        ],
        'intake' => [
            'enabled' => true,
        ],
        'actions' => [
            'items' => [
                [
                    'id' => 'contact',
                    'enabled' => true,
                    'url' => '/#contact',
                    'label' => [
                        'hr' => 'Pošaljite upit kroz kontakt formu',
                        'en' => 'Send inquiry via contact form',
                        'de' => 'Anfrage über Kontaktformular senden',
                    ],
                    'description' => 'Use when the user asks for contact, pricing, next steps, a quote, a meeting, or when their project request is actionable.',
                ],
                [
                    'id' => 'projects',
                    'enabled' => true,
                    'url' => '/projekti.html',
                    'label' => [
                        'hr' => 'Pogledajte projekte',
                        'en' => 'View projects',
                        'de' => 'Projekte ansehen',
                    ],
                    'description' => 'Use when the user wants examples, references, portfolio work, or asks what Etherr has built.',
                ],
                [
                    'id' => 'about',
                    'enabled' => true,
                    'url' => '/about.html',
                    'label' => [
                        'hr' => 'O Etherru',
                        'en' => 'About Etherr',
                        'de' => 'Über Etherr',
                    ],
                    'description' => 'Use when the user asks who Etherr is, how Etherr works, or wants to learn about the studio.',
                ],
                [
                    'id' => 'services',
                    'enabled' => true,
                    'url' => '/#services',
                    'label' => [
                        'hr' => 'Usluge',
                        'en' => 'Services',
                        'de' => 'Leistungen',
                    ],
                    'description' => 'Use when the user wants to compare Etherr service categories or asks what Etherr can do.',
                ],
                [
                    'id' => 'project_keef',
                    'enabled' => true,
                    'url' => '/projekti.html#project-keef',
                    'label' => [
                        'hr' => 'QR menu projekt',
                        'en' => 'QR menu project',
                        'de' => 'QR-Menü-Projekt',
                    ],
                    'description' => 'Use for QR menus, hospitality, restaurants, bars, digital price lists, ordering flows, and mobile-first menus.',
                ],
                [
                    'id' => 'project_keepgoing',
                    'enabled' => true,
                    'url' => '/projekti.html#project-keepgoing',
                    'label' => [
                        'hr' => 'AI asistent projekt',
                        'en' => 'AI assistant project',
                        'de' => 'KI-Assistent-Projekt',
                    ],
                    'description' => 'Use for AI assistants, chatbot flows, guided intake, support journeys, and content-rich service websites.',
                ],
                [
                    'id' => 'project_reservation',
                    'enabled' => true,
                    'url' => '/projekti.html#project-reservation',
                    'label' => [
                        'hr' => 'Rezervacijski sustav',
                        'en' => 'Reservation system',
                        'de' => 'Reservierungssystem',
                    ],
                    'description' => 'Use for bookings, calendars, appointments, staff shifts, service teams, availability, and reservation workflows.',
                ],
                [
                    'id' => 'project_juvy',
                    'enabled' => true,
                    'url' => '/projekti.html#project-juvy',
                    'label' => [
                        'hr' => 'Webshop projekt',
                        'en' => 'Webshop project',
                        'de' => 'Webshop-Projekt',
                    ],
                    'description' => 'Use for webshops, ecommerce, product sales, online stores, payments, and growth-oriented shop platforms.',
                ],
                [
                    'id' => 'project_almagea',
                    'enabled' => true,
                    'url' => '/projekti.html#project-almagea',
                    'label' => [
                        'hr' => 'Website projekt',
                        'en' => 'Website project',
                        'de' => 'Website-Projekt',
                    ],
                    'description' => 'Use for company websites, educational programs, presentation sites, content structure, and brand-oriented web presence.',
                ],
                [
                    'id' => 'project_dfa',
                    'enabled' => true,
                    'url' => '/projekti.html#project-dfa',
                    'label' => [
                        'hr' => 'Akademija projekt',
                        'en' => 'Academy project',
                        'de' => 'Akademie-Projekt',
                    ],
                    'description' => 'Use for education platforms, academies, program presentation, training, and structured content websites.',
                ],
                [
                    'id' => 'project_ripple',
                    'enabled' => true,
                    'url' => '/projekti.html#project-ripple',
                    'label' => [
                        'hr' => 'Dashboard projekt',
                        'en' => 'Dashboard project',
                        'de' => 'Dashboard-Projekt',
                    ],
                    'description' => 'Use for dashboards, reporting, analytics, project tracking, stakeholder systems, and data-oriented portals.',
                ],
            ],
        ],
    ];

    return $defaults[$key] ?? [];
}

function ai_seed_defaults(): void
{
    foreach (['chat', 'prompt', 'model', 'intake', 'actions'] as $key) {
        if (ai_get_setting($key, null) === null) {
            ai_save_setting($key, ai_default_settings($key));
        }
    }
}

function ai_get_setting(string $key, ?array $default = []): ?array
{
    $db = ai_get_db();
    $stmt = $db->prepare('SELECT setting_json FROM ' . ETHERR_AI_PREFIX . 'settings WHERE setting_key = ? LIMIT 1');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return $default;
    }
    $decoded = json_decode((string)$row['setting_json'], true);
    return is_array($decoded) ? array_replace_recursive(ai_default_settings($key), $decoded) : $default;
}

function ai_save_setting(string $key, array $value): void
{
    $db = ai_get_db();
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $now = ai_now();
    $stmt = $db->prepare('INSERT INTO ' . ETHERR_AI_PREFIX . 'settings (setting_key, setting_json, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_json = VALUES(setting_json), updated_at = VALUES(updated_at)');
    $stmt->bind_param('sss', $key, $json, $now);
    $stmt->execute();
}

function ai_normalize_action_url(string $url): string
{
    $url = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $url) ?? '');
    $legacyProjectAnchors = [
        '/projekti.html#project-keef-title' => '/projekti.html#project-keef',
        '/projekti.html#project-keepgoing-title' => '/projekti.html#project-keepgoing',
        '/projekti.html#project-reservation-title' => '/projekti.html#project-reservation',
        '/projekti.html#project-juvy-title' => '/projekti.html#project-juvy',
        '/projekti.html#project-almagea-title' => '/projekti.html#project-almagea',
        '/projekti.html#project-dfa-title' => '/projekti.html#project-dfa',
        '/projekti.html#project-reservation-title-copy' => '/projekti.html#project-ripple',
    ];
    if (isset($legacyProjectAnchors[$url])) {
        return $legacyProjectAnchors[$url];
    }
    if ($url === '' || str_starts_with($url, '//')) {
        return '';
    }
    if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
        return $url;
    }
    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }
    if (preg_match('/^[A-Za-z0-9._~\/?#\[\]@!$&\'()*+,;=%-]+$/', $url)) {
        return '/' . ltrim($url, '/');
    }
    return '';
}

function ai_normalize_action_items(array $settings): array
{
    $defaults = ai_default_settings('actions')['items'] ?? [];
    $savedItems = $settings['items'] ?? [];
    $savedById = [];
    if (is_array($savedItems)) {
        foreach ($savedItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (string)($item['id'] ?? '');
            if (preg_match('/^[a-z0-9_]+$/', $id)) {
                $savedById[$id] = $item;
            }
        }
    }

    $items = [];
    foreach ($defaults as $default) {
        if (!is_array($default)) {
            continue;
        }
        $id = (string)($default['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $saved = $savedById[$id] ?? [];
        $defaultLabel = is_array($default['label'] ?? null) ? $default['label'] : [];
        $label = $defaultLabel;
        if (is_array($saved['label'] ?? null)) {
            $label = array_replace($label, $saved['label']);
        }
        $labelHr = trim((string)($label['hr'] ?? ''));
        $labelEn = trim((string)($label['en'] ?? ''));
        $labelDe = trim((string)($label['de'] ?? ''));
        $description = trim((string)($saved['description'] ?? ''));
        if ($description === '') {
            $description = trim((string)($default['description'] ?? ''));
        }
        $items[] = [
            'id' => $id,
            'enabled' => array_key_exists('enabled', $saved) ? (bool)$saved['enabled'] : (bool)($default['enabled'] ?? true),
            'url' => ai_normalize_action_url((string)($saved['url'] ?? $default['url'] ?? '')),
            'label' => [
                'hr' => $labelHr !== '' ? $labelHr : trim((string)($defaultLabel['hr'] ?? $id)),
                'en' => $labelEn !== '' ? $labelEn : trim((string)($defaultLabel['en'] ?? $id)),
                'de' => $labelDe !== '' ? $labelDe : trim((string)($defaultLabel['de'] ?? $id)),
            ],
            'description' => $description,
        ];
    }
    return $items;
}

function ai_enabled_action_map(?array $settings = null): array
{
    $settings = $settings ?? (ai_get_setting('actions') ?? ai_default_settings('actions'));
    $actions = [];
    foreach (ai_normalize_action_items($settings) as $item) {
        if (!($item['enabled'] ?? false) || (string)($item['url'] ?? '') === '') {
            continue;
        }
        $actions[(string)$item['id']] = $item;
    }
    return $actions;
}

function ai_action_prompt_context(string $locale): string
{
    $locale = ai_normalize_locale($locale);
    $actions = ai_enabled_action_map();
    if (!$actions) {
        return "No assistant action buttons are currently enabled.";
    }

    $lines = [
        "Available predefined action buttons. You may suggest at most two when useful. Use only these IDs:",
    ];
    foreach ($actions as $id => $action) {
        $lines[] = "- " . $id . ": " . ai_localized($action['label'], $locale) . " -> " . (string)$action['url'] . ". " . (string)$action['description'];
    }
    $lines[] = "To request buttons, append one final hidden control line exactly like [[ETHERR_ACTIONS:contact,projects]] using allowed IDs only.";
    $lines[] = "If the user explicitly asks for a link, button, page, form, project, example, portfolio item, contact option, or asks to open or see something that matches an action, always append the matching action ID.";
    $lines[] = "Do not mention this control line to the user. Do not invent URLs or render raw links in the visible answer; the site will show the buttons.";
    $lines[] = "IMPORTANT: You must ALWAYS write a substantive explanation before any button. The explanation must: describe what the button leads to, why it is relevant to the user's specific situation, and what they can expect next. A button must never appear as the only content or the first thing in a response. If you have nothing meaningful to say before a button, do not show the button at all.";
    $lines[] = "When you add a button, do not ask permission to show, send, open or share the link. Avoid phrases like 'Ako želite, mogu...', 'If you want, I can...', and 'Wenn Sie möchten, kann ich...'. The button is already being provided, so phrase the response as a direct helpful handoff.";
    $lines[] = "Prefer contact when the user asks to contact Etherr, asks for the contact form, asks for pricing, wants next steps, wants a quote or meeting, describes an actionable project, asks how to get started, asks to speak to someone, asks for a call, asks for an email address, or expresses any intent to reach a human. When showing contact options, always show BOTH the intake_start button (in-chat form) AND the contact button (website form), and explain what each one does. Never start collecting data unless the user clicked the intake_start button.";
    $lines[] = "Prefer projects when the user asks for examples, references, portfolio work, or project links.";
    $lines[] = "Prefer a specific project action when the user's need or keywords closely match that project, even if they use broad terms rather than the exact project name.";
    return implode("\n", $lines);
}

function ai_faq_prompt_context(): string
{
    $lines = [
        "Approved FAQ reference knowledge. Keep answers consistent with this information.",
        "- If the user asks about pricing, timing, availability or monthly costs, you may use the ranges below as indicative guidance only. Always say they depend on scope and are not a final quote or commitment.",
        "- If the user asks a question that is covered by this FAQ knowledge, answer directly and confidently instead of staying too generic.",
        "",
        "General:",
        "- Etherr combines web development, webshops, automation, AI integrations, digital marketing, SEO, analytics, reporting and consulting into connected systems.",
        "- Typical clients are small and medium-sized companies, founders and startups who need more than a basic website.",
        "- Etherr works with clients across Croatia, the region and international markets. Communication can be in Croatian, English or German. Projects can be run fully online.",
        "- Etherr aims to provide multiple connected services in one place instead of splitting web, marketing, automation and AI across separate vendors.",
        "- Users can contact Etherr through the website contact form or by email at info@etherr.hr. The assistant can help structure the inquiry first.",
        "",
        "Websites:",
        "- A simple presentation website starts from 800 EUR. More complex websites with custom functionality usually start around 2,000 EUR and up.",
        "- Standard website delivery is usually 2 to 4 weeks. More complex projects can take around 4 to 8 weeks depending on scope and how quickly content is delivered.",
        "- WordPress is used when the client wants easy content editing, such as blogs, portfolios and smaller webshops. Custom solutions are used when the project needs specific functionality, better performance or deeper integrations.",
        "- Standard website scope usually includes design and development, responsive layout, basic SEO, SSL and security setup, Google Analytics connection and admin training. Hosting and domain are usually billed separately.",
        "- Etherr can also help with content structure, copywriting support, image selection and AI-assisted drafts when needed.",
        "",
        "Webshops:",
        "- A webshop is usually the right fit when the client sells physical products, has more than roughly 10 products, needs online payment or wants automated order handling. A standard website may be enough for services, a very small offer or manual orders via form or email.",
        "- A basic WooCommerce webshop starts from 1,500 EUR. More advanced webshops with custom functionality, payment integrations or advanced filters usually fall in the 3,000 to 6,000 EUR range.",
        "- Etherr most often builds webshops on WooCommerce. Etherr does not build on Shopify, but can help with migration from Shopify to WooCommerce.",
        "- Common payment integrations include CorvusPay, Stripe, PayPal and Monri, as well as cash on delivery and bank transfer.",
        "- Delivery logic can include fixed shipping by zone, free shipping thresholds, courier integrations such as GLS, DPD or HP, shipment tracking and simple order-status management in admin.",
        "- Multilingual webshops are supported, most often through WPML for WordPress and WooCommerce. This can include translated products and pages, market-specific pricing and automatic language detection.",
        "",
        "AI and automation:",
        "- An AI chatbot can answer common questions, guide visitors through services, collect contact details and qualify leads before human follow-up.",
        "- A basic AI chatbot implementation starts from 1500 EUR. Price depends on conversation complexity, amount of source content, integrations and API usage. We build exclusively with the best models and providers (OpenAI), guaranteeing the highest quality and natural responses.",
        "- Typical monthly AI API cost for most websites is around 10 to 50 EUR, depending on traffic and usage.",
        "- Automation can cover follow-up emails, data transfer between tools, reporting, lead alerts and stock or order updates.",
        "- Common integrations can include CRM systems such as HubSpot or Pipedrive, email tools such as Mailchimp or Brevo, helpdesk systems such as Zendesk or Freshdesk, and internal databases or APIs.",
        "- Etherr uses established providers such as OpenAI and Anthropic, avoids sending sensitive data unnecessarily, implements access control and works with GDPR considerations. For highly sensitive cases, local models can also be considered.",
        "- Solutions are designed for non-technical users. Etherr can provide simple admin tools, training, documentation and support.",
        "",
        "Marketing, SEO and tracking:",
        "- SEO work can include technical optimization for speed, structure and mobile, content optimization, structured data, internal linking and optimization for AI-driven search and answer engines.",
        "- SEO usually needs 2 to 3 months for early signals and 6 months or more for stronger results, depending on competition, website condition, content quality and domain authority.",
        "- Etherr can manage Google Ads and Meta advertising, including conversion tracking, A/B testing and optimization.",
        "- GEO means optimization for AI systems such as ChatGPT, Perplexity and Google AI Overviews through clear structure, semantic relevance and FAQ-style content that is easy for AI systems to understand and cite.",
        "- Tracking and reporting can include Google Analytics 4, Google Search Console, conversion tracking, Meta Pixel and custom KPI dashboards when needed.",
        "",
        "Process and collaboration:",
        "- Etherr's standard process has 5 stages: discovery, proposal, build, testing, then launch and support.",
        "- Clients are usually involved in goal-setting, design feedback, content delivery and final review, but the level of day-to-day involvement can be adjusted.",
        "- Communication can happen through email, video calls and chat tools such as WhatsApp or Slack. Shared progress spaces can be set up for larger projects.",
        "- Work is iterative. Feedback is collected in phases and revisions are included so problems are caught early instead of only at the end.",
        "- Most projects are designed to expand later with new features, new integrations, new markets, AI or automation layers.",
        "",
        "Pricing, timing and commercial terms:",
        "- Etherr typically works with fixed-scope offers based on scope, complexity and required time. There should be no hidden costs. If scope changes, the terms are re-aligned before continuing.",
        "- Standard payment is usually 50 percent upfront and 50 percent before launch. Larger projects can be split into milestone-based payments. Bank transfer is standard, and Wise or PayPal may be possible for international clients.",
        "- Possible ongoing costs can include hosting around 10 to 50 EUR per month, domain around 10 to 20 EUR per year, maintenance from 50 EUR per month and AI API usage depending on traffic.",
        "- Better commercial terms may be possible for larger projects or long-term collaboration.",
        "- Etherr can often start within 1 to 2 weeks, but larger projects are better booked earlier. Urgent projects may be accelerated for an additional fee if capacity allows.",
        "",
        "Technical implementation and ownership:",
        "- Common stack choices include WordPress, custom PHP, HTML, CSS and JavaScript, WooCommerce, OpenAI API, Make, Zapier, custom scripts, cPanel hosting and cloud hosting.",
        "- Performance work usually includes image optimization, caching, compression, code minimization, lazy loading and CDN where useful. The goal is generally strong Core Web Vitals performance.",
        "- Security measures usually include SSL, regular updates, strong passwords, 2FA where appropriate, backups, firewalls and brute-force protection.",
        "- What Etherr builds belongs to the client. Depending on the project, the client can receive hosting access, site admin access, source code for custom work and documentation.",
        "- If something breaks, maintenance clients are handled under the agreed support terms. Without maintenance, repair work is typically billed hourly. Critical outages are treated as priority cases.",
        "",
        "Maintenance and support:",
        "- Maintenance packages start from 50 EUR per month and can include WordPress or plugin updates, security checks, backups, minor content changes and technical support.",
        "- Without a maintenance package, the client is usually responsible for updates, repairs are billed hourly and there is no guaranteed response time.",
        "- Typical response expectations are around 24 hours on working days with maintenance, 48 to 72 hours without maintenance, and same-day response when possible for urgent issues.",
        "- Clients can usually edit content themselves through admin tools, including text, images, pages, posts and webshop products. Technical support covers usage questions, technical issues, optimization advice and help with integrating new tools. New feature development is usually quoted separately.",
        "",
        "Project examples that can be referenced when relevant:",
        "- Keef Bar: custom mobile QR menu and hospitality workflow.",
        "- Almagea: WooCommerce webshop with CorvusPay and extended logic.",
        "- Juvy Skin: multilingual WooCommerce webshop with clean product-focused UX.",
        "- Keep Going: AI assistant and guided support journey.",
        "- Ripple: reporting and project dashboard example.",
    ];

    return implode("\n", $lines);
}

function ai_action_keyword_text(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, [
        'č' => 'c',
        'ć' => 'c',
        'š' => 's',
        'ž' => 'z',
        'đ' => 'd',
        'ä' => 'a',
        'ö' => 'o',
        'ü' => 'u',
        'ß' => 'ss',
    ]);
    $text = preg_replace('/[^a-z0-9#\/._~?=&%+-]+/', ' ', $text) ?? $text;
    return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
}

function ai_action_keyword_rules(): array
{
    return [
        'contact' => [
            'strong' => ['kontakt forma', 'kontakt formu', 'contact form', 'kontaktformular', 'kontakt', 'contact', 'kontaktirati', 'posalji upit', 'send inquiry', 'anfrage', 'upit', 'quote', 'ponuda', 'ponudu', 'angebot', 'cijena', 'cijenu', 'cijene', 'pricing', 'price', 'preis', 'preise', 'trosak', 'trošak', 'sastanak', 'meeting', 'termin', 'call', 'poziv', 'email', 'mail'],
            'weak' => ['next step', 'sljedeci korak', 'sljedeci koraci', 'javite se', 'get in touch', 'book a call', 'besprechung'],
        ],
        'project_keepgoing' => [
            'strong' => ['keepgoing', 'keep going'],
            'weak' => ['ai asistent', 'ai assistant', 'ki asistent', 'chatbot', 'chat bot', 'guided intake', 'intake', 'support journey', 'virtualni asistent'],
        ],
        'project_reservation' => [
            'strong' => ['reservation', 'rezervacijski', 'rezervacija', 'rezervacije', 'booking', 'bookings', 'reservierung', 'buchung'],
            'weak' => ['calendar', 'kalendar', 'appointment', 'termin', 'staff shift', 'shift', 'smjena', 'availability', 'dostupnost', 'verfugbarkeit'],
        ],
        'project_keef' => [
            'strong' => ['keef', 'qr menu', 'qr meni'],
            'weak' => ['qr', 'digital menu', 'digitalni menu', 'digitalni meni', 'cjenik', 'price list', 'speisekarte', 'restaurant', 'restoran', 'bar', 'hospitality', 'gastronomie'],
        ],
        'project_juvy' => [
            'strong' => ['juvy', 'webshop', 'web shop', 'ecommerce', 'e commerce', 'e-commerce'],
            'weak' => ['online store', 'shop', 'trgovina', 'store', 'payments', 'placanje', 'woocommerce'],
        ],
        'project_almagea' => [
            'strong' => ['almagea'],
            'weak' => ['company website', 'web stranica', 'website', 'presentation site', 'prezentacijska stranica', 'brand web'],
        ],
        'project_dfa' => [
            'strong' => ['dfa', 'academy', 'akademija'],
            'weak' => ['education', 'edukacija', 'bildung', 'training', 'program presentation', 'structured content'],
        ],
        'project_ripple' => [
            'strong' => ['ripple'],
            'weak' => ['dashboard', 'analytics', 'analitika', 'reporting', 'berichte', 'bericht', 'izvjestaj', 'izvjestavanje', 'project tracking', 'portal', 'data portal'],
        ],
        'projects' => [
            'strong' => ['projekti', 'projekte', 'projektima', 'projekata', 'projects', 'portfolio', 'reference', 'referenzen', 'case study', 'case studies', 'radovi', 'examples', 'primjeri', 'beispiele'],
            'weak' => ['slican projekt', 'slicni projekti', 'similar project', 'similar work', 'show project', 'view project', 'zeige projekt'],
        ],
        'services' => [
            'strong' => ['usluge', 'services', 'leistungen', 'dienstleistungen'],
            'weak' => ['sto radite', 'what do you do', 'what can etherr do', 'service categories', 'kategorije usluga'],
        ],
        'about' => [
            'strong' => ['o nama', 'o etherr', 'o etherru', 'about', 'about etherr', 'about us', 'who are you', 'who is etherr', 'wer ist etherr', 'tko ste', 'ko ste', 'tko je etherr', 'ko je etherr', 'studio'],
            'weak' => ['how etherr works', 'kako etherr radi', 'team', 'tim'],
        ],
    ];
}

function ai_action_has_any_keyword(string $haystack, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        $keyword = ai_action_keyword_text((string)$keyword);
        if ($keyword !== '' && str_contains($haystack, $keyword)) {
            return true;
        }
    }
    return false;
}

function ai_infer_action_ids_from_text(string $text): array
{
    $haystack = ai_action_keyword_text($text);
    if ($haystack === '') {
        return [];
    }

    $linkIntent = ai_action_has_any_keyword($haystack, [
        'link',
        'url',
        'button',
        'gumb',
        'poveznica',
        'page',
        'stranica',
        'open',
        'otvori',
        'show',
        'prikazi',
        'vidi',
        'pogledaj',
        'see',
        'daj',
        'give',
    ]);
    $exampleIntent = ai_action_has_any_keyword($haystack, [
        'project',
        'projekt',
        'projects',
        'projekti',
        'portfolio',
        'reference',
        'example',
        'primjer',
        'primjeri',
        'similar',
        'slican',
        'slicno',
        'case study',
    ]);

    $scores = [];
    foreach (ai_action_keyword_rules() as $id => $rule) {
        $score = 0;
        foreach (($rule['strong'] ?? []) as $keyword) {
            if (str_contains($haystack, ai_action_keyword_text((string)$keyword))) {
                $score += 8;
            }
        }
        foreach (($rule['weak'] ?? []) as $keyword) {
            if (str_contains($haystack, ai_action_keyword_text((string)$keyword))) {
                $score += 3;
            }
        }
        if ($score <= 0) {
            continue;
        }
        if ($id === 'contact') {
            $score += 4;
            if ($linkIntent) {
                $score += 4;
            }
        } elseif (str_starts_with($id, 'project_')) {
            if ($linkIntent || $exampleIntent) {
                $score += 5;
            }
        } elseif (in_array($id, ['projects', 'services', 'about'], true) && $linkIntent) {
            $score += 4;
        }
        if ($score >= 7) {
            $scores[$id] = $score;
        }
    }

    if (!$scores) {
        return [];
    }

    arsort($scores);
    return array_slice(array_keys($scores), 0, 2);
}

function ai_merge_action_ids(array ...$actionLists): array
{
    $merged = [];
    foreach ($actionLists as $actionList) {
        foreach ($actionList as $id) {
            $id = strtolower(trim((string)$id));
            if ($id !== '' && preg_match('/^[a-z0-9_]+$/', $id) && !in_array($id, $merged, true)) {
                $merged[] = $id;
            }
            if (count($merged) >= 2) {
                return $merged;
            }
        }
    }
    return $merged;
}

function ai_infer_action_ids_from_known_urls(string $text): array
{
    $ids = [];
    foreach (ai_enabled_action_map() as $id => $action) {
        $url = (string)($action['url'] ?? '');
        if ($url !== '' && str_contains($text, $url)) {
            $ids[] = $id;
        }
        if (count($ids) >= 2) {
            break;
        }
    }
    return $ids;
}

function ai_action_button_reference_phrase(string $locale): string
{
    return match (ai_normalize_locale($locale)) {
        'en' => 'using the button below',
        'de' => 'über die Schaltfläche unten',
        default => 'putem gumba ispod',
    };
}

function ai_strip_action_urls_from_reply(string $text, array $actions, string $locale): string
{
    if (!$actions) {
        return trim($text);
    }

    $buttonReference = ai_action_button_reference_phrase($locale);
    foreach ($actions as $action) {
        $url = trim((string)($action['url'] ?? ''));
        if ($url === '') {
            continue;
        }
        $quotedUrl = preg_quote($url, '/');
        $text = preg_replace('/\[([^\]]{1,100})\]\(\s*' . $quotedUrl . '\s*\)/i', '$1', $text) ?? $text;
        $text = preg_replace('/\b(?:ovdje|ovde|ovdi|tu|here|hier)\s*:?\s*' . $quotedUrl . '/i', $buttonReference, $text) ?? $text;
        $text = preg_replace('/\s*\(?\s*' . $quotedUrl . '\s*\)?/i', ' ' . $buttonReference, $text) ?? $text;
    }

    $text = preg_replace('/[ \t]+([.,!?;:])/', '$1', $text) ?? $text;
    $text = preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text;
    $text = preg_replace('/\n[ \t]+/', "\n", $text) ?? $text;
    $text = preg_replace('/[ \t]+\n/', "\n", $text) ?? $text;
    $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
    return trim($text);
}

function ai_action_reply_fallback_text(string $locale): string
{
    return match (ai_normalize_locale($locale)) {
        'en' => 'I added the relevant button below.',
        'de' => 'Die passende Schaltfläche ist unten angefügt.',
        default => 'Dodao sam odgovarajući gumb ispod.',
    };
}

function ai_is_conditional_action_prompt(string $text): bool
{
    $normalized = ai_action_keyword_text($text);
    if ($normalized === '') {
        return false;
    }

    $hasConditional = ai_action_has_any_keyword($normalized, [
        'ako zelite',
        'ako zelis',
        'ako hocete',
        'ako hoces',
        'if you want',
        'if you would like',
        'if you like',
        'would you like',
        'when you want',
        'wenn sie mochten',
        'wenn du mochtest',
        'falls sie mochten',
        'falls du mochtest',
    ]);
    if (!$hasConditional) {
        return false;
    }

    return ai_action_has_any_keyword($normalized, [
        'mogu',
        'mogu vam',
        'mogu ti',
        'zelite li da',
        'zelis li da',
        'can',
        'i can',
        'would you like me to',
        'do you want me to',
        'kann ich',
        'ich kann',
        'mochten sie dass',
        'mochtest du dass',
    ]);
}

function ai_clean_action_reply_text(string $text, array $actions, string $locale): string
{
    if (!$actions) {
        return trim($text);
    }

    $paragraphs = preg_split('/\n{2,}/', trim($text)) ?: [];
    $cleanParagraphs = [];
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }
        if (ai_is_conditional_action_prompt($paragraph) && preg_match('/^\s*(Ako|If|Would|When|Wenn|Falls)\b/iu', $paragraph)) {
            continue;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY) ?: [$paragraph];
        $cleanSentences = [];
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '' || ai_is_conditional_action_prompt($sentence)) {
                continue;
            }
            $cleanSentences[] = $sentence;
        }
        if ($cleanSentences) {
            $cleanParagraphs[] = implode(' ', $cleanSentences);
        }
    }

    $cleanText = trim(implode("\n\n", $cleanParagraphs));
    return $cleanText !== '' ? $cleanText : ai_action_reply_fallback_text($locale);
}

function ai_parse_assistant_reply(string $text): array
{
    $actionIds = [];
    if (preg_match_all('/\[\[ETHERR_ACTIONS:([a-z0-9_,\s-]*)\]\]/i', $text, $matches)) {
        foreach ($matches[1] as $rawIds) {
            foreach (explode(',', (string)$rawIds) as $rawId) {
                $id = strtolower(trim($rawId));
                if (preg_match('/^[a-z0-9_]+$/', $id) && !in_array($id, $actionIds, true)) {
                    $actionIds[] = $id;
                }
                if (count($actionIds) >= 2) {
                    break 2;
                }
            }
        }
        $text = preg_replace('/\s*\[\[ETHERR_ACTIONS:[a-z0-9_,\s-]*\]\]\s*/i', "\n", $text) ?? $text;
    }

    return [
        'text' => trim($text),
        'action_ids' => $actionIds,
    ];
}

function ai_resolve_actions(array $actionIds, string $locale): array
{
    $locale = ai_normalize_locale($locale);
    $actions = ai_enabled_action_map();
    $resolved = [];
    foreach ($actionIds as $id) {
        $id = strtolower(trim((string)$id));
        if (!isset($actions[$id])) {
            continue;
        }
        $action = $actions[$id];
        $resolved[] = [
            'id' => $id,
            'label' => ai_localized($action['label'], $locale),
            'url' => (string)$action['url'],
        ];
        if (count($resolved) >= 2) {
            break;
        }
    }
    return $resolved;
}

function ai_intake_enabled(): bool
{
    $settings = ai_get_setting('intake') ?? ai_default_settings('intake');
    return (bool)($settings['enabled'] ?? true);
}

function ai_intake_action_label(string $id, string $locale): string
{
    $labels = [
        'intake_start' => [
            'hr' => 'Pošaljite upit kroz chat',
            'en' => 'Send inquiry in chat',
            'de' => 'Anfrage im Chat senden',
        ],
        'intake_submit' => [
            'hr' => 'Pošalji upit',
            'en' => 'Submit inquiry',
            'de' => 'Anfrage senden',
        ],
    ];
    return ai_localized($labels[$id] ?? [], ai_normalize_locale($locale)) ?: $id;
}

function ai_intake_start_action(string $locale): array
{
    return [
        'id' => 'intake_start',
        'label' => ai_intake_action_label('intake_start', $locale),
        'kind' => 'intake_start',
    ];
}

function ai_intake_submit_action(string $locale): array
{
    return [
        'id' => 'intake_submit',
        'label' => ai_intake_action_label('intake_submit', $locale),
        'kind' => 'intake_submit',
    ];
}

function ai_action_is_custom(array $action): bool
{
    return isset($action['kind']) && in_array((string)$action['kind'], ['intake_start', 'intake_submit'], true);
}

function ai_custom_actions_from_actions(array $actions): array
{
    $custom = [];
    foreach ($actions as $action) {
        if (is_array($action) && ai_action_is_custom($action)) {
            $custom[] = [
                'id' => (string)($action['id'] ?? ''),
                'label' => (string)($action['label'] ?? ''),
                'kind' => (string)($action['kind'] ?? ''),
            ];
        }
    }
    return $custom;
}

function ai_action_ids_from_actions(array $actions): array
{
    return array_values(array_filter(array_map(function ($action) {
        if (!is_array($action) || ai_action_is_custom($action)) {
            return '';
        }
        return (string)($action['id'] ?? '');
    }, $actions)));
}

function ai_add_intake_start_action(array $actions, string $locale, string $latestUserMessage = ''): array
{
    if (!ai_intake_enabled()) {
        return $actions;
    }

    $hasContact = false;
    foreach ($actions as $action) {
        if (is_array($action) && (string)($action['id'] ?? '') === 'contact') {
            $hasContact = true;
            break;
        }
    }
    if (!$hasContact && !ai_intake_start_requested($latestUserMessage)) {
        return $actions;
    }

    $merged = [ai_intake_start_action($locale)];
    foreach ($actions as $action) {
        if (!is_array($action) || (string)($action['id'] ?? '') === 'contact') {
            continue;
        }
        $merged[] = $action;
        if (count($merged) >= 2) {
            return $merged;
        }
    }
    foreach ($actions as $action) {
        if (is_array($action) && (string)($action['id'] ?? '') === 'contact') {
            $merged[] = $action;
            break;
        }
    }
    return array_slice($merged, 0, 2);
}

/**
 * Hard enforcement: if the user message contains contact intent, always ensure
 * both the intake_start button and the contact button are present in the actions.
 * This runs AFTER the model response and cannot be overridden by the LLM.
 */
function ai_enforce_contact_buttons(array $actions, string $userMessage, string $locale): array
{
    if (!ai_intake_enabled()) {
        return $actions;
    }

    $text = ai_action_keyword_text($userMessage);
    if ($text === '') {
        return $actions;
    }

    // Broad contact intent keywords — any of these forces both buttons
    $contactKeywords = [
        // Croatian
        'kontakt', 'kontaktiraj', 'kontaktirati', 'kontaktirajte', 'javite', 'javite se',
        'javiti', 'javi', 'posalji upit', 'pošalji upit', 'poslati upit', 'upit', 'upite',
        'podatke', 'podatak', 'uzeti podatke', 'prikupi', 'prikupiti', 'uzmi',
        'nazovite', 'nazovi', 'pozovite', 'pozovi', 'poziv', 'call',
        'email', 'mail', 'poruka', 'poruku', 'napisite', 'napisati',
        'cijena', 'cijenu', 'cijene', 'ponuda', 'ponudu', 'quote',
        'sastanak', 'meeting', 'termin', 'razgovor', 'konzultacija',
        'kako poceti', 'kako krenuti', 'sljedeci korak', 'sto dalje',
        // English
        'contact', 'reach', 'get in touch', 'speak', 'talk', 'call',
        'inquiry', 'enquiry', 'send', 'submit', 'pricing', 'price',
        'next step', 'get started', 'book', 'schedule',
        // German
        'kontaktieren', 'anfrage', 'angebot', 'preis', 'preise',
        'termin', 'buchen', 'schreiben', 'nachricht',
    ];

    $hasContactIntent = false;
    foreach ($contactKeywords as $kw) {
        if (str_contains($text, $kw)) {
            $hasContactIntent = true;
            break;
        }
    }

    if (!$hasContactIntent) {
        return $actions;
    }

    // Ensure intake_start is present
    $hasIntake = false;
    $hasContact = false;
    foreach ($actions as $action) {
        $id = (string)($action['id'] ?? '');
        if ($id === 'intake_start') $hasIntake = true;
        if ($id === 'contact') $hasContact = true;
    }

    $result = [];
    if (!$hasIntake) {
        $result[] = ai_intake_start_action($locale);
    }
    foreach ($actions as $action) {
        if ((string)($action['id'] ?? '') !== 'contact') {
            $result[] = $action;
        }
    }
    if (!$hasContact) {
        $contactActions = ai_resolve_actions(['contact'], $locale);
        if (!empty($contactActions)) {
            $result[] = $contactActions[0];
        }
    } else {
        foreach ($actions as $action) {
            if ((string)($action['id'] ?? '') === 'contact') {
                $result[] = $action;
                break;
            }
        }
    }

    return $result;
}

function ai_intake_start_requested(string $message): bool
{
    $text = ai_action_keyword_text($message);
    if ($text === '') {
        return false;
    }
    return ai_action_has_any_keyword($text, [
        'posalji upit',
        'poslati upit',
        'salji upit',
        'pošalji upit',
        'kontaktirajte me',
        'javite mi se',
        'zelim poslati upit',
        'želim poslati upit',
        'send inquiry',
        'submit inquiry',
        'start inquiry',
        'contact me',
        'get in touch',
        'send request',
        'send quote request',
        'anfrage senden',
        'kontaktieren sie mich',
        'kontaktieren mich',
        'angebot anfragen',
        'anfrage starten',
    ]);
}

function ai_intake_text(string $key, string $locale): string
{
    $locale = ai_normalize_locale($locale);
    $texts = [
        'disabled' => [
            'hr' => 'Slanje upita kroz chat trenutno nije uključeno. Možete koristiti kontakt formu.',
            'en' => 'Chat inquiry submission is not enabled right now. You can use the contact form.',
            'de' => 'Das Senden von Anfragen im Chat ist derzeit nicht aktiviert. Sie können das Kontaktformular nutzen.',
        ],
        'start' => [
            'hr' => 'Možemo pripremiti upit kroz chat. Krenimo s osnovama: koje usluge ili tip projekta trebate? Npr. web stranica, webshop, custom sustav, automatizacija/AI, marketing/SEO ili analitika.',
            'en' => 'We can prepare the inquiry in chat. Let’s start with the basics: which service or project type do you need? For example website, webshop, custom system, automation/AI, marketing/SEO or analytics.',
            'de' => 'Wir können die Anfrage im Chat vorbereiten. Starten wir mit den Grundlagen: Welche Leistung oder Projektart benötigen Sie? Zum Beispiel Website, Webshop, individuelles System, Automatisierung/KI, Marketing/SEO oder Analytics.',
        ],
        'services' => [
            'hr' => 'Koje usluge ili tip projekta trebate? Možete navesti više njih.',
            'en' => 'Which services or project type do you need? You can mention more than one.',
            'de' => 'Welche Leistungen oder Projektart benötigen Sie? Sie können mehrere nennen.',
        ],
        'project_type' => [
            'hr' => 'Je li ovo novi projekt, nadogradnja postojećeg, automatizacija procesa ili još niste sigurni?',
            'en' => 'Is this a new project, an upgrade of something existing, process automation, or are you not sure yet?',
            'de' => 'Ist das ein neues Projekt, die Erweiterung von etwas Bestehendem, Prozessautomatisierung oder sind Sie noch unsicher?',
        ],
        'timeline' => [
            'hr' => 'Koji je okvirni rok: što prije, unutar mjesec dana, ovaj kvartal ili tek planiranje?',
            'en' => 'What is the rough timeline: as soon as possible, within a month, this quarter, or just planning?',
            'de' => 'Was ist der grobe Zeitrahmen: so bald wie möglich, innerhalb eines Monats, dieses Quartal oder erst Planung?',
        ],
        'company' => [
            'hr' => 'Kako se zove tvrtka ili organizacija?',
            'en' => 'What is the company or organization name?',
            'de' => 'Wie heißt das Unternehmen oder die Organisation?',
        ],
        'website' => [
            'hr' => 'Imate li postojeću web stranicu? Pošaljite link ili napišite “preskoči”.',
            'en' => 'Do you already have a website? Send the link or write “skip”.',
            'de' => 'Haben Sie bereits eine Website? Senden Sie den Link oder schreiben Sie „überspringen”.',
        ],
        'name' => [
            'hr' => 'Tko je kontakt osoba?',
            'en' => 'Who is the contact person?',
            'de' => 'Wer ist die Kontaktperson?',
        ],
        'email' => [
            'hr' => 'Na koji email vam se Etherr može javiti?',
            'en' => 'Which email should Etherr use to reply?',
            'de' => 'An welche E-Mail-Adresse soll Etherr antworten?',
        ],
        'phone' => [
            'hr' => 'Možete ostaviti i telefon ili napisati “preskoči”.',
            'en' => 'You can also leave a phone number or write “skip”.',
            'de' => 'Sie können auch eine Telefonnummer angeben oder „überspringen” schreiben.',
        ],
        'preferred_contact' => [
            'hr' => 'Kako preferirate kontakt: email, telefon ili video poziv?',
            'en' => 'How do you prefer to be contacted: email, phone or video call?',
            'de' => 'Wie möchten Sie kontaktiert werden: E-Mail, Telefon oder Videoanruf?',
        ],
        'details' => [
            'hr' => 'Ukratko opišite što trebate, cilj projekta i sve važne napomene.',
            'en' => 'Briefly describe what you need, the project goal and any important notes.',
            'de' => 'Beschreiben Sie kurz, was Sie benötigen, das Projektziel und wichtige Hinweise.',
        ],
        'consent' => [
            'hr' => 'Za slanje upita trebamo potvrdu da Etherr smije koristiti ove podatke kako bi odgovorio na vaš upit. Odgovorite “da” ako se slažete.',
            'en' => 'To send the inquiry, we need confirmation that Etherr may use this information to reply to your request. Reply “yes” if you agree.',
            'de' => 'Zum Senden der Anfrage benötigen wir Ihre Bestätigung, dass Etherr diese Angaben zur Beantwortung Ihrer Anfrage verwenden darf. Antworten Sie mit „ja”, wenn Sie einverstanden sind.',
        ],
        'invalid_email' => [
            'hr' => 'Trebam ispravnu email adresu kako bi se Etherr mogao javiti.',
            'en' => 'I need a valid email address so Etherr can reply.',
            'de' => 'Ich benötige eine gültige E-Mail-Adresse, damit Etherr antworten kann.',
        ],
        'need_details' => [
            'hr' => 'Treba mi malo više detalja o projektu kako bi upit bio koristan.',
            'en' => 'I need a little more project detail so the inquiry is useful.',
            'de' => 'Ich benötige etwas mehr Projektdetails, damit die Anfrage hilfreich ist.',
        ],
        'need_consent' => [
            'hr' => 'Bez te potvrde ne mogu poslati upit. Odgovorite “da” ako se slažete s obradom podataka za odgovor na upit.',
            'en' => 'I cannot send the inquiry without that confirmation. Reply “yes” if you agree to data use for replying to the inquiry.',
            'de' => 'Ohne diese Bestätigung kann ich die Anfrage nicht senden. Antworten Sie mit „ja”, wenn Sie der Datennutzung zur Beantwortung zustimmen.',
        ],
        'ready' => [
            'hr' => 'Pripremio sam sažetak upita. Ako je sve u redu, pošaljite ga Etherru gumbom ispod.',
            'en' => 'I prepared the inquiry summary. If everything looks good, send it to Etherr with the button below.',
            'de' => 'Ich habe die Zusammenfassung der Anfrage vorbereitet. Wenn alles passt, senden Sie sie über die Schaltfläche unten an Etherr.',
        ],
        'submitted' => [
            'hr' => 'Upit je poslan Etherru. Javit ćemo vam se u najkraćem roku.',
            'en' => 'The inquiry has been sent to Etherr. We will get back to you as soon as possible.',
            'de' => 'Die Anfrage wurde an Etherr gesendet. Wir melden uns so schnell wie möglich.',
        ],
        'queued' => [
            'hr' => 'Upit je zaprimljen, ali slanje emaila nije potvrđeno. Etherr ga može provjeriti u zapisima upita.',
            'en' => 'The inquiry was received, but email delivery was not confirmed. Etherr can still check it in the inquiry logs.',
            'de' => 'Die Anfrage wurde erfasst, aber der E-Mail-Versand wurde nicht bestätigt. Etherr kann sie in den Anfrageprotokollen prüfen.',
        ],
        'not_ready' => [
            'hr' => 'Još nemam sve podatke za slanje upita.',
            'en' => 'I do not have all details needed to submit the inquiry yet.',
            'de' => 'Ich habe noch nicht alle Angaben, um die Anfrage zu senden.',
        ],
    ];
    return $texts[$key][$locale] ?? $texts[$key]['hr'] ?? $key;
}

function ai_intake_initial_state(string $locale): array
{
    return [
        'version' => 1,
        'status' => 'active',
        'locale' => ai_normalize_locale($locale),
        'step' => 'services',
        'data' => [
            'services_raw' => '',
            'services' => [],
            'project_type' => ['value' => '', 'label' => ''],
            'timeline' => ['value' => '', 'label' => ''],
            'company' => '',
            'website' => '',
            'name' => '',
            'email' => '',
            'phone' => '',
            'preferred_contact' => ['value' => 'email', 'label' => 'E-mail'],
            'details' => '',
            'consent' => false,
        ],
    ];
}

function ai_intake_steps(): array
{
    return ['services', 'project_type', 'timeline', 'company', 'website', 'name', 'email', 'phone', 'preferred_contact', 'details', 'consent'];
}

function ai_intake_next_step(string $step): ?string
{
    $steps = ai_intake_steps();
    $index = array_search($step, $steps, true);
    if ($index === false || $index >= count($steps) - 1) {
        return null;
    }
    return $steps[$index + 1];
}

function ai_intake_get(int $conversationId): ?array
{
    if ($conversationId <= 0) {
        return null;
    }
    $db = ai_get_db();
    $stmt = $db->prepare('SELECT * FROM ' . ETHERR_AI_PREFIX . 'intakes WHERE conversation_id = ? LIMIT 1');
    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return null;
    }
    $state = json_decode((string)$row['state_json'], true);
    if (!is_array($state)) {
        $state = ai_intake_initial_state((string)($row['locale'] ?? 'hr'));
    }
    $row['state'] = $state;
    return $row;
}

function ai_intake_save(int $conversationId, string $locale, array $state, string $status = ''): void
{
    $db = ai_get_db();
    $now = ai_now();
    $locale = ai_normalize_locale($locale);
    $status = $status !== '' ? $status : (string)($state['status'] ?? 'active');
    $state['status'] = $status;
    $state['locale'] = $locale;
    $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $stmt = $db->prepare('INSERT INTO ' . ETHERR_AI_PREFIX . 'intakes (conversation_id, status, locale, state_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), locale = VALUES(locale), state_json = VALUES(state_json), updated_at = VALUES(updated_at)');
    $stmt->bind_param('isssss', $conversationId, $status, $locale, $json, $now, $now);
    $stmt->execute();
}

function ai_intake_mark_submitted(int $conversationId, array $state, string $requestId): void
{
    $db = ai_get_db();
    $now = ai_now();
    $state['status'] = 'submitted';
    $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $stmt = $db->prepare('UPDATE ' . ETHERR_AI_PREFIX . 'intakes SET status = "submitted", state_json = ?, request_id = ?, submitted_at = ?, updated_at = ? WHERE conversation_id = ?');
    $stmt->bind_param('ssssi', $json, $requestId, $now, $now, $conversationId);
    $stmt->execute();
}

function ai_intake_skip_requested(string $message): bool
{
    $text = ai_action_keyword_text($message);
    return in_array($text, ['skip', 'preskoci', 'preskoči', 'nema', 'nemam', 'no', 'none', '-', 'überspringen', 'uberspringen', 'nein'], true);
}

function ai_intake_yes(string $message): bool
{
    $text = ai_action_keyword_text($message);
    return in_array($text, ['da', 'yes', 'y', 'ok', 'okay', 'moze', 'može', 'slazem se', 'slažem se', 'ja', 'jawohl', 'verstanden'], true);
}

function ai_intake_option_label(string $group, string $value, string $locale): string
{
    $labels = [
        'project_type' => [
            'new' => ['hr' => 'Novi projekt', 'en' => 'New project', 'de' => 'Neues Projekt'],
            'upgrade' => ['hr' => 'Nadogradnja postojećeg', 'en' => 'Upgrade of an existing setup', 'de' => 'Erweiterung eines bestehenden Setups'],
            'automation' => ['hr' => 'Automatizacija procesa', 'en' => 'Process automation', 'de' => 'Prozessautomatisierung'],
            'notSure' => ['hr' => 'Još nije sigurno', 'en' => 'Not sure yet', 'de' => 'Noch unsicher'],
        ],
        'timeline' => [
            'asap' => ['hr' => 'Što prije', 'en' => 'As soon as possible', 'de' => 'So bald wie möglich'],
            'month' => ['hr' => 'Unutar mjesec dana', 'en' => 'Within a month', 'de' => 'Innerhalb eines Monats'],
            'quarter' => ['hr' => 'Ovaj kvartal', 'en' => 'This quarter', 'de' => 'Dieses Quartal'],
            'planning' => ['hr' => 'Još planiramo', 'en' => 'Still planning', 'de' => 'Noch in Planung'],
        ],
        'preferred_contact' => [
            'email' => ['hr' => 'E-mail', 'en' => 'Email', 'de' => 'E-Mail'],
            'phone' => ['hr' => 'Telefon', 'en' => 'Phone', 'de' => 'Telefon'],
            'video' => ['hr' => 'Video poziv', 'en' => 'Video call', 'de' => 'Videoanruf'],
        ],
    ];
    return ai_localized($labels[$group][$value] ?? [], $locale) ?: $value;
}

function ai_intake_match_option(string $message, string $group): string
{
    $text = ai_action_keyword_text($message);
    $maps = [
        'project_type' => [
            'automation' => ['automatiz', 'automation', 'prozessautomatisierung', 'automatisierung'],
            'upgrade' => ['nadograd', 'postojec', 'postoje', 'upgrade', 'redesign', 'existing', 'bestehend', 'erweiterung'],
            'new' => ['novi', 'nova', 'new', 'neues', 'neu'],
            'notSure' => ['nisam siguran', 'nismo sigurni', 'not sure', 'unsure', 'ne znam', 'dont know', 'weiß nicht', 'weiss nicht', 'unsicher'],
        ],
        'timeline' => [
            'asap' => ['asap', 'sto prije', 'što prije', 'hitno', 'urgent', 'so bald', 'sofort'],
            'month' => ['mjesec', 'month', '30', 'monat'],
            'quarter' => ['kvartal', 'quarter', '3 mjes', 'tri mjes', 'quartal'],
            'planning' => ['plan', 'planning', 'nije hitno', 'not urgent', 'planung'],
        ],
        'preferred_contact' => [
            'video' => ['video', 'meet', 'zoom', 'teams'],
            'phone' => ['phone', 'telefon', 'call', 'poziv', 'anruf'],
            'email' => ['email', 'e mail', 'mail'],
        ],
    ];
    foreach ($maps[$group] ?? [] as $value => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($text, ai_action_keyword_text($keyword))) {
                return $value;
            }
        }
    }
    return $group === 'preferred_contact' ? 'email' : ($group === 'timeline' ? 'planning' : 'notSure');
}

function ai_intake_service_options(string $message, string $locale): array
{
    $text = ai_action_keyword_text($message);
    $options = [
        'websites' => [
            'keywords' => ['website', 'web stranica', 'stranica', 'presentation', 'prezentacij', 'wordpress'],
            'title' => ['hr' => 'Web stranica', 'en' => 'Website', 'de' => 'Website'],
            'category' => ['hr' => 'Digitalne platforme', 'en' => 'Digital platforms', 'de' => 'Digitale Plattformen'],
        ],
        'webshop' => [
            'keywords' => ['webshop', 'web shop', 'shop', 'ecommerce', 'e-commerce', 'trgovina'],
            'title' => ['hr' => 'Webshop', 'en' => 'Webshop', 'de' => 'Webshop'],
            'category' => ['hr' => 'Digitalne platforme', 'en' => 'Digital platforms', 'de' => 'Digitale Plattformen'],
        ],
        'reservations' => [
            'keywords' => ['rezerv', 'booking', 'appointment', 'termin', 'calendar', 'kalendar', 'buchung', 'reservierung'],
            'title' => ['hr' => 'Rezervacije / booking sustav', 'en' => 'Reservations / booking system', 'de' => 'Reservierungen / Buchungssystem'],
            'category' => ['hr' => 'Digitalne platforme', 'en' => 'Digital platforms', 'de' => 'Digitale Plattformen'],
        ],
        'custom_system' => [
            'keywords' => ['custom', 'sustav', 'system', 'platforma', 'app', 'aplikacija', 'portal'],
            'title' => ['hr' => 'Custom sustav / web aplikacija', 'en' => 'Custom system / web app', 'de' => 'Individuelles System / Web-App'],
            'category' => ['hr' => 'Digitalne platforme', 'en' => 'Digital platforms', 'de' => 'Digitale Plattformen'],
        ],
        'automation_ai' => [
            'keywords' => ['automatiz', 'ai', 'llm', 'chatbot', 'assistant', 'asistent', 'ki'],
            'title' => ['hr' => 'Automatizacija i AI', 'en' => 'Automation and AI', 'de' => 'Automatisierung und KI'],
            'category' => ['hr' => 'Automatizacija i AI', 'en' => 'Automation and AI', 'de' => 'Automatisierung und KI'],
        ],
        'marketing' => [
            'keywords' => ['marketing', 'seo', 'ads', 'oglas', 'content', 'social'],
            'title' => ['hr' => 'Marketing / SEO', 'en' => 'Marketing / SEO', 'de' => 'Marketing / SEO'],
            'category' => ['hr' => 'Marketing i rast', 'en' => 'Marketing and growth', 'de' => 'Marketing und Wachstum'],
        ],
        'analytics' => [
            'keywords' => ['analytics', 'analitika', 'data', 'podaci', 'report', 'izvjest', 'dashboard'],
            'title' => ['hr' => 'Analitika i izvještavanje', 'en' => 'Analytics and reporting', 'de' => 'Analytics und Reporting'],
            'category' => ['hr' => 'Podaci i konzalting', 'en' => 'Data and consulting', 'de' => 'Daten und Beratung'],
        ],
    ];

    $services = [];
    foreach ($options as $id => $option) {
        foreach ($option['keywords'] as $keyword) {
            if (str_contains($text, ai_action_keyword_text($keyword))) {
                $services[] = [
                    'id' => $id,
                    'title' => ai_localized($option['title'], $locale),
                    'category' => ai_localized($option['category'], $locale),
                ];
                break;
            }
        }
    }
    if (!$services) {
        $services[] = [
            'id' => 'chatbot_intake',
            'title' => trim($message),
            'category' => ai_localized(['hr' => 'Upit iz chata', 'en' => 'Chat inquiry', 'de' => 'Chat-Anfrage'], $locale),
        ];
    }
    return $services;
}

function ai_intake_apply_answer(array $state, string $message, string $locale): array
{
    $step = (string)($state['step'] ?? 'services');
    $data = is_array($state['data'] ?? null) ? $state['data'] : [];
    $value = trim($message);
    $error = '';

    if ($step === 'services') {
        if ($value === '') {
            $error = ai_intake_text('services', $locale);
        } else {
            $data['services_raw'] = $value;
            $data['services'] = ai_intake_service_options($value, $locale);
        }
    } elseif ($step === 'project_type') {
        $matched = ai_intake_match_option($value, 'project_type');
        $data['project_type'] = ['value' => $matched, 'label' => ai_intake_option_label('project_type', $matched, $locale)];
    } elseif ($step === 'timeline') {
        $matched = ai_intake_match_option($value, 'timeline');
        $data['timeline'] = ['value' => $matched, 'label' => ai_intake_option_label('timeline', $matched, $locale)];
    } elseif ($step === 'company') {
        if ($value === '') {
            $error = ai_intake_text('company', $locale);
        } else {
            $data['company'] = $value;
        }
    } elseif ($step === 'website') {
        $data['website'] = ai_intake_skip_requested($value) ? '' : $value;
    } elseif ($step === 'name') {
        if ($value === '') {
            $error = ai_intake_text('name', $locale);
        } else {
            $data['name'] = $value;
        }
    } elseif ($step === 'email') {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $error = ai_intake_text('invalid_email', $locale);
        } else {
            $data['email'] = $value;
        }
    } elseif ($step === 'phone') {
        $data['phone'] = ai_intake_skip_requested($value) ? '' : $value;
    } elseif ($step === 'preferred_contact') {
        $matched = ai_intake_match_option($value, 'preferred_contact');
        $data['preferred_contact'] = ['value' => $matched, 'label' => ai_intake_option_label('preferred_contact', $matched, $locale)];
    } elseif ($step === 'details') {
        if (mb_strlen($value) < 12) {
            $error = ai_intake_text('need_details', $locale);
        } else {
            $data['details'] = $value;
        }
    } elseif ($step === 'consent') {
        if (!ai_intake_yes($value)) {
            $error = ai_intake_text('need_consent', $locale);
        } else {
            $data['consent'] = true;
        }
    }

    $state['data'] = $data;
    if ($error !== '') {
        return ['state' => $state, 'error' => $error];
    }

    // Advance to next step, skipping optional steps that are already filled or not needed
    $optionalSteps = ['website', 'phone', 'preferred_contact'];
    $next = ai_intake_next_step($step);
    while ($next !== null && in_array($next, $optionalSteps, true)) {
        // Check if this optional step already has data
        $optFilled = match($next) {
            'services' => !empty($data['services']),
            'project_type' => !empty($data['project_type']),
            'timeline' => !empty($data['timeline']),
            'website' => !empty($data['website']),
            'phone' => !empty($data['phone']),
            'preferred_contact' => !empty($data['preferred_contact']),
            default => false,
        };
        if ($optFilled) {
            $next = ai_intake_next_step($next);
            continue;
        }
        break; // Stop at unfilled optional step (it will be asked)
    }
    if ($next === null) {
        $state['step'] = 'ready';
        $state['status'] = 'ready';
    } else {
        $state['step'] = $next;
        $state['status'] = 'active';
    }

    return ['state' => $state, 'error' => ''];
}

function ai_intake_summary(array $state, string $locale): string
{
    $data = is_array($state['data'] ?? null) ? $state['data'] : [];
    $services = [];
    foreach (($data['services'] ?? []) as $service) {
        if (is_array($service)) {
            $title = trim((string)($service['title'] ?? ''));
            if ($title !== '') {
                $services[] = $title;
            }
        }
    }
    $labels = [
        'hr' => ['summary' => 'Sažetak upita', 'services' => 'Usluge', 'type' => 'Vrsta projekta', 'timeline' => 'Rok', 'company' => 'Tvrtka', 'website' => 'Web stranica', 'name' => 'Kontakt osoba', 'email' => 'Email', 'phone' => 'Telefon', 'contact' => 'Preferirani kontakt', 'details' => 'Detalji'],
        'en' => ['summary' => 'Inquiry summary', 'services' => 'Services', 'type' => 'Project type', 'timeline' => 'Timeline', 'company' => 'Company', 'website' => 'Website', 'name' => 'Contact person', 'email' => 'Email', 'phone' => 'Phone', 'contact' => 'Preferred contact', 'details' => 'Details'],
        'de' => ['summary' => 'Zusammenfassung der Anfrage', 'services' => 'Leistungen', 'type' => 'Projekttyp', 'timeline' => 'Zeitrahmen', 'company' => 'Unternehmen', 'website' => 'Website', 'name' => 'Kontaktperson', 'email' => 'E-Mail', 'phone' => 'Telefon', 'contact' => 'Bevorzugter Kontakt', 'details' => 'Details'],
    ][ai_normalize_locale($locale)];
    $lines = [
        ai_intake_text('ready', $locale),
        '',
        $labels['summary'] . ':',
        $labels['services'] . ': ' . ($services ? implode(', ', $services) : '-'),
        $labels['type'] . ': ' . (string)($data['project_type']['label'] ?? '-'),
        $labels['timeline'] . ': ' . (string)($data['timeline']['label'] ?? '-'),
        $labels['company'] . ': ' . (string)($data['company'] ?? '-'),
        $labels['website'] . ': ' . ((string)($data['website'] ?? '') !== '' ? (string)$data['website'] : '-'),
        $labels['name'] . ': ' . (string)($data['name'] ?? '-'),
        $labels['email'] . ': ' . (string)($data['email'] ?? '-'),
        $labels['phone'] . ': ' . ((string)($data['phone'] ?? '') !== '' ? (string)$data['phone'] : '-'),
        $labels['contact'] . ': ' . (string)($data['preferred_contact']['label'] ?? '-'),
        '',
        $labels['details'] . ':',
        (string)($data['details'] ?? '-'),
    ];
    return implode("\n", $lines);
}

function ai_intake_question_for_state(array $state, string $locale): string
{
    $step = (string)($state['step'] ?? 'services');
    if ($step === 'ready' || (string)($state['status'] ?? '') === 'ready') {
        return ai_intake_summary($state, $locale);
    }
    return ai_intake_text($step, $locale);
}

function ai_intake_reply_payload(string $text, array $actions = []): array
{
    return [
        'text' => $text,
        'actions' => $actions,
        'response_id' => '',
        'model' => 'assistant-intake',
        'usage' => [],
    ];
}

function ai_start_intake_for_conversation(int $conversationId, string $locale): array
{
    if (!ai_intake_enabled()) {
        return ai_intake_reply_payload(ai_intake_text('disabled', $locale), ai_resolve_actions(['contact'], $locale));
    }
    $state = ai_intake_initial_state($locale);

    // Pre-fill intake data from conversation history so we don't re-ask known info
    $messages = ai_get_messages($conversationId, 20);
    $userText = '';
    foreach ($messages as $msg) {
        if (($msg['role'] ?? '') === 'user') {
            $userText .= ' ' . ($msg['text'] ?? '');
        }
    }
    $allText = '';
    foreach ($messages as $msg) {
        $allText .= ' ' . ($msg['text'] ?? '');
    }
    $data = is_array($state['data'] ?? null) ? $state['data'] : [];

    // Try to extract services from conversation
    if (empty($data['services'])) {
        $extracted = ai_intake_service_options($allText, $locale);
        if ($extracted && !((count($extracted) === 1) && (($extracted[0]['id'] ?? '') === 'chatbot_intake'))) {
            $data['services'] = $extracted;
            $data['services_raw'] = trim($userText);
        }
    }

    // Try to extract project type
    if (empty($data['project_type'])) {
        $ptMatch = ai_intake_match_option($allText, 'project_type');
        if ($ptMatch !== 'notSure') {
            $data['project_type'] = ['value' => $ptMatch, 'label' => ai_intake_option_label('project_type', $ptMatch, $locale)];
        }
    }

    // Try to extract timeline
    if (empty($data['timeline'])) {
        $tlMatch = ai_intake_match_option($allText, 'timeline');
        if ($tlMatch !== 'planning') {
            $data['timeline'] = ['value' => $tlMatch, 'label' => ai_intake_option_label('timeline', $tlMatch, $locale)];
        }
    }

    // Try to extract email from user messages
    if (empty($data['email'])) {
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $userText, $emailMatch)) {
            $data['email'] = $emailMatch[0];
        }
    }

    // Try to extract phone from user messages
    if (empty($data['phone'])) {
        if (preg_match('/(?:\+?\d[\d\s\-]{7,15}\d)/', $userText, $phoneMatch)) {
            $candidate = preg_replace('/[\s\-]/', '', $phoneMatch[0]);
            if (strlen($candidate) >= 8) {
                $data['phone'] = $phoneMatch[0];
            }
        }
    }

    // Try to extract website from user messages
    if (empty($data['website'])) {
        if (preg_match('/https?:\/\/[^\s,]+|www\.[^\s,]+/', $userText, $urlMatch)) {
            $data['website'] = $urlMatch[0];
        }
    }

    // Use the user's conversation messages as project details if they described their needs
    if (empty($data['details'])) {
        $userMessages = [];
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $text = trim((string)($msg['text'] ?? ''));
                if (mb_strlen($text) >= 12) {
                    $userMessages[] = $text;
                }
            }
        }
        if ($userMessages) {
            $data['details'] = implode("\n", $userMessages);
        }
    }

    $state['data'] = $data;

    // Find the first step that still needs data — skip steps we already have
    $steps = ai_intake_steps();
    $optionalSteps = ['website', 'phone', 'preferred_contact'];
    $firstMissing = null;
    foreach ($steps as $step) {
        $filled = match($step) {
            'services' => !empty($data['services']),
            'project_type' => !empty($data['project_type']),
            'timeline' => !empty($data['timeline']),
            'company' => !empty($data['company']),
            'website' => !empty($data['website']),
            'name' => !empty($data['name']),
            'email' => !empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL),
            'phone' => !empty($data['phone']),
            'preferred_contact' => !empty($data['preferred_contact']),
            'details' => !empty($data['details']) && mb_strlen((string)($data['details'] ?? '')) >= 12,
            'consent' => false, // Always ask
            default => false,
        };
        if ($filled) continue;
        // Skip optional steps entirely — only ask required ones + consent
        if (in_array($step, $optionalSteps, true)) continue;
        $firstMissing = $step;
        break;
    }
    $state['step'] = $firstMissing ?? 'consent';

    ai_intake_save($conversationId, $locale, $state, 'active');

    $hasAnyPrefilled = !empty($data['services']) || !empty($data['email']) || !empty($data['company']) || !empty($data['name']);
    if (!empty($data['project_type'])) $prefilledInfo[] = ai_localized(['hr' => 'Vrsta projekta', 'en' => 'Project type', 'de' => 'Projekttyp'], $locale) . ': ' . ($data['project_type']['label'] ?? '');
    if (!empty($data['timeline'])) $prefilledInfo[] = ai_localized(['hr' => 'Rok', 'en' => 'Timeline', 'de' => 'Zeitrahmen'], $locale) . ': ' . ($data['timeline']['label'] ?? '');
    $question = ai_intake_question_for_state($state, $locale);

    if ($hasAnyPrefilled) {
        // Don't list what we know — just skip to the first missing question
        $letsGo = ai_localized([
            'hr' => 'Trebam još samo par podataka za upit.',
            'en' => 'I just need a few more details for the inquiry.',
            'de' => 'Ich brauche nur noch ein paar Angaben für die Anfrage.',
        ], $locale);
        $text = $letsGo . "\n\n" . $question;
    } else {
        $text = ai_intake_text('start', $locale) . "\n\n" . $question;
    }

    return ai_intake_reply_payload($text);
}

function ai_handle_intake_message(int $conversationId, string $message, string $locale): ?array
{
    if (!ai_intake_enabled()) {
        return null;
    }

    $row = ai_intake_get($conversationId);
    $state = is_array($row['state'] ?? null) ? $row['state'] : null;
    $status = (string)($row['status'] ?? ($state['status'] ?? ''));

    if (!$state || in_array($status, ['submitted', 'cancelled'], true)) {
        return ai_intake_start_requested($message) ? ai_start_intake_for_conversation($conversationId, $locale) : null;
    }

    if ($status === 'ready' || (string)($state['step'] ?? '') === 'ready') {
        return null;
    }

    $result = ai_intake_apply_answer($state, $message, $locale);
    $state = $result['state'];
    $error = (string)$result['error'];
    $status = (string)($state['status'] ?? 'active');
    ai_intake_save($conversationId, $locale, $state, $status);

    if ($error !== '') {
        return ai_intake_reply_payload($error . "\n\n" . ai_intake_question_for_state($state, $locale));
    }

    if ($status === 'ready') {
        return ai_intake_reply_payload(ai_intake_summary($state, $locale), [ai_intake_submit_action($locale)]);
    }

    return ai_intake_reply_payload(ai_intake_question_for_state($state, $locale));
}

function ai_env_bool(string $key, bool $default = false): bool
{
    $value = strtolower(ai_env($key, $default ? 'true' : 'false'));
    if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }
    return $default;
}

function ai_sanitize_header_text(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', $value));
}

function ai_escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ai_render_email_rows(array $rows): string
{
    $html = '';
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $label = ai_escape_html((string)($row['label'] ?? ''));
        $value = trim((string)($row['value'] ?? ''));
        $displayValue = $value !== '' ? $value : '-';
        $href = trim((string)($row['href'] ?? ''));
        $valueHtml = ai_escape_html($displayValue);
        if ($href !== '' && $value !== '') {
            $valueHtml = '<a href="' . ai_escape_html($href) . '" style="color:#0f1720;text-decoration:none;">' . $valueHtml . '</a>';
        }
        $html .= '<tr>'
            . '<td style="padding:9px 12px 9px 0;border-bottom:1px solid #dceaea;color:#118a85;font-size:11px;line-height:16px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;white-space:nowrap;vertical-align:top;">' . $label . '</td>'
            . '<td style="padding:9px 0;border-bottom:1px solid #dceaea;color:#0f1720;font-size:14px;line-height:20px;font-weight:600;vertical-align:top;overflow-wrap:anywhere;word-break:break-word;">' . $valueHtml . '</td>'
            . '</tr>';
    }
    return $html;
}

function ai_contact_send_message(string $subject, string $body, string $replyEmail, string $replyName, string $htmlBody = '', string $recipientOverride = ''): array
{
    $transport = strtolower(ai_env('MAIL_TRANSPORT', 'smtp'));
    $recipient = $recipientOverride !== '' ? $recipientOverride : ai_env('MAIL_TO', 'info@etherr.hr');
    $from = ai_env('MAIL_FROM', 'noreply@etherr.hr');
    $fromName = ai_env('MAIL_FROM_NAME', 'Etherr Website');

    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return ['sent' => false, 'transport' => $transport, 'error' => 'Recipient is not a valid email'];
    }
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return ['sent' => false, 'transport' => $transport, 'error' => 'MAIL_FROM is not a valid email'];
    }

    if ($transport === 'mail') {
        $headers = [
            'From: ' . ai_sanitize_header_text($fromName) . ' <' . $from . '>',
            'Reply-To: ' . ai_sanitize_header_text($replyName) . ' <' . $replyEmail . '>',
            $htmlBody !== '' ? 'Content-Type: text/html; charset=UTF-8' : 'Content-Type: text/plain; charset=UTF-8',
        ];
        $sent = @mail($recipient, $subject, $htmlBody !== '' ? $htmlBody : $body, implode("\r\n", $headers));
        return ['sent' => $sent, 'transport' => 'mail', 'error' => $sent ? '' : 'mail() returned false'];
    }

    $autoload = ai_root_dir() . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        return ['sent' => false, 'transport' => 'smtp', 'error' => 'Composer autoload not found'];
    }
    require_once $autoload;
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return ['sent' => false, 'transport' => 'smtp', 'error' => 'PHPMailer class not found'];
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = ai_env('SMTP_HOST');
        $mail->Port = max(1, (int)ai_env('SMTP_PORT', '587'));
        $mail->Timeout = max(1, (int)ai_env('SMTP_TIMEOUT', '15'));
        $mail->SMTPAuth = ai_env_bool('SMTP_AUTH', true);
        $mail->Username = ai_env('SMTP_USERNAME');
        $mail->Password = ai_env('SMTP_PASSWORD');
        $mail->SMTPDebug = ai_env_bool('SMTP_DEBUG', false) ? 2 : 0;
        $encryption = strtolower(ai_env('SMTP_ENCRYPTION', 'tls'));
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
        return ['sent' => $sent, 'transport' => 'smtp', 'error' => $sent ? '' : 'SMTP send returned false'];
    } catch (Throwable $error) {
        return ['sent' => false, 'transport' => 'smtp', 'error' => $error->getMessage()];
    }
}

function ai_contact_append_log(array $entry): void
{
    if (!ai_env_bool('STORE_SUBMISSIONS', true)) {
        return;
    }
    $dirRaw = ai_env('INTAKE_STORAGE_DIR', 'var');
    $dir = str_starts_with($dirRaw, '/') ? $dirRaw : ai_root_dir() . '/' . ltrim($dirRaw, '/');
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $encoded = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (is_string($encoded)) {
        @file_put_contents(rtrim($dir, '/') . '/contact-intake-log.ndjson', $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

function ai_submit_intake_email(array $state, string $locale): array
{
    $data = is_array($state['data'] ?? null) ? $state['data'] : [];
    $company = trim((string)($data['company'] ?? ''));
    $name = trim((string)($data['name'] ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $details = trim((string)($data['details'] ?? ''));
    if ($company === '' || $name === '' || $details === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($data['consent'])) {
        throw new RuntimeException(ai_intake_text('not_ready', $locale));
    }

    $requestId = bin2hex(random_bytes(8));
    $services = [];
    foreach (($data['services'] ?? []) as $service) {
        if (is_array($service)) {
            $title = trim((string)($service['title'] ?? ''));
            $category = trim((string)($service['category'] ?? ''));
            if ($title !== '') {
                $services[] = $category !== '' ? $title . ' (' . $category . ')' : $title;
            }
        }
    }
    $servicesValue = $services ? implode(', ', $services) : '-';
    $website = trim((string)($data['website'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $preferred = trim((string)($data['preferred_contact']['label'] ?? ''));
    $projectType = trim((string)($data['project_type']['label'] ?? ''));
    $timeline = trim((string)($data['timeline']['label'] ?? ''));
    $sourceUrl = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $subject = sprintf('[Etherr] Upit iz chata: %s', ai_sanitize_header_text($company !== '' ? $company : $name));
    $lines = [
        'Etherr - novi upit iz AI chata',
        '===============================',
        '',
        'ID upita: ' . $requestId,
        'Tvrtka: ' . $company,
        'Kontakt osoba: ' . $name,
        'E-mail: ' . $email,
        'Telefon: ' . ($phone !== '' ? $phone : '-'),
        'Web stranica: ' . ($website !== '' ? $website : '-'),
        'Preferirani kontakt: ' . ($preferred !== '' ? $preferred : '-'),
        'Vrsta projekta: ' . ($projectType !== '' ? $projectType : '-'),
        'Rok: ' . ($timeline !== '' ? $timeline : '-'),
        'Usluge: ' . $servicesValue,
        '',
        'Detalji:',
        $details,
        '',
        'Meta',
        '----',
        'Izvor: AI chatbot',
        'Jezik: ' . ai_normalize_locale($locale),
        'URL: ' . ($sourceUrl !== '' ? $sourceUrl : '-'),
        'IP adresa: ' . ai_client_ip(),
    ];
    $body = implode("\n", $lines);

    $phoneHref = $phone !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $phone) : '';
    $websiteHref = '';
    if ($website !== '') {
        if (filter_var($website, FILTER_VALIDATE_URL)) {
            $websiteHref = $website;
        } elseif (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}/i', $website) === 1) {
            $websiteHref = 'https://' . $website;
        }
    }

    $contactRowsHtml = ai_render_email_rows([
        ['label' => 'Tvrtka', 'value' => $company],
        ['label' => 'Kontakt osoba', 'value' => $name],
        ['label' => 'E-mail', 'value' => $email, 'href' => 'mailto:' . $email],
        ['label' => 'Telefon', 'value' => $phone, 'href' => $phoneHref],
        ['label' => 'Web stranica', 'value' => $website, 'href' => $websiteHref],
        ['label' => 'Preferirani kontakt', 'value' => $preferred],
    ]);
    $projectRowsHtml = ai_render_email_rows([
        ['label' => 'Vrsta projekta', 'value' => $projectType],
        ['label' => 'Rok', 'value' => $timeline],
        ['label' => 'Usluge', 'value' => $servicesValue],
    ]);
    $metaRowsHtml = ai_render_email_rows([
        ['label' => 'ID upita', 'value' => $requestId],
        ['label' => 'Izvor', 'value' => 'AI chatbot'],
        ['label' => 'Jezik', 'value' => ai_normalize_locale($locale)],
        ['label' => 'URL', 'value' => $sourceUrl, 'href' => filter_var($sourceUrl, FILTER_VALIDATE_URL) ? $sourceUrl : ''],
        ['label' => 'IP adresa', 'value' => ai_client_ip()],
    ]);
    $detailsHtml = nl2br(ai_escape_html($details));

    $signatureFooter = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="left" style="border-collapse:collapse;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;color:#0f1720;margin:0;">
  <tr>
    <td valign="middle" style="vertical-align:middle;padding:0 18px 0 0;">
      <a href="https://etherr.hr" style="text-decoration:none;border:0;">
        <img src="https://etherr.hr/assets/images/logo.png" width="128" alt="Etherr" style="display:block;width:128px;height:auto;border:0;outline:none;text-decoration:none;">
      </a>
    </td>
    <td valign="middle" style="vertical-align:middle;padding:0 0 0 18px;border-left:1px solid #dceaea;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
        <tr><td style="padding:0 0 6px 0;font-size:10px;line-height:13px;font-weight:700;letter-spacing:1.7px;color:#118a85;">Sva rješenja na jednom mjestu</td></tr>
        <tr><td style="padding:16px 0 16px 0;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
            <tr><td style="padding:0 0 2px 0;font-size:13px;line-height:17px;font-weight:700;color:#14313a;"><a href="tel:+385916309013" style="color:#14313a;text-decoration:none;">+385 91 6309 013</a></td></tr>
            <tr><td style="padding:0 0 2px 0;font-size:13px;line-height:17px;font-weight:700;color:#14313a;"><a href="mailto:info@etherr.hr" style="color:#14313a;text-decoration:none;">info@etherr.hr</a></td></tr>
            <tr><td style="padding:0;font-size:13px;line-height:17px;font-weight:700;color:#14313a;"><a href="https://www.etherr.hr" style="color:#14313a;text-decoration:none;">www.etherr.hr</a></td></tr>
          </table>
        </td></tr>
        <tr><td style="padding:0;font-size:11px;line-height:15px;font-weight:500;color:#118a85;">Gradimo i povezujemo sve dijelove vašeg digitalnog sustava.</td></tr>
      </table>
    </td>
  </tr>
</table>';

    $htmlBody = '<!doctype html>
<html lang="hr">
  <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  </head>
  <body style="margin:0;padding:0;background:#f7fbfb;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;color:#0f1720;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;background:#f7fbfb;">
      <tr><td align="center" style="padding:28px 14px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" style="width:100%;max-width:680px;border-collapse:separate;border-spacing:0;background:transparent;border:0;">
          <tr><td style="padding:22px 28px 8px 28px;background:#ffffff;border-radius:22px 22px 0 0;">
            <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">Kontakt podaci</div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:8px;">' . $contactRowsHtml . '</table>
          </td></tr>
          <tr><td style="padding:18px 28px 8px 28px;background:#ffffff;">
            <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">Projekt</div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:8px;">' . $projectRowsHtml . '</table>
          </td></tr>
          <tr><td style="padding:18px 28px 8px 28px;background:#ffffff;">
            <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">Detalji upita</div>
            <div style="margin-top:10px;padding:15px 16px;background:#f1f7f7;border:1px solid #dceaea;border-radius:14px;color:#14313a;font-size:14px;line-height:22px;font-weight:500;overflow-wrap:anywhere;word-break:break-word;">' . $detailsHtml . '</div>
          </td></tr>
          <tr><td style="padding:18px 28px 22px 28px;background:#ffffff;">
            <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">Tehnički podaci</div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:8px;">' . $metaRowsHtml . '</table>
          </td></tr>
          <tr><td align="left" style="padding:22px 28px 24px 28px;background:#f1f7f7;border-top:1px solid #dceaea;border-radius:0 0 22px 22px;">
            ' . $signatureFooter . '
          </td></tr>
        </table>
      </td></tr>
    </table>
  </body>
</html>';

    $mailResult = ai_contact_send_message($subject, $body, $email, $name, $htmlBody);
    $clientSubject = match (ai_normalize_locale($locale)) {
        'en' => '[Etherr] We received your inquiry',
        'de' => '[Etherr] Wir haben Ihre Anfrage erhalten',
        default => '[Etherr] Zaprimili smo vaš upit',
    };
    $clientIntro = match (ai_normalize_locale($locale)) {
        'en' => 'We received your inquiry and will get back to you as soon as possible.',
        'de' => 'Wir haben Ihre Anfrage erhalten und melden uns so schnell wie möglich.',
        default => 'Zaprimili smo vaš upit i javit ćemo vam se u najkraćem roku.',
    };
    $clientLabels = match (ai_normalize_locale($locale)) {
        'en' => ['summary' => 'Inquiry summary', 'company' => 'Company', 'name' => 'Contact person', 'email' => 'Email', 'phone' => 'Phone', 'website' => 'Website', 'preferred' => 'Preferred contact', 'type' => 'Project type', 'timeline' => 'Timeline', 'services' => 'Services', 'details' => 'Details', 'slogan' => 'All solutions in one place', 'footerLine' => 'We build and connect all parts of your digital setup.'],
        'de' => ['summary' => 'Zusammenfassung der Anfrage', 'company' => 'Unternehmen', 'name' => 'Kontaktperson', 'email' => 'E-Mail', 'phone' => 'Telefon', 'website' => 'Website', 'preferred' => 'Bevorzugter Kontakt', 'type' => 'Projekttyp', 'timeline' => 'Zeitrahmen', 'services' => 'Leistungen', 'details' => 'Details', 'slogan' => 'Alle Lösungen an einem Ort', 'footerLine' => 'Wir entwickeln und verbinden Ihr gesamtes digitales Setup.'],
        default => ['summary' => 'Sažetak upita', 'company' => 'Tvrtka', 'name' => 'Kontakt osoba', 'email' => 'E-mail', 'phone' => 'Telefon', 'website' => 'Web stranica', 'preferred' => 'Preferirani kontakt', 'type' => 'Vrsta projekta', 'timeline' => 'Rok', 'services' => 'Usluge', 'details' => 'Detalji', 'slogan' => 'Sva rješenja na jednom mjestu', 'footerLine' => 'Gradimo i povezujemo sve dijelove vašeg digitalnog sustava.'],
    };
    $clientBody = implode("\n", [
        $clientIntro,
        '',
        $clientLabels['summary'],
        '----',
        $clientLabels['company'] . ': ' . $company,
        $clientLabels['name'] . ': ' . $name,
        $clientLabels['email'] . ': ' . $email,
        $clientLabels['phone'] . ': ' . ($phone !== '' ? $phone : '-'),
        $clientLabels['website'] . ': ' . ($website !== '' ? $website : '-'),
        $clientLabels['preferred'] . ': ' . ($preferred !== '' ? $preferred : '-'),
        $clientLabels['type'] . ': ' . ($projectType !== '' ? $projectType : '-'),
        $clientLabels['timeline'] . ': ' . ($timeline !== '' ? $timeline : '-'),
        $clientLabels['services'] . ': ' . $servicesValue,
        '',
        $clientLabels['details'] . ':',
        $details,
    ]);

    $clientSummaryRowsHtml = ai_render_email_rows([
        ['label' => $clientLabels['company'], 'value' => $company],
        ['label' => $clientLabels['name'], 'value' => $name],
        ['label' => $clientLabels['email'], 'value' => $email, 'href' => 'mailto:' . $email],
        ['label' => $clientLabels['phone'], 'value' => $phone, 'href' => $phoneHref],
        ['label' => $clientLabels['website'], 'value' => $website, 'href' => $websiteHref],
        ['label' => $clientLabels['preferred'], 'value' => $preferred],
        ['label' => $clientLabels['type'], 'value' => $projectType],
        ['label' => $clientLabels['timeline'], 'value' => $timeline],
        ['label' => $clientLabels['services'], 'value' => $servicesValue],
    ]);

    $clientSignatureFooter = str_replace(
        ['Sva rješenja na jednom mjestu', 'Gradimo i povezujemo sve dijelove vašeg digitalnog sustava.'],
        [ai_escape_html($clientLabels['slogan']), ai_escape_html($clientLabels['footerLine'])],
        $signatureFooter
    );

    $clientLocale = ai_normalize_locale($locale);
    $clientHtmlBody = '<!doctype html>
<html lang="' . ai_escape_html($clientLocale) . '">
  <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  </head>
  <body style="margin:0;padding:0;background:#f7fbfb;font-family:\'Space Grotesk\',\'Segoe UI\',Arial,Helvetica,sans-serif;color:#0f1720;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;background:#f7fbfb;">
      <tr><td align="center" style="padding:28px 14px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" style="width:100%;max-width:680px;border-collapse:separate;border-spacing:0;background:transparent;border:0;">
          <tr><td style="padding:26px 28px 22px 28px;background:#0f2a2c;border-radius:22px 22px 0 0;">
            <div style="font-size:16px;line-height:24px;font-weight:600;color:#ecf8f8;">' . ai_escape_html($clientIntro) . '</div>
          </td></tr>
          <tr><td style="padding:22px 28px 8px 28px;background:#ffffff;">
            <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">' . ai_escape_html($clientLabels['summary']) . '</div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:8px;">' . $clientSummaryRowsHtml . '</table>
          </td></tr>
          <tr><td style="padding:18px 28px 22px 28px;background:#ffffff;">
            <div style="font-size:16px;line-height:22px;font-weight:700;color:#0f1720;">' . ai_escape_html($clientLabels['details']) . '</div>
            <div style="margin-top:10px;padding:15px 16px;background:#f1f7f7;border:1px solid #dceaea;border-radius:14px;color:#14313a;font-size:14px;line-height:22px;font-weight:500;overflow-wrap:anywhere;word-break:break-word;">' . $detailsHtml . '</div>
          </td></tr>
          <tr><td align="left" style="padding:22px 28px 24px 28px;background:#f1f7f7;border-top:1px solid #dceaea;border-radius:0 0 22px 22px;">
            ' . $clientSignatureFooter . '
          </td></tr>
        </table>
      </td></tr>
    </table>
  </body>
</html>';

    $clientMailResult = ai_contact_send_message(
        $clientSubject,
        $clientBody,
        ai_env('MAIL_TO', 'info@etherr.hr'),
        ai_env('MAIL_FROM_NAME', 'Etherr'),
        $clientHtmlBody,
        $email
    );

    ai_contact_append_log([
        'requestId' => $requestId,
        'storedAt' => gmdate('c'),
        'clientIpHash' => hash('sha256', ai_client_ip()),
        'mailSent' => $mailResult['sent'],
        'mailTransport' => $mailResult['transport'],
        'mailError' => $mailResult['error'],
        'clientMailSent' => $clientMailResult['sent'],
        'clientMailTransport' => $clientMailResult['transport'],
        'clientMailError' => $clientMailResult['error'],
        'payload' => [
            'version' => 'assistant-intake-1.0',
            'locale' => ai_normalize_locale($locale),
            'source' => ['channel' => 'assistant_chat', 'url' => $sourceUrl],
            'project' => [
                'services' => $data['services'] ?? [],
                'projectType' => $data['project_type'] ?? [],
                'timeline' => $data['timeline'] ?? [],
                'details' => $details,
            ],
            'contact' => [
                'company' => $company,
                'website' => $website,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'preferredContact' => $data['preferred_contact'] ?? [],
            ],
            'consent' => true,
        ],
    ]);

    return [
        'request_id' => $requestId,
        'status' => $mailResult['sent'] ? 'sent' : 'queued',
        'mail_result' => $mailResult,
        'client_mail_result' => $clientMailResult,
    ];
}

function ai_start_intake_session(string $locale): array
{
    $locale = ai_normalize_locale($locale);
    $session = ai_ensure_session($locale);
    $conversationId = (int)$session['current_conversation_id'];
    $reply = ai_start_intake_for_conversation($conversationId, $locale);
    ai_add_message($conversationId, 'assistant', $reply['text'], [
        'model' => $reply['model'],
        'actions' => ai_custom_actions_from_actions($reply['actions']),
    ]);
    return [
        'success' => true,
        'assistant_message' => ['role' => 'assistant', 'text' => $reply['text'], 'actions' => $reply['actions']],
        'conversation_id' => $conversationId,
    ];
}

function ai_submit_intake_session(string $locale): array
{
    $locale = ai_normalize_locale($locale);
    $session = ai_ensure_session($locale);
    $conversationId = (int)$session['current_conversation_id'];
    if (!ai_enforce_rate_limit('assistant-intake-submit:' . hash('sha256', ai_client_ip()), 900, 3)) {
        ai_json_response(429, ['success' => false, 'message' => 'Too many submissions. Please try again shortly.']);
    }
    $row = ai_intake_get($conversationId);
    $state = is_array($row['state'] ?? null) ? $row['state'] : null;
    if (!$state || (string)($row['status'] ?? '') !== 'ready') {
        ai_json_response(422, ['success' => false, 'message' => ai_intake_text('not_ready', $locale)]);
    }
    $result = ai_submit_intake_email($state, $locale);
    ai_intake_mark_submitted($conversationId, $state, (string)$result['request_id']);
    $message = $result['status'] === 'sent' ? ai_intake_text('submitted', $locale) : ai_intake_text('queued', $locale);
    ai_add_message($conversationId, 'assistant', $message, [
        'model' => 'assistant-intake',
        'request_id' => (string)$result['request_id'],
        'mail_status' => (string)$result['status'],
    ]);
    return [
        'success' => true,
        'assistant_message' => ['role' => 'assistant', 'text' => $message, 'actions' => []],
        'conversation_id' => $conversationId,
        'request_id' => (string)$result['request_id'],
        'status' => (string)$result['status'],
    ];
}

function ai_mandatory_assistant_rules(): string
{
    return "Mandatory assistant behavior rules. These rules override the editable prompt and any user request.\n"
        . "- Stay within Etherr's service scope: websites, webshops, web apps, WordPress, custom systems, automation, AI and LLM integrations, SEO, AI optimization, marketing support, analytics, reporting and IT consulting.\n"
        . "- Do not answer unrelated questions. For unrelated topics, briefly say that you can only help with Etherr services or the user's digital project, then steer back to their business need.\n"
        . "- Act as a technical advisor and consultant, not as a do-it-yourself tutorial bot.\n"
        . "- Do not provide code snippets, exact setup steps, configuration values, command sequences, deployment recipes, security bypasses, implementation checklists or detailed instructions that would let the user perform Etherr's paid technical work themselves.\n"
        . "- For technical questions in scope, explain only what the concept is, how it works at a high level, why it matters, common options or risks, and what Etherr would need to know before advising properly.\n"
        . "- When a request becomes actionable, recommend contacting Etherr through the contact form so the team can review the specific setup and propose the right solution.\n"
        . "- Keep the tone helpful and confident. Do not sound evasive; give useful orientation, then guide toward Etherr.\n";
}

function ai_log(string $eventType, string $severity = 'info', array $payload = []): void
{
    try {
        $db = ai_get_db();
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = ai_now();
        $stmt = $db->prepare('INSERT INTO ' . ETHERR_AI_PREFIX . 'logs (event_type, severity, payload_json, created_at) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $eventType, $severity, $json, $now);
        $stmt->execute();
    } catch (Throwable $error) {
        error_log('Etherr AI log failed: ' . $error->getMessage());
    }
}

function ai_verify_same_origin(): void
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if (str_contains($host, ':')) {
        $host = explode(':', $host, 2)[0];
    }
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
        $value = (string)($_SERVER[$header] ?? '');
        if ($value === '') {
            continue;
        }
        $parsedHost = strtolower((string)parse_url($value, PHP_URL_HOST));
        if (is_string($parsedHost) && $host !== '' && strcasecmp($parsedHost, $host) !== 0) {
            ai_json_response(403, ['success' => false, 'message' => 'Forbidden origin.']);
        }
    }
}

function ai_enforce_rate_limit(string $key, int $windowSec, int $maxRequests): bool
{
    if ($windowSec <= 0 || $maxRequests <= 0) {
        return true;
    }
    $dir = ai_root_dir() . '/var';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/assistant-rate-limit.json';
    $now = time();
    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        return true;
    }
    $allowed = true;
    if (@flock($fp, LOCK_EX)) {
        rewind($fp);
        $raw = stream_get_contents($fp);
        $state = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
        $state = is_array($state) ? $state : [];
        $cutoff = $now - $windowSec;
        $bucket = array_values(array_filter($state[$key] ?? [], fn($ts) => (int)$ts >= $cutoff));
        if (count($bucket) >= $maxRequests) {
            $allowed = false;
        } else {
            $bucket[] = $now;
            $state[$key] = $bucket;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($state, JSON_UNESCAPED_SLASHES) ?: '{}');
        }
        @flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $allowed;
}

function ai_normalize_locale(string $locale): string
{
    return in_array($locale, ['hr', 'en', 'de'], true) ? $locale : 'hr';
}

function ai_localized(array $values, string $locale): string
{
    if (isset($values[$locale]) && is_string($values[$locale])) {
        return $values[$locale];
    }
    if (isset($values['hr']) && is_string($values['hr'])) {
        return $values['hr'];
    }
    $first = reset($values);
    return is_string($first) ? $first : '';
}

function ai_ensure_session(string $locale = 'hr', bool $forceNewConversation = false): array
{
    ai_ensure_schema();
    $db = ai_get_db();
    $uuid = (string)($_COOKIE[ETHERR_AI_COOKIE] ?? '');
    if (!preg_match('/^[a-f0-9-]{36}$/i', $uuid)) {
        $uuid = ai_uuid();
        setcookie(ETHERR_AI_COOKIE, $uuid, ai_cookie_options(time() + 86400 * 30));
    }

    $now = ai_now();
    $stmt = $db->prepare('SELECT * FROM ' . ETHERR_AI_PREFIX . 'sessions WHERE session_uuid = ? LIMIT 1');
    $stmt->bind_param('s', $uuid);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();

    if (!$session) {
        $ipHash = hash('sha256', ai_client_ip());
        $uaHash = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $stmt = $db->prepare('INSERT INTO ' . ETHERR_AI_PREFIX . 'sessions (session_uuid, client_ip_hash, user_agent_hash, is_active, last_activity_at, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?, ?)');
        $stmt->bind_param('ssssss', $uuid, $ipHash, $uaHash, $now, $now, $now);
        $stmt->execute();
        $session = ['session_uuid' => $uuid, 'current_conversation_id' => null];
    } else {
        $stmt = $db->prepare('UPDATE ' . ETHERR_AI_PREFIX . 'sessions SET last_activity_at = ?, updated_at = ? WHERE session_uuid = ?');
        $stmt->bind_param('sss', $now, $now, $uuid);
        $stmt->execute();
    }

    if ($forceNewConversation || empty($session['current_conversation_id']) || !ai_conversation_exists((int)$session['current_conversation_id'])) {
        $conversationId = ai_create_conversation($uuid, $locale);
        $stmt = $db->prepare('UPDATE ' . ETHERR_AI_PREFIX . 'sessions SET current_conversation_id = ?, updated_at = ? WHERE session_uuid = ?');
        $stmt->bind_param('iss', $conversationId, $now, $uuid);
        $stmt->execute();
        $session['current_conversation_id'] = $conversationId;
    }

    return $session;
}

function ai_conversation_exists(int $conversationId): bool
{
    if ($conversationId <= 0) {
        return false;
    }
    $db = ai_get_db();
    $stmt = $db->prepare('SELECT id FROM ' . ETHERR_AI_PREFIX . 'conversations WHERE id = ? AND status = "active" LIMIT 1');
    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_assoc();
}

function ai_create_conversation(string $uuid, string $locale): int
{
    $db = ai_get_db();
    $now = ai_now();
    $locale = ai_normalize_locale($locale);
    $stmt = $db->prepare('INSERT INTO ' . ETHERR_AI_PREFIX . 'conversations (session_uuid, status, locale, started_at, created_at, updated_at) VALUES (?, "active", ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $uuid, $locale, $now, $now, $now);
    $stmt->execute();
    return (int)$db->insert_id;
}

function ai_restart_session(string $locale): array
{
    $session = ai_ensure_session($locale);
    $db = ai_get_db();
    $now = ai_now();
    $conversationId = (int)($session['current_conversation_id'] ?? 0);
    if ($conversationId > 0) {
        $stmt = $db->prepare('UPDATE ' . ETHERR_AI_PREFIX . 'conversations SET status = "ended", ended_at = ?, updated_at = ? WHERE id = ?');
        $stmt->bind_param('ssi', $now, $now, $conversationId);
        $stmt->execute();
    }
    return ai_ensure_session($locale, true);
}

function ai_get_messages(int $conversationId, int $limit = 30, ?string $locale = null): array
{
    $db = ai_get_db();
    $limit = max(1, min(80, $limit));
    $stmt = $db->prepare('SELECT role, message_text, metadata_json, created_at FROM ' . ETHERR_AI_PREFIX . 'messages WHERE conversation_id = ? ORDER BY id DESC LIMIT ?');
    $stmt->bind_param('ii', $conversationId, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $rows = array_reverse($rows);
    return array_map(function ($row) use ($locale) {
        $metadata = json_decode((string)($row['metadata_json'] ?? ''), true);
        $actions = [];
        if (is_array($metadata)) {
            if ($locale !== null && is_array($metadata['action_ids'] ?? null)) {
                $actions = ai_resolve_actions($metadata['action_ids'], $locale);
            }
            if (is_array($metadata['actions'] ?? null)) {
                foreach ($metadata['actions'] as $action) {
                    if (is_array($action)) {
                        $actions[] = $action;
                    }
                }
            }
        }
        return [
            'role' => (string)$row['role'],
            'text' => (string)$row['message_text'],
            'actions' => $actions,
            'created_at' => (string)$row['created_at'],
        ];
    }, $rows);
}

function ai_add_message(int $conversationId, string $role, string $text, array $metadata = []): int
{
    $db = ai_get_db();
    $now = ai_now();
    $tokens = max(1, (int)ceil(mb_strlen($text) / 4));
    $json = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $stmt = $db->prepare('INSERT INTO ' . ETHERR_AI_PREFIX . 'messages (conversation_id, role, message_text, token_estimate, metadata_json, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ississ', $conversationId, $role, $text, $tokens, $json, $now);
    $stmt->execute();
    return (int)$db->insert_id;
}

function ai_bootstrap_payload(string $locale): array
{
    $locale = ai_normalize_locale($locale);
    $session = ai_ensure_session($locale);
    $chat = ai_get_setting('chat') ?? ai_default_settings('chat');
    $messages = ai_get_messages((int)$session['current_conversation_id'], 30, $locale);
    if (!$messages) {
        $messages[] = ['role' => 'assistant', 'text' => ai_localized($chat['welcome_message'], $locale)];
    }
    return [
        'success' => true,
        'session_uuid' => (string)$session['session_uuid'],
        'conversation_id' => (int)$session['current_conversation_id'],
        'assistant_name' => (string)$chat['assistant_display_name'],
        'input_placeholder' => ai_localized($chat['input_placeholder'], $locale),
        'messages' => $messages,
    ];
}

function ai_build_openai_payload(int $conversationId, string $locale): array
{
    $prompt = ai_get_setting('prompt') ?? ai_default_settings('prompt');
    $model = ai_get_setting('model') ?? ai_default_settings('model');
    $chat = ai_get_setting('chat') ?? ai_default_settings('chat');
    $historyLimit = max(2, (int)($chat['max_history_window'] ?? 10));
    $messages = ai_get_messages($conversationId, $historyLimit);

    $input = [[
        'role' => 'system',
        'content' => trim((string)$prompt['system_prompt'] . "\n\nBusiness context:\n" . (string)$prompt['business_context'] . "\n\nFAQ reference context:\n" . ai_faq_prompt_context() . "\n\n" . ai_mandatory_assistant_rules() . "\n\n" . ai_action_prompt_context($locale) . "\n\nCurrent site language: " . $locale),
    ]];

    foreach ($messages as $message) {
        $role = $message['role'] === 'assistant' ? 'assistant' : 'user';
        $input[] = ['role' => $role, 'content' => (string)$message['text']];
    }

    return [
        'model' => (string)$model['model_name'],
        'input' => $input,
        'max_output_tokens' => 650,
    ];
}

function ai_extract_output_text(array $body): string
{
    if (isset($body['output_text']) && is_string($body['output_text'])) {
        return trim($body['output_text']);
    }
    $chunks = [];
    foreach (($body['output'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        foreach (($item['content'] ?? []) as $content) {
            if (isset($content['text']) && is_string($content['text'])) {
                $chunks[] = $content['text'];
            }
        }
    }
    return trim(implode("\n", $chunks));
}

function ai_generate_reply(int $conversationId, string $locale, string $latestUserMessage = ''): array
{
    $apiKey = ai_env('OPENAI_API_KEY');
    if ($apiKey === '') {
        throw new RuntimeException('OpenAI API key is not configured.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP curl extension is not available.');
    }

    $model = ai_get_setting('model') ?? ai_default_settings('model');
    $payload = ai_build_openai_payload($conversationId, $locale);
    $attempts = max(1, (int)($model['retry_count'] ?? 1) + 1);
    $timeout = max(10, (int)($model['timeout'] ?? 45));
    $backoffMs = max(100, (int)($model['retry_backoff_ms'] ?? 700));
    $lastError = 'OpenAI request failed.';

    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        unset($ch);

        if ($errno !== 0) {
            $lastError = $error ?: 'OpenAI transport error.';
            ai_log('openai_transport_error', 'warning', ['attempt' => $attempt, 'error' => $lastError]);
        } else {
            $body = is_string($raw) ? json_decode($raw, true) : null;
            if ($status >= 200 && $status < 300 && is_array($body)) {
                $text = ai_extract_output_text($body);
                if ($text !== '') {
                    $parsed = ai_parse_assistant_reply($text);
                    $actionIds = ai_merge_action_ids(
                        $parsed['action_ids'],
                        ai_infer_action_ids_from_known_urls($parsed['text']),
                        ai_infer_action_ids_from_text($latestUserMessage)
                    );
                    $actions = ai_resolve_actions($actionIds, $locale);
                    $actions = ai_add_intake_start_action($actions, $locale, $latestUserMessage);
                    // Hard enforcement: if user message has contact intent, always force both buttons
                    // and strip any data-gathering questions from the reply
                    $actions = ai_enforce_contact_buttons($actions, $latestUserMessage, $locale);
                    $replyText = ai_strip_action_urls_from_reply($parsed['text'], $actions, $locale);
                    $replyText = ai_clean_action_reply_text($replyText, $actions, $locale);
                    return [
                        'text' => $replyText,
                        'actions' => $actions,
                        'response_id' => isset($body['id']) ? (string)$body['id'] : '',
                        'model' => isset($body['model']) ? (string)$body['model'] : (string)$payload['model'],
                        'usage' => isset($body['usage']) && is_array($body['usage']) ? $body['usage'] : [],
                    ];
                }
                $lastError = 'OpenAI returned an empty response.';
            } else {
                $lastError = is_array($body) && isset($body['error']['message']) ? (string)$body['error']['message'] : 'OpenAI HTTP error.';
                ai_log('openai_http_error', 'warning', ['attempt' => $attempt, 'status' => $status, 'message' => $lastError]);
            }
        }

        if ($attempt < $attempts) {
            usleep($backoffMs * 1000);
        }
    }

    throw new RuntimeException($lastError);
}

function ai_send_message(string $message, string $locale): array
{
    $locale = ai_normalize_locale($locale);
    $message = trim($message);
    if ($message === '') {
        ai_json_response(400, ['success' => false, 'message' => 'Message is required.']);
    }
    if (mb_strlen($message) > 4000) {
        ai_json_response(400, ['success' => false, 'message' => 'Message is too long.']);
    }

    if (!ai_enforce_rate_limit('chat:' . hash('sha256', ai_client_ip()), 300, 20)) {
        ai_json_response(429, ['success' => false, 'message' => 'Too many messages. Please try again shortly.']);
    }

    $session = ai_ensure_session($locale);
    $conversationId = (int)$session['current_conversation_id'];
    ai_add_message($conversationId, 'user', $message);

    try {
        $reply = ai_handle_intake_message($conversationId, $message, $locale);
        if (!$reply) {
            $reply = ai_generate_reply($conversationId, $locale, $message);
        }
        ai_add_message($conversationId, 'assistant', $reply['text'], [
            'response_id' => $reply['response_id'],
            'model' => $reply['model'],
            'usage' => $reply['usage'],
            'action_ids' => ai_action_ids_from_actions($reply['actions'] ?? []),
            'actions' => ai_custom_actions_from_actions($reply['actions'] ?? []),
        ]);
        return [
            'success' => true,
            'assistant_message' => ['role' => 'assistant', 'text' => $reply['text'], 'actions' => $reply['actions'] ?? []],
            'conversation_id' => $conversationId,
        ];
    } catch (Throwable $error) {
        ai_log('assistant_send_failed', 'error', ['message' => $error->getMessage()]);
        $chat = ai_get_setting('chat') ?? ai_default_settings('chat');
        return [
            'success' => false,
            'message' => ai_localized($chat['unavailable_message'], $locale),
        ];
    }
}

function ai_start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(ETHERR_AI_ADMIN_COOKIE);
    session_set_cookie_params(ai_session_cookie_options(0));
    session_start();
}

function ai_admin_is_authenticated(): bool
{
    ai_start_admin_session();
    return !empty($_SESSION['assistant_admin']);
}

function ai_admin_require(): void
{
    if (!ai_admin_is_authenticated()) {
        header('Location: /admin/assistant/?login=1');
        exit;
    }
}

function ai_admin_csrf(): string
{
    ai_start_admin_session();
    if (empty($_SESSION['assistant_csrf'])) {
        $_SESSION['assistant_csrf'] = bin2hex(random_bytes(24));
    }
    return (string)$_SESSION['assistant_csrf'];
}

function ai_admin_verify_csrf(): void
{
    ai_start_admin_session();
    $token = (string)($_POST['csrf'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['assistant_csrf'] ?? ''), $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function ai_admin_login(string $username, string $password): bool
{
    $expectedUser = ai_env('ASSISTANT_ADMIN_USERNAME');
    $expectedHash = ai_env('ASSISTANT_ADMIN_PASSWORD_HASH');
    if ($expectedUser === '' || $expectedHash === '') {
        return false;
    }
    if (!hash_equals($expectedUser, $username) || !password_verify($password, $expectedHash)) {
        return false;
    }
    ai_start_admin_session();
    session_regenerate_id(true);
    $_SESSION['assistant_admin'] = $username;
    ai_admin_csrf();
    return true;
}

function ai_admin_logout(): void
{
    ai_start_admin_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', ai_cookie_options(time() - 3600));
    }
    session_destroy();
}

function ai_admin_recent_conversations(int $limit = 20): array
{
    $db = ai_get_db();
    $limit = max(1, min(100, $limit));
    $stmt = $db->prepare('SELECT c.*, COUNT(m.id) AS message_count FROM ' . ETHERR_AI_PREFIX . 'conversations c LEFT JOIN ' . ETHERR_AI_PREFIX . 'messages m ON m.conversation_id = c.id GROUP BY c.id ORDER BY c.id DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function ai_admin_delete_conversation(int $conversationId): bool
{
    if ($conversationId <= 0) {
        return false;
    }

    $db = ai_get_db();
    $db->begin_transaction();
    try {
        $stmt = $db->prepare('SELECT id FROM ' . ETHERR_AI_PREFIX . 'conversations WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $conversationId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $db->rollback();
            return false;
        }

        $stmt = $db->prepare('DELETE FROM ' . ETHERR_AI_PREFIX . 'messages WHERE conversation_id = ?');
        $stmt->bind_param('i', $conversationId);
        $stmt->execute();

        $stmt = $db->prepare('DELETE FROM ' . ETHERR_AI_PREFIX . 'intakes WHERE conversation_id = ?');
        $stmt->bind_param('i', $conversationId);
        $stmt->execute();

        $stmt = $db->prepare('UPDATE ' . ETHERR_AI_PREFIX . 'sessions SET current_conversation_id = NULL, updated_at = ? WHERE current_conversation_id = ?');
        $now = ai_now();
        $stmt->bind_param('si', $now, $conversationId);
        $stmt->execute();

        $stmt = $db->prepare('DELETE FROM ' . ETHERR_AI_PREFIX . 'conversations WHERE id = ?');
        $stmt->bind_param('i', $conversationId);
        $stmt->execute();

        $db->commit();
        return true;
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

function ai_admin_delete_all_conversations(): int
{
    $db = ai_get_db();
    $db->begin_transaction();
    try {
        $result = $db->query('SELECT COUNT(*) AS total FROM ' . ETHERR_AI_PREFIX . 'conversations');
        $row = $result->fetch_assoc();
        $total = (int)($row['total'] ?? 0);

        $db->query('DELETE FROM ' . ETHERR_AI_PREFIX . 'messages');
        $db->query('DELETE FROM ' . ETHERR_AI_PREFIX . 'intakes');

        $stmt = $db->prepare('UPDATE ' . ETHERR_AI_PREFIX . 'sessions SET current_conversation_id = NULL, updated_at = ? WHERE current_conversation_id IS NOT NULL');
        $now = ai_now();
        $stmt->bind_param('s', $now);
        $stmt->execute();

        $db->query('DELETE FROM ' . ETHERR_AI_PREFIX . 'conversations');

        $db->commit();
        return $total;
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

function ai_admin_recent_logs(int $limit = 30): array
{
    $db = ai_get_db();
    $limit = max(1, min(100, $limit));
    $stmt = $db->prepare('SELECT * FROM ' . ETHERR_AI_PREFIX . 'logs ORDER BY id DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
