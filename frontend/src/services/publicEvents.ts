import API from "../api";
import type { Paginated, PublicEvent, PublicSection } from "@/types/public";

export interface EventListParams {
  category?: string;
  from?: string;
  to?: string;
  page?: number;
}

export const listPublicEvents = (params: EventListParams) =>
  API.get<Paginated<PublicEvent>>("/events", { params });

export const getPublicEvent = (slug: string) =>
  API.get<{ data: PublicEvent }>(`/events/${slug}`);

export const getEventSeats = (slug: string) =>
  API.get<{ data: PublicSection[] }>(`/events/${slug}/seats`);