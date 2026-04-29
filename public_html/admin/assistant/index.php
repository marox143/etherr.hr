<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/api/assistant/common.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function textarea_value(array $settings, string $key): string
{
    return h((string)($settings[$key] ?? ''));
}

function setting_text(array $settings, string $key, string $locale): string
{
    $value = $settings[$key] ?? [];
    return h(is_array($value) ? (string)($value[$locale] ?? '') : (string)$value);
}

ai_start_admin_session();

if (($_GET['logout'] ?? '') === '1') {
    ai_admin_logout();
    header('Location: /admin/assistant/');
    exit;
}

$loginError = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (ai_admin_login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''))) {
        header('Location: /admin/assistant/');
        exit;
    }
    $loginError = 'Invalid credentials or admin environment variables are missing.';
}

if (!ai_admin_is_authenticated()) {
    $hashExample = "php -r 'echo password_hash(\"your-password\", PASSWORD_DEFAULT) . PHP_EOL;'";
    ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Etherr AI Admin</title>
    <link rel="stylesheet" href="/style.css" />
    <link rel="stylesheet" href="/assets/assistant/assistant.css" />
  </head>
  <body class="etherr-ai-admin-page">
    <main class="etherr-ai-admin-login">
      <form class="etherr-ai-admin-card" method="post">
        <input type="hidden" name="action" value="login" />
        <h1>Etherr AI Admin</h1>
        <p>Sign in with credentials configured in `.env`.</p>
        <?php if ($loginError !== '') : ?>
          <div class="etherr-ai-admin-alert"><?php echo h($loginError); ?></div>
        <?php endif; ?>
        <?php if (ai_env('ASSISTANT_ADMIN_USERNAME') === '' || ai_env('ASSISTANT_ADMIN_PASSWORD_HASH') === '') : ?>
          <div class="etherr-ai-admin-alert">
            Add `ASSISTANT_ADMIN_USERNAME` and `ASSISTANT_ADMIN_PASSWORD_HASH` to `.env`.
            Generate the hash with: <code><?php echo h($hashExample); ?></code>
          </div>
        <?php endif; ?>
        <label>
          Username
          <input type="text" name="username" autocomplete="username" required />
        </label>
        <label>
          Password
          <input type="password" name="password" autocomplete="current-password" required />
        </label>
        <button type="submit">Sign in</button>
      </form>
    </main>
  </body>
</html>
    <?php
    exit;
}

$notice = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    ai_admin_verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'install') {
            ai_ensure_schema();
            $notice = 'Database tables are ready.';
        }

        if ($action === 'save_chat') {
            $current = ai_get_setting('chat') ?? ai_default_settings('chat');
            $current['assistant_display_name'] = trim((string)($_POST['assistant_display_name'] ?? 'Etherr AI'));
            $current['default_language'] = ai_normalize_locale((string)($_POST['default_language'] ?? 'hr'));
            $current['max_history_window'] = max(2, (int)($_POST['max_history_window'] ?? 10));
            foreach (['hr', 'en', 'de'] as $locale) {
                $current['welcome_message'][$locale] = trim((string)($_POST['welcome_message_' . $locale] ?? ''));
                $current['input_placeholder'][$locale] = trim((string)($_POST['input_placeholder_' . $locale] ?? ''));
                $current['unavailable_message'][$locale] = trim((string)($_POST['unavailable_message_' . $locale] ?? ''));
            }
            ai_save_setting('chat', $current);
            $notice = 'Chat settings saved.';
        }

        if ($action === 'save_prompt') {
            ai_save_setting('prompt', [
                'system_prompt' => trim((string)($_POST['system_prompt'] ?? '')),
                'business_context' => trim((string)($_POST['business_context'] ?? '')),
            ]);
            $notice = 'Prompt settings saved.';
        }

        if ($action === 'save_actions') {
            $currentActions = ai_get_setting('actions') ?? ai_default_settings('actions');
            $items = ai_normalize_action_items($currentActions);
            $enabled = is_array($_POST['action_enabled'] ?? null) ? $_POST['action_enabled'] : [];
            $urls = is_array($_POST['action_url'] ?? null) ? $_POST['action_url'] : [];
            $labels = is_array($_POST['action_label'] ?? null) ? $_POST['action_label'] : [];
            $descriptions = is_array($_POST['action_description'] ?? null) ? $_POST['action_description'] : [];
            $savedItems = [];
            foreach ($items as $item) {
                $id = (string)$item['id'];
                $postedLabels = is_array($labels[$id] ?? null) ? $labels[$id] : [];
                $savedItems[] = [
                    'id' => $id,
                    'enabled' => isset($enabled[$id]),
                    'url' => ai_normalize_action_url((string)($urls[$id] ?? '')),
                    'label' => [
                        'hr' => trim((string)($postedLabels['hr'] ?? '')),
                        'en' => trim((string)($postedLabels['en'] ?? '')),
                        'de' => trim((string)($postedLabels['de'] ?? '')),
                    ],
                    'description' => trim((string)($descriptions[$id] ?? '')),
                ];
            }
            ai_save_setting('actions', ['items' => $savedItems]);
            $notice = 'Action button settings saved.';
        }

        if ($action === 'save_model') {
            ai_save_setting('model', [
                'model_name' => trim((string)($_POST['model_name'] ?? 'gpt-5.4-mini')),
                'timeout' => max(10, (int)($_POST['timeout'] ?? 45)),
                'retry_count' => max(0, (int)($_POST['retry_count'] ?? 1)),
                'retry_backoff_ms' => max(100, (int)($_POST['retry_backoff_ms'] ?? 700)),
            ]);
            $notice = 'Model settings saved.';
        }

        if ($action === 'save_intake') {
            ai_save_setting('intake', [
                'enabled' => isset($_POST['intake_enabled']),
            ]);
            $notice = 'Chat intake settings saved.';
        }

        if ($action === 'delete_conversation') {
            $conversationId = max(0, (int)($_POST['conversation_id'] ?? 0));
            if (ai_admin_delete_conversation($conversationId)) {
                $notice = 'Conversation deleted.';
            } else {
                $error = 'Conversation was not found.';
            }
        }

        if ($action === 'delete_all_conversations') {
            $deletedCount = ai_admin_delete_all_conversations();
            $notice = $deletedCount === 1 ? '1 conversation deleted.' : $deletedCount . ' conversations deleted.';
        }
    } catch (Throwable $caught) {
        $error = $caught->getMessage();
    }
}

