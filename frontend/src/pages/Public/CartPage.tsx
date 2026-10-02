// pages/Public/CartPage.tsx
import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { toast } from "react-toastify";
import { useCartStore } from "../../store/useCartStore";
import { useStore } from "../../store/useStore";
import { placeOrder } from "../../services/orders";
import { getPublicEvent, listPublicEvents } from "../../services/publicEvents";
import { getPublicConfig } from "../../services/config";
import type { PublicEvent } from "@/types/public";

const money = (c: number) =>
  (c / 100).toLocaleString("ro-RO", { style: "currency", currency: "EUR" });

export default function CartPage() {
  const navigate = useNavigate();
  const isAuth = useStore((s) => s.isAuth);
  const { event, seats, pendingOrder, removeSeat, clearCart, setPendingOrder } = useCartStore();

  const [busy, setBusy] = useState(false);
  const [details, setDetails] = useState<PublicEvent | null>(null);
  const [suggestions, setSuggestions] = useState<PublicEvent[]>([]);
  const [vatRate, setVatRate] = useState<number | null>(null);
  const [holdMinutes, setHoldMinutes] = useState(10);

  // Prices already include VAT, so the cart splits the gross total instead of adding tax on top
  const total = seats.reduce((sum, s) => sum + s.priceCents, 0);
  const vat = vatRate ? Math.round(total - total / (1 + vatRate / 100)) : 0;
  const subtotal = total - vat;

  const pendingValid = pendingOrder && new Date(pendingOrder.expiresAt).getTime() > Date.now();

  useEffect(() => {
    getPublicConfig()
      .then((res) => {
        setVatRate(res.data.vat_rate);
        setHoldMinutes(res.data.hold_minutes);
      })
      .catch(() => setVatRate(null));
  }, []);

  // Event banner + two related events (same category first, then any upcoming event)
  useEffect(() => {
    if (!event?.slug) {
      setDetails(null);
      setSuggestions([]);
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        const ev = (await getPublicEvent(event.slug)).data.data;
        if (cancelled) return;
        setDetails(ev);

        const others = async (category?: string) =>
          (await listPublicEvents({ page: 1, category })).data.data.filter((e) => e.id !== ev.id);

        let list = ev.category?.slug ? await others(ev.category.slug) : [];
        if (list.length < 2) {
          const more = await others();
          list = [...list, ...more.filter((m) => !list.some((l) => l.id === m.id))];
        }

        if (!cancelled) setSuggestions(list.slice(0, 2));
      } catch {
        /* the cart works without banner or suggestions */
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [event?.slug]);

  const checkout = async () => {
    if (!event || !seats.length) return;
    if (!isAuth) {
      toast.info("Log in to continue. Your cart is saved.");
      return navigate("/login");
    }
    setBusy(true);
    try {
      const res = await placeOrder(event.id, seats.map((s) => s.id));
      const order = res.data.data;
      setPendingOrder({ id: order.id, expiresAt: order.expires_at! });
      clearCart(); // seats are now held in Redis by the order
      navigate(`/checkout/${order.id}`, { state: { clientSecret: res.data.client_secret } });
    } catch (e: any) {
      // 422 validation (e.g. no price set) -> errors.seat_ids[0]; 409 -> message
      toast.error(
        e.response?.data?.errors?.seat_ids?.[0] ??
          e.response?.data?.message ??
          "Could not reserve seats",
      );
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="container my-4" style={{ maxWidth: 960 }}>
      <h2 className="mb-3">Your cart</h2>

      {pendingValid && (
        <div className="alert alert-warning d-flex justify-content-between align-items-center">
          <span>You have an unpaid order with reserved seats.</span>
          <Link className="btn btn-sm btn-warning" to={`/checkout/${pendingOrder!.id}`}>
            Resume payment
          </Link>
        </div>
      )}

      {seats.length === 0 ? (
        <p className="text-muted">
          Your cart is empty. <Link to="/events">Browse events</Link>
        </p>
      ) : (
        <div className="row g-4">
          {/* LEFT: items + related events */}
          <div className="col-lg-8">
            <div className="card shadow-sm mb-4">
              <div className="card-body">
                <div className="d-flex gap-3 mb-3">
                  {details?.banner_url && (
                    <img
                      src={details.banner_url}
                      alt={details.title}
                      style={{ width: 96, height: 64, objectFit: "cover", borderRadius: 6 }}
                    />
                  )}
                  <div>
                    <div className="fw-semibold">{event?.title}</div>
                    {details && (
                      <div className="text-muted small">
                        {new Date(details.starts_at).toLocaleString()}
                        {details.venue ? ` · ${details.venue.name}, ${details.venue.city}` : ""}
                      </div>
                    )}
                  </div>
                </div>

                <ul className="list-group list-group-flush">
                  {seats.map((s) => (
                    <li
                      key={s.id}
                      className="list-group-item d-flex justify-content-between align-items-center px-0"
                    >
                      <span>
                        {s.section} · Seat {s.label}
                        <span className="text-muted small d-block">1 ticket</span>
                      </span>
                      <span>
                        {money(s.priceCents)}{" "}
                        <button
                          className="btn btn-sm btn-link text-danger"
                          onClick={() => removeSeat(s.id)}
                        >
                          Remove
                        </button>
                      </span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            {suggestions.length > 0 && (
              <div className="card shadow-sm">
                <div className="card-header bg-light">
                  <h6 className="mb-0">More events you might like</h6>
                </div>
                <div className="card-body">
                  <div className="row g-3">
                    {suggestions.map((ev) => (
                      <div className="col-sm-6" key={ev.id}>
                        <Link
                          to={`/events/${ev.slug}`}
                          className="text-decoration-none text-reset d-flex gap-2"
                        >
                          {ev.banner_url ? (
                            <img
                              src={ev.banner_url}
                              alt={ev.title}
                              style={{ width: 72, height: 54, objectFit: "cover", borderRadius: 6 }}
                            />
                          ) : (
                            <div
                              style={{ width: 72, height: 54, borderRadius: 6, background: "#e5e7eb" }}
                            />
                          )}
                          <div>
                            <div className="fw-semibold small">{ev.title}</div>
                            <div className="text-muted small">
                              {new Date(ev.starts_at).toLocaleDateString()}
                              {ev.venue ? ` · ${ev.venue.city}` : ""}
                            </div>
                          </div>
                        </Link>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* RIGHT: totals + trust + CTA */}
          <div className="col-lg-4">
            <div className="card shadow-sm" style={{ position: "sticky", top: 16 }}>
              <div className="card-body">
                <h5 className="card-title">Order total</h5>

                {vatRate !== null ? (
                  <>
                    <div className="d-flex justify-content-between text-muted small mb-1">
                      <span>Subtotal (excl. VAT)</span>
                      <span>{money(subtotal)}</span>
                    </div>
                    <div className="d-flex justify-content-between text-muted small mb-1">
                      <span>VAT ({vatRate}%)</span>
                      <span>{money(vat)}</span>
                    </div>
                  </>
                ) : (
                  <div className="text-muted small mb-1">VAT is included in the price.</div>
                )}

                <div className="d-flex justify-content-between text-muted small mb-2">
                  <span>Shipping</span>
                  <span>Free · digital tickets</span>
                </div>

                <hr className="my-2" />

                <div className="d-flex justify-content-between fw-bold fs-5 mb-3">
                  <span>Total</span>
                  <span>{money(total)}</span>
                </div>

                <button
                  className="btn btn-dark btn-lg w-100 fw-semibold"
                  onClick={checkout}
                  disabled={busy}
                >
                  {busy ? "Reserving seats..." : "🔒 Proceed to Secure Checkout"}
                </button>

                <button
                  className="btn btn-link btn-sm text-muted w-100 mt-1"
                  onClick={clearCart}
                  disabled={busy}
                >
                  Clear cart
                </button>

                <ul className="list-unstyled small text-muted mt-3 mb-0 d-flex flex-column gap-1">
                  <li>🔒 Secure payment powered by Stripe</li>
                  <li>📧 Tickets and invoice sent by email</li>
                  <li>⏱ Seats reserved for {holdMinutes} min once you proceed</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}