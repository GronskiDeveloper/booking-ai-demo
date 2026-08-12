# Praca AI-first — notatki dla tego repo

Trzymam ten plik w repozytorium, ponieważ buduję z Claude Code (Anthropic) i chcę, żeby podział „człowiek/AI" był widoczny z drzewa plików, a nie deklarowany w README. Rekruter, klient albo kolega z zespołu ma tu dowody, nie ogólniki.

## To repo jest inne niż pozostałe

Cztery moje wcześniejsze publiczne repo (`claude-chat-widget`, `claude-chat-react`, `booking-slots-php`, `woocommerce-custom-product-data`) to **izolowane komponenty** — każde robi jedną rzecz. To repo (`booking-ai-demo`) jest **demonstracją integracji** — pokazuje jak trzy z tamtych komponentów składają się w realny, działający produkt biznesowy (landing salonu kosmetycznego z rezerwacją online i asystentem AI).

Podział człowiek/AI wygląda tu więc inaczej — najważniejszą warstwą **nie jest kod**, tylko **decyzja o architekturze fuzji**: jak połączyć te trzy komponenty tak, żeby razem dawały wartość biznesową, nie były trzema oderwanymi elementami na jednej stronie.

## Podział pracy człowiek vs AI

| Warstwa | Kto zrobił | Dlaczego tak |
|---|---|---|
| **Wybór use-case'a** (salon kosmetyczny) i **spójna wizja produktu** — landing + rezerwacje + AI kwalifikujące leada | **Człowiek** | To biznes decision, nie technical. Salon jest wybrany bo: (1) ma naturalny cykl „question → check availability → book" idealnie ilustrujący sensowność AI + booking, (2) polska firma B2C = target GroDev, (3) niski próg zrozumienia dla rekrutera/klienta oglądającego demo. |
| **Decyzja o dependency**: `grodev/booking-slots` przez composer + `@grodev/claude-chat-react` przez npm | **Człowiek** | To ważna decyzja — pokazuje, że moje wcześniejsze biblioteki **są używalne w innych projektach**, nie tylko sample'em w README. Alternatywne podejście (kopiuj-wklej kod) byłoby wygodniejsze do deployu, ale zniszczyłoby cały point tego demo. |
| **Backend endpointy** (`slots.php`, `chat.php`) — sygnatury API + walidacja + CORS + SSE | **Draft AI, hardening człowieka** | Claude wygenerował szkielety obu endpointów; ja przeszedłem linia po linii: dodałem `regex` walidacji daty na `slots.php`, wymusiłem 2h `notBefore` lead time (żeby użytkownicy nie próbowali umawiać się „za godzinę"), sprawdziłem że `chat.php` używa tego samego wire-format co `claude-chat-widget` (żeby React frontend nie wymagał modyfikacji). |
| **System prompt dla asystentki AI** (salon Anna) | **Człowiek** | Kluczowa decyzja: **AI ma delegować pytania o wolne terminy do widocznego obok kalendarza, nie wymyślać terminów**. Bez tej reguły widget powiedziałby „mam wolny wtorek 14:00" wymyślając odpowiedź, co byłoby katastrofalne w produkcji (klient by przyjechał). System prompt jawnie mówi: „Sprawdzę dostępne terminy — jaka data Cię interesuje?" i deleguje do kalendarza. To jest architektural decision, nie prompt tuning. |
| **Fake data** (`bookings.json` — kilka rezerwacji ilustrujących overlap w kalendarzu) | **Człowiek** | Ręcznie dobrane, żeby demo pokazywało realistyczny wzór: dzień z 2 rezerwacjami widocznie zmniejsza dostępność, dzień pusty daje pełną siatkę slotów. Losowe generowanie by nie pokazywało wartości. |
| **React `<App>` layout, cennik, hero copy** | **Draft AI, styling człowieka** | Standard boilerplate React. Ja wybrałem paletę (terracotta + cream = beauty industry palette, nie generic „AI slop" fiolet z gradientem), wybrałem serif fonts (branding beauty salon), dobrałem strukturę sekcji. |
| **`BookingCalendar` komponent** — fetch/loading/error states + „closed" branch dla niedziel | **Draft AI, edge case'y człowieka** | Claude napisał happy path (fetch → render slots); ja dodałem `closed: true` branch dla niedziel, obsługę „brak wolnych" jako neutralnego komunikatu (nie błędu), i **jawnie zostawiłem** brak realnego bookowania (przycisk pokazuje confirmation, nie POST-uje) z komentarzem *dlaczego* — atomowe bookowanie z race-condition-safe insertem to ~50 linii kodu, celowo poza scope demo. |
| **Vite proxy `/api → :8000`** | **Człowiek** | Świadoma decyzja żeby w dev nie było CORS-a; produkcyjnie deploy jest za tym samym origin (Apache/nginx serwuje frontend + PHP na tej samej domenie). Vite proxy to *dev-time convenience*, nie deploy strategy. Udokumentowane w README. |
| **README + AI-first block + docs SVG** | **Człowiek** | Marketing. |

## Co zweryfikowałem przed wypchnięciem

- Endpoint `slots.php` przetestowany ręcznie: różne daty (dziś/jutro/niedziela/data z rezerwacją), walidacja `?date=` (pusty, zły format, nieistniejący dzień). Bufor 10 min między rezerwacjami działa (rezerwacja 10:00–10:30 blokuje 09:50–10:40 → slot 09:30–10:00 wypada).
- Endpoint `chat.php` przetestowany ręcznie z curl-em (`curl -N -X POST ... -H "Content-Type: application/json" -d '{"messages":[...]}'`). Streaming SSE działa. System prompt się respektuje (na pytanie „jaki dziś dzień masz wolny 14:00?" model mówi „sprawdzę dostępne terminy — jaka data Cię interesuje?" zgodnie z instrukcją).
- Frontend zbudowany przez `npm run build` → czyste. TypeScript check przechodzi.
- Vite dev server + PHP dev server (`php -S localhost:8000 -t backend/public`) — cała aplikacja odpalona lokalnie, kalendarz ładuje sloty, widget czatu odpowiada, przełączanie dat działa, error states się renderują.

## Znane pułapki dla następnej iteracji AI

- **Nie zmieniaj `bookings.json` na tabelę SQL** dla samego zmieniania — to demo, plik jest celowo prosty żeby ktoś sforkował repo i uruchomił bez setupu bazy. Migracja do SQL to punkt „stąd do produkcji" opisany w README, nie krok integracyjny.
- **Kalendarz NIE robi realnego bookingu.** Przycisk pokazuje confirmation message, nie POST-uje niczego. Dodanie realnego booking'u wymaga: (1) atomowego insertu z unique constraint na `(date, start)` żeby uniknąć double-bookingu przy race condition, (2) email confirmation, (3) rate limitingu żeby ktoś nie zajął całego dnia. To osobny projekt, poza scope demo.
- **System prompt asystentki musi zostać w polskim** — cały UX jest po polsku, mieszanie EN/PL byłoby dziwne dla klienta końcowego.
- **`endpoint="/api/chat.php"` w `<ClaudeChat>`** — kropka po `.php` jest ważna, bez niej PHP nie serwuje. Nie „poprawiaj" ścieżki na `/api/chat`.
- **Nie dodawaj bundlera do backendu.** Backend to plain PHP z composer autoload — proste do deployu na dowolnym hoście PHP (home.pl, mydevil, hetzner). Framework (Symfony, Laravel) to overkill dla dwóch endpointów.

## Kiedy sięgać po Claude na tym projekcie, a kiedy pisać samodzielnie

- **Sięgnąć po Claude:** dodanie realnego bookingu (atomowy insert + email), dodanie panelu admin dla salonu (widok rezerwacji, akceptacja/odrzucenie), rozszerzenie systemu o multi-staff (booking-slots to obsługuje).
- **Zrobić samodzielnie:** zmiana architektury fuzji (np. jak podłączyć Zapier/Make, jak dodać płatności Stripe), zmiana system promptu asystentki (bo to biznesowa reguła, nie tekst), decyzja o skalowaniu (kiedy przenieść bookings z JSON do bazy).

## Powiązane repo

- [`booking-slots-php`](https://github.com/GronskiDeveloper/booking-slots-php) — silnik overlapa używany przez backend
- [`claude-chat-react`](https://github.com/GronskiDeveloper/claude-chat-react) — widget czatu używany na froncie
- [`claude-chat-widget`](https://github.com/GronskiDeveloper/claude-chat-widget) — vanilla-JS wersja + referencyjny PHP proxy (na którym opiera się chat.php tutaj)
