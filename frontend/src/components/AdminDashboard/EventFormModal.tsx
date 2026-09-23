import { ChangeEvent, FormEvent, useEffect, useState } from "react";
import { toast } from "react-toastify";
import styles from "./EventsTab.module.css";
import {
  createEvent,
  listCategories,
  listVenues,
  removeBanner,
  updateEvent,
  uploadBanner,
} from "../../services/adminEvents";
import type { Category, EventItem, EventStatus, Venue } from "@/types/events";

const MAX_BANNER_BYTES = 2 * 1024 * 1024;
const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/webp"];

const pad = (n: number) => String(n).padStart(2, "0");
// ISO (from API) -> value for <input type="datetime-local"> in the browser's timezone
const toInputValue = (iso: string) => {
  const d = new Date(iso);
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

interface Props {
  event: EventItem | null; // null = create
  onClose: () => void;
  onSaved: () => void;
}

export default function EventFormModal({ event, onClose, onSaved }: Props) {
  const isEdit = !!event;

  const [categories, setCategories] = useState<Category[]>([]);
  const [venues, setVenues] = useState<Venue[]>([]);

  const [title, setTitle] = useState(event?.title ?? "");
  const [description, setDescription] = useState(event?.description ?? "");
  const [categoryId, setCategoryId] = useState(String(event?.category?.id ?? ""));
  const [venueId, setVenueId] = useState(String(event?.venue?.id ?? ""));
  const [startsAt, setStartsAt] = useState(event ? toInputValue(event.starts_at) : "");
  const [endsAt, setEndsAt] = useState(event ? toInputValue(event.ends_at) : "");
  const [status, setStatus] = useState<EventStatus>(event?.status ?? "draft");

  const [banner, setBanner] = useState<File | null>(null);
  const [preview, setPreview] = useState<string | null>(event?.banner_url ?? null);
  const [removeCurrent, setRemoveCurrent] = useState(false);

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    Promise.all([listCategories(), listVenues()])
      .then(([c, v]) => {
        setCategories(c.data.data);
        setVenues(v.data.data);
      })
      .catch(() => toast.error("Failed to load categories/venues"));
  }, []);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => e.key === "Escape" && onClose();
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [onClose]);

  // free the blob URL when the preview changes / component unmounts
  useEffect(() => {
    return () => {
      if (preview?.startsWith("blob:")) URL.revokeObjectURL(preview);
    };
  }, [preview]);

  const handleFile = (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!ALLOWED_TYPES.includes(file.type)) {
      setErrors((p) => ({ ...p, banner: "Only JPG, PNG or WEBP images are allowed" }));
      return;
    }
    if (file.size > MAX_BANNER_BYTES) {
      setErrors((p) => ({ ...p, banner: "Image must be 2 MB or smaller" }));
      return;
    }

    setErrors((p) => {
      const { banner: _omit, ...rest } = p;
      return rest;
    });
    setBanner(file);
    setRemoveCurrent(false);
    setPreview(URL.createObjectURL(file));
  };

  const clearBanner = () => {
    setBanner(null);
    setPreview(null);
    setRemoveCurrent(true);
  };

  const validate = () => {
    const e: Record<string, string> = {};
    if (!title.trim()) e.title = "Title is required";
    if (!categoryId) e.category_id = "Select a category";
    if (!venueId) e.venue_id = "Select a venue";
    if (!startsAt) e.starts_at = "Start date is required";
    if (!endsAt) e.ends_at = "End date is required";
    if (startsAt && endsAt && new Date(endsAt) <= new Date(startsAt)) {
      e.ends_at = "End must be after start";
    }
    if (!isEdit && startsAt && new Date(startsAt) <= new Date()) {
      e.starts_at = "Start must be in the future";
    }
    return e;
  };

  const handleSubmit = async (ev: FormEvent) => {
    ev.preventDefault();

    const clientErrors = validate();
    setErrors(clientErrors);
    if (Object.keys(clientErrors).length) return;

    // toISOString() sends an unambiguous UTC instant; keep the Laravel app timezone on UTC.
    const body = {
      title: title.trim(),
      description: description.trim() || null,
      category_id: Number(categoryId),
      venue_id: Number(venueId),
      starts_at: new Date(startsAt).toISOString(),
      ends_at: new Date(endsAt).toISOString(),
      status,
    };

    setSaving(true);
    try {
      if (isEdit && event) {
        await updateEvent(event.id, body);
        if (banner) await uploadBanner(event.id, banner);
        else if (removeCurrent && event.banner_url) await removeBanner(event.id);
        toast.success("Event updated");
      } else {
        const fd = new FormData();
        Object.entries(body).forEach(([k, v]) => {
          if (v !== null) fd.append(k, String(v));
        });
        if (banner) fd.append("banner", banner);
        await createEvent(fd);
        toast.success("Event created");
      }
      onSaved();
    } catch (err: any) {
      const serverErrors = err.response?.data?.errors as
        | Record<string, string[]>
        | undefined;
      if (serverErrors) {
        setErrors(
          Object.fromEntries(Object.entries(serverErrors).map(([k, v]) => [k, v[0]])),
        );
      } else {
        toast.error(err.response?.data?.message || "Save failed");
      }
    } finally {
      setSaving(false);
    }
  };

  const fieldClass = (name: string) =>
    `${styles.field} ${errors[name] ? styles.invalid : ""}`;

  return (
    <div className={styles.overlay} onMouseDown={onClose}>
      <form
        className={styles.modal}
        onMouseDown={(e) => e.stopPropagation()}
        onSubmit={handleSubmit}
        noValidate
      >
        <h3>{isEdit ? "Edit event" : "New event"}</h3>

        <div className={styles.grid}>
          <div className={`${fieldClass("title")} ${styles.full}`}>
            <label>Title</label>
            <input value={title} onChange={(e) => setTitle(e.target.value)} maxLength={200} autoFocus />
            {errors.title && <div className={styles.error}>{errors.title}</div>}
          </div>

          <div className={fieldClass("category_id")}>
            <label>Category</label>
            <select value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
              <option value="">Select…</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            {errors.category_id && <div className={styles.error}>{errors.category_id}</div>}
          </div>

          <div className={fieldClass("venue_id")}>
            <label>Venue</label>
            <select value={venueId} onChange={(e) => setVenueId(e.target.value)}>
              <option value="">Select…</option>
              {venues.map((v) => (
                <option key={v.id} value={v.id}>{v.name} — {v.city}</option>
              ))}
            </select>
            {errors.venue_id && <div className={styles.error}>{errors.venue_id}</div>}
          </div>

          <div className={fieldClass("starts_at")}>
            <label>Starts</label>
            <input type="datetime-local" value={startsAt} onChange={(e) => setStartsAt(e.target.value)} />
            {errors.starts_at && <div className={styles.error}>{errors.starts_at}</div>}
          </div>

          <div className={fieldClass("ends_at")}>
            <label>Ends</label>
            <input type="datetime-local" value={endsAt} onChange={(e) => setEndsAt(e.target.value)} />
            {errors.ends_at && <div className={styles.error}>{errors.ends_at}</div>}
          </div>

          <div className={fieldClass("status")}>
            <label>Status</label>
            <select value={status} onChange={(e) => setStatus(e.target.value as EventStatus)}>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="cancelled">Cancelled</option>
            </select>
            {errors.status && <div className={styles.error}>{errors.status}</div>}
          </div>

          <div className={`${fieldClass("description")} ${styles.full}`}>
            <label>Description</label>
            <textarea value={description} onChange={(e) => setDescription(e.target.value)} maxLength={5000} />
            {errors.description && <div className={styles.error}>{errors.description}</div>}
          </div>

          <div className={`${fieldClass("banner")} ${styles.full}`}>
            <label>Banner (JPG/PNG/WEBP, max 2 MB)</label>
            {preview && <img className={styles.preview} src={preview} alt="Banner preview" />}
            <input type="file" accept="image/jpeg,image/png,image/webp" onChange={handleFile} />
            {preview && (
              <button type="button" className={styles.ghostBtn} style={{ marginTop: 8 }} onClick={clearBanner}>
                Remove banner
              </button>
            )}
            {errors.banner && <div className={styles.error}>{errors.banner}</div>}
          </div>
        </div>

        <div className={styles.actions}>
          <button type="button" className={styles.ghostBtn} onClick={onClose} disabled={saving}>
            Cancel
          </button>
          <button type="submit" className={styles.primaryBtn} disabled={saving}>
            {saving ? "Saving…" : isEdit ? "Save changes" : "Create event"}
          </button>
        </div>
      </form>
    </div>
  );
}
