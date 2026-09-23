import { useEffect, useState } from "react";
import API from "../../api";
import styles from "../../styles/AdminDashboard.module.css"; // Ensure this matches your file path
import TrafficTrendChart from "./TrafficTrendChart";
import DoughnutChart from "./DoughnutChart";

type TrafficData = {
  stats: {
    total_visitors: number;
    unique_visitors: number;
    page_views: number;
    active_users: number;
  };
  trend: { date: string; visitors: number }[];
  by_country: { country: string; count: number }[];
  by_device: { device: string; count: number }[];
  by_browser: { browser: string; count: number }[];
  recent_visitors: any[];
  online_count: number;
};

export default function TrafficDashboard() {
  const [data, setData] = useState<TrafficData | null>(null);
  const [period, setPeriod] = useState<"today" | "7d" | "30d" | "12m">("7d");
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    API.get(`/admin/traffic?period=${period}`)
      .then((res) => setData(res.data))
      .catch((err) => console.error("Traffic fetch error:", err))
      .finally(() => setLoading(false));
  }, [period]);

  if (loading)
    return (
      <div className={styles.loadingState}>Loading traffic analytics...</div>
    );
  if (!data)
    return <div className={styles.errorState}>Failed to load traffic data</div>;

  return (
    <div className={styles.mainContent}>
      <header className={styles.header}>
        <h2 className={styles.welcomeTitle}>🌐 Traffic Analytics</h2>
        <p className={styles.subtitle}>
          Real-time visitor insights and behavior
        </p>
      </header>

      <div
        className={styles.filterBar}
        style={{ marginBottom: "24px", display: "flex", gap: "8px" }}
      >
        {(["today", "7d", "30d", "12m"] as const).map((p) => (
          <button
            key={p}
            className={`${styles.navItem} ${period === p ? styles.activeNavItem : ""}`}
            onClick={() => setPeriod(p)}
          >
            {p === "today"
              ? "Today"
              : p === "7d"
                ? "7 Days"
                : p === "30d"
                  ? "30 Days"
                  : "12 Months"}
          </button>
        ))}
      </div>

      <div className={styles.statsGrid}>
        <div className={styles.statCard}>
          <span className={styles.statLabel}>Total Visitors</span>
          <span className={styles.statValue}>
            {data.stats.total_visitors.toLocaleString()}
          </span>
        </div>
        <div className={styles.statCard}>
          <span className={styles.statLabel}>Unique Visitors</span>
          <span className={styles.statValue}>
            {data.stats.unique_visitors.toLocaleString()}
          </span>
        </div>
        <div className={styles.statCard}>
          <span className={styles.statLabel}>Page Views</span>
          <span className={styles.statValue}>
            {data.stats.page_views.toLocaleString()}
          </span>
        </div>
        <div className={styles.statCard}>
          <span className={styles.statLabel}>🟢 Online Now</span>
          <span className={`${styles.statValue} ${styles.primaryText}`}>
            {data.online_count}
          </span>
        </div>
      </div>

      <div className={styles.card} style={{ marginBottom: "24px" }}>
        <h3 style={{ marginTop: 0 }}>Traffic Trend</h3>
        <TrafficTrendChart data={data.trend} />
      </div>

      <div className={styles.twoColumn}>
        <div className={styles.card}>
          <div className={styles.tableHeader}>🌍 Visitors by Country</div>
          <table className={styles.adminTable}>
            <tbody>
              {data.by_country.map((item, i) => (
                <tr key={i}>
                  <td>{item.country || "Unknown"}</td>
                  <td style={{ textAlign: "right", fontWeight: 600 }}>
                    {item.count}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className={styles.card}>
          <h3>Device & Browser</h3>
          <DoughnutChart
            data={data.by_device.map((d) => ({
              name: d.device,
              value: d.count,
            }))}
          />
          <div style={{ marginTop: "2rem" }}>
            <DoughnutChart
              data={data.by_browser.map((d) => ({
                name: d.browser,
                value: d.count,
              }))}
            />
          </div>
        </div>
      </div>
    </div>
  );
}
