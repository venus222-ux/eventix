// components/AdminDashboard/PriceInput.tsx
import { useEffect, useState } from "react";

const toCents = (text: string) => {
  const n = Math.round(Number(text.replace(",", ".")) * 100);
  return Number.isFinite(n) && n > 0 ? n : 0;
};

interface Props {
  valueCents: number;
  onChange: (cents: number) => void;
}

export default function PriceInput({ valueCents, onChange }: Props) {
  const [text, setText] = useState((valueCents / 100).toFixed(2));

  // re-sync only when the form is reset/loaded from outside (e.g. opening "Edit")
  useEffect(() => {
    if (toCents(text) !== valueCents) setText((valueCents / 100).toFixed(2));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [valueCents]);

  return (
    <div>
      <label className="form-label">
        Base ticket price (EUR, VAT included)
      </label>
      <input
        type="number"
        min="0"
        step="0.01"
        className="form-control"
        value={text}
        onChange={(e) => {
          setText(e.target.value);
          onChange(toCents(e.target.value));
        }}
        required
      />
      <div className="form-text">
        Used for sections that have no price of their own.
      </div>
    </div>
  );
}