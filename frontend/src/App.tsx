import { ClaudeChat } from '@grodev/claude-chat-react';
import '@grodev/claude-chat-react/styles.css';
import { BookingCalendar } from './BookingCalendar';
import styles from './App.module.css';

/**
 * booking-ai-demo — landing salonu kosmetycznego pokazujący fuzję trzech
 * projektów GroDev na jednej stronie:
 *
 *   1. Silnik wolnych terminów (grodev/booking-slots — PHP) → backend/public/slots.php
 *   2. Widget czatu AI  (@grodev/claude-chat-react) → osadzony niżej
 *   3. Proxy Claude API  (jak w claude-chat-widget) → backend/public/chat.php
 *
 * To jest demo integracji, nie osobne komponenty. Cały point: pokazać, że
 * fragmenty stacku GroDev składają się w realny, działający produkt.
 */
export function App() {
  const services = [
    { name: 'Manicure hybrydowy', meta: '45 min', price: '120 zł' },
    { name: 'Pedicure klasyczny',  meta: '60 min', price: '150 zł' },
    { name: 'Farbowanie (długie)', meta: '1,5–2h', price: '250–350 zł' },
    { name: 'Strzyżenie damskie',  meta: '45 min', price: '80 zł'  },
    { name: 'Strzyżenie męskie',   meta: '30 min', price: '50 zł'  },
    { name: 'Zabieg oczyszczający', meta: '60 min', price: '180 zł' },
  ];

  return (
    <>
      <header className={styles.hero}>
        <div className={styles.brand}>Beauty Studio</div>
        <h1 className={styles.h1}>
          Anna — <em>salon kosmetyczny w Poznaniu</em>
        </h1>
        <p className={styles.sub}>
          Manicure, pedicure, farbowanie, strzyżenie. Umów wizytę online albo
          zapytaj naszą asystentkę AI po prawej stronie o cennik i dostępność.
        </p>
        <a href="#booking" className={styles.pill}>
          Wolne terminy →
        </a>
      </header>

      <main className={styles.container}>
        <section className={styles.section}>
          <h2>Cennik</h2>
          <ul className={styles.priceList}>
            {services.map((s) => (
              <li key={s.name} className={styles.priceItem}>
                <div>
                  <div className={styles.priceName}>{s.name}</div>
                  <div className={styles.priceMeta}>{s.meta}</div>
                </div>
                <div className={styles.priceValue}>{s.price}</div>
              </li>
            ))}
          </ul>
        </section>

        <section className={styles.section} id="booking" style={{ marginTop: 48 }}>
          <h2>Umów wizytę</h2>
          <BookingCalendar />
        </section>
      </main>

      <footer className={styles.footer}>
        Beauty Studio Anna · ul. Kwiatowa 12, Poznań · +48 555 123 456 · Dane fikcyjne (demo)
        <br />
        Demo integracji zbudowane przez{' '}
        <a href="https://grodev.pl">GroDev</a> —{' '}
        <a href="https://github.com/GronskiDeveloper/booking-ai-demo">kod źródłowy</a>
      </footer>

      {/* Widget czatu AI — floating launcher w prawym dolnym rogu.
          Rozmawia z /api/chat.php (proxy trzymające klucz Anthropic). */}
      <ClaudeChat
        endpoint="/api/chat.php"
        title="Anna — asystentka"
        greeting="Cześć! Jestem asystentką salonu — pytaj o usługi, ceny, wolne terminy. W czym mogę pomóc?"
        accentColor="#c68a5c"
      />
    </>
  );
}
