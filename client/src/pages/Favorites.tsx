import { useAuth } from "@/_core/hooks/useAuth";
import { useLocation } from "wouter";
import { useEffect } from "react";
import { trpc } from "@/lib/trpc";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Loader2, MapPin, Bed, Bath, Heart, Search } from "lucide-react";

export default function Favorites() {
  const { user, isAuthenticated } = useAuth();
  const [, navigate] = useLocation();

  const { data: favorites, isLoading } = trpc.favorite.list.useQuery();
  const removeFavorite = trpc.favorite.remove.useMutation();

  useEffect(() => {
    if (!isAuthenticated) {
      navigate("/");
    }
  }, [isAuthenticated, navigate]);

  if (!isAuthenticated) return null;

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Header */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Mis Favoritos</h1>
          <Button variant="outline" onClick={() => navigate("/dashboard")}>
            Volver
          </Button>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 py-8">
        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
          </div>
        ) : favorites && favorites.length > 0 ? (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {favorites.map((property: any) => (
              <Card
                key={property.id}
                className="overflow-hidden hover:shadow-lg transition cursor-pointer"
                onClick={() => navigate(`/property/${property.id}`)}
              >
                {/* Image */}
                <div className="relative h-48 bg-slate-200 dark:bg-slate-700 overflow-hidden">
                  {property.imagenUrl ? (
                    <img
                      src={property.imagenUrl}
                      alt={property.titulo}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <Search className="w-12 h-12 text-slate-400" />
                    </div>
                  )}
                  <div className="absolute top-3 right-3 flex gap-2">
                    <span className="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                      {property.tipoOperacion === "venta" ? "VENTA" : "ALQUILER"}
                    </span>
                  </div>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      removeFavorite.mutate({ propertyId: property.id });
                    }}
                    className="absolute top-3 left-3 bg-white dark:bg-slate-800 rounded-full p-2 hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    <Heart className="w-5 h-5 fill-red-500 text-red-500" />
                  </button>
                </div>

                {/* Content */}
                <div className="p-4">
                  <div className="flex justify-between items-start mb-2">
                    <h3 className="font-semibold text-slate-900 dark:text-white truncate flex-1">
                      {property.titulo}
                    </h3>
                    <span className="text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-1 rounded">
                      {property.tipoPropiedad}
                    </span>
                  </div>

                  <p className="text-2xl font-bold text-blue-600 mb-3">
                    ${property.precio?.toLocaleString()}
                  </p>

                  <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400 mb-3">
                    <MapPin className="w-4 h-4" />
                    <span className="text-sm">{property.ubicacion}</span>
                  </div>

                  <div className="flex gap-4 text-sm text-slate-600 dark:text-slate-400 border-t border-slate-200 dark:border-slate-700 pt-3">
                    {property.habitaciones > 0 && (
                      <div className="flex items-center gap-1">
                        <Bed className="w-4 h-4" />
                        <span>{property.habitaciones}</span>
                      </div>
                    )}
                    {property.banos > 0 && (
                      <div className="flex items-center gap-1">
                        <Bath className="w-4 h-4" />
                        <span>{property.banos}</span>
                      </div>
                    )}
                    {property.areaM2 && (
                      <div className="flex items-center gap-1">
                        <span>{property.areaM2}m²</span>
                      </div>
                    )}
                  </div>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <Card className="p-12 text-center">
            <Heart className="w-12 h-12 text-slate-400 mx-auto mb-4" />
            <h2 className="text-xl font-semibold text-slate-900 dark:text-white mb-2">
              No tienes favoritos aún
            </h2>
            <p className="text-slate-600 dark:text-slate-400 mb-6">
              Explora propiedades y agrega tus favoritas para verlas aquí
            </p>
            <Button onClick={() => navigate("/dashboard")}>
              Explorar Propiedades
            </Button>
          </Card>
        )}
      </div>
    </div>
  );
}
