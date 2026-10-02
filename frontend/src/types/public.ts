import type { BillingData } from "@/types"; 


export interface PublicCategory { id: number; name: string; slug: string }
export interface PublicVenue { id: number; name: string; city: string; address: string; capacity: number }

export interface PublicEvent {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  status: "draft" | "published" | "cancelled";
  starts_at: string;
  ends_at: string;
  banner_url: string | null;
  category?: PublicCategory;
  venue?: PublicVenue;
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number; per_page: number };
}

export type SeatStatus = "available" | "blocked" | "sold";

export interface PublicSeat { id: number; row: string; number: number; status: SeatStatus }
export interface PublicSection { id: number; name: string; price_cents: number; seats: PublicSeat[] }

export interface OrderItem { seat: { id: number; label: string; section: string }; unit_price_cents: number }
export interface OrderResult {
  id: string; // UUID
  status: "pending" | "completed" | "cancelled" | "refunded";
  refund_request?: { status: "pending" | "approved" | "declined"; reason: string; created_at: string } | null;

  total_cents: number;
  subtotal_cents: number;
  vat_cents: number;
  vat_rate: number;
  can_refund?: boolean;
  billing_address?: { name: string; email: string; country: string | null; city: string | null; postal_code: string | null; street: string | null } | null;

  invoice_number: string | null;
  expires_at: string | null;
  paid_at: string | null;


  event?: {
    id: number;
    title: string;
    slug: string;
    starts_at: string;
  };

  user?: {
    name: string;
    email: string;
  } & Partial<BillingData>;

  items?: OrderItem[];
  created_at: string;
}