
import { useEffect, useState, FormEvent } from "react";
import { useLocation, useNavigate, useParams } from "react-router-dom";
import { loadStripe } from "@stripe/stripe-js";
import {
  Elements,
  PaymentElement,
  useElements,
  useStripe,
} from "@stripe/react-stripe-js";
import { Country } from "country-state-city";
import { toast } from "react-toastify";
import { cancelOrder, getOrder } from "../../services/orders";
import { updateBilling } from "../../services/profile";
import BillingForm from "../../components/BillingForm";
import { useCartStore } from "../../store/useCartStore";
import type { OrderResult } from "@/types/public";
import type { BillingData } from "@/types";

const publishableKey = import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY || "";
const keyLooksWrong = !publishableKey.startsWith("pk_");
const stripePromise = !keyLooksWrong ? loadStripe(publishableKey) : null;

const money = (c: number) =>
  (c / 100).toLocaleString("ro-RO", {
    style: "currency",
    currency: "EUR",
  });

interface PayFormProps {
  order: OrderResult;
  timeLeft: number;
}

function PayForm({ order, timeLeft }: PayFormProps) {
  const stripe = useStripe();
  const elements = useElements();

  const [paying, setPaying] = useState(false);
  const [ready, setReady] = useState(false);
  const [acceptedTerms, setAcceptedTerms] = useState(false);

  // Valorile sunt calculate și returnate de backend.
  // Nu mai presupunem că TVA-ul este întotdeauna 19%.
  const {
    total_cents: totalCents,
    vat_cents: vatCents,
    subtotal_cents: subtotalCents,
    vat_rate,
  } = order;

  const user = order.user;

  // Adresa salvată + draftul din formularul de editare
  const [billing, setBilling] = useState<BillingData>({
    billing_country: user?.billing_country ?? "",
    billing_city: user?.billing_city ?? "",
    billing_postal_code: user?.billing_postal_code ?? "",
    billing_street: user?.billing_street ?? "",
  });

  const [draft, setDraft] = useState<BillingData>(billing);
  const [editingBilling, setEditingBilling] = useState(false);
  const [savingBilling, setSavingBilling] = useState(false);

  // Factura cere țara, orașul și codul poștal
  const hasRequiredBilling = Boolean(
    billing.billing_country &&
      billing.billing_city &&
      billing.billing_postal_code
  );

  const countryName = billing.billing_country
    ? Country.getCountryByCode(billing.billing_country)?.name ??
      billing.billing_country
    : "";

  const billingAddressString = [
    billing.billing_street,
    billing.billing_postal_code,
    billing.billing_city,
    countryName,
  ]
    .filter(Boolean)
    .join(", ");

  const startEditBilling = () => {
    setDraft(billing);
    setEditingBilling(true);
  };

  const saveBilling = async () => {
    if (
      !draft.billing_country ||
      !draft.billing_city ||
      !draft.billing_postal_code
    ) {
      toast.error("Completează țara, orașul și codul poștal.");
      return;
    }

    setSavingBilling(true);

    try {
      await updateBilling(draft);
      setBilling(draft);
      setEditingBilling(false);
      toast.success("Adresa a fost salvată");
    } catch (err) {
      const message = (
        err as {
          response?: {
            data?: {
              message?: string;
            };
          };
        }
      ).response?.data?.message;

      toast.error(message ?? "Nu s-a putut salva adresa");
    } finally {
      setSavingBilling(false);
    }
  };

  const submit = async (e: FormEvent) => {
    e.preventDefault();

    if (!acceptedTerms) {
      toast.error(
        "Trebuie să accepți Termenii și Condițiile pentru a continua."
      );
      return;
    }

    if (!hasRequiredBilling || editingBilling) {
      toast.error("Completează și salvează adresa de facturare.");
      return;
    }

    if (!stripe || !elements) return;

    setPaying(true);

    const { error } = await stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: `${window.location.origin}/orders/${order.id}/confirmation`,
        receipt_email: user?.email,
      },
    });

    if (error) {
      toast.error(error.message ?? "Plata a eșuat");
      setPaying(false);
    }
  };

  return (
    <form onSubmit={submit} className="d-flex flex-column gap-3">
      {/* 1. ORDER SUMMARY */}
      <div className="card shadow-sm">
        <div className="card-header bg-light">
          <h5 className="card-title mb-0">Order Summary</h5>
        </div>

        <div className="card-body">
          <div className="mb-2 fw-semibold">{order.event?.title}</div>

          <ul className="list-group list-group-flush mb-3">
            {order.items && order.items.length > 0 ? (
              order.items.map((item, index) => (
                <li
                  key={index}
                  className="list-group-item d-flex justify-content-between align-items-center px-0"
                >
                  <span>
                    Bilet: {item.seat.section} - Locul {item.seat.label}
                  </span>

                  <span className="fw-medium">
                    {money(item.unit_price_cents)}
                  </span>
                </li>
              ))
            ) : (
              <li className="list-group-item d-flex justify-content-between align-items-center px-0">
                <span>Bilete eveniment</span>
                <span>{money(order.total_cents)}</span>
              </li>
            )}
          </ul>

          <div className="d-flex justify-content-between text-muted small mb-1">
            <span>Subtotal</span>
            <span>{money(subtotalCents)}</span>
          </div>

          <div className="d-flex justify-content-between text-muted small mb-2">
            <span>TVA ({vat_rate}%)</span>
            <span>{money(vatCents)}</span>
          </div>

          <hr className="my-2" />

          <div className="d-flex justify-content-between fw-bold fs-5">
            <span>Total</span>
            <span className="text-primary">{money(totalCents)}</span>
          </div>
        </div>
      </div>

      {/* 2. BILLING INFORMATION */}
      <div className="card shadow-sm">
        <div className="card-header bg-light d-flex justify-content-between align-items-center">
          <h5 className="card-title mb-0">Billing Information</h5>

          {!editingBilling ? (
            <button
              type="button"
              className="btn btn-sm btn-outline-secondary"
              onClick={startEditBilling}
            >
              {billingAddressString
                ? "Editează adresa"
                : "+ Adaugă adresa"}
            </button>
          ) : (
            <button
              type="button"
              className="btn btn-sm btn-outline-secondary"
              onClick={() => setEditingBilling(false)}
              disabled={savingBilling}
            >
              Renunță
            </button>
          )}
        </div>

        <div className="card-body">
          <div className="mb-2">
            <span className="text-muted small d-block">Client:</span>
            <strong>{user?.name || "N/A"}</strong>
          </div>

          <div className="mb-2">
            <span className="text-muted small d-block">
              Email livrare bilete:
            </span>
            <span>{user?.email || "N/A"}</span>
          </div>

          {!editingBilling ? (
            <div>
              <span className="text-muted small d-block">
                Adresă de facturare:
              </span>

              {billingAddressString ? (
                <span>{billingAddressString}</span>
              ) : (
                <span className="text-warning small">
                  Nicio adresă salvată.
                </span>
              )}
            </div>
          ) : (
            <>
              <BillingForm value={draft} onChange={setDraft} />

              <button
                type="button"
                className="btn btn-primary btn-sm mt-3"
                onClick={saveBilling}
                disabled={savingBilling}
              >
                {savingBilling ? "Se salvează..." : "Salvează adresa"}
              </button>
            </>
          )}
        </div>
      </div>

      {!hasRequiredBilling && !editingBilling && (
        <div className="alert alert-warning small py-2 mb-0">
          Adaugă adresa de facturare pentru a putea plăti.
        </div>
      )}

      {/* 3. PAYMENT */}
      <div className="card shadow-sm">
        <div className="card-header bg-light">
          <h5 className="card-title mb-0">Payment</h5>
        </div>

        <div className="card-body">
          <PaymentElement
            onReady={() => setReady(true)}
            onLoadError={(e) =>
              toast.error(
                e.error.message ??
                  "Nu s-a putut încărca formularul de plată"
              )
            }
          />
        </div>
      </div>

      {/* TERMS CHECKBOX */}
      <div className="form-check my-1">
        <input
          className="form-check-input"
          type="checkbox"
          id="termsCheckbox"
          checked={acceptedTerms}
          onChange={(e) => setAcceptedTerms(e.target.checked)}
        />

        <label className="form-check-label small" htmlFor="termsCheckbox">
          Accept Termenii și Condițiile
        </label>
      </div>

      {/* SUBMIT BUTTON */}
      <button
        type="submit"
        className="btn btn-primary btn-lg w-100"
        disabled={
          !stripe ||
          !ready ||
          paying ||
          !acceptedTerms ||
          !hasRequiredBilling ||
          editingBilling
        }
      >
        {paying
          ? "Se procesează..."
          : `Plătește ${money(totalCents)}`}
      </button>

      {/* TIMER RESERVATION HOLD */}
      <div className="text-center text-muted small">
        Locurile sunt rezervate timp de:{" "}
        <strong className="text-danger">
          {Math.floor(timeLeft / 60)}:
          {String(timeLeft % 60).padStart(2, "0")}
        </strong>
      </div>
    </form>
  );
}

