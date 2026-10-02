import { useMemo } from "react";
import { Country, City } from "country-state-city";
import type { BillingData } from "@/types";

interface Props {
  value: BillingData;
  onChange: (v: BillingData) => void;
}

export default function BillingForm({ value, onChange }: Props) {
  const countries = useMemo(() => Country.getAllCountries(), []);

  const cityOptions = useMemo(() => {
    const names = value.billing_country
      ? (City.getCitiesOfCountry(value.billing_country) ?? []).map((c) => c.name)
      : [];
    const set = new Set(names);
    if (value.billing_city) set.add(value.billing_city); // păstrează orașul salvat
    return [...set].sort((a, b) => a.localeCompare(b));
  }, [value.billing_country, value.billing_city]);

  const patch = (p: Partial<BillingData>) => onChange({ ...value, ...p });

  return (
    <div className="row g-2 mt-1">
      <div className="col-md-4">
        <label className="form-label small mb-1">Țară</label>
        <select
          className="form-select"
          value={value.billing_country}
          onChange={(e) => patch({ billing_country: e.target.value, billing_city: "" })}
          required
        >
          <option value="">Alege țara...</option>
          {countries.map((c) => (
            <option key={c.isoCode} value={c.isoCode}>
              {c.name} ({c.isoCode})
            </option>
          ))}
        </select>
      </div>

      <div className="col-md-4">
        <label className="form-label small mb-1">Oraș</label>
        {cityOptions.length > 0 ? (
          <select
            className="form-select"
            value={value.billing_city}
            onChange={(e) => patch({ billing_city: e.target.value })}
            required
          >
            <option value="">Alege orașul...</option>
            {cityOptions.map((n) => (
              <option key={n} value={n}>{n}</option>
            ))}
          </select>
        ) : (
          <input
            className="form-control"
            placeholder="ex: București"
            value={value.billing_city}
            onChange={(e) => patch({ billing_city: e.target.value })}
            required
          />
        )}
      </div>

      <div className="col-md-4">
        <label className="form-label small mb-1">Cod poștal</label>
        <input
          className="form-control"
          placeholder="ex: 010101"
          value={value.billing_postal_code}
          onChange={(e) => patch({ billing_postal_code: e.target.value })}
          required
        />
      </div>

      <div className="col-12 mt-2">
        <label className="form-label small mb-1">Stradă / Adresă (Opțional)</label>
        <input
          className="form-control"
          placeholder="Strada, Număr, Bloc..."
          value={value.billing_street}
          onChange={(e) => patch({ billing_street: e.target.value })}
        />
      </div>
    </div>
  );
}