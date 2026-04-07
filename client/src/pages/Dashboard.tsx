import { useAuth } from "@/_core/hooks/useAuth";
import { useLocation } from "wouter";
import { useEffect, useState } from "react";
import { trpc } from "@/lib/trpc";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card } from "@/components/ui/card";
import { Loader2, MapPin, Bed, Bath, Heart, Search } from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function Dashboard() {
  const { user, isAuthenticated } = useAuth();
  const [, navigate] = useLocation();
  const [filters, setFilters] = useState({
    tipoOperacion: "",
    tipoPropiedad: "",
    precioMin: 0,
    precioMax: 999999999,
    ubicacion: "",
    search: "",
  });

  useEffect(() => {
    if (!isAuthenticated) {
      navigate("/");
    }
  }, [isAuthenticated, navigate]);

  const { data: properties, isLoading } = trpc.property.list.useQuery(filters);
  const { data: favorites } = trpc.favorite.list.useQuery();
  const addFavorite = trpc.favorite.add.useMutation();
  const removeFavorite = trpc.favorite.remove.useMutation();

  const isFavorite = (propertyId: number) => {
    return favorites?.some((fav: any) => fav.id === propertyId);
  };

  const handleToggleFavorite = (propertyId: number) => {
    if (isFavorite(propertyId)) {
      removeFavorite.mutate({ propertyId });
    } else {
      addFavorite.mutate({ propertyId });
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Header */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-slate-900 dark:text-white">VEXTO</h1>
          <div className="flex items-center gap-4">
            <Button variant="outline" onClick={() => navigate("/create-property")}>
              + Publicar Propiedad
            </Button>
            <Button variant="outline" onClick={() => navigate("/favorites")}>
              ❤️ Favoritos
            </Button>
            <Button variant="outline" onClick={() => navigate("/messages")}>
              💬 Mensajes
            </Button>
            <Button variant="outline" onClick={() => navigate("/settings")}>
              ⚙️ Perfil
            </Button>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 py-8">
        {/* Filters */}
        <Card className="p-6 mb-8">
          <h2 className="text-lg font-semibold text-slate-900 dark:text-white mb-4">Filtrar Propiedades</h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Operación
              </label>
              <Select value={filters.tipoOperacion} onValueChange={(value) => setFilters({ ...filters, tipoOperacion: value })}>
                <SelectTrigger>
                  <SelectValue placeholder="Seleccionar" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Todas</SelectItem>
                  <SelectItem value="venta">Venta</SelectItem>
                  <SelectItem value="alquiler">Alquiler</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Tipo de Propiedad
              </label>
              <Select value={filters.tipoPropiedad} onValueChange={(value) => setFilters({ ...filters, tipoPropiedad: value })}>
                <SelectTrigger>
                  <SelectValue placeholder="Seleccionar" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Todos</SelectItem>
                  <SelectItem value="casa">Casa</SelectItem>
                  <SelectItem value="apartamento">Apartamento</SelectItem>
                  <SelectItem value="local">Local Comercial</SelectItem>
                  <SelectItem value="terreno">Terreno</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Ubicación
              </label>
              <Input
                placeholder="Ciudad o zona..."
                value={filters.ubicacion}
                onChange={(e) => setFilters({ ...filters, ubicacion: e.target.value })}
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Búsqueda
              </label>
              <Input
                placeholder="Buscar..."
                value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
              />
            </div>
          </div>
        </Card>

        {/* Properties Grid */}
        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
          </div>
        ) : properties && properties.length > 0 ? (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {properties.map((property: any) => (
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
                      handleToggleFavorite(property.id);
                    }}
                    className="absolute top-3 left-3 bg-white dark:bg-slate-800 rounded-full p-2 hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    <Heart
                      className={`w-5 h-5 ${isFavorite(property.id) ? "fill-red-500 text-red-500" : "text-slate-400"}`}
                    />
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
          <div className="text-center py-20">
            <Search className="w-12 h-12 text-slate-400 mx-auto mb-4" />
            <p className="text-slate-600 dark:text-slate-400">No se encontraron propiedades</p>
          </div>
        )}
      </div>
    </div>
  );
}
