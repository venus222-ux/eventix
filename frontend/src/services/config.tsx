import API from "../api";

export interface PublicConfig {
  vat_rate: number;
  hold_minutes: number;
}

export const getPublicConfig = () => API.get<PublicConfig>("/config");