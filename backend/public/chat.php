<?php

declare(strict_types=1);

/**
 * POST /chat.php  { messages: [{role, content}, ...] }
 *
 * SSE-streaming chat proxy tailored for a beauty-salon assistant. Same wire
 * contract as @grodev/claude-chat-widget so the @grodev/claude-chat-react
 * component can talk to it out of the box.
 *
 * The system prompt is salon-specific: opening hours, price list, booking
 * flow, and a rule that price/availability questions get delegated to the
 * booking endpoint rather than answered from thin air.
 *
 * Built by GroDev — https://grodev.pl/ai
 */

require __DIR__ . '/../vendor/autoload.php';

use Anthropic\Client;
use Anthropic\Messages\RawContentBlockDeltaEvent;
use Anthropic\Messages\TextDelta;
use Anthropic\Core\Exceptions\APIStatusException;

// -- Config -------------------------------------------------------------------

$apiKey        = getenv('ANTHROPIC_API_KEY') ?: '';
$model         = getenv('ANTHROPIC_MODEL') ?: 'claude-opus-5';
$allowedOrigin = getenv('CHAT_ALLOWED_ORIGIN') ?: '*';
$maxTurns      = (int) (getenv('CHAT_MAX_TURNS') ?: 20);
$maxChars      = (int) (getenv('CHAT_MAX_CHARS') ?: 4000);

$systemPrompt = <<<'PROMPT'
Jesteś asystentką salonu Beauty Studio "Anna". Odpowiadasz na pytania klientów po polsku, krótko i konkretnie. Zawsze przyjazna i profesjonalna.

FAKTY O SALONIE:
- Godziny otwarcia: poniedziałek–sobota 9:00–17:00. Nieczynne w niedziele.
- Adres: ul. Kwiatowa 12, Poznań.
- Telefon: +48 555 123 456.

CENNIK:
- Manicure hybrydowy: 120 zł (45 min)
- Pedicure klasyczny: 150 zł (60 min)
- Farbowanie włosów (długie): 250-350 zł (1,5–2h)
- Strzyżenie damskie: 80 zł (45 min)
- Strzyżenie męskie: 50 zł (30 min)
- Zabieg oczyszczający na twarz: 180 zł (60 min)

ZASADY:
- Gdy klient pyta o WOLNE TERMINY albo chce się umówić — powiedz: "Sprawdzę dostępne terminy — jaka data Cię interesuje?" i poczekaj na datę. NIE zgaduj terminów; system rezerwacji obok pokaże realne wolne sloty.
- Gdy klient pyta o cenę usługi spoza cennika — odpowiedz: "Ta usługa nie jest w naszym standardowym cenniku — poproszę o telefon albo osobisty kontakt, damy wycenę indywidualną."
- Gdy pytanie jest złożone (reklamacja, zwrot, alergia, przeciwwskazania) — powiedz uczciwie: "To lepiej omówić telefonicznie z Anną — proszę dzwonić na +48 555 123 456."
- Nie wymyślaj promocji, rabatów ani programów lojalnościowych.
- Odpowiedzi krótkie: 1-3 zdania. To czat, nie broszura.
PROMPT;

// -- Small helpers ------------------------------------------------------------

function sse(array $payload): void
{
    echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    if (ob_get_level() > 0) { ob_flush(); }
    flush();
}

function fail(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// -- CORS + method handling ---------------------------------------------------

header('Access-Control-Allow-Origin: ' . $allowedOrigin);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { fail(405, 'Method not allowed. Use POST.'); }
if ($apiKey === '') { fail(500, 'Server not configured: ANTHROPIC_API_KEY missing.'); }

// -- Parse and validate the conversation -------------------------------------

$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['messages']) || !is_array($data['messages'])) {
    fail(400, 'Expected JSON body with a "messages" array.');
}

$messages = [];
foreach ($data['messages'] as $m) {
    $role    = is_array($m) ? ($m['role'] ?? '') : '';
    $content = is_array($m) ? ($m['content'] ?? '') : '';
    if (!in_array($role, ['user', 'assistant'], true) || !is_string($content)) { continue; }
    $content = trim($content);
    if ($content === '') { continue; }
    if (mb_strlen($content) > $maxChars) { $content = mb_substr($content, 0, $maxChars); }
    $messages[] = ['role' => $role, 'content' => $content];
}

if (count($messages) > $maxTurns) { $messages = array_slice($messages, -$maxTurns); }
if ($messages === [] || $messages[array_key_last($messages)]['role'] !== 'user') {
    fail(400, 'Conversation must end with a user message.');
}

// -- Stream reply -------------------------------------------------------------

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

while (ob_get_level() > 0) { ob_end_flush(); }

$client = new Client(apiKey: $apiKey);

try {
    $stream = $client->messages->createStream(
        model: $model,
        maxTokens: 512, // salon replies are short — no need for essays
        system: [
            ['type' => 'text', 'text' => $systemPrompt, 'cacheControl' => ['type' => 'ephemeral']],
        ],
        messages: $messages,
    );

    foreach ($stream as $event) {
        if ($event instanceof RawContentBlockDeltaEvent && $event->delta instanceof TextDelta) {
            sse(['text' => $event->delta->text]);
        }
    }
    sse(['done' => true]);
} catch (APIStatusException $e) {
    sse(['error' => 'Upstream error (' . ($e->type?->value ?? 'api_error') . '). Please try again.']);
    sse(['done' => true]);
} catch (\Throwable $e) {
    sse(['error' => 'Something went wrong. Please try again.']);
    sse(['done' => true]);
}
