// Animaciones y funcionalidades mejoradas para VEXTO

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tema
    initTheme();
    
    // Inicializar animaciones
    initAnimations();
    
    // Inicializar interacciones
    initInteractions();
});

// ==================== TEMA ====================
function initTheme() {
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    
    // Obtener tema guardado o usar el del sistema
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Persistir en servidor si el usuario está logueado
            fetch('../views/update_theme.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'theme=' + newTheme
            }).catch(err => console.error('Error persistiendo tema:', err));
            
            // Animación del icono
            anime({
                targets: themeToggle,
                rotate: [0, 360],
                duration: 600,
                easing: 'easeInOutQuad'
            });
        });
    }
}

// ==================== ANIMACIONES ====================
function initAnimations() {
    // Animar tarjetas de propiedades al entrar en vista
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                anime({
                    targets: entry.target,
                    opacity: [0, 1],
                    translateY: [20, 0],
                    duration: 600,
                    easing: 'easeOutQuad'
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.property-card').forEach(card => {
        observer.observe(card);
    });
    
    // Animar elementos del filtro
    const filterGroups = document.querySelectorAll('.filter-group');
    filterGroups.forEach((group, index) => {
        anime({
            targets: group,
            opacity: [0, 1],
            translateX: [-20, 0],
            duration: 600,
            delay: index * 100,
            easing: 'easeOutQuad'
        });
    });
}

// ==================== INTERACCIONES ====================
function initInteractions() {
    // Efecto hover en tarjetas
    document.querySelectorAll('.property-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            anime({
                targets: this,
                scale: 1.02,
                duration: 300,
                easing: 'easeOutQuad'
            });
        });
        
        card.addEventListener('mouseleave', function() {
            anime({
                targets: this,
                scale: 1,
                duration: 300,
                easing: 'easeOutQuad'
            });
        });
    });
    
    // Efecto ripple en botones
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.width = '0';
            ripple.style.height = '0';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255,255,255,0.5)';
            ripple.style.pointerEvents = 'none';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            anime({
                targets: ripple,
                width: [0, Math.max(rect.width, rect.height) * 2],
                height: [0, Math.max(rect.width, rect.height) * 2],
                opacity: [1, 0],
                duration: 600,
                easing: 'easeOutQuad',
                complete: () => ripple.remove()
            });
        });
    });
}

let logoutUrl = null;
function showLogoutConfirm(url) {
    logoutUrl = url;
    const modal = document.getElementById('logoutConfirmModal');
    if (modal) modal.style.display = 'flex';
}

function closeLogoutConfirm() {
    const modal = document.getElementById('logoutConfirmModal');
    if (modal) modal.style.display = 'none';
    logoutUrl = null;
}

function confirmLogout() {
    if (logoutUrl) {
        window.location.href = logoutUrl;
    }
}

// ==================== MAPA ====================
function initMap(lat = 18.4861, lng = -69.9312, elementId = 'map', editable = false) {
    if (!document.getElementById(elementId)) return;
    
    const map = L.map(elementId).setView([lat, lng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    let marker = L.marker([lat, lng], { draggable: editable }).addTo(map);
    
    // Actualizar coordenadas si el mapa es editable
    if (editable) {
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value = e.latlng.lat.toFixed(8);
            document.getElementById('lng').value = e.latlng.lng.toFixed(8);
        });
        
        marker.on('dragend', function() {
            const pos = marker.getLatLng();
            document.getElementById('lat').value = pos.lat.toFixed(8);
            document.getElementById('lng').value = pos.lng.toFixed(8);
        });
    }
    
    return map;
}

// ==================== BÚSQUEDA Y FILTROS ====================
function filterProperties(query) {
    const cards = document.querySelectorAll('.property-card');
    const queryLower = query.toLowerCase();
    
    cards.forEach(card => {
        const title = card.querySelector('.property-title')?.textContent.toLowerCase() || '';
        const meta = card.querySelector('.property-meta')?.textContent.toLowerCase() || '';
        
        if (title.includes(queryLower) || meta.includes(queryLower)) {
            anime({
                targets: card,
                opacity: [0.3, 1],
                duration: 300,
                easing: 'easeOutQuad'
            });
            card.style.display = 'block';
        } else {
            anime({
                targets: card,
                opacity: [1, 0.3],
                duration: 300,
                easing: 'easeOutQuad'
            });
            card.style.display = 'none';
        }
    });
}

// ==================== NOTIFICACIONES ====================
function showNotification(message, type = 'success', duration = 3000) {
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.padding = '16px 24px';
    notification.style.borderRadius = '8px';
    notification.style.color = '#fff';
    notification.style.fontWeight = '600';
    notification.style.zIndex = '10000';
    notification.style.maxWidth = '400px';
    notification.style.wordWrap = 'break-word';
    notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    
    if (type === 'success') {
        notification.style.background = '#10b981';
    } else if (type === 'error') {
        notification.style.background = '#ef4444';
    } else if (type === 'warning') {
        notification.style.background = '#f59e0b';
    } else {
        notification.style.background = '#3b82f6';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    anime({
        targets: notification,
        opacity: [0, 1],
        translateX: [50, 0],
        duration: 400,
        easing: 'easeOutQuad'
    });
    
    setTimeout(() => {
        anime({
            targets: notification,
            opacity: [1, 0],
            translateX: [0, 50],
            duration: 400,
            easing: 'easeInQuad',
            complete: () => notification.remove()
        });
    }, duration);
}

// ==================== SCROLL SUAVE ====================
function smoothScroll(target) {
    const element = document.querySelector(target);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// ==================== CARGA PEREZOSA ====================
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// ==================== UTILIDADES ====================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ==================== VALIDACIONES ====================
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\-\+\(\)]+$/;
    return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

// ==================== EXPORTAR FUNCIONES ====================
window.VEXTO = {
    showNotification,
    smoothScroll,
    filterProperties,
    initMap,
    lazyLoadImages,
    validateEmail,
    validatePhone,
    debounce,
    throttle
};
