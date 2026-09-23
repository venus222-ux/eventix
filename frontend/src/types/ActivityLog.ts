export type ActivityLog = {
  _id: string;
  email: string;
  action: string;
  status: string;
  ip_address?: string;
  device?: string;
  created_at: string;
};

export type DashboardStats = {
  logins_today: number;
  failed_logins_today: number;
  active_users: number;
};

export type DashboardData = {
  stats: DashboardStats;
  recent_activity: ActivityLog[];
};
