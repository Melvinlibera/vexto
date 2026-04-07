<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/helpers.php';
require dirname(__DIR__) . '/config/db.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'venta';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT p.*, u.nombre, u.apellido FROM properties p JOIN users u ON p.user_id = u.id WHERE p.tipo_operacion = ?";
$params = [$filter];

if (!empty($search)) {
    $query .= " AND (p.titulo LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEXTO | Explorar</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #000000;
            --border: #222222;
            --accent: #ffffff;
            --text-primary: #ffffff;
            --text-secondary: #71767b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar */
        .sidebar {
            width: 275px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid var(--border);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sidebar-logo { font-size: 1.8rem; font-weight: 900; margin-bottom: 30px; padding-left: 15px; }
        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px;
            font-size: 1.2rem;
            border-radius: 30px;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 5px;
            text-decoration: none;
            color: #fff;
        }
        .nav-item:hover { background: #181818; }
        .nav-item i { margin-right: 20px; width: 25px; text-align: center; }
        .nav-item.active { font-weight: 700; }
        /* Main Content */
        .main-content {
            margin-left: 275px;
            flex: 1;
            max-width: 600px;
            border-right: 1px solid var(--border);
            min-height: 100vh;
        }
        .header-sticky {
            position: sticky;
            top: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 15px;
            z-index: 100;
        }
        .search-bar {
            background: #202327;
            padding: 10px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        .search-bar input { background: transparent; border: none; color: #fff; outline: none; width: 100%; }
        .tabs { display: flex; border-bottom: 1px solid var(--border); }
        .tab {
            flex: 1;
            text-align: center;
            padding: 15px;
            cursor: pointer;
            color: var(--text-secondary);
            font-weight: 600;
            transition: background 0.2s;
            text-decoration: none;
        }
        .tab:hover { background: #181818; }
        .tab.active { color: #fff; border-bottom: 4px solid #fff; }
        /* Grid */
        .grid { padding: 15px; display: grid; grid-template-columns: 1fr; gap: 20px; }
        .prop-card {
            background: #0a0a0a;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            opacity: 0;
            transform: translateY(20px);
        }
        .prop-card img { width: 100%; height: 250px; object-fit: cover; }
        .prop-info { padding: 15px; }
        .prop-info h3 { margin-bottom: 10px; font-size: 1.2rem; }
        .prop-info p { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 15px; }
        .prop-footer { display: flex; justify-content: space-between; align-items: center; }
        .price { font-weight: 800; font-size: 1.3rem; }
        .btn-view {
            background: #fff;
            color: #000;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo">VEXTO</div>
            <a href="<?php echo BASE_URL; ?>views/dashboard.php" class="nav-item"><i class="fas fa-home"></i> Inicio</a>
            <a href="<?php echo BASE_URL; ?>views/properties.php" class="nav-item active"><i class="fas fa-search"></i> Explorar</a>
            <a href="<?php echo BASE_URL; ?>views/publish.php" class="nav-item"><i class="fas fa-plus-circle"></i> Publicar</a>
            <a href="<?php echo BASE_URL; ?>views/settings.php" class="nav-item"><i class="fas fa-user"></i> Perfil</a>
        </div>
        <div class="nav-item" onclick="showLogoutConfirm('<?php echo BASE_URL; ?>views/logout.php')">
            <i class="fas fa-sign-out-alt"></i> Salir
        </div>
    </div>

    <div class="main-content">
        <div class="header-sticky">
            <h2>Explorar</h2>
            <form action="<?php echo BASE_URL; ?>views/properties.php" method="GET" class="search-bar">
                <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                <i class="fas fa-search" style="color: var(--text-secondary);"></i>
                <input type="text" name="search" placeholder="Buscar propiedades..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>
        <div class="tabs">
            <a href="?filter=venta&search=<?php echo urlencode($search); ?>" class="tab <?php echo $filter == 'venta' ? 'active' : ''; ?>">Venta</a>
            <a href="?filter=alquiler&search=<?php echo urlencode($search); ?>" class="tab <?php echo $filter == 'alquiler' ? 'active' : ''; ?>">Alquiler</a>
        </div>

        <div class="grid">
            <?php if (empty($properties)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                    No se encontraron propiedades que coincidan con tu búsqueda.
                </div>
            <?php else: ?>
                <?php foreach ($properties as $prop): ?>
                    <?php
                        $imageSrc = getPropertyImageUrl($prop['imagen_url']);
                        if (!empty($prop['imagen'])) {
                            $imageSrc = 'data:' . htmlspecialchars($prop['imagen_tipo']) . ';base64,' . base64_encode($prop['imagen']);
                        }
                    ?>
                    <div class="prop-card">
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Propiedad">
                        <div class="prop-info">
                            <h3><?php echo htmlspecialchars($prop['titulo']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($prop['descripcion'], 0, 100)) . '...'; ?></p>
                            <div class="prop-footer">
                                <span class="price">$<?php echo number_format($prop['precio'], 2); ?></span>
                                <a href="#" class="btn-view">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        window.onload = () => {
            anime({
                targets: '.prop-card',
                opacity: [0, 1],
                translateY: [20, 0],
                delay: anime.stagger(150),
                duration: 800,
                easing: 'easeOutQuart'
            });
        };
    </script>
</body>
</html>
