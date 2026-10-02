// components/AdminDashboard/SectionPricesEditor.tsx
import { useEffect, useState } from "react";
import API from "../../api";

interface SectionRow {
  id: number;
  name: string;
  default_price_cents: number;
  event_price_cents: number | null;
}

interface Props {
  venueId: string;
  eventId?: number;
  /** section_id -> cents. Sections left blank are not included (they use the fallback price). */
  onChange: (prices: Record<number, number>) => void;
}

const toCents = (text: string) => {
  const n = Math.round(Number(text.replace(",", ".")) * 100);
  return Number.isFinite(n) && n > 0 ? n : 0;
};

const eur = (cents: number) => (cents / 100).toFixed(2);

export default function SectionPricesEditor({ venueId, eventId, onChange }: Props) {
  const [rows, setRows] = useState<SectionRow[]>([]);
  const [texts, setTexts] = useState<Record<number, string>>({});
  const [loading, setLoading] = useState(false);

  const emit = (t: Record<number, string>) => {
    const out: Record<number, number> = {};
    Object.entries(t).forEach(([id, text]) => {
      const cents = toCents(text);
      if (cents > 0) out[Number(id)] = cents;
    });
    onChange(out);
  };

  useEffect(() => {
    if (!venueId) {
      setRows([]);
      setTexts({});
      return;
    }

    let cancelled = false;
    setLoading(true);

    API.get<{ data: SectionRow[] }>(`/admin/venues/${venueId}/sections`, {
      params: { event_id: eventId },
    })
      .then((res) => {
        if (cancelled) return;
        const data = res.data.data;
        const initial: Record<number, string> = {};
        data.forEach((r) => {
          if (r.event_price_cents) initial[r.id] = eur(r.event_price_cents);
        });
        setRows(data);
        setTexts(initial);
        emit(initial);
      })
      .catch(() => {
        if (!cancelled) setRows([]);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [venueId, eventId]);

  if (!venueId) return <div className="text-muted small">Select a venue to set section prices.</div>;
  if (loading) return <div className="text-muted small">Loading sections…</div>;
  if (rows.length === 0) return <div className="text-muted small">This venue has no sections.</div>;

  return (
    <div>
      <label className="form-label">Price per section (EUR, VAT included)</label>
      <div className="d-flex flex-column gap-2">
        {rows.map((r) => (
          <div key={r.id} className="d-flex align-items-center gap-2">
            <span style={{ minWidth: 140 }}>{r.name}</span>
            <input
              type="number"
              min="0"
              step="0.01"
              className="form-control"
              placeholder={
                r.default_price_cents
                  ? `Default ${eur(r.default_price_cents)}`
                  : "Uses the base ticket price"
              }
              value={texts[r.id] ?? ""}
              onChange={(e) => {
                const next = { ...texts, [r.id]: e.target.value };
                setTexts(next);
                emit(next);
              }}
            />
          </div>
        ))}
      </div>
      <div className="text-muted small mt-1">
        Leave a section blank to use its default price, or the base ticket price if it has none.
      </div>
    </div>
  );
}