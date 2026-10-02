import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import RefundModal from "../components/RefundModal";
import { downloadDocument, listMyOrders, refundOrder, type RefundPayload } from "../services/orders";
import type { OrderResult } from "@/types/public";

const money = (c: number) => (c / 100).toLocaleString(undefined, { style: "currency", currency: "EUR" });
const badge: Record<string, string> = {
  completed: "success", pending: "warning", cancelled: "secondary", refunded: "info",
};

const Dashboard = () => {
  const [orders, setOrders] = useState<OrderResult[]>([]);
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);
  const [refundTarget, setRefundTarget] = useState<OrderResult | null>(null);
  const [submittingRefund, setSubmittingRefund] = useState(false);
  const [downloading, setDownloading] = useState<string | null>(null);

  useEffect(() => {
    listMyOrders(page)
      .then((res) => {
        setOrders(res.data.data);
        setLast(res.data.meta.last_page);
      })
      .catch(() => toast.error("Failed to load orders"));
  }, [page]);

  const download = async (o: OrderResult, kind: "tickets" | "invoice") => {
    setDownloading(`${o.id}:${kind}`);
    try {
      await downloadDocument(o.id, kind, kind === "tickets" ? "tickets.pdf" : `${o.invoice_number}.pdf`);
    } catch (e) {
      toast.error((e as Error).message);
    } finally {
      setDownloading(null);
    }
  };

  const submitRefund = async (payload: RefundPayload) => {
    if (!refundTarget) return;
    setSubmittingRefund(true);
    try {
      const res = await refundOrder(refundTarget.id, payload);
      setOrders((prev) => prev.map((x) => (x.id === refundTarget.id ? res.data.data : x)));
      setRefundTarget(null);

      if (res.data.refund.status === "approved") {
        toast.success("Refund issued. A confirmation email is on its way.");
      } else {
        toast.info("Refund request received. Our team will review it and email you the outcome.");
      }
    } catch (e: any) {
      const errors = e.response?.data?.errors as Record<string, string[]> | undefined;
      toast.error((errors && Object.values(errors)[0]?.[0]) ?? e.response?.data?.message ?? "Refund failed");
    } finally {
      setSubmittingRefund(false);
    }
  };

  return (
    <div className="container mt-4">
      <h1>My orders</h1>
      <table className="table align-middle">
        <thead><tr><th>Event</th><th>Date</th><th>Total</th><th>Status</th><th /></tr></thead>
        <tbody>
          {orders.map((o) => (
            <tr key={o.id}>
              <td>{o.event?.title}</td>
              <td>{new Date(o.created_at).toLocaleDateString()}</td>
              <td>{money(o.total_cents)}</td>
              <td>
                <span className={`badge text-bg-${badge[o.status]}`}>{o.status}</span>
                {o.status === "completed" && o.refund_request?.status === "pending" && (
                  <div className="small text-muted mt-1">Refund under review</div>
                )}
                {o.status === "completed" && o.refund_request?.status === "declined" && (
                  <div className="small text-danger mt-1">Refund declined</div>
                )}
              </td>
              <td className="text-end">
                {o.status === "completed" && (
                  <button className="btn btn-sm btn-outline-primary me-1"
                    disabled={downloading === `${o.id}:tickets`}
                    onClick={() => download(o, "tickets")}>
                    {downloading === `${o.id}:tickets` ? "Preparing…" : "Tickets"}
                  </button>
                )}
                {o.invoice_number && (
                  <button className="btn btn-sm btn-outline-secondary me-1"
                    disabled={downloading === `${o.id}:invoice`}
                    onClick={() => download(o, "invoice")}>
                    {downloading === `${o.id}:invoice` ? "Preparing…" : "Invoice"}
                  </button>
                )}
                {o.can_refund && (
                  <button className="btn btn-sm btn-outline-danger" onClick={() => setRefundTarget(o)}>
                    Request refund
                  </button>
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

      {refundTarget && (
        <RefundModal
          order={refundTarget}
          submitting={submittingRefund}
          onClose={() => setRefundTarget(null)}
          onSubmit={submitRefund}
        />
      )}
    </div>
  );
};
export default Dashboard;