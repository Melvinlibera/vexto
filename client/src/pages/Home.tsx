import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { useLocation as useWouterLocation } from "wouter";
import { getLoginUrl } from "@/const";
import { Building2, Search, MapPin, DollarSign, Users, TrendingUp } from "lucide-react";

export default function Home() {
  const { user, isAuthenticated } = useAuth();
  const [, navigate] = useWouterLocation();

  const handleGetStarted = () => {
    if (isAuthenticated) {
      navigate("/dashboard");
    } else {
      window.location.href = getLoginUrl();
    }
  };

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Header */}
      <header className="border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50 bg-white dark:bg-slate-950">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Building2 className="w-8 h-8 text-blue-600" />
            <span className="text-2xl font-bold text-slate-900 dark:text-white">VEXTO</span>
          </div>
          <nav className="hidden md:flex items-center gap-8">
            <a href="#features" className="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
              Características
            </a>
            <a href="#how-it-works" className="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
              Cómo Funciona
            </a>
          </nav>
          <div className="flex items-center gap-4">
            {isAuthenticated ? (
              <>
                <Button variant="outline" onClick={() => navigate("/dashboard")}>
                  Dashboard
                </Button>
                <Button onClick={() => navigate("/settings")}>
                  {user?.name || "Mi Perfil"}
                </Button>
              </>
            ) : (
              <>
              <Button variant="outline" onClick={() => { window.location.href = getLoginUrl(); }}>
                Iniciar Sesión
              </Button>
              <Button onClick={() => { window.location.href = getLoginUrl(); }}>
                Registrarse
              </Button>
              </>
            )}
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="max-w-7xl mx-auto px-4 py-20 md:py-32">
        <div className="grid md:grid-cols-2 gap-12 items-center">
          <div>
            <h1 className="text-5xl md:text-6xl font-bold text-slate-900 dark:text-white mb-6">
              Encuentra tu Propiedad Ideal
            </h1>
            <p className="text-xl text-slate-600 dark:text-slate-400 mb-8">
              La plataforma inmobiliaria más moderna. Compra, vende o alquila propiedades con confianza y transparencia.
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <Button size="lg" onClick={handleGetStarted} className="bg-blue-600 hover:bg-blue-700">
                Comenzar Ahora
              </Button>
              <Button size="lg" variant="outline">
                Ver Propiedades
              </Button>
            </div>
          </div>
          <div className="bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900 dark:to-blue-800 rounded-2xl p-12 h-96 flex items-center justify-center">
            <Building2 className="w-32 h-32 text-blue-600 dark:text-blue-400 opacity-50" />
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="bg-slate-50 dark:bg-slate-900 py-20">
        <div className="max-w-7xl mx-auto px-4">
          <h2 className="text-4xl font-bold text-center text-slate-900 dark:text-white mb-16">
            Características Principales
          </h2>
          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                icon: Search,
                title: "Búsqueda Avanzada",
                description: "Filtra por tipo, precio, ubicación y más características para encontrar exactamente lo que buscas."
              },
              {
                icon: MapPin,
                title: "Mapas Interactivos",
                description: "Visualiza propiedades en mapas interactivos con información de ubicación y vecindario."
              },
              {
                icon: Users,
                title: "Perfiles Verificados",
                description: "Conecta con vendedores y compradores verificados con calificaciones y reseñas."
              },
              {
                icon: DollarSign,
                title: "Precios Transparentes",
                description: "Sin comisiones ocultas. Todos los precios son claros y verificables."
              },
              {
                icon: TrendingUp,
                title: "Estadísticas",
                description: "Accede a análisis de mercado y tendencias de precios en tu área."
              },
              {
                icon: Building2,
                title: "Gestión Completa",
                description: "Publica, edita y gestiona tus propiedades desde un panel intuitivo."
              }
            ].map((feature, idx) => (
              <div key={idx} className="bg-white dark:bg-slate-800 rounded-xl p-8 shadow-sm hover:shadow-lg transition">
                <feature.icon className="w-12 h-12 text-blue-600 mb-4" />
                <h3 className="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                  {feature.title}
                </h3>
                <p className="text-slate-600 dark:text-slate-400">
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section id="how-it-works" className="max-w-7xl mx-auto px-4 py-20">
        <h2 className="text-4xl font-bold text-center text-slate-900 dark:text-white mb-16">
          Cómo Funciona
        </h2>
        <div className="grid md:grid-cols-4 gap-8">
          {[
            { step: "1", title: "Regístrate", description: "Crea tu cuenta en segundos" },
            { step: "2", title: "Busca", description: "Explora miles de propiedades" },
            { step: "3", title: "Contacta", description: "Comunícate con vendedores" },
            { step: "4", title: "Cierra", description: "Completa tu transacción" }
          ].map((item, idx) => (
            <div key={idx} className="text-center">
              <div className="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                {item.step}
              </div>
              <h3 className="text-xl font-semibold text-slate-900 dark:text-white mb-2">
                {item.title}
              </h3>
              <p className="text-slate-600 dark:text-slate-400">
                {item.description}
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* CTA Section */}
      <section className="bg-blue-600 dark:bg-blue-700 py-16">
        <div className="max-w-4xl mx-auto px-4 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            ¿Listo para Comenzar?
          </h2>
          <p className="text-xl text-blue-100 mb-8">
            Únete a miles de usuarios que ya están usando VEXTO para comprar, vender y alquilar propiedades.
          </p>
          <Button 
            size="lg" 
            className="bg-white text-blue-600 hover:bg-slate-100"
            onClick={handleGetStarted}
          >
            Comenzar Ahora
          </Button>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-slate-900 dark:bg-black text-white py-12">
        <div className="max-w-7xl mx-auto px-4">
          <div className="grid md:grid-cols-4 gap-8 mb-8">
            <div>
              <div className="flex items-center gap-2 mb-4">
                <Building2 className="w-6 h-6" />
                <span className="text-xl font-bold">VEXTO</span>
              </div>
              <p className="text-slate-400">
                La plataforma inmobiliaria más confiable de Latinoamérica.
              </p>
            </div>
            <div>
              <h4 className="font-semibold mb-4">Producto</h4>
              <ul className="space-y-2 text-slate-400">
                <li><a href="#" className="hover:text-white">Características</a></li>
                <li><a href="#" className="hover:text-white">Precios</a></li>
                <li><a href="#" className="hover:text-white">Blog</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-4">Empresa</h4>
              <ul className="space-y-2 text-slate-400">
                <li><a href="#" className="hover:text-white">Acerca de</a></li>
                <li><a href="#" className="hover:text-white">Contacto</a></li>
                <li><a href="#" className="hover:text-white">Careers</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-4">Legal</h4>
              <ul className="space-y-2 text-slate-400">
                <li><a href="#" className="hover:text-white">Privacidad</a></li>
                <li><a href="#" className="hover:text-white">Términos</a></li>
                <li><a href="#" className="hover:text-white">Cookies</a></li>
              </ul>
            </div>
          </div>
          <div className="border-t border-slate-800 pt-8 text-center text-slate-400">
            <p>&copy; 2026 VEXTO. Todos los derechos reservados.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