export default function CheckoutPage() {
  const { reservationId } = useParams<{ reservationId: string }>();
  const { state } = useLocation();
  const navigate = useNavigate();

  const { setPendingOrder, clearPendingOrder } = useCartStore();

  const [order, setOrder] = useState<OrderResult | null>(null);
  const [secret, setSecret] = useState<string | null>(
    state?.clientSecret ?? null
  );
  const [left, setLeft] = useState(0);

  useEffect(() => {
    if (!reservationId) return;

    getOrder(reservationId)
      .then((res) => {
        const o = res.data.data;

        // Dacă rezervarea nu mai este pending, nu mai afișăm checkout-ul.
        if (o.status !== "pending") {
          clearPendingOrder();

          return navigate(
            `/orders/${o.id}/confirmation`,
            { replace: true }
          );
        }

        setOrder(o);

        // Păstrăm rezervarea în Zustand chiar dacă utilizatorul:
        // - deschide checkout-ul direct prin link
        // - dă refresh paginii
        if (o.expires_at) {
          setPendingOrder({
            id: o.id,
            expiresAt: o.expires_at,
          });
        }

        setSecret(
          (s) => s ?? res.data.client_secret ?? null
        );
      })
      .catch(() => {
        clearPendingOrder();
        navigate("/events");
      });
  }, [
    reservationId,
    navigate,
    setPendingOrder,
    clearPendingOrder,
  ]);

  useEffect(() => {
    if (!order?.expires_at) return;

    const tick = () => {
      const s = Math.max(
        0,
        Math.floor(
          (new Date(order.expires_at).getTime() - Date.now()) /
            1000
        )
      );

      setLeft(s);

      if (s === 0) {
        clearPendingOrder();
        toast.warn("Rezervarea locurilor a expirat.");
        navigate("/events");
      }
    };

    tick();

    const t = setInterval(tick, 1000);

    return () => clearInterval(t);
  }, [order, navigate, clearPendingOrder]);

  if (keyLooksWrong) {
    return (
      <div className="container mt-4 alert alert-danger">
        <code>VITE_STRIPE_PUBLISHABLE_KEY</code> trebuie să înceapă cu{" "}
        <code>pk_</code>.
      </div>
    );
  }

  if (!order || !secret) {
    return (
      <div className="container mt-5 text-center">
        Se încarcă detaliile comenzii...
      </div>
    );
  }

  return (
    <div
      className="container my-4"
      style={{ maxWidth: 560 }}
    >
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h2>Checkout</h2>

        <button
          className="btn btn-sm btn-outline-danger"
          onClick={async () => {
            await cancelOrder(order.id);
            clearPendingOrder();
            navigate("/events");
          }}
        >
          Anulează comanda
        </button>
      </div>

      <Elements
        stripe={stripePromise}
        options={{ clientSecret: secret }}
      >
        <PayForm order={order} timeLeft={left} />
      </Elements>
    </div>
  );
}