$dbOk = false;
$schemaOk = false;
$chat = ai_default_settings('chat');
$prompt = ai_default_settings('prompt');
$model = ai_default_settings('model');
$intake = ai_default_settings('intake');
$actions = ai_default_settings('actions');
$conversations = [];
$logs = [];
$selectedMessages = [];
$selectedConversationId = 0;

try {
    ai_ensure_schema();
    $dbOk = true;
    $schemaOk = true;
    $chat = ai_get_setting('chat') ?? $chat;
    $prompt = ai_get_setting('prompt') ?? $prompt;
    $model = ai_get_setting('model') ?? $model;
    $intake = ai_get_setting('intake') ?? $intake;
    $actions = ai_get_setting('actions') ?? $actions;
    $conversations = ai_admin_recent_conversations();
    $logs = ai_admin_recent_logs();
    $selectedConversationId = max(0, (int)($_GET['conversation'] ?? 0));
    if ($selectedConversationId > 0) {
        $selectedMessages = ai_get_messages($selectedConversationId, 80);
    }
} catch (Throwable $caught) {
    $error = $error !== '' ? $error : $caught->getMessage();
}

$csrf = ai_admin_csrf();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Etherr AI Admin</title>
    <link rel="stylesheet" href="/style.css" />
    <link rel="stylesheet" href="/assets/assistant/assistant.css" />
  </head>
  <body class="etherr-ai-admin-page">
    <main class="etherr-ai-admin-shell">
      <header class="etherr-ai-admin-topbar">
        <div>
          <p>Etherr</p>
          <h1>AI Assistant Admin</h1>
        </div>
        <a href="/admin/assistant/?logout=1">Log out</a>
      </header>

      <?php if ($notice !== '') : ?>
        <div class="etherr-ai-admin-success"><?php echo h($notice); ?></div>
      <?php endif; ?>
      <?php if ($error !== '') : ?>
        <div class="etherr-ai-admin-alert"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="etherr-ai-admin-grid">
        <article class="etherr-ai-admin-card">
          <h2>Status</h2>
          <p><strong>Database:</strong> <?php echo $dbOk ? 'Connected' : 'Not ready'; ?></p>
          <p><strong>Schema:</strong> <?php echo $schemaOk ? 'Ready' : 'Needs install'; ?></p>
          <p><strong>OpenAI key:</strong> <?php echo ai_env('OPENAI_API_KEY') !== '' ? 'Configured' : 'Missing'; ?></p>
          <p><strong>Admin auth:</strong> <?php echo ai_env('ASSISTANT_ADMIN_USERNAME') !== '' && ai_env('ASSISTANT_ADMIN_PASSWORD_HASH') !== '' ? 'Configured' : 'Missing'; ?></p>
          <form method="post">
            <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
            <input type="hidden" name="action" value="install" />
            <button type="submit">Install / repair tables</button>
          </form>
        </article>

        <article class="etherr-ai-admin-card">
          <h2>Model</h2>
          <form method="post" class="etherr-ai-admin-form">
            <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
            <input type="hidden" name="action" value="save_model" />
            <label>Model name <input name="model_name" value="<?php echo h((string)$model['model_name']); ?>" /></label>
            <label>Timeout <input name="timeout" type="number" min="10" value="<?php echo h((string)$model['timeout']); ?>" /></label>
            <label>Retry count <input name="retry_count" type="number" min="0" value="<?php echo h((string)$model['retry_count']); ?>" /></label>
            <label>Retry backoff ms <input name="retry_backoff_ms" type="number" min="100" value="<?php echo h((string)$model['retry_backoff_ms']); ?>" /></label>
            <button type="submit">Save model</button>
          </form>
        </article>
      </section>

      <section class="etherr-ai-admin-card">
        <h2>Chat Settings</h2>
        <form method="post" class="etherr-ai-admin-form etherr-ai-admin-form-wide">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
          <input type="hidden" name="action" value="save_chat" />
          <label>Assistant name <input name="assistant_display_name" value="<?php echo h((string)$chat['assistant_display_name']); ?>" /></label>
          <label>Default language
            <select name="default_language">
              <?php foreach (['hr', 'en', 'de'] as $locale) : ?>
                <option value="<?php echo h($locale); ?>"<?php echo ($chat['default_language'] ?? 'hr') === $locale ? ' selected' : ''; ?>><?php echo h(strtoupper($locale)); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>History window <input name="max_history_window" type="number" min="2" value="<?php echo h((string)$chat['max_history_window']); ?>" /></label>
          <div class="etherr-ai-admin-locale-grid">
            <?php foreach (['hr', 'en', 'de'] as $locale) : ?>
              <div class="etherr-ai-admin-locale-group">
                <h3><?php echo h(strtoupper($locale)); ?></h3>
                <label>Welcome message <textarea name="welcome_message_<?php echo h($locale); ?>" rows="3"><?php echo setting_text($chat, 'welcome_message', $locale); ?></textarea></label>
                <label>Input placeholder <input name="input_placeholder_<?php echo h($locale); ?>" value="<?php echo setting_text($chat, 'input_placeholder', $locale); ?>" /></label>
                <label>Unavailable message <input name="unavailable_message_<?php echo h($locale); ?>" value="<?php echo setting_text($chat, 'unavailable_message', $locale); ?>" /></label>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="submit">Save chat settings</button>
        </form>
      </section>

      <section class="etherr-ai-admin-card">
        <h2>Prompt Settings</h2>
        <form method="post" class="etherr-ai-admin-form etherr-ai-admin-form-wide">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
          <input type="hidden" name="action" value="save_prompt" />
          <label>System prompt <textarea name="system_prompt" rows="10"><?php echo textarea_value($prompt, 'system_prompt'); ?></textarea></label>
          <label>Business context <textarea name="business_context" rows="12"><?php echo textarea_value($prompt, 'business_context'); ?></textarea></label>
          <button type="submit">Save prompt</button>
        </form>
      </section>

      <section class="etherr-ai-admin-card">
        <h2>Chatbot Contact Intake</h2>
        <form method="post" class="etherr-ai-admin-form etherr-ai-admin-form-wide">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
          <input type="hidden" name="action" value="save_intake" />
          <label class="etherr-ai-admin-check">
            <input type="checkbox" name="intake_enabled" value="1"<?php echo !empty($intake['enabled']) ? ' checked' : ''; ?> />
            Enable in-chat contact inquiry collection and submission
          </label>
          <p class="etherr-ai-admin-help">When enabled, the assistant can collect contact-form details in chat and submit them to the configured email only after the visitor confirms with the final button.</p>
          <button type="submit">Save intake settings</button>
        </form>
      </section>

      <section class="etherr-ai-admin-card">
        <h2>Action Button Settings</h2>
        <form method="post" class="etherr-ai-admin-form etherr-ai-admin-form-wide">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
          <input type="hidden" name="action" value="save_actions" />
          <div class="etherr-ai-admin-action-settings">
            <?php foreach (ai_normalize_action_items($actions) as $item) : ?>
              <?php $actionId = (string)$item['id']; ?>
              <fieldset class="etherr-ai-admin-action-setting">
                <legend><?php echo h($actionId); ?></legend>
                <label class="etherr-ai-admin-check">
                  <input type="checkbox" name="action_enabled[<?php echo h($actionId); ?>]" value="1"<?php echo !empty($item['enabled']) ? ' checked' : ''; ?> />
                  Enabled
                </label>
                <label>URL <input name="action_url[<?php echo h($actionId); ?>]" value="<?php echo h((string)$item['url']); ?>" /></label>
                <div class="etherr-ai-admin-action-labels">
                  <?php foreach (['hr', 'en', 'de'] as $locale) : ?>
                    <label><?php echo h(strtoupper($locale)); ?> label <input name="action_label[<?php echo h($actionId); ?>][<?php echo h($locale); ?>]" value="<?php echo h((string)($item['label'][$locale] ?? '')); ?>" /></label>
                  <?php endforeach; ?>
                </div>
                <label>When to use <textarea name="action_description[<?php echo h($actionId); ?>]" rows="2"><?php echo h((string)$item['description']); ?></textarea></label>
              </fieldset>
            <?php endforeach; ?>
          </div>
          <button type="submit">Save action buttons</button>
        </form>
      </section>

      <section class="etherr-ai-admin-grid">
        <article class="etherr-ai-admin-card">
          <div class="etherr-ai-admin-card-heading">
            <h2>Recent Conversations</h2>
            <?php if ($conversations) : ?>
              <form method="post" action="/admin/assistant/" onsubmit="return confirm('Delete ALL conversations and messages? This cannot be undone.');">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
                <input type="hidden" name="action" value="delete_all_conversations" />
                <button type="submit" class="etherr-ai-admin-danger-button">Delete all</button>
              </form>
            <?php endif; ?>
          </div>
          <?php if (!$conversations) : ?>
            <p>No conversations yet.</p>
          <?php else : ?>
            <div class="etherr-ai-admin-table-wrap">
              <table>
                <thead><tr><th>ID</th><th>Locale</th><th>Status</th><th>Messages</th><th>Started</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($conversations as $conversation) : ?>
                    <tr>
                      <td><a href="/admin/assistant/?conversation=<?php echo h((string)$conversation['id']); ?>"><?php echo h((string)$conversation['id']); ?></a></td>
                      <td><?php echo h((string)$conversation['locale']); ?></td>
                      <td><?php echo h((string)$conversation['status']); ?></td>
                      <td><?php echo h((string)$conversation['message_count']); ?></td>
                      <td><?php echo h((string)$conversation['started_at']); ?></td>
                      <td>
                        <div class="etherr-ai-admin-row-actions">
                          <a class="etherr-ai-admin-action-link" href="/admin/assistant/?conversation=<?php echo h((string)$conversation['id']); ?>#conversation-view">View</a>
                          <form method="post" action="/admin/assistant/" onsubmit="return confirm('Delete this conversation and all its messages?');">
                            <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>" />
                            <input type="hidden" name="action" value="delete_conversation" />
                            <input type="hidden" name="conversation_id" value="<?php echo h((string)$conversation['id']); ?>" />
                            <button type="submit" class="etherr-ai-admin-danger-button">Delete</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </article>

        <article class="etherr-ai-admin-card">
          <h2>Recent Logs</h2>
          <?php if (!$logs) : ?>
            <p>No logs yet.</p>
          <?php else : ?>
            <div class="etherr-ai-admin-log-list">
              <?php foreach ($logs as $log) : ?>
                <details>
                  <summary><?php echo h((string)$log['created_at'] . ' · ' . (string)$log['severity'] . ' · ' . (string)$log['event_type']); ?></summary>
                  <pre><?php echo h((string)$log['payload_json']); ?></pre>
                </details>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      </section>

      <?php if ($selectedConversationId > 0) : ?>
        <section class="etherr-ai-admin-card" id="conversation-view">
          <h2>Conversation <?php echo h((string)$selectedConversationId); ?></h2>
          <?php if ($selectedMessages) : ?>
            <div class="etherr-ai-admin-messages">
              <?php foreach ($selectedMessages as $message) : ?>
                <div class="etherr-ai-admin-message etherr-ai-admin-message-<?php echo h((string)$message['role']); ?>">
                  <strong><?php echo h((string)$message['role']); ?></strong>
                  <p><?php echo nl2br(h((string)$message['text'])); ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else : ?>
            <p>No messages in this conversation yet.</p>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    </main>
  </body>
</html>
