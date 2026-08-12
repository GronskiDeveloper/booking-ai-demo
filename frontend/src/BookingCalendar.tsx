import { useEffect, useState } from 'react';
import styles from './App.module.css';

/**
 * Calendar of free slots for the salon. Fetches /api/slots.php?date=YYYY-MM-DD
 * on each date change, renders a grid of clickable time buttons.
 *
 * On slot click, this demo does NOT actually book — it just shows a confirmation
 * message. A real implementation would POST to /api/book.php, which would (a)
 * re-check availability atomically, (b) append to bookings.json, (c) return
 * booking id. That's ~50 lines of code, deliberately out of scope for a demo
 * repo — the point is to show the frontend/backend/AI-chat integration cleanly.
 */
export function BookingCalendar() {
  const [date, setDate] = useState<string>(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1); // default to tomorrow
    return d.toISOString().slice(0, 10);
  });
  const [slots, setSlots] = useState<{ label: string; start: string }[]>([]);
  const [status, setStatus] = useState<'idle' | 'loading' | 'closed' | 'error'>('idle');
  const [message, setMessage] = useState<string | null>(null);
  const [confirmed, setConfirmed] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setStatus('loading');
    setConfirmed(null);
    fetch(`/api/slots.php?date=${date}`)
      .then((r) => r.json())
      .then((data) => {
        if (cancelled) return;
        if (data.error) {
          setStatus('error');
          setMessage(data.error);
          return;
        }
        if (data.closed) {
          setStatus('closed');
          setMessage(data.reason);
          setSlots([]);
          return;
        }
        setStatus('idle');
        setMessage(null);
        setSlots(data.slots ?? []);
      })
      .catch((err) => {
        if (cancelled) return;
        setStatus('error');
        setMessage(`Nie udało się pobrać terminów: ${(err as Error).message}`);
      });
    return () => {
      cancelled = true;
    };
  }, [date]);

  const handleBook = (slotLabel: string) => {
    // Demo: nie robimy realnego bookowania. Pokazujemy komunikat "wysłalibyśmy
    // rezerwację". Real implementation: POST /api/book.php with slot + service + contact
    setConfirmed(
      `Świetnie! W realnej wersji zostałaby wysłana rezerwacja na ${date} o ${slotLabel}. To wersja demo — dokończenie flow (email potwierdzający, kalendarz, płatność) opisane w README.`,
    );
  };

  return (
    <div className={styles.calendar}>
      <div className={styles.calendarHead}>
        <label htmlFor="date">Wybierz datę:</label>
        <input
          id="date"
          type="date"
          value={date}
          min={new Date().toISOString().slice(0, 10)}
          onChange={(e) => setDate(e.target.value)}
        />
        <span className={styles.calendarNote}>Otwarte pon-sob, 9:00-17:00</span>
      </div>

      {status === 'loading' && <p className={styles.empty}>Ładowanie terminów…</p>}
      {status === 'error' && <p className={styles.error}>{message}</p>}
      {status === 'closed' && <p className={styles.empty}>{message}</p>}
      {status === 'idle' && slots.length === 0 && (
        <p className={styles.empty}>Brak wolnych terminów na ten dzień — wybierz inny.</p>
      )}
      {status === 'idle' && slots.length > 0 && (
        <div className={styles.slotsGrid}>
          {slots.map((s) => (
            <button key={s.start} className={styles.slotBtn} onClick={() => handleBook(s.label)}>
              {s.label}
            </button>
          ))}
        </div>
      )}

      {confirmed && <div className={styles.confirm}>{confirmed}</div>}
    </div>
  );
}
