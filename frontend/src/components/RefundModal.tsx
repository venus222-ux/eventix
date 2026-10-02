import { useEffect, useState } from "react";
import type { OrderResult } from "@/types/public";
import type { RefundPayload } from "../services/orders";

export const REFUND_REASONS: Record<string, string> = {
  cannot_attend: "I can't attend the event",
  event_changed: "The event date or details changed",
  duplicate_purchase: "I bought the tickets twice",
  bought_by_mistake: "I bought the wrong seats or tickets",
  other: "Other",
};

const MIN_CHARS = 10;
const MAX_CHARS = 1000;

const money = (c: number) =>
  (c / 100).toLocaleString(undefined, { style: "currency", currency: "EUR" });

interface Props {
  order: OrderResult;
  submitting: boolean;
  onClose: () => void;
  onSubmit: (payload: RefundPayload) => void;
}

export default function RefundModal({ order, submitting, onClose, onSubmit }: Props) {
  const [reason, setReason] = useState("");
  const [message, setMessage] = useState("");
  const [accept, setAccept] = useState(false);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape" && !submitting) onClose();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [onClose, submitting]);

  const messageLength = message.trim().length;
  const valid = reason !== "" && messageLength >= MIN_CHARS && accept;
  const seatCount = order.items?.length;

  return (
    <>
      <div className="modal-backdrop fade show" />
      <div
        className="modal fade show d-block"
        tabIndex={-1}
        role="dialog"
        aria-modal="true"
        aria-labelledby="refundTitle"
        onMouseDown={() => !submitting && onClose()}
      >
        <div className="modal-dialog modal-dialog-centered" onMouseDown={(e) => e.stopPropagation()}>
          <form
            className="modal-content"
            onSubmit={(e) => {
              e.preventDefault();
              if (valid && !submitting) onSubmit({ reason, message: message.trim(), accept });
            }}
          >
            <div className="modal-header">
              <h5 className="modal-title" id="refundTitle">Request a refund</h5>
              <button type="button" className="btn-close" aria-label="Close" onClick={onClose} disabled={submitting} />
            </div>

            <div className="modal-body d-flex flex-column gap-3">
              <div className="p-3 bg-light rounded border">
                <div className="fw-semibold">{order.event?.title}</div>
                <div className="text-muted small">
                  {order.invoice_number ?? order.id}
                  {seatCount ? ` · ${seatCount} ticket(s)` : ""}
                </div>
                <div className="mt-1">
                  Refund amount: <strong>{money(order.total_cents)}</strong>
                </div>
              </div>

              <div>
                <label className="form-label" htmlFor="refundReason">Reason</label>
                <select
                  id="refundReason"
                  className="form-select"
                  value={reason}
                  onChange={(e) => setReason(e.target.value)}
                  required
                >
                  <option value="">Select a reason…</option>
                  {Object.entries(REFUND_REASONS).map(([key, label]) => (
                    <option key={key} value={key}>{label}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="form-label" htmlFor="refundMessage">Tell us more</label>
                <textarea
                  id="refundMessage"
                  className={`form-control ${message && messageLength < MIN_CHARS ? "is-invalid" : ""}`}
                  rows={4}
                  maxLength={MAX_CHARS}
                  placeholder="Please explain why you are asking for a refund."
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                />
                <div className="d-flex justify-content-between small text-muted mt-1">
                  <span className={messageLength < MIN_CHARS ? "text-danger" : ""}>
                    Minimum {MIN_CHARS} characters
                  </span>
                  <span>{messageLength} / {MAX_CHARS}</span>
                </div>
              </div>

              <div className="form-check">
                <input
                  className="form-check-input"
                  type="checkbox"
                  id="refundAccept"
                  checked={accept}
                  onChange={(e) => setAccept(e.target.checked)}
                />
                <label className="form-check-label small" htmlFor="refundAccept">
                  I understand that my seats will be released and these tickets will stop
                  working. This cannot be undone.
                </label>
              </div>

              <div className="text-muted small">
                Some requests are reviewed by our team before the refund is issued. We will
                email you the outcome either way.
              </div>
            </div>

            <div className="modal-footer">
              <button type="button" className="btn btn-outline-secondary" onClick={onClose} disabled={submitting}>
                Keep my tickets
              </button>
              <button type="submit" className="btn btn-danger" disabled={!valid || submitting}>
                {submitting ? "Sending…" : "Confirm refund request"}
              </button>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
