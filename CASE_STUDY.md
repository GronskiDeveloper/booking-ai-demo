# Case study - `booking-ai-demo`, czyli jak trzy repo składają się w jeden system

Krótka retrospektywa: dlaczego zbudowałem to jako *demo integracji*, a nie kolejny standalone plugin, i co to pokazuje o mojej pracy.

## Brief (30 sekund)

Klikalna aplikacja pokazująca, że moje trzy niezależne publiczne repo grają razem jak jeden produkt:

- [`booking-slots-php`](https://github.com/GronskiDeveloper/booking-slots-php) - silnik wolnych slotów (PHP library)
- [`@grodev/claude-chat-react`](https://github.com/GronskiDeveloper/claude-chat-react) - Reactowy widget czatu na Claude API
- Proxy do Claude API (jak w [`claude-chat-widget`](https://github.com/GronskiDeveloper/claude-chat-widget))

Efekt użytkownika: rezerwuje wizytę u fryzjera przez chat („chciałabym w piątek po 15" → asystent dostaje slots z backendu, potwierdza wybór, klika rezerwację).

Trzy twarde wymagania:

1. **Realny dowód, nie mock.** Backend PHP wywołuje realne `SlotGenerator` z pierwszego repo. Frontend React używa realnego pakietu z npm. Nikt tu nie „szkicował". Możesz sklonować i uruchomić.
2. **Podział backend / frontend jak w rzeczywistym wdrożeniu.** `backend/` (composer + proxy) i `frontend/` (npm + Vite) w jednym repo, ale każde samodzielnie deploy'owalne. Backend na VPS, frontend na Vercel / Netlify - tak jak robi się to u klientów.
3. **Zero magii AI-first.** Chat to warstwa doradcza. Faktyczna rezerwacja to strukturalny call z frontendu do backendu z ID slotu. AI nie tworzy slotów samo - byłoby to niebezpieczne.

## Gdzie faktycznie zdarzyła się praca człowieka

### 1. Kontrakt między warstwami (człowiek, przed jakimkolwiek kodem)

Zaprojektowałem trzy endpoints backendu z jasnymi obowiązkami:

- `POST /api/chat` - proxy do Claude z systemowym promptem „jesteś asystentem rezerwacji"
- `GET /api/slots?date=YYYY-MM-DD` - zwraca wolne sloty z `SlotGenerator`
- `POST /api/booking` - potwierdza rezerwację (strukturalnie, nie przez czat)

Frontend to trzy komponenty - `ChatPanel` (mój pakiet Reactowy), `SlotPicker` (własna komponenta), `BookingConfirmation`. Chat może polecić („polecam piątek 15:30"), ale samo kliknięcie „rezerwuj" idzie przez `POST /api/booking` z ID, żeby użytkownik nie zarezerwował nieświadomie („chatgpt jest niepewny").

### 2. System prompt dla asystenta (człowiek)

Prompt asystenta jest napisany po polsku, krótki (~180 znaków), z jasnymi granicami: „nie wymyślaj slotów, nie potwierdzaj rezerwacji, tylko pomagaj wybrać z listy". To decyzja design której **nie zostawia się do AI**. Zły prompt = asystent obiecuje slot 22:00 w niedzielę bo brzmi grzecznie.

### 3. AI zrobił frontend UX draft (Claude, ~1 h)

Layout ChatPanel + SlotPicker side by side, animacja przekazania „AI proponuje slot" → highlight w picker, dark mode, responsive breakpointy - to Claude Code w draftowym flow. Podałem: „użyj pakietu `@grodev/claude-chat-react` z hooka, side-panel z kalendarzem, styl inspirowany Linear (dark, monospace numery)". Draft dostał 3 iteracje na drobiazgi (a11y focus trap w modalu, kontrast disabled slotów).

### 4. Deploy pipeline (człowiek)

Backend to composer install + kilka `.env` sekretów. Frontend to `npm run build` + statyczny hosting. Zdefiniowałem to w `README.md` z konkretnymi krokami, żeby ktoś kto to klonuje w piątek wieczorem miał uruchomione w 10 minut.

## Gdzie to się sprawdza

- **Demo dla klienta** - „możesz kliknąć realny system rezerwacji z asystentem" zamiast slajdu w PowerPoincie
- **Reference implementation** - developer który chce podpiąć mój chat widget do własnego systemu rezerwacji ma tu wzorzec kontraktu
- **Pokazanie połączenia trzech repo** - że ktoś kto ma ekosystem publiczny, umie ten ekosystem złożyć

## Kiedy nie to demo

- Jeśli chcesz produkcyjny system rezerwacji dla swojego biznesu - to demo pokazuje mechanikę, ale realne wdrożenie ma jeszcze SMS, email confirmations, kalendarz operatora, integrację płatności. Demo pomija to celowo, żeby móc uruchomić bez klucza SMSAPI.
- Jeśli szukasz gotowego SaaS booking - to nie to. To dowód, że umiem takie systemy budować pod klienta.

## Kontakt

Wdrożenie systemu rezerwacji z asystentem AI pod branding klienta: [dominik@grodev.pl](mailto:dominik@grodev.pl) · [grodev.pl/system-rezerwacji-online](https://grodev.pl/system-rezerwacji-online).

Wszystkie trzy repo bazowe są MIT - możesz je łączyć w swój produkt bez licencyjnej biurokracji.
