import API from "../api";
import type { OrderResult, Paginated } from "@/types/public";

export const placeOrder = (eventId: number, seatIds: number[]) =>
  API.post<{ data: OrderResult; client_secret: string }>("/orders", {
    event_id: eventId,
    seat_ids: seatIds,
  });

export const getOrder = (id: string) =>
  API.get<{ data: OrderResult; client_secret?: string }>(`/orders/${id}`);

export const listMyOrders = (page = 1) =>
  API.get<Paginated<OrderResult>>("/orders", { params: { page } });

export const cancelOrder = (id: string) => API.post(`/orders/${id}/cancel`);

export interface RefundPayload {
  reason: string;
  message: string;
  accept: boolean;
}

// "approved" = refunded right now, "pending" = waiting for a human review
export const refundOrder = (id: string, payload: RefundPayload) =>
  API.post<{ data: OrderResult; refund: { status: "approved" | "pending" } }>(
    `/orders/${id}/refund`,
    payload,
  );

// The JWT lives in a header, so a plain <a href> can't download: fetch as a blob instead.
// Throws an Error with the server's message, so callers can toast it.
export async function downloadDocument(
  id: string,
  kind: "invoice" | "tickets",
  filename: string,
) {
  try {
    const res = await API.get(`/orders/${id}/${kind}`, { responseType: "blob" });
    const url = URL.createObjectURL(new Blob([res.data], { type: "application/pdf" }));

    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a); // Firefox needs the link in the DOM
    a.click();
    a.remove();

    // Revoking right after click() cancels the download in Firefox
    setTimeout(() => URL.revokeObjectURL(url), 10_000);
  } catch (err) {
    // With responseType "blob" a JSON error body also arrives as a Blob
    let message = "Download failed";
    const data = (err as { response?: { data?: unknown } }).response?.data;
    if (data instanceof Blob) {
      try {
        message = JSON.parse(await data.text()).message ?? message;
      } catch {
        /* not JSON: keep the generic message */
      }
    }
    throw new Error(message);
  }
}
