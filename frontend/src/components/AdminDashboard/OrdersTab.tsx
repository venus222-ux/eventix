import { useCallback, useEffect, useState } from "react";
import { toast } from "react-toastify";
import API from "../../api";
import type { OrderResult, Paginated } from "@/types/public";

const money = (c: number) => (c / 100).toLocaleString(undefined, { style: "currency", currency: "EUR" });

interface Sales {
  total_revenue_cents: number; refunded_cents: number; orders: number;
  pending_orders: number; tickets_sold: number;
  top_events: { id: number; title: string; revenue: number; orders: number }[];
}

export default function OrdersTab() {
  const [sales, setSales] = useState<Sales | null>(null);
  const [orders, setOrders] = useState<OrderResult[]>([]);
  const [status, setStatus] = useState("");
  const [q, setQ] = useState("");
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);

  const load = useCallback(() => {
    API.get<Paginated<OrderResult>>("/admin/orders", { params: { status: status || undefined, q: q || undefined, page } })
      .then((res) => { setOrders(res.data.data); setLast(res.data.meta.last_page); });
    API.get<Sales>("/admin/reports/sales").then((res) => setSales(res.data));
  }, [status, q, page]);

  useEffect(load, [load]);

  const refund = async (o: OrderResult) => {
    if (!window.confirm(`Refund ${money(o.total_cents)} and free the seats?`)) return;
    try { await API.post(`/admin/orders/${o.id}/refund`); toast.success("Refunded"); load(); }
    catch (e: any) { toast.error(e.response?.data?.message || "Refund failed"); }
  };

  const resend = async (o: OrderResult) => {
    await API.post(`/admin/orders/${o.id}/resend`);
    toast.success("Email queued");
  };

  return (
    <div>
      <h2>Orders & Sales</h2>

      {sales && (
        <div className="row g-3 mb-4">
          {[
            ["Revenue", money(sales.total_revenue_cents)],
            ["Refunded", money(sales.refunded_cents)],
            ["Paid orders", sales.orders],
            ["Tickets sold", sales.tickets_sold],
            ["Pending", sales.pending_orders],
          ].map(([label, value]) => (
            <div className="col" key={label as string}>
              <div className="card p-3"><small className="text-muted">{label}</small><strong className="fs-5">{value}</strong></div>
            </div>
          ))}
        </div>
      )}

      <div className="d-flex gap-2 mb-3">
        <input className="form-control" placeholder="Invoice # or email" value={q}
          onChange={(e) => { setQ(e.target.value); setPage(1); }} />
        <select className="form-select w-auto" value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1); }}>
          <option value="">All</option>
          {["pending", "completed", "cancelled", "refunded"].map((s) => <option key={s}>{s}</option>)}
        </select>
      </div>

      <table className="table align-middle">
        <thead><tr><th>Invoice</th><th>Customer</th><th>Event</th><th>Total</th><th>Status</th><th /></tr></thead>
        <tbody>
          {orders.map((o) => (
            <tr key={o.id}>
              <td>{o.invoice_number ?? "—"}</td>
              <td>{o.user?.email}</td>
              <td>{o.event?.title}</td>
              <td>{money(o.total_cents)}</td>
              <td>{o.status}</td>
              <td className="text-end">
                {o.status === "completed" && (
                  <>
                    <button className="btn btn-sm btn-outline-secondary me-1" onClick={() => resend(o)}>Resend</button>
                    <button className="btn btn-sm btn-outline-danger" onClick={() => refund(o)}>Refund</button>
                  </>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {last > 1 && (
        <div className="d-flex gap-2 align-items-center">
          <button className="btn btn-sm btn-light" disabled={page <= 1} onClick={() => setPage(page - 1)}>←</button>
          <span>{page} / {last}</span>
          <button className="btn btn-sm btn-light" disabled={page >= last} onClick={() => setPage(page + 1)}>→</button>
        </div>
      )}
    </div>
  );
}