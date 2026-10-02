// store/useCartStore.ts
import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";

export interface CartSeat { id: number; label: string; section: string; priceCents: number }
interface CartEvent { id: number; slug: string; title: string }
interface PendingOrder { id: string; expiresAt: string }

const MAX_SEATS = 8;

interface CartState {
  event: CartEvent | null;
  seats: CartSeat[];
  pendingOrder: PendingOrder | null;
  addSeat: (event: CartEvent, seat: CartSeat) => boolean;
  removeSeat: (seatId: number) => void;
  clearCart: () => void;
  setPendingOrder: (o: PendingOrder) => void;
  clearPendingOrder: () => void;
  reset: () => void;
}

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      event: null,
      seats: [],
      pendingOrder: null,

      addSeat: (event, seat) => {
        const { event: current, seats } = get();
        const base = current && current.id !== event.id ? [] : seats; // one event per order
        if (base.some((s) => s.id === seat.id)) return true;
        if (base.length >= MAX_SEATS) return false;
        set({ event, seats: [...base, seat] });
        return true;
      },
      removeSeat: (id) =>
        set((s) => {
          const seats = s.seats.filter((x) => x.id !== id);
          return { seats, event: seats.length ? s.event : null };
        }),
      clearCart: () => set({ event: null, seats: [] }),
      setPendingOrder: (pendingOrder) => set({ pendingOrder }),
      clearPendingOrder: () => set({ pendingOrder: null }),
      reset: () => set({ event: null, seats: [], pendingOrder: null }),
    }),
    {
      name: "eventix-cart",
      version: 1,
      storage: createJSONStorage(() => localStorage),
      partialize: (s) => ({ event: s.event, seats: s.seats, pendingOrder: s.pendingOrder }),
    }
  )
);

// keep tabs in sync
if (typeof window !== "undefined") {
  window.addEventListener("storage", (e) => {
    if (e.key === "eventix-cart") useCartStore.persist.rehydrate();
  });
}