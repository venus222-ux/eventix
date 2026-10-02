import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { downloadDocument, getOrder } from "../../services/orders";
import type { OrderResult } from "@/types/public";

const money = (c: number) => (c / 100).toLocaleString(undefined, { style: "currency", currency: "EUR" });

export default function OrderConfirmationPage() {
  const { id } = useParams<{ id: string }>();
  const [order, setOrder] = useState<OrderResult | null>(null);

  useEffect(() => {
    let tries = 0;
    const poll = async () => {
      const res = await getOrder(id!);
      setOrder(res.data.data);
      if (res.data.data.status === "pending" && tries++ < 20) timer = setTimeout(poll, 1500);
    };
    let timer: ReturnType<typeof setTimeout>;
    poll();
    return () => clearTimeout(timer);
  }, [id]);

  if (!order) return <div className="container mt-4">Loading…</div>;

  if (order.status === "pending")
    return <div className="container mt-4">Confirming your payment… this usually takes a few seconds.</div>;

  if (order.status !== "completed")
    return <div className="container mt-4">This order is {order.status}. <Link to="/events">Browse events</Link></div>;

  return (
    <div className="container mt-4" style={{ maxWidth: 560 }}>
      <h2>🎉 Payment received</h2>
      <p>Invoice {order.invoice_number} · <strong>{money(order.total_cents)}</strong></p>
      <p>We've emailed your tickets and invoice.</p>
      <button className="btn btn-outline-primary me-2" onClick={() => downloadDocument(order.id, "tickets", "tickets.pdf")}>Tickets (PDF)</button>
      <button className="btn btn-outline-secondary" onClick={() => downloadDocument(order.id, "invoice", `${order.invoice_number}.pdf`)}>Invoice (PDF)</button>
      <div className="mt-3"><Link to="/dashboard">My orders</Link></div>
    </div>
  );
}