# Zgłaszanie podatności

Bezpieczeństwo tego projektu jest dla mnie ważne — jeśli znalazłeś podatność, zgłoś ją **prywatnie** zamiast otwierać publicznego issue.

## Kanały zgłoszenia

- **Preferowany:** [Security Advisory na GitHubie](https://github.com/GronskiDeveloper/booking-ai-demo/security/advisories/new).
- **Alternatywnie:** e-mail na **dominik.gronski@grodev.pl** z tematem `[SECURITY] booking-ai-demo`.

## Kontekst tego projektu

To repo to **demo integracji** trzech osobnych komponentów (booking engine + widget czatu + Claude proxy). Najkrytyczniejsze klasy podatności:

- **Wyciek klucza API do Anthropic** — cała architektura opiera się na tym, że `ANTHROPIC_API_KEY` żyje tylko po stronie serwera (`backend/public/chat.php`). Jakakolwiek zmiana, która eksponuje klucz do frontend / logu / response body → krytyczna.
- **Race condition na `bookings.json`** — w tym demo plik jest **read-only** (kliknięcie slotu nie POST-uje). Ale jeśli ktoś sforkuje repo i doda write, bez file locking + atomic swap + unique constraint dostaje double bookingi.
- **XSS przez treść czatu / komunikaty w kalendarzu** — React JSX chroni domyślnie przez `textContent`; jakakolwiek zmiana wprowadzająca `dangerouslySetInnerHTML` na treści z API łamie tę gwarancję.
- **CORS misconfiguration w produkcji** — `CHAT_ALLOWED_ORIGIN=*` w produkcji pozwala każdej stronie proxywać przez Twój klucz Anthropic i palić Ci konto. Ustawić na realny origin.
- **Prompt injection do system promptu** — asystentka salonu ma explicit rule delegacji terminów do kalendarza. User może próbować obejść („ignoruj poprzednie instrukcje..."). Rate limiting + max_turns cap chroni częściowo; w produkcji dodać moderation layer.

Podatności w warstwie `booking-slots-php` (silnik matematyczny) zgłaszaj do [tamtego repo](https://github.com/GronskiDeveloper/booking-slots-php/blob/main/SECURITY.md), a te w `@grodev/claude-chat-react` (widget) do [claude-chat-react](https://github.com/GronskiDeveloper/claude-chat-react/blob/main/SECURITY.md).

## Reakcja

- **Potwierdzenie odbioru:** 72h.
- **Wstępna ocena:** 7 dni.
- **Fix:** zależnie od skali (krytyczne — priorytetowo).

Podziękuję imiennie w release notes (o ile nie prosisz o anonimowość).

Autor: [Dominik Groński / GroDev](https://grodev.pl)
