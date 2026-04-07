import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useState } from "react";
import { LogOut, Plus, MessageSquare, Heart } from "lucide-react";
import { useLocation } from "wouter";

export default function Dashboard() {
  const { user, logout } = useAuth();
  const [, setLocation] = useLocation();
  const [activeTab, setActiveTab] = useState<"properties" | "favorites" | "messages" | "admin">("properties");

  const handleLogout = async () => {
    await logout();
    setLocation("/");
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div className="container mx-auto px-4 py-4 flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-slate-900">Dashboard</h1>
            <p className="text-sm text-slate-600">Bienvenido, {user?.name || "Usuario"}</p>
          </div>
          <Button variant="outline" onClick={handleLogout} className="gap-2">
            <LogOut className="w-4 h-4" />
            Cerrar Sesión
          </Button>
        </div>
      </header>

      <div className="container mx-auto px-4 py-8">
        <div className="flex gap-2 mb-8 border-b border-slate-200">
          <button
            onClick={() => setActiveTab("properties")}
            className={`px-4 py-2 font-medium border-b-2 transition-colors ${
              activeTab === "properties"
                ? "border-blue-600 text-blue-600"
                : "border-transparent text-slate-600 hover:text-slate-900"
            }`}
          >
            Mis Propiedades
          </button>
          <button
            onClick={() => setActiveTab("favorites")}
            className={`px-4 py-2 font-medium border-b-2 transition-colors ${
              activeTab === "favorites"
                ? "border-blue-600 text-blue-600"
                : "border-transparent text-slate-600 hover:text-slate-900"
            }`}
          >
            <Heart className="w-4 h-4 inline mr-2" />
            Favoritos
          </button>
          <button
            onClick={() => setActiveTab("messages")}
            className={`px-4 py-2 font-medium border-b-2 transition-colors ${
              activeTab === "messages"
                ? "border-blue-600 text-blue-600"
                : "border-transparent text-slate-600 hover:text-slate-900"
            }`}
          >
            <MessageSquare className="w-4 h-4 inline mr-2" />
            Mensajes
          </button>
        </div>

        <div>
          {activeTab === "properties" && (
            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-bold">Mis Propiedades</h2>
                <Button className="gap-2" onClick={() => setLocation("/create-property")}>
                  <Plus className="w-4 h-4" />
                  Nueva Propiedad
                </Button>
              </div>
              <Card>
                <CardContent className="py-8 text-center">
                  <p className="text-slate-600">No tienes propiedades publicadas aún.</p>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === "favorites" && (
            <div className="space-y-4">
              <h2 className="text-xl font-bold">Mis Favoritos</h2>
              <Card>
                <CardContent className="py-8 text-center">
                  <p className="text-slate-600">No tienes favoritos aún.</p>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === "messages" && (
            <div className="space-y-4">
              <h2 className="text-xl font-bold">Mensajes</h2>
              <Card>
                <CardContent className="py-8 text-center">
                  <p className="text-slate-600">No tienes mensajes nuevos.</p>
                </CardContent>
              </Card>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
