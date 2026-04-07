import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import { useParams } from "wouter";
import { useState } from "react";
import { Heart, MapPin, DollarSign, Send } from "lucide-react";
import { toast } from "sonner";

const MOCK_PROPERTY = {
  id: 1,
  title: "Apartamento Moderno en el Centro",
  location: "Madrid, España",
  price: 250000,
  operationType: "venta",
  bedrooms: 2,
  bathrooms: 1,
  areaM2: 75,
  description: "Increíble apartamento reformado con vistas a la ciudad. Cuenta con acabados de alta calidad, cocina equipada y mucha luz natural.",
  imageUrl: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&q=80&w=800",
  userId: "user123"
};

const MOCK_COMMENTS = [
  { id: 1, comment: "Excelente ubicación!", createdAt: new Date().toISOString() },
  { id: 2, comment: "¿Sigue disponible?", createdAt: new Date().toISOString() }
];

export default function PropertyDetail() {
  const { isAuthenticated } = useAuth();
  const params = useParams();
  const propertyId = parseInt(params?.id || "1");
  const property = MOCK_PROPERTY;
  const comments = MOCK_COMMENTS;

  const [commentText, setCommentText] = useState("");
  const [messageText, setMessageText] = useState("");
  const [isFavorite, setIsFavorite] = useState(false);

  const handleAddComment = () => {
    if (!commentText.trim()) {
      toast.error("Por favor escribe un comentario");
      return;
    }
    toast.success("Comentario agregado (Simulado)");
    setCommentText("");
  };

  const handleSendMessage = () => {
    if (!messageText.trim()) {
      toast.error("Por favor escribe un mensaje");
      return;
    }
    toast.success("Mensaje enviado (Simulado)");
    setMessageText("");
  };

  const handleAddFavorite = () => {
    setIsFavorite(!isFavorite);
    toast.success(isFavorite ? "Eliminado de favoritos" : "Agregado a favoritos");
  };

  return (
    <div className="min-h-screen bg-slate-50 py-8">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-8">
            <div className="w-full h-96 bg-slate-200 rounded-lg overflow-hidden">
              <img
                src={property.imageUrl}
                alt={property.title}
                className="w-full h-full object-cover"
              />
            </div>

            <Card>
              <CardHeader>
                <div className="flex justify-between items-start">
                  <div>
                    <CardTitle className="text-3xl">{property.title}</CardTitle>
                    <CardDescription className="flex items-center gap-1 mt-2">
                      <MapPin className="w-4 h-4" />
                      {property.location}
                    </CardDescription>
                  </div>
                  <Button
                    variant="outline"
                    size="icon"
                    onClick={handleAddFavorite}
                  >
                    <Heart className={`w-5 h-5 ${isFavorite ? "fill-red-500 text-red-500" : ""}`} />
                  </Button>
                </div>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="flex items-center gap-2">
                  <DollarSign className="w-6 h-6 text-green-600" />
                  <span className="text-3xl font-bold text-slate-900">
                    {property.price?.toLocaleString()}
                  </span>
                  <span className="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded-full capitalize">
                    {property.operationType}
                  </span>
                </div>

                <div className="grid grid-cols-3 gap-4">
                  <div className="text-center p-4 bg-slate-100 rounded-lg">
                    <p className="text-2xl font-bold">{property.bedrooms}</p>
                    <p className="text-sm text-slate-600">Habitaciones</p>
                  </div>
                  <div className="text-center p-4 bg-slate-100 rounded-lg">
                    <p className="text-2xl font-bold">{property.bathrooms}</p>
                    <p className="text-sm text-slate-600">Baños</p>
                  </div>
                  <div className="text-center p-4 bg-slate-100 rounded-lg">
                    <p className="text-2xl font-bold">{property.areaM2}</p>
                    <p className="text-sm text-slate-600">m²</p>
                  </div>
                </div>

                <div>
                  <h3 className="text-lg font-semibold mb-2">Descripción</h3>
                  <p className="text-slate-600">{property.description}</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Comentarios</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Textarea
                    placeholder="Escribe un comentario..."
                    value={commentText}
                    onChange={(e) => setCommentText(e.target.value)}
                    rows={3}
                  />
                  <Button onClick={handleAddComment}>Comentar</Button>
                </div>

                <div className="space-y-4">
                  {comments.map((comment) => (
                    <div key={comment.id} className="p-4 bg-slate-50 rounded-lg">
                      <p className="text-slate-900">{comment.comment}</p>
                      <p className="text-xs text-slate-500 mt-2">
                        {new Date(comment.createdAt).toLocaleDateString()}
                      </p>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="lg:col-span-1">
            <Card className="sticky top-4">
              <CardHeader>
                <CardTitle>Contactar al vendedor</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <Textarea
                  placeholder="Escribe tu mensaje..."
                  value={messageText}
                  onChange={(e) => setMessageText(e.target.value)}
                  rows={4}
                />
                <Button
                  className="w-full gap-2"
                  onClick={handleSendMessage}
                >
                  <Send className="w-4 h-4" />
                  Enviar Mensaje
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
