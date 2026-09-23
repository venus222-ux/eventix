import { useCallback, useEffect, useRef, useState } from "react";
import { toast } from "react-toastify";
import styles from "../../styles/AdminDashboard.module.css";
import local from "./EventsTab.module.css";
import EventFormModal from "./EventFormModal";
import {
  deleteEvent,
  listEvents,
  restoreEvent,
} from "../../services/adminEvents";
import type { EventItem, EventStatus } from "@/types/events";

export default function EventsTab() {
  const [items, setItems] = useState<EventItem[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);

  const [q, setQ] = useState("");
  const [search, setSearch] = useState(""); // debounced value used for the request
  const [status, setStatus] = useState<"" | EventStatus>("");
  const [onlyTrashed, setOnlyTrashed] = useState(false);

  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<EventItem | null>(null);

  const requestId = useRef(0);

  useEffect(() => {
    const t = setTimeout(() => {
      setSearch(q.trim());
      setPage(1);
    }, 300);
    return () => clearTimeout(t);
  }, [q]);

  const load = useCallback(async () => {
    const id = ++requestId.current;
    setLoading(true);
    try {
      const res = await listEvents({
        page,
        q: search || undefined,
        status: status || undefined,
        trashed: onlyTrashed ? "only" : undefined,
      });
      if (id !== requestId.current) return; // a newer request superseded this one
      setItems(res.data.data);
      setLastPage(res.data.meta.last_page);
      setTotal(res.data.meta.total);
    } catch {
      if (id === requestId.current) toast.error("Failed to load events");
    } finally {
      if (id === requestId.current) setLoading(false);
    }
  }, [page, search, status, onlyTrashed]);

  useEffect(() => {
    load();
  }, [load]);

  const openCreate = () => {
    setEditing(null);
    setShowForm(true);
  };

  const openEdit = (ev: EventItem) => {
    setEditing(ev);
    setShowForm(true);
  };

  const handleSaved = () => {
    setShowForm(false);
    setEditing(null);
    load();
  };

  const handleDelete = async (ev: EventItem) => {
    if (!window.confirm(`Delete "${ev.title}"? You can restore it later.`)) return;
    try {
      await deleteEvent(ev.id);
      toast.success("Event deleted");
      // if this was the last row on the page, step back one page
      if (items.length === 1 && page > 1) setPage(page - 1);
      else load();
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Delete failed");
    }
  };

  const handleRestore = async (ev: EventItem) => {
    try {
      await restoreEvent(ev.id);
      toast.success("Event restored");
      load();
    } catch (err: any) {
      toast.error(err.response?.data?.message || "Restore failed");
    }
  };

  return (
    <div className={styles.tabFadeIn}>
      <header className={styles.header}>
        <h2>Events</h2>
        <p className={styles.subtitle}>Create and manage events, venues and banners.</p>
      </header>

      <div className={local.toolbar}>
        <input
          placeholder="Search title…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        <select
          value={status}
          onChange={(e) => {
            setStatus(e.target.value as "" | EventStatus);
            setPage(1);
          }}
        >
          <option value="">All statuses</option>
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <label>
          <input
            type="checkbox"
            checked={onlyTrashed}
            onChange={(e) => {
              setOnlyTrashed(e.target.checked);
              setPage(1);
            }}
          />{" "}
          Deleted only
        </label>
        <span className={local.spacer} />
        <button className={local.primaryBtn} onClick={openCreate}>
          + New event
        </button>
      </div>

      <div className={styles.tableWrapper}>
        <div className={styles.tableHeader}>Total events: {total}</div>
        <table className={styles.adminTable}>
          <thead>
            <tr>
              <th>Banner</th>
              <th>Title</th>
              <th>Category</th>
              <th>Venue</th>
              <th>Starts</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody className={loading ? local.muted : ""}>
            {!loading && items.length === 0 && (
              <tr>
                <td colSpan={7} style={{ textAlign: "center", padding: 24 }}>
                  No events found
                </td>
              </tr>
            )}
            {items.map((ev) => (
              <tr key={ev.id}>
                <td>
                  {ev.banner_url ? (
                    <img className={local.thumb} src={ev.banner_url} alt="" loading="lazy" />
                  ) : (
                    <div className={local.thumb} />
                  )}
                </td>
                <td>{ev.title}</td>
                <td>{ev.category?.name ?? "—"}</td>
                <td>{ev.venue ? `${ev.venue.name}, ${ev.venue.city}` : "—"}</td>
                <td>{new Date(ev.starts_at).toLocaleString()}</td>
                <td>
                  <span className={`${local.badge} ${local[ev.status]}`}>{ev.status}</span>
                </td>
                <td>
                  {ev.deleted_at ? (
                    <button className={local.ghostBtn} onClick={() => handleRestore(ev)}>
                      Restore
                    </button>
                  ) : (
                    <>
                      <button className={local.ghostBtn} onClick={() => openEdit(ev)}>
                        Edit
                      </button>
                      <button className={styles.deleteBtn} onClick={() => handleDelete(ev)}>
                        Delete
                      </button>
                    </>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className={local.pager}>
        <button className={local.ghostBtn} disabled={page <= 1} onClick={() => setPage(page - 1)}>
          ← Prev
        </button>
        <span>
          Page {page} / {lastPage}
        </span>
        <button className={local.ghostBtn} disabled={page >= lastPage} onClick={() => setPage(page + 1)}>
          Next →
        </button>
      </div>

      {showForm && (
        <EventFormModal
          event={editing}
          onClose={() => setShowForm(false)}
          onSaved={handleSaved}
        />
      )}
    </div>
  );
}
