import { ReactNode } from "react";
import { Navigate } from "react-router-dom";
import { useStore } from "../store/useStore";

interface Props {
  children: ReactNode;
  role?: string;
}

const ProtectedRoute = ({ children, role }: Props) => {
  const { isAuth, role: userRole, initialized } = useStore();

  if (!initialized) return <div>Loading...</div>;

  if (!isAuth) return <Navigate to="/login" replace />;

  if (role && userRole !== role) return <Navigate to="/dashboard" replace />;

  return <>{children}</>;
};

export default ProtectedRoute;
