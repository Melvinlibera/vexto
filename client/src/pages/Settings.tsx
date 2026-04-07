import { useAuth } from "@/_core/hooks/useAuth";
import { useLocation } from "wouter";
import { useEffect, useState } from "react";
import { trpc } from "@/lib/trpc";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Loader2, LogOut } from "lucide-react";
import { toast } from "sonner";

export default function Settings() {
  const { user, isAuthenticated, logout } = useAuth();
  const [, navigate] = useLocation();
  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState({
    name: user?.name || "",
    apellido: user?.apellido || "",
    bio: user?.bio || "",
    ubicacion: user?.ubicacion || "",
    themePreference: user?.themePreference || "light",
    companyName: user?.companyName || "",
    companyWebsite: user?.companyWebsite || "",
  });

  const updateProfile = trpc.user.updateProfile.useMutation();

  useEffect(() => {
    if (!isAuthenticated) {
      navigate("/");
    }
  }, [isAuthenticated, navigate]);

  if (!isAuthenticated) return null;

  const handleSave = async () => {
    updateProfile.mutate(formData, {
      onSuccess: () => {
        toast.success("Perfil actualizado");
        setIsEditing(false);
      },
      onError: () => {
        toast.error("Error al actualizar el perfil");
      },
    });
  };

  const handleLogout = async () => {
    await logout();
    navigate("/");
  };

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Header */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40">
        <div className="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Configuración</h1>
          <Button variant="outline" onClick={() => navigate("/dashboard")}>
            Volver
          </Button>
        </div>
      </header>

      <div className="max-w-4xl mx-auto px-4 py-8">
        {/* Profile Card */}
        <Card className="p-8 mb-6">
          <div className="flex items-start justify-between mb-6">
            <div>
              <h2 className="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                Mi Perfil
              </h2>
              <p className="text-slate-600 dark:text-slate-400">
                Gestiona tu información personal
              </p>
            </div>
            {!isEditing && (
              <Button onClick={() => setIsEditing(true)}>
                Editar
              </Button>
            )}
          </div>

          {isEditing ? (
            <div className="space-y-4">
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Nombre
                  </label>
                  <Input
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Apellido
                  </label>
                  <Input
                    value={formData.apellido}
                    onChange={(e) => setFormData({ ...formData, apellido: e.target.value })}
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  Ubicación
                </label>
                <Input
                  value={formData.ubicacion}
                  onChange={(e) => setFormData({ ...formData, ubicacion: e.target.value })}
                  placeholder="Ciudad o zona"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  Bio
                </label>
                <Textarea
                  value={formData.bio}
                  onChange={(e) => setFormData({ ...formData, bio: e.target.value })}
                  placeholder="Cuéntanos sobre ti..."
                  rows={4}
                />
              </div>

              {user?.userType === "compania" && (
                <>
                  <div>
                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                      Nombre de la Compañía
                    </label>
                    <Input
                      value={formData.companyName}
                      onChange={(e) => setFormData({ ...formData, companyName: e.target.value })}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                      Sitio Web
                    </label>
                    <Input
                      type="url"
                      value={formData.companyWebsite}
                      onChange={(e) => setFormData({ ...formData, companyWebsite: e.target.value })}
                      placeholder="https://ejemplo.com"
                    />
                  </div>
                </>
              )}

              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  Tema
                </label>
                <Select value={formData.themePreference} onValueChange={(value) => setFormData({ ...formData, themePreference: value as any })}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="light">Claro</SelectItem>
                    <SelectItem value="dark">Oscuro</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="flex gap-3 pt-4">
                <Button
                  className="bg-blue-600 hover:bg-blue-700"
                  onClick={handleSave}
                  disabled={updateProfile.isPending}
                >
                  {updateProfile.isPending && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
                  Guardar
                </Button>
                <Button
                  variant="outline"
                  onClick={() => setIsEditing(false)}
                >
                  Cancelar
                </Button>
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Nombre</p>
                  <p className="font-semibold text-slate-900 dark:text-white">{user?.name}</p>
                </div>
                <div>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Apellido</p>
                  <p className="font-semibold text-slate-900 dark:text-white">{user?.apellido || "-"}</p>
                </div>
              </div>

              <div>
                <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Email</p>
                <p className="font-semibold text-slate-900 dark:text-white">{user?.email}</p>
              </div>

              <div>
                <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Tipo de Cuenta</p>
                <p className="font-semibold text-slate-900 dark:text-white capitalize">
                  {user?.userType === "compania" ? "Compañía" : "Usuario"}
                </p>
              </div>

              {user?.ubicacion && (
                <div>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Ubicación</p>
                  <p className="font-semibold text-slate-900 dark:text-white">{user.ubicacion}</p>
                </div>
              )}

              {user?.bio && (
                <div>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Bio</p>
                  <p className="font-semibold text-slate-900 dark:text-white">{user.bio}</p>
                </div>
              )}
            </div>
          )}
        </Card>

        {/* Account Stats */}
        <Card className="p-8 mb-6">
          <h2 className="text-lg font-semibold text-slate-900 dark:text-white mb-4">
            Estadísticas de Cuenta
          </h2>
          <div className="grid md:grid-cols-3 gap-6">
            <div>
              <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Propiedades Publicadas</p>
              <p className="text-3xl font-bold text-blue-600">{user?.publishedCount || 0}</p>
            </div>
            <div>
              <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Calificación</p>
              <p className="text-3xl font-bold text-yellow-500">{user?.rating?.toFixed(1) || "0.0"}</p>
            </div>
            <div>
              <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Total de Reseñas</p>
              <p className="text-3xl font-bold text-slate-900 dark:text-white">{user?.totalReviews || 0}</p>
            </div>
          </div>
        </Card>

        {/* Danger Zone */}
        <Card className="p-8 border-red-200 dark:border-red-900">
          <h2 className="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">
            Zona de Peligro
          </h2>
          <p className="text-slate-600 dark:text-slate-400 mb-4">
            Acciones irreversibles para tu cuenta
          </p>
          <Button
            variant="destructive"
            onClick={handleLogout}
            className="bg-red-600 hover:bg-red-700"
          >
            <LogOut className="w-4 h-4 mr-2" />
            Cerrar Sesión
          </Button>
        </Card>
      </div>
    </div>
  );
}
