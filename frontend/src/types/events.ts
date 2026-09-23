export type EventStatus = "draft" | "published" | "cancelled";

export interface Category {
  id: number;
  name: string;
  slug: string;
  events_count?: number;
}

export interface Venue {
  id: number;
  name: string;
  city: string;
  address: string;
  capacity: number;
  events_count?: number;
}

export interface EventItem {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  status: EventStatus;
  starts_at: string;
  ends_at: string;
  banner_url: string | null;
  category?: Category;
  venue?: Venue;
  deleted_at: string | null;
  created_at: string;
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number; per_page: number };
}

export interface EventFilters {
  q?: string;
  status?: EventStatus;
  trashed?: "with" | "only";
  page?: number;
  per_page?: number;
}
