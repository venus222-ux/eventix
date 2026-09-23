import API from "../api";
import type {
  Category,
  EventFilters,
  EventItem,
  Paginated,
  Venue,
} from "@/types/events";

// NOTE: no manual Content-Type for FormData — axios sets the multipart boundary itself.

export const listEvents = (params: EventFilters) =>
  API.get<Paginated<EventItem>>("/admin/events", { params });

export const createEvent = (body: FormData) =>
  API.post<{ data: EventItem }>("/admin/events", body);

export const updateEvent = (id: number, body: Record<string, unknown>) =>
  API.put<{ data: EventItem }>(`/admin/events/${id}`, body);

export const deleteEvent = (id: number) => API.delete(`/admin/events/${id}`);

export const restoreEvent = (id: number) =>
  API.patch(`/admin/events/${id}/restore`);

export const uploadBanner = (id: number, file: File) => {
  const fd = new FormData();
  fd.append("banner", file);
  return API.post<{ data: EventItem }>(`/admin/events/${id}/banner`, fd);
};

export const removeBanner = (id: number) =>
  API.delete(`/admin/events/${id}/banner`);

export const listCategories = () =>
  API.get<{ data: Category[] }>("/admin/categories");

export const listVenues = () => API.get<{ data: Venue[] }>("/admin/venues");
