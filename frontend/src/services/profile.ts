import API from "../api";
import type { BillingData } from "@/types";

export const updateBilling = (data: BillingData) => API.put("/profile/billing", data);