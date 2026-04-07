import { useAuth } from "@/_core/hooks/useAuth";
import { useLocation, useParams } from "wouter";
import { useEffect, useState } from "react";
import { trpc } from "@/lib/trpc";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Loader2, MapPin, MessageCircle, Star } from "lucide-react";
import { toast } from "sonner";

export default function UserProfile() {
  const { id } = useParams();
  const { user, isAuthenticated } = useAuth();
  const [, navigate] = useLocation();

  const userId = parseInt(id as string);

  const { data: profile, isLoading } = trpc.user.getProfile.useQuery({ userId });
  const { data: publications } = trpc.property.list.useQuery({});
  const { data: reviews } = trpc.user.getReviews.useQuery({ userId });

  const sendMessage = trpc.message.send.useMutation();

  const handleContactUser = () => {
    if (!isAuthenticated) {
      toast.error("Debes iniciar sesión");
      return;
    }
    if (!profile?.id) return;

    sendMessage.mutate({
      receiverId: profile.id,
      contenido: `Hola ${profile.name}, me gustaría contactarte`,
    });
    toast.success("Mensaje enviado");
  };

  const userPublications = publications?.filter((p: any) => p.userId === userId) || [];

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
      </div>
    );
  }

  if (!profile) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-slate-600 dark:text-slate-400 mb-4">Usuario no encontrado</p>
          <Button onClick={() => navigate("/dashboard")}>Volver al Dashboard</Button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Header */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <Button variant="ghost" onClick={() => navigate("/dashboard")}>
            ← Volver
          </Button>
          <h1 className="text-2xl font-bold text-slate-900 dark:text-white">VEXTO</h1>
          <div className="w-20" />
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 py-8">
        {/* Profile Header */}
        <Card className="p-8 mb-8">
          <div className="flex flex-col md:flex-row gap-8 items-start">
            {/* Avatar */}
            <div className="w-32 h-32 bg-blue-600 rounded-full flex items-center justify-center text-white text-5xl font-bold flex-shrink-0">
              {profile.name?.charAt(0) || "U"}
            </div>

            {/* Info */}
            <div className="flex-1">
              <div className="flex items-center gap-3 mb-2">
                <h1 className="text-3xl font-bold text-slate-900 dark:text-white">
                  {profile.name} {profile.apellido}
                </h1>
                {profile.verified && (
                  <span className="bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-3 py-1 rounded-full text-sm font-semibold">
                    ✓ Verificado
                  </span>
                )}
              </div>

              {profile.userType === "compania" && (
                <p className="text-lg text-blue-600 dark:text-blue-400 font-semibold mb-2">
                  🏢 {profile.companyName || "Compañía"}
                </p>
              )}

              {profile.ubicacion && (
                <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400 mb-2">
                  <MapPin className="w-4 h-4" />
                  <span>{profile.ubicacion}</span>
                </div>
              )}

              {/* Rating */}
              <div className="flex items-center gap-4 mb-4">
                <div className="flex items-center gap-1">
                  {[...Array(5)].map((_, i) => (
                    <Star
                      key={i}
                      className={`w-5 h-5 ${
                        i < Math.round(profile.rating || 0)
                          ? "fill-yellow-400 text-yellow-400"
                          : "text-slate-300"
                      }`}
                    />
                  ))}
                </div>
                <span className="font-semibold text-slate-900 dark:text-white">
                  {profile.rating?.toFixed(1) || "0.0"}
                </span>
                <span className="text-slate-600 dark:text-slate-400">
                  ({profile.totalReviews || 0} reseñas)
                </span>
              </div>

              {profile.bio && (
                <p className="text-slate-600 dark:text-slate-400 mb-4">
                  {profile.bio}
                </p>
              )}

              {/* Actions */}
              <div className="flex gap-3">
                <Button
                  className="bg-blue-600 hover:bg-blue-700"
                  onClick={handleContactUser}
                  disabled={sendMessage.isPending}
                >
                  <MessageCircle className="w-4 h-4 mr-2" />
                  Contactar
                </Button>
                {profile.companyWebsite && (
                  <Button variant="outline" asChild>
                    <a href={profile.companyWebsite} target="_blank" rel="noopener noreferrer">
                      Sitio Web
                    </a>
                  </Button>
                )}
              </div>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 gap-4 md:gap-8">
              <div className="text-center">
                <p className="text-3xl font-bold text-slate-900 dark:text-white">
                  {profile.publishedCount || 0}
                </p>
                <p className="text-sm text-slate-600 dark:text-slate-400">Publicaciones</p>
              </div>
              <div className="text-center">
                <p className="text-3xl font-bold text-slate-900 dark:text-white">
                  {profile.totalReviews || 0}
                </p>
                <p className="text-sm text-slate-600 dark:text-slate-400">Reseñas</p>
              </div>
            </div>
          </div>
        </Card>

        {/* Publications */}
        <div className="mb-8">
          <h2 className="text-2xl font-bold text-slate-900 dark:text-white mb-6">
            Propiedades Publicadas ({userPublications.length})
          </h2>
          {userPublications.length > 0 ? (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {userPublications.map((property: any) => (
                <Card
                  key={property.id}
                  className="overflow-hidden hover:shadow-lg transition cursor-pointer"
                  onClick={() => navigate(`/property/${property.id}`)}
                >
                  <div className="h-40 bg-slate-200 dark:bg-slate-700">
                    {property.imagenUrl && (
                      <img
                        src={property.imagenUrl}
                        alt={property.titulo}
                        className="w-full h-full object-cover"
                      />
                    )}
                  </div>
                  <div className="p-4">
                    <h3 className="font-semibold text-slate-900 dark:text-white truncate mb-2">
                      {property.titulo}
                    </h3>
                    <p className="text-xl font-bold text-blue-600 mb-2">
                      ${property.precio?.toLocaleString()}
                    </p>
                    <div className="flex justify-between text-sm text-slate-600 dark:text-slate-400">
                      <span>{property.tipoOperacion}</span>
                      <span>{property.tipoPropiedad}</span>
                    </div>
                  </div>
                </Card>
              ))}
            </div>
          ) : (
            <Card className="p-8 text-center">
              <p className="text-slate-600 dark:text-slate-400">
                Este usuario aún no ha publicado propiedades
              </p>
            </Card>
          )}
        </div>

        {/* Reviews */}
        {reviews && reviews.length > 0 && (
          <div>
            <h2 className="text-2xl font-bold text-slate-900 dark:text-white mb-6">
              Reseñas ({reviews.length})
            </h2>
            <div className="space-y-4">
              {reviews.map((review: any) => (
                <Card key={review.id} className="p-6">
                  <div className="flex items-center justify-between mb-3">
                    <div className="flex gap-1">
                      {[...Array(5)].map((_, i) => (
                        <Star
                          key={i}
                          className={`w-4 h-4 ${
                            i < review.rating
                              ? "fill-yellow-400 text-yellow-400"
                              : "text-slate-300"
                          }`}
                        />
                      ))}
                    </div>
                    <span className="text-sm text-slate-500 dark:text-slate-400">
                      {new Date(review.createdAt).toLocaleDateString()}
                    </span>
                  </div>
                  {review.comentario && (
                    <p className="text-slate-600 dark:text-slate-400">
                      {review.comentario}
                    </p>
                  )}
                </Card>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
