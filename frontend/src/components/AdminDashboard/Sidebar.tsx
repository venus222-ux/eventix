import styles from "../../styles/AdminDashboard.module.css";
import type { TabType } from "@/types";

interface SidebarProps {
  currentTab: TabType;
  setCurrentTab: React.Dispatch<React.SetStateAction<TabType>>;
}

export default function Sidebar({ currentTab, setCurrentTab }: SidebarProps) {
  return (
    <aside className={styles.sidebar}>
      <div className={styles.logo}>ShieldAdmin</div>
      <nav className={styles.navGroup}>
        <div
          className={`${styles.navItem} ${currentTab === "home" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("home")}
        >
          📊 Dashboard
        </div>

        <div
          className={`${styles.navItem} ${currentTab === "events" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("events")}
        >
          🎟️ Events
        </div>

<div
          className={`${styles.navItem} ${currentTab === "orders" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("orders")}
        >
          🎟️ Orders
        </div>

<div
  className={`${styles.navItem} ${currentTab === "refunds" ? styles.activeNavItem : ""}`}
  onClick={() => setCurrentTab("refunds")}
>
  ↩️ Refund requests
</div>
        {/* Added Traffic Analytics Tab */}
        <div
          className={`${styles.navItem} ${currentTab === "traffic" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("traffic")}
        >
          🌐 Traffic Analytics
        </div>

        <div
          className={`${styles.navItem} ${currentTab === "logs" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("logs")}
        >
          📜 Activity Logs
        </div>

        <div
          className={`${styles.navItem} ${currentTab === "users" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("users")}
        >
          👥 Users
        </div>
      </nav>
    </aside>
  );
}
