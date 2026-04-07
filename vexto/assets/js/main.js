document.addEventListener('DOMContentLoaded', () => {
    // Modo Oscuro/Claro Persistente
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    let currentTheme = html.getAttribute('data-theme');
    let useDB = !!currentTheme; // Si hay data-theme, usar DB, sino localStorage
    
    if (!useDB) {
        currentTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', currentTheme);
    }
    
    updateThemeIcon(currentTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            let newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            currentTheme = newTheme;
            updateThemeIcon(newTheme);
            
            if (useDB) {
                // Enviar a servidor
                fetch('update_theme.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'theme=' + encodeURIComponent(newTheme)
                }).catch(error => console.error('Error updating theme:', error));
            } else {
                localStorage.setItem('theme', newTheme);
            }
            
            // Animación de cambio de tema
            anime({
                targets: 'body',
                opacity: [0.8, 1],
                duration: 400,
                easing: 'easeInOutQuad'
            });
        });
    }

    function updateThemeIcon(theme) {
        const icon = document.querySelector('#theme-toggle i');
        if (icon) {
            icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
    }

    // Animaciones de entrada para tarjetas
    if (document.querySelector('.property-card')) {
        anime({
            targets: '.property-card',
            opacity: [0, 1],
            translateY: [20, 0],
            delay: anime.stagger(100),
            duration: 800,
            easing: 'easeOutQuart'
        });
    }

    // Animación para el logo
    if (document.querySelector('.logo')) {
        anime({
            targets: '.logo',
            letterSpacing: ['10px', '3px'],
            opacity: [0, 1],
            duration: 1500,
            easing: 'easeOutExpo'
        });
    }
});

// Función para inicializar mapa (Leaflet)
function initMap(lat, lng, elementId = 'map', interactive = false) {
    if (!document.getElementById(elementId)) return;
    
    const map = L.map(elementId).setView([lat, lng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([lat, lng], { draggable: interactive }).addTo(map);
    
    if (interactive) {
        marker.on('dragend', function(event) {
            const position = marker.getLatLng();
            document.getElementById('lat').value = position.lat;
            document.getElementById('lng').value = position.lng;
        });
        
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value = e.latlng.lat;
            document.getElementById('lng').value = e.latlng.lng;
        });
    }
    
    return map;
}
