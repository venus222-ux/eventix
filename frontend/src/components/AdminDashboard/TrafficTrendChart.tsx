import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

export default function TrafficTrendChart({
  data,
}: {
  data: { date: string; visitors: number }[];
}) {
  if (!data?.length) {
    return <div>No traffic data available</div>;
  }

  return (
    <ResponsiveContainer width="100%" height={320}>
      <LineChart data={data}>
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis
          dataKey="date"
          tickFormatter={(d) =>
            new Date(d).toLocaleDateString("en", {
              month: "short",
              day: "numeric",
            })
          }
        />
        <YAxis />
        <Tooltip />

        <Line
          type="monotone"
          dataKey="visitors"
          stroke="#6366f1"
          strokeWidth={3}
        />
      </LineChart>
    </ResponsiveContainer>
  );
}
