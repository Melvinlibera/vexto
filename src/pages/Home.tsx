import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Home as HomeIcon, MapPin, DollarSign } from "lucide-react";

const MOCK_PROPERTIES = [
  {
    id: "1",
    title: "Apartamento Moderno en el Centro",
    location: "Madrid, España",
    price: 250000,
    operationType: "venta",
    bedrooms: 2,
    bathrooms: 1,
    areaM2: 75,
    description: "Increíble apartamento reformado con vistas a la ciudad.",
    imageUrl: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&q=80&w=800"
  },
  {
    id: "2",
    title: "Chalet de Lujo con Piscina",
    location: "Marbella, España",
    price: 1200000,
    operationType: "venta",
    bedrooms: 5,
    bathrooms: 4,
    areaM2: 350,
    description: "Exclusiva villa con jardín privado y vistas al mar.",
    imageUrl: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=800"
  },
  {
    id: "3",
    title: "Estudio acogedor cerca del mar",
    location: "Barcelona, España",
    price: 1200,
    operationType: "alquiler",
    bedrooms: 1,
    bathrooms: 1,
    areaM2: 45,
    description: "Perfecto para parejas o nómadas digitales.",
    imageUrl: "https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&q=80&w=800"
  }
];

export default function Home() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white">
      {/* Header */}
      <header className="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div className="container mx-auto px-4 py-4 flex justify-between items-center">
          <div className="flex items-center gap-2">
            <HomeIcon className="w-8 h-8 text-blue-600" />
            <h1 className="text-2xl font-bold text-slate-900">VEXTO</h1>
          </div>
          <nav className="flex items-center gap-4">
            <Button size="sm" variant="outline">
              Explorar
            </Button>
            <Button size="sm">
              Iniciar Sesión
            </Button>
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section className="container mx-auto px-4 py-16">
        <div className="text-center mb-12">
          <h2 className="text-4xl font-bold text-slate-900 mb-4">
            Encuentra tu propiedad ideal
          </h2>
          <p className="text-xl text-slate-600 mb-8">
            Explora miles de propiedades en venta y alquiler en nuestra plataforma modernizada.
          </p>
        </div>
      </section>

      {/* Properties Grid */}
      <section className="container mx-auto px-4 py-12">
        <h3 className="text-2xl font-bold text-slate-900 mb-8">Propiedades Destacadas</h3>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {MOCK_PROPERTIES.map((property) => (
            <Card key={property.id} className="hover:shadow-lg transition-shadow overflow-hidden">
              <div className="w-full h-48 bg-slate-200 overflow-hidden">
                <img
                  src={property.imageUrl}
                  alt={property.title}
                  className="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                />
              </div>
              <CardHeader>
                <CardTitle className="line-clamp-2">{property.title}</CardTitle>
                <CardDescription className="flex items-center gap-1">
                  <MapPin className="w-4 h-4" />
                  {property.location}
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <DollarSign className="w-5 h-5 text-green-600" />
                    <span className="text-2xl font-bold text-slate-900">
                      {property.price?.toLocaleString()}
                    </span>
                  </div>
                  <span className="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded-full capitalize">
                    {property.operationType}
                  </span>
                </div>

                <div className="flex gap-4 text-sm text-slate-600">
                  <span>🛏️ {property.bedrooms} hab.</span>
                  <span>🚿 {property.bathrooms} baños</span>
                  <span>📐 {property.areaM2} m²</span>
                </div>

                <p className="text-sm text-slate-600 line-clamp-2">
                  {property.description}
                </p>

                <Button className="w-full">
                  Ver Detalles
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-slate-900 text-white mt-16 py-8">
        <div className="container mx-auto px-4 text-center">
          <p>&copy; 2026 VEXTO - Plataforma Inmobiliaria. Adaptado a estándares modernos.</p>
        </div>
      </footer>
    </div>
  );
}
