import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { toast } from "react-toastify";
import styles from "../../styles/PublicEvents.module.css";
import { listPublicEvents } from "../../services/publicEvents";
import type { PublicEvent } from "@/types/public";

export default function EventsListPage() {
  const [events, setEvents] = useState<PublicEvent[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [category, setCategory] = useState("");

  useEffect(() => {
    setLoading(true);
    listPublicEvents({ page, category: category || undefined })
      .then((res) => {
        setEvents(res.data.data);
        setLastPage(res.data.meta.last_page);
      })
      .catch(() => toast.error("Failed to load events"))
      .finally(() => setLoading(false));
  }, [page, category]);

  return (
    <div className={styles.wrapper}>
      <h1>Upcoming events</h1>

      <div className={styles.filters}>
        <input
          placeholder="Category slug (e.g. rock)"
          value={category}
          onChange={(e) => {
            setCategory(e.target.value);
            setPage(1);
          }}
        />
      </div>

      {loading ? (
        <p>Loading…</p>
      ) : events.length === 0 ? (
        <p>No upcoming events.</p>
      ) : (
        <div className={styles.grid}>
          {events.map((ev) => (
            <Link key={ev.id} to={`/events/${ev.slug}`} className={styles.card}>
              {ev.banner_url ? (
                <img className={styles.banner} src={ev.banner_url} alt={ev.title} loading="lazy" />
              ) : (
                <div className={styles.banner} />
              )}
              <div className={styles.cardBody}>
                <p className={styles.cardTitle}>{ev.title}</p>
                <p className={styles.cardMeta}>
                  {ev.category?.name ?? "—"} · {new Date(ev.starts_at).toLocaleDateString()}
                </p>
                <p className={styles.cardMeta}>
                  {ev.venue ? `${ev.venue.name}, ${ev.venue.city}` : ""}
                </p>
              </div>
            </Link>
          ))}
        </div>
      )}

      {lastPage > 1 && (
        <div className={styles.pager}>
          <button className={styles.ghostBtn} disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            ← Prev
          </button>
          <span>Page {page} / {lastPage}</span>
          <button className={styles.ghostBtn} disabled={page >= lastPage} onClick={() => setPage((p) => p + 1)}>
            Next →
          </button>
        </div>
      )}
    </div>
  );
}
