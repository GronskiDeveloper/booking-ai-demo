# Jak współtworzyć — booking-ai-demo

Chętnie przyjmę pull requesty i sensowne issue.

## Zanim otworzysz issue

1. **Sprawdź [otwarte issue](https://github.com/GronskiDeveloper/booking-ai-demo/issues)** — może już ktoś to zgłosił.
2. **Sprawdź [`CLAUDE.md`](CLAUDE.md)** — sekcje *Znane pułapki* i *Co to demo nie robi (deliberately)* wyjaśniają scope. Jeśli Twoje zgłoszenie chce dodać feature specjalnie wyłączony ze scope'u (realne bookowanie, multi-staff, admin panel), opisz dlaczego — nie zamykam takich propozycji automatem, ale to musi być uzasadnione.
3. **Zgłoszenia bezpieczeństwa** → NIE otwieraj publicznego issue, patrz [`SECURITY.md`](SECURITY.md).

## Setup lokalny

Wymaga PHP 8.1+ i Node 18+.

```bash
cd backend && composer install
cd ../frontend && npm install
cd ..

# Terminal A
ANTHROPIC_API_KEY=sk-ant-... php -S localhost:8000 -t backend/public

# Terminal B
cd frontend && npm run dev
```

Otwórz http://localhost:5173.

## Pull requesty

- **Małe, skupione zmiany** — jeden PR = jeden temat.
- **Trzymaj się konwencji z repo** — spójrz na istniejący kod. Backend to plain PHP + composer autoload (no framework); frontend to React + TypeScript strict + CSS Modules.
- **Test manualny obowiązkowy dla zmian dotykających integracji** — uruchom pełny stack (Vite + PHP), przeklikaj: zmianę daty, zapytanie do asystentki o cenę, zapytanie o wolny termin (**musi delegować do kalendarza, nie zgadywać**).
- **Zmiany dotykające `chat.php` system promptu** wymagają dyskusji — to biznesowa reguła, nie prompt tweak. System prompt świadomie mówi „nie zgaduj terminów, deleguj do kalendarza" — usunięcie/osłabienie tej reguły zepsuje demo.
- **Zmiany w formacie wire** (`slots.php` response, `chat.php` SSE frame) muszą aktualizować obie strony (backend + frontend).

## Praca z AI (dowolny model)

Nie ukrywaj tego. Jeśli używałeś AI do wygenerowania draftu:

- **Uczciwie o tym napisz** w opisie PR: „Draft wygenerowany przez [narzędzie], zaudytowany ręcznie przed pushem".
- **Zweryfikuj każdą linię integracji, którą podpisujesz swoim commit.** Integracja to miejsce gdzie LLM najczęściej pomija: kontrakty API między warstwami, edge cases (co jak PHP zwróci 500), race conditions.
- **Aktualizuj `CLAUDE.md`** — jeśli dodajesz feature, dopisz do sekcji *Podział pracy* wpis „kto zrobił co".

Ten projekt jest [AI-first](CLAUDE.md) — dokumentowanie workflow to wartość, nie problem.

## Kontakt

Pytania: dominik.gronski@grodev.pl.

Autor: [Dominik Groński / GroDev](https://grodev.pl)
