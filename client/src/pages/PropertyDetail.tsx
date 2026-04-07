import { useAuth } from "@/_core/hooks/useAuth";
import { useLocation, useParams } from "wouter";
import { useEffect, useState } from "react";
import { trpc } from "@/lib/trpc";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Loader2, MapPin, Bed, Bath, Square, Heart, MessageCircle, Flag, ChevronLeft, ChevronRight } from "lucide-react";
import { toast } from "sonner";

export default function PropertyDetail() {
  const { id } = useParams();
  const { user, isAuthenticated } = useAuth();
  const [, navigate] = useLocation();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  const propertyId = parseInt(id as string);

  const { data: property, isLoading } = trpc.property.getById.useQuery({ id: propertyId });
  const { data: isFav } = trpc.favorite.isFavorite.useQuery({ propertyId });
  const { data: seller } = trpc.user.getProfile.useQuery({ userId: property?.userId });
  const { data: reviews } = trpc.user.getReviews.useQuery({ userId: property?.userId || 0 });

  const addFavorite = trpc.favorite.add.useMutation();
  const removeFavorite = trpc.favorite.remove.useMutation();
  const sendMessage = trpc.message.send.useMutation();
  const createAppointment = trpc.appointment.create.useMutation();

  const handleToggleFavorite = () => {
    if (!isAuthenticated) {
      toast.error("Debes iniciar sesión");
      return;
    }
    if (isFav) {
      removeFavorite.mutate({ propertyId });
    } else {
      addFavorite.mutate({ propertyId });
    }
  };

  const handleContactSeller = () => {
    if (!isAuthenticated) {
      toast.error("Debes iniciar sesión");
      return;
    }
    if (!property?.userId) return;

    sendMessage.mutate({
      receiverId: property.userId,
      contenido: `Hola, me interesa esta propiedad: ${property.titulo}`,
      propertyId,
    });
    toast.success("Mensaje enviado");
  };

  const handleScheduleAppointment = () => {
    if (!isAuthenticated) {
      toast.error("Debes iniciar sesión");
      return;
    }
    if (!property?.userId) return;

    createAppointment.mutate({
      propertyId,
      ownerId: property.userId,
    });
    toast.success("Cita solicitada");
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
      </div>
    );
  }

  if (!property) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-slate-600 dark:text-slate-400 mb-4">Propiedad no encontrada</p>
          <Button onClick={() => navigate("/dashboard")}>Volver al Dashboard</Button>
        </div>
      </div>
    );
  }

  const images: string[] = (property.imagenes as string[]) || (property.imagenUrl ? [property.imagenUrl] : []);

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Header */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <Button variant="ghost" onClick={() => navigate("/dashboard")}>
            <ChevronLeft className="w-5 h-5" />
            Volver
          </Button>
          <h1 className="text-2xl font-bold text-slate-900 dark:text-white">VEXTO</h1>
          <div className="w-20" />
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 py-8">
        <div className="grid lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2">
            {/* Image Gallery */}
            <div className="relative bg-slate-200 dark:bg-slate-700 rounded-lg overflow-hidden mb-6 h-96">
              {images.length > 0 ? (
                <>
                  <img
                    src={images[currentImageIndex]}
                    alt={property.titulo}
                    className="w-full h-full object-cover"
                  />
                  {images.length > 1 && (
                    <>
                      <button
                        onClick={() => setCurrentImageIndex((i) => (i - 1 + images.length) % images.length)}
                        className="absolute left-4 top-1/2 -translate-y-1/2 bg-white dark:bg-slate-800 rounded-full p-2 hover:bg-slate-100 dark:hover:bg-slate-700"
                      >
                        <ChevronLeft className="w-5 h-5" />
                      </button>
                      <button
                        onClick={() => setCurrentImageIndex((i) => (i + 1) % images.length)}
                        className="absolute right-4 top-1/2 -translate-y-1/2 bg-white dark:bg-slate-800 rounded-full p-2 hover:bg-slate-100 dark:hover:bg-slate-700"
                      >
                        <ChevronRight className="w-5 h-5" />
                      </button>
                      <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                        {images.map((_: any, idx: number) => (
                          <button
                            key={idx}
                            onClick={() => setCurrentImageIndex(idx)}
                            className={`w-2 h-2 rounded-full transition ${
                              idx === currentImageIndex ? "bg-white" : "bg-white/50"
                            }`}
                          />
                        ))}
                      </div>
                    </>
                  )}
                </>
              ) : (
                <div className="w-full h-full flex items-center justify-center">
                  <span className="text-slate-400">Sin imagen</span>
                </div>
              )}
            </div>

            {/* Property Info */}
            <Card className="p-6 mb-6">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h1 className="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    {property.titulo}
                  </h1>
                  <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400 mb-4">
                    <MapPin className="w-5 h-5" />
                    <span>{property.ubicacion}</span>
                  </div>
                </div>
                <span className="bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-4 py-2 rounded-full font-semibold">
                  {property.tipoOperacion === "venta" ? "VENTA" : "ALQUILER"}
                </span>
              </div>

              <div className="text-4xl font-bold text-blue-600 mb-6">
                ${property.precio?.toLocaleString()}
              </div>

              <div className="grid grid-cols-4 gap-4 mb-6 pb-6 border-b border-slate-200 dark:border-slate-700">
                {property.habitaciones && property.habitaciones > 0 && (
                  <div className="text-center">
                    <Bed className="w-6 h-6 mx-auto mb-2 text-slate-600 dark:text-slate-400" />
                    <p className="font-semibold text-slate-900 dark:text-white">{property.habitaciones}</p>
                    <p className="text-sm text-slate-600 dark:text-slate-400">Habitaciones</p>
                  </div>
                )}
                {property.banos && property.banos > 0 && (
                  <div className="text-center">
                    <Bath className="w-6 h-6 mx-auto mb-2 text-slate-600 dark:text-slate-400" />
                    <p className="font-semibold text-slate-900 dark:text-white">{property.banos}</p>
                    <p className="text-sm text-slate-600 dark:text-slate-400">Baños</p>
                  </div>
                )}
                {property.areaM2 && (
                  <div className="text-center">
                    <Square className="w-6 h-6 mx-auto mb-2 text-slate-600 dark:text-slate-400" />
                    <p className="font-semibold text-slate-900 dark:text-white">{property.areaM2}</p>
                    <p className="text-sm text-slate-600 dark:text-slate-400">m²</p>
                  </div>
                )}
                <div className="text-center">
                  <span className="text-sm text-slate-600 dark:text-slate-400">Tipo</span>
                  <p className="font-semibold text-slate-900 dark:text-white capitalize">{property.tipoPropiedad}</p>
                </div>
              </div>

              <div>
                <h2 className="text-xl font-semibold text-slate-900 dark:text-white mb-3">Descripción</h2>
                <p className="text-slate-600 dark:text-slate-400 leading-relaxed">
                  {property.descripcion || "Sin descripción disponible"}
                </p>
              </div>
            </Card>

            {/* Reviews Section */}
            {reviews && reviews.length > 0 && (
              <Card className="p-6">
                <h2 className="text-xl font-semibold text-slate-900 dark:text-white mb-4">
                  Reseñas del Vendedor
                </h2>
                <div className="space-y-4">
                  {reviews.map((review: any) => (
                    <div key={review.id} className="border-b border-slate-200 dark:border-slate-700 pb-4 last:border-0">
                      <div className="flex items-center justify-between mb-2">
                        <div className="flex gap-1">
                          {[...Array(5)].map((_, i) => (
                            <span
                              key={i}
                              className={`text-lg ${i < review.rating ? "text-yellow-400" : "text-slate-300"}`}
                            >
                              ★
                            </span>
                          ))}
                        </div>
                        <span className="text-sm text-slate-500 dark:text-slate-400">
                          {new Date(review.createdAt).toLocaleDateString()}
                        </span>
                      </div>
                      {review.comentario && (
                        <p className="text-slate-600 dark:text-slate-400">{review.comentario}</p>
                      )}
                    </div>
                  ))}
                </div>
              </Card>
            )}
          </div>

          {/* Sidebar */}
          <div>
            {/* Seller Card */}
            {seller && (
              <Card className="p-6 mb-6">
                <div className="text-center mb-6">
                  <div className="w-16 h-16 bg-blue-600 rounded-full mx-auto mb-3 flex items-center justify-center text-white text-2xl font-bold">
                    {seller.name?.charAt(0) || "U"}
                  </div>
                  <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
                    {seller.name} {seller.apellido}
                  </h3>
                  {seller.userType === "compania" && (
                    <p className="text-sm text-blue-600 dark:text-blue-400 font-semibold">
                      🏢 {seller.companyName || "Compañía"}
                    </p>
                  )}
                  <div className="flex items-center justify-center gap-1 mt-2">
                    <span className="text-yellow-400">★</span>
                    <span className="font-semibold text-slate-900 dark:text-white">
                      {seller.rating?.toFixed(1) || "0.0"}
                    </span>
                    <span className="text-sm text-slate-600 dark:text-slate-400">
                      ({seller.totalReviews || 0} reseñas)
                    </span>
                  </div>
                </div>

                {seller.bio && (
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-4 text-center">
                    {seller.bio}
                  </p>
                )}

                <div className="space-y-3">
                  <Button
                    className="w-full bg-blue-600 hover:bg-blue-700"
                    onClick={handleContactSeller}
                    disabled={sendMessage.isPending}
                  >
                    <MessageCircle className="w-4 h-4 mr-2" />
                    Contactar
                  </Button>
                  <Button
                    variant="outline"
                    className="w-full"
                    onClick={handleScheduleAppointment}
                    disabled={createAppointment.isPending}
                  >
                    📅 Agendar Cita
                  </Button>
                  <Button
                    variant="ghost"
                    className="w-full"
                    onClick={handleToggleFavorite}
                  >
                    <Heart
                      className={`w-4 h-4 mr-2 ${isFav ? "fill-red-500 text-red-500" : ""}`}
                    />
                    {isFav ? "Quitar de Favoritos" : "Agregar a Favoritos"}
                  </Button>
                  <Button
                    variant="ghost"
                    className="w-full text-red-600 hover:text-red-700"
                  >
                    <Flag className="w-4 h-4 mr-2" />
                    Reportar
                  </Button>
                </div>
              </Card>
            )}

            {/* Stats */}
            <Card className="p-6">
              <h3 className="font-semibold text-slate-900 dark:text-white mb-4">Información</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-slate-600 dark:text-slate-400">Publicado:</span>
                  <span className="font-semibold text-slate-900 dark:text-white">
                    {new Date(property.createdAt).toLocaleDateString()}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-600 dark:text-slate-400">Vistas:</span>
                  <span className="font-semibold text-slate-900 dark:text-white">
                    {property.views || 0}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-600 dark:text-slate-400">Favoritos:</span>
                  <span className="font-semibold text-slate-900 dark:text-white">
                    {property.favoriteCount || 0}
                  </span>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
