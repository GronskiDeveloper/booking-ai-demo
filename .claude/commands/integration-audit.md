---
description: Audyt kontraktów między frontend/backend/AI po każdej zmianie w integracji
---

Recenzujesz zmianę w `booking-ai-demo` — repo, które łączy trzy komponenty: `booking-slots-php` (backend engine), `@grodev/claude-chat-react` (frontend widget), oraz PHP proxy do Claude API. Najczęstsza klasa błędu w integracji to **rozjazd kontraktów między warstwami** — coś zmienia się w jednym miejscu, a druga strona nie wie o tym i sypie w runtime, nie w compile time.

Zanim zaakceptujesz diff, sprawdź osiem punktów:

1. **Wire format `chat.php` musi zgadzać się z `useClaudeStream` hook w `@grodev/claude-chat-react`.** POST JSON `{messages: [{role, content}]}`, response SSE `data: {"text": "..."}` / `data: {"done": true}` / `data: {"error": "..."}`. Jeśli diff zmienia format response (np. dodaje pole `type`), musi zmienić też version constraint na `@grodev/claude-chat-react` w `frontend/package.json`.

2. **`endpoint` prop w `<ClaudeChat>` musi wskazywać na to co `vite.config.ts` proxy'uje.** Obecnie: `/api/chat.php` → Vite proxy → `http://localhost:8000/chat.php` (PHP dev server). Jeśli diff zmienia ścieżkę w jednym miejscu, sprawdź drugie. Test: `npm run dev` + `php -S localhost:8000 -t backend/public`, kliknij widget, zobacz Network tab.

3. **System prompt w `chat.php` MUSI zawierać zasadę delegacji do kalendarza.** Bez linii „Gdy klient pyta o WOLNE TERMINY — powiedz: 'Sprawdzę dostępne terminy — jaka data Cię interesuje?' i NIE zgaduj terminów" — asystentka będzie halucynować terminy, a klient przyjedzie na termin którego nie ma. To biznesowo krytyczne, nie tuning promptu.

4. **`slots.php` musi respektować `notBefore` lead time.** Bez tego użytkownik może próbować umówić się „za godzinę" — realny system musi mieć bufor (my dajemy 2h, `->modify('+2 hours')`). Jeśli diff usuwa `notBefore`, blokuj.

5. **Bufor 10 min między rezerwacjami (`bufferMinutes: 10`)** musi zostać — bez tego dwie sąsiednie wizyty stykają się bez czasu na sprzątanie / turnaround. Dokumentacja `booking-slots-php` tłumaczy dlaczego.

6. **Frontend NIE może zawierać klucza API do Anthropica.** Cała komunikacja z Claude idzie przez `chat.php` proxy. Diff który dodaje `Anthropic()` client albo `import '@anthropic-ai/sdk'` w `frontend/src/` → fundamentalnie zły, blokuj natychmiast.

7. **CORS musi pasować do deploy strategy.** W dev: Vite proxy = same origin = brak CORS-a potrzebny. W produkcji: albo (a) frontend i PHP na tej samej domenie (najprostsze), albo (b) ustaw `BOOKING_ALLOWED_ORIGIN` i `CHAT_ALLOWED_ORIGIN` na env-vars z realną domeną frontendu. `*` w produkcji = każdy może palić Twój klucz.

8. **`bookings.json` jest read-only w tym demo.** Jeśli diff dodaje write (fs.writeFileSync albo file_put_contents), musi też dodać: (a) file locking (flock) żeby uniknąć corrupted JSON pod concurrent writes, (b) unique constraint na `(date, start)`, (c) atomic swap (write to temp file + rename). Bez tego → race condition + double booking.

Jeśli którykolwiek punkt pęka — zablokuj zmianę i wróć z konkretnym scenariuszem, który się złamie.

Bonus: uruchom cały stack lokalnie przed acceptem (`npm run dev` + `php -S localhost:8000 -t backend/public`), przeklikaj: (a) zmiana daty w kalendarzu, (b) zapytanie asystentki o cenę, (c) zapytanie o wolny termin (musi delegować do kalendarza, nie zgadywać). Jeśli któryś krok sypie — repo nie jest gotowe do mergu.
