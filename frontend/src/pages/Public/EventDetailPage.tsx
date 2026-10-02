import { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { toast } from "react-toastify";
import styles from "../../styles/PublicEvents.module.css";
import { useCartStore } from "../../store/useCartStore";
import {
  getEventSeats,
  getPublicEvent,
} from "../../services/publicEvents";
import type {
  PublicEvent,
  PublicSection,
} from "@/types/public";

const money = (cents: number) =>
  (cents / 100).toLocaleString(undefined, {
    style: "currency",
    currency: "EUR",
  });

export default function EventDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();

  const {
    event: cartEvent,
    seats: cartSeats,
    addSeat,
    removeSeat,
  } = useCartStore();

  const [event, setEvent] = useState<PublicEvent | null>(null);
  const [sections, setSections] = useState<PublicSection[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    if (!slug) return;

    setLoading(true);

    Promise.all([
      getPublicEvent(slug),
      getEventSeats(slug),
    ])
      .then(([ev, seats]) => {
        setEvent(ev.data.data);
        setSections(seats.data.data);
      })
      .catch(() => toast.error("Event not found"))
      .finally(() => setLoading(false));
  }, [slug]);

  useEffect(() => {
    load();
  }, [load]);

  /*
   * Selection belongs to the persisted cart.
   *
   * We only consider cart seats when the cart belongs to
   * the event currently being displayed.
   */
  const selected = useMemo(
    () =>
      new Set(
        event && cartEvent?.id === event.id
          ? cartSeats.map((seat) => seat.id)
          : [],
      ),
    [event, cartEvent, cartSeats],
  );

  const totalCents = useMemo(
    () =>
      cartSeats.reduce(
        (sum, seat) => sum + seat.priceCents,
        0,
      ),
    [cartSeats],
  );

  /*
   * A persisted cart can become stale while the user is away
   * from the event page.
   *
   * Remove seats that are no longer available according to
   * the freshly loaded seat map.
   */
  useEffect(() => {
    if (
      !event ||
      cartEvent?.id !== event.id ||
      sections.length === 0
    ) {
      return;
    }

    const status = new Map(
      sections.flatMap((section) =>
        section.seats.map(
          (seat) => [seat.id, seat.status] as const,
        ),
      ),
    );

    const stale = cartSeats.filter(
      (seat) => status.get(seat.id) !== "available",
    );

    if (stale.length) {
      stale.forEach((seat) => removeSeat(seat.id));

      toast.warn(
        `${stale.length} seat(s) in your cart are no longer available`,
      );
    }

    // Intentionally depends on the fresh seat map.
    // removeSeat/cartSeats changes should not re-trigger this effect.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [sections, event]);

  const toggleSeat = (
    seat: {
      id: number;
      number: number;
      status: string;
    },
    section: PublicSection,
    row: string,
  ) => {
    if (!event) return;

    if (selected.has(seat.id)) {
      removeSeat(seat.id);
      return;
    }

    if (seat.status !== "available") {
      return;
    }

    const ok = addSeat(
      {
        id: event.id,
        slug: event.slug,
        title: event.title,
      },
      {
        id: seat.id,
        label: `${row}${seat.number}`,
        section: section.name,
        priceCents: section.price_cents,
      },
    );

    if (!ok) {
      toast.warn("You can select up to 10 seats");
    }
  };

  const seatClass = (status: string, id: number) => {
    if (selected.has(id)) {
      return `${styles.seat} ${styles.seatSelected}`;
    }

    if (status === "sold") {
      return `${styles.seat} ${styles.seatSold}`;
    }

    if (status === "blocked") {
      return `${styles.seat} ${styles.seatBlocked}`;
    }

    return `${styles.seat} ${styles.seatAvailable}`;
  };

  if (loading) {
    return (
      <div className={styles.wrapper}>
        Loading…
      </div>
    );
  }

  if (!event) {
    return (
      <div className={styles.wrapper}>
        Event not found.
      </div>
    );
  }

  return (
    <div className={styles.wrapper}>
      {event.banner_url && (
        <img
          className={styles.detailBanner}
          src={event.banner_url}
          alt={event.title}
        />
      )}

      <h1>{event.title}</h1>

      <p className={styles.detailMeta}>
        {event.category?.name} ·{" "}
        {new Date(event.starts_at).toLocaleString()} ·{" "}
        {event.venue
          ? `${event.venue.name}, ${event.venue.city}`
          : ""}
      </p>

      {event.description && <p>{event.description}</p>}

      <h2>Select your seats</h2>

      <div className={styles.legend}>
        <span>
          <i
            className={styles.legendDot}
            style={{ background: "#e5e7eb" }}
          />{" "}
          Available
        </span>

        <span>
          <i
            className={styles.legendDot}
            style={{ background: "#4f46e5" }}
          />{" "}
          Selected
        </span>

        <span>
          <i
            className={styles.legendDot}
            style={{ background: "#f3f4f6" }}
          />{" "}
          Sold
        </span>
      </div>

      {sections.map((section) => (
        <div
          key={section.id}
          className={styles.section}
        >
          <div className={styles.sectionHeader}>
            <span>{section.name}</span>
            <span>{money(section.price_cents)}</span>
          </div>

          {Object.entries(
            section.seats.reduce<
              Record<string, typeof section.seats>
            >((acc, seat) => {
              (acc[seat.row] ??= []).push(seat);
              return acc;
            }, {}),
          ).map(([row, seats]) => (
            <div
              key={row}
              className={styles.seatRow}
            >
              <span className={styles.rowLabel}>
                {row}
              </span>

              <div className={styles.seats}>
                {seats.map((seat) => (
                  <button
                    key={seat.id}
                    type="button"
                    className={seatClass(
                      seat.status,
                      seat.id,
                    )}
                    disabled={
                      seat.status !== "available" &&
                      !selected.has(seat.id)
                    }
                    onClick={() =>
                      toggleSeat(
                        seat,
                        section,
                        row,
                      )
                    }
                    title={`${row}${seat.number} — ${seat.status}`}
                  >
                    {seat.number}
                  </button>
                ))}
              </div>
            </div>
          ))}
        </div>
      ))}

      {selected.size > 0 && (
        <div className={styles.checkoutBar}>
          <span>
            {selected.size} seat(s),{" "}
            {money(totalCents)}
          </span>

          <button
            className={styles.primaryBtn}
            onClick={() => navigate("/cart")}
          >
            Go to cart
          </button>
        </div>
      )}
    </div>
  );
}

