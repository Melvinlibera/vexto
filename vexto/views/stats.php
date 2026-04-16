<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'compania') {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/db.php';
include dirname(__DIR__) . '/includes/header.php';

$user_id = $_SESSION['user_id'];

// 1. Estadísticas generales del usuario
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_props, 
        SUM(vistas) as total_vistas,
        (SELECT COUNT(*) FROM favorites f JOIN properties p2 ON f.property_id = p2.id WHERE p2.user_id = ?) as total_favoritos
    FROM properties 
    WHERE user_id = ?
");
$stmt->execute([$user_id, $user_id]);
$stats = $stmt->fetch();

// 2. Citas (Personas que han escrito/interesado)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_citas 
    FROM appointments a 
    JOIN properties p ON a.property_id = p.id 
    WHERE p.user_id = ?
");
$stmt->execute([$user_id]);
$citas = $stmt->fetch();

// 3. Propiedades con más interés (Vistas + Favoritos + Citas)
$stmt = $pdo->prepare("
    SELECT 
        p.titulo, 
        p.vistas, 
        p.precio,
        (SELECT COUNT(*) FROM favorites f WHERE f.property_id = p.id) as favs,
        (SELECT COUNT(*) FROM appointments a WHERE a.property_id = p.id) as appointments
    FROM properties p
    WHERE p.user_id = ? 
    ORDER BY (p.vistas + (SELECT COUNT(*) FROM favorites f WHERE f.property_id = p.id)*5 + (SELECT COUNT(*) FROM appointments a WHERE a.property_id = p.id)*10) DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$top_props = $stmt->fetchAll();

// 4. Datos para el gráfico general de interés (Toda la plataforma)
// Agrupamos por tipo de propiedad para ver qué interesa más en general
$stmt = $pdo->query("
    SELECT 
        tipo_propiedad, 
        SUM(vistas) as vistas_totales,
        COUNT(*) as cantidad
    FROM properties 
    GROUP BY tipo_propiedad 
    ORDER BY vistas_totales DESC
");
$general_interest = $stmt->fetchAll();

// Preparar datos para JS
$labels_top = [];
$vistas_top = [];
$interes_top = [];
foreach ($top_props as $p) {
    $labels_top[] = strlen($p['titulo']) > 15 ? substr($p['titulo'], 0, 12) . '...' : $p['titulo'];
    $vistas_top[] = (int)$p['vistas'];
    $interes_top[] = (int)$p['vistas'] + ($p['favs'] * 5) + ($p['appointments'] * 10);
}

$labels_gen = [];
$data_gen = [];
foreach ($general_interest as $gi) {
    $labels_gen[] = ucfirst($gi['tipo_propiedad']);
    $data_gen[] = (int)$gi['vistas_totales'];
}
?>

<!-- Cargar Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="main-container" style="flex-direction: column; max-width: 1200px; margin: 40px auto; gap: 40px;">
    <div style="animation: slideInLeft 0.6s ease;">
        <h1 style="margin-bottom: 10px; font-size: 2.5rem; font-weight: 900;">Panel de Estadísticas</h1>
        <p style="color: #777; font-size: 1.1rem;">Análisis detallado de tus publicaciones y tendencias del mercado.</p>
    </div>
    
    <!-- Tarjetas de Resumen -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px;">
        <div class="filter-card" style="text-align: center; padding: 30px; border-top: 4px solid var(--accent-color);">
            <i class="fas fa-home" style="font-size: 1.5rem; margin-bottom: 15px; color: var(--accent-color);"></i>
            <h3 style="font-size: 0.8rem; color: #777; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px;">Propiedades</h3>
            <div style="font-size: 2.5rem; font-weight: 900;"><?php echo $stats['total_props']; ?></div>
        </div>
        <div class="filter-card" style="text-align: center; padding: 30px; border-top: 4px solid #3498db;">
            <i class="fas fa-eye" style="font-size: 1.5rem; margin-bottom: 15px; color: #3498db;"></i>
            <h3 style="font-size: 0.8rem; color: #777; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px;">Vistas Totales</h3>
            <div style="font-size: 2.5rem; font-weight: 900;"><?php echo number_format($stats['total_vistas'] ?? 0); ?></div>
        </div>
        <div class="filter-card" style="text-align: center; padding: 30px; border-top: 4px solid #e74c3c;">
            <i class="fas fa-heart" style="font-size: 1.5rem; margin-bottom: 15px; color: #e74c3c;"></i>
            <h3 style="font-size: 0.8rem; color: #777; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px;">Favoritos</h3>
            <div style="font-size: 2.5rem; font-weight: 900;"><?php echo $stats['total_favoritos']; ?></div>
        </div>
        <div class="filter-card" style="text-align: center; padding: 30px; border-top: 4px solid #2ecc71;">
            <i class="fas fa-envelope" style="font-size: 1.5rem; margin-bottom: 15px; color: #2ecc71;"></i>
            <h3 style="font-size: 0.8rem; color: #777; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px;">Interesados</h3>
            <div style="font-size: 2.5rem; font-weight: 900;"><?php echo $citas['total_citas']; ?></div>
        </div>
    </div>

    <!-- Gráficos Principales -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 30px;">
        <!-- Gráfico de tus publicaciones -->
        <div class="filter-card" style="padding: 30px;">
            <h2 style="margin-bottom: 25px; font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-chart-bar"></i> Rendimiento por Publicación
            </h2>
            <div style="height: 300px; position: relative;">
                <canvas id="userChart"></canvas>
            </div>
        </div>

        <!-- Gráfico de interés general -->
        <div class="filter-card" style="padding: 30px;">
            <h2 style="margin-bottom: 25px; font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-pie-chart"></i> Interés General en la Plataforma
            </h2>
            <div style="height: 300px; position: relative;">
                <canvas id="generalChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="filter-card" style="padding: 35px; overflow-x: auto;">
        <h2 style="margin-bottom: 25px; font-size: 1.6rem; font-weight: 800;">Ranking de Interés Detallado</h2>
        <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 15px; font-weight: 800;">Propiedad</th>
                    <th style="padding: 15px; font-weight: 800;">Vistas</th>
                    <th style="padding: 15px; font-weight: 800;">Favoritos</th>
                    <th style="padding: 15px; font-weight: 800;">Citas</th>
                    <th style="padding: 15px; font-weight: 800;">Nivel de Interés</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_props as $prop): 
                    $interes = (int)$prop['vistas'] + ($prop['favs'] * 5) + ($prop['appointments'] * 10);
                    $max_interes = max($interes_top) ?: 1;
                    $percent = min(100, ($interes / $max_interes) * 100);
                ?>
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s;">
                        <td style="padding: 15px;">
                            <div style="font-weight: 700;"><?php echo htmlspecialchars($prop['titulo']); ?></div>
                            <div style="font-size: 0.8rem; color: #777;">$<?php echo number_format($prop['precio'], 2); ?></div>
                        </td>
                        <td style="padding: 15px; font-weight: 600;"><?php echo number_format($prop['vistas']); ?></td>
                        <td style="padding: 15px;"><?php echo $prop['favs']; ?></td>
                        <td style="padding: 15px;"><?php echo $prop['appointments']; ?></td>
                        <td style="padding: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; height: 8px; background: var(--secondary-bg); border-radius: 4px; overflow: hidden;">
                                    <div style="width: <?php echo $percent; ?>%; height: 100%; background: linear-gradient(90deg, #3498db, var(--accent-color));"></div>
                                </div>
                                <span style="font-size: 0.85rem; font-weight: 800; min-width: 35px;"><?php echo round($percent); ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#ffffff' : '#000000';
    const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';

    // Configuración común
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: textColor, font: { family: 'Inter', weight: '600' } }
            }
        },
        scales: {
            y: {
                grid: { color: gridColor },
                ticks: { color: textColor }
            },
            x: {
                grid: { display: false },
                ticks: { color: textColor }
            }
        }
    };

    // Gráfico de Usuario (Barras combinadas)
    new Chart(document.getElementById('userChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_top); ?>,
            datasets: [
                {
                    label: 'Vistas',
                    data: <?php echo json_encode($vistas_top); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderRadius: 5
                },
                {
                    label: 'Puntaje de Interés',
                    data: <?php echo json_encode($interes_top); ?>,
                    backgroundColor: isDark ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)',
                    borderRadius: 5
                }
            ]
        },
        options: commonOptions
    });

    // Gráfico General (Dona/Pie)
    new Chart(document.getElementById('generalChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($labels_gen); ?>,
            datasets: [{
                data: <?php echo json_encode($data_gen); ?>,
                backgroundColor: [
                    '#2ecc71', '#3498db', '#9b59b6', '#f1c40f', '#e67e22', '#e74c3c'
                ],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: textColor, padding: 20, font: { family: 'Inter', size: 12 } }
                }
            }
        }
    });
});
</script>

<style>
    /* Asegurar que el modo oscuro se aplique a los textos de la tabla si no están heredando bien */
    [data-theme="dark"] .filter-card h3 { color: #aaa !important; }
    [data-theme="dark"] tr:hover { background: rgba(255,255,255,0.03); }
    
    /* Animaciones suaves para las barras */
    .filter-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .filter-card:hover {
        transform: translateY(-5px);
    }
</style>

</body>
</html>
