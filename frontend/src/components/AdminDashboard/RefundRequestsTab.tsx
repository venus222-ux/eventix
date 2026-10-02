// components/AdminDashboard/RefundRequestsTab.tsx
import { useCallback, useEffect, useState } from "react";
import { toast } from "react-toastify";
import API from "../../api";
import type { Paginated } from "@/types/public";

interface Row {
  id: number;
  status: "pending" | "approved" | "declined";
  reason_label: string;
  message: string;
  customer: { name: string | null; email: string | null };
  order: { id: string; invoice_number: string | null; total_cents: number | null; event_title: string | null };
  snapshot: { hours_until_event?: number; approved_refunds_30d?: number; recommendation_note?: string; tickets?: number };
  decision_note: string | null;
  created_at: string;
}

const money = (c: number | null) =>
  c == null ? "—" : (c / 100).toLocaleString(undefined, { style: "currency", currency: "EUR" });

export default function RefundRequestsTab() {
  const [rows, setRows] = useState<Row[]>([]);
  const [status, setStatus] = useState("pending");
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);
  const [busyId, setBusyId] = useState<number | null>(null);

  const load = useCallback(() => {
    API.get<Paginated<Row>>("/admin/refund-requests", { params: { status: status || undefined, page } })
      .then((res) => { setRows(res.data.data); setLast(res.data.meta.last_page); })
      .catch(() => toast.error("Failed to load refund requests"));
  }, [status, page]);

  useEffect(load, [load]);

  const act = async (r: Row, kind: "approve" | "decline") => {
    let note: string | undefined;

    if (kind === "decline") {
      const input = window.prompt("Reason for declining (shown to the customer, min 5 characters):");
      if (input === null) return;
      if (input.trim().length < 5) return toast.error("Please write at least 5 characters");
      note = input.trim();
    } else if (!window.confirm(`Refund ${money(r.order.total_cents)} to ${r.customer.email}?`)) {
      return;
    }

    setBusyId(r.id);
    try {
      await API.post(`/admin/refund-requests/${r.id}/${kind}`, { note });
      toast.success(kind === "approve" ? "Refund issued" : "Request declined");
      load();
    } catch (e: any) {
      toast.error(e.response?.data?.message ?? "Action failed");
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div>
      <h2>Refund requests</h2>
      <select className="form-select w-auto mb-3" value={status}
        onChange={(e) => { setStatus(e.target.value); setPage(1); }}>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="declined">Declined</option>
        <option value="">All</option>
      </select>

      {rows.length === 0 && <p className="text-muted">Nothing here.</p>}

      <div className="d-flex flex-column gap-3">
        {rows.map((r) => (
          <div key={r.id} className="card p-3">
            <div className="d-flex justify-content-between">
              <div>
                <strong>{r.order.event_title}</strong>{" "}
                <span className="text-muted small">{r.order.invoice_number ?? r.order.id}</span>
                <div className="small">{r.customer.name} ({r.customer.email})</div>
              </div>
              <div className="text-end">
                <div className="fw-bold">{money(r.order.total_cents)}</div>
                <span className="badge text-bg-secondary">{r.status}</span>
              </div>
            </div>

            <div className="mt-2"><strong>{r.reason_label}.</strong> {r.message}</div>

            <div className="small text-muted mt-2">
              {r.snapshot.tickets ?? "?"} ticket(s) · event in {r.snapshot.hours_until_event ?? "?"} h ·
              {" "}{r.snapshot.approved_refunds_30d ?? 0} refund(s) in last 30 days
              {r.snapshot.recommendation_note ? ` · ${r.snapshot.recommendation_note}` : ""}
            </div>

            {r.decision_note && <div className="small mt-1">Note: {r.decision_note}</div>}

            {r.status === "pending" && (
              <div className="mt-3 d-flex gap-2">
                <button className="btn btn-sm btn-success" disabled={busyId === r.id} onClick={() => act(r, "approve")}>
                  Approve & refund
                </button>
                <button className="btn btn-sm btn-outline-danger" disabled={busyId === r.id} onClick={() => act(r, "decline")}>
                  Decline
                </button>
              </div>
            )}
          </div>
        ))}
      </div>

      {last > 1 && (
        <div className="d-flex gap-2 align-items-center mt-3">
          <button className="btn btn-sm btn-light" disabled={page <= 1} onClick={() => setPage(page - 1)}>←</button>
          <span>{page} / {last}</span>
          <button className="btn btn-sm btn-light" disabled={page >= last} onClick={() => setPage(page + 1)}>→</button>
        </div>
      )}
    </div>
  );
}