window.onload = () => {
    const tl = anime.timeline({ easing: 'easeOutExpo' });
    tl.add({
        targets: '.logo-anim',
        opacity: [0, 1],
        scale: [0.5, 1],
        letterSpacing: ['40px', '20px'],
        duration: 2000,
        delay: 500
    }).add({
        targets: '#splash',
        opacity: 0,
        duration: 1000,
        delay: 800,
        complete: () => {
            document.getElementById('splash').style.display = 'none';
            toggleAccountType();
            anime({
                targets: '#auth-container',
                opacity: [0, 1],
                translateY: [50, 0],
                duration: 1200,
                easing: 'easeOutQuart'
            });
        }
    });
};

function switchForm(type) {
    const login = document.getElementById('login-section');
    const register = document.getElementById('register-section');
    if (type === 'register') {
        login.classList.add('hidden');
        register.classList.remove('hidden');
        anime({
            targets: '#register-section',
            opacity: [0, 1],
            duration: 400,
            easing: 'easeOutQuad'
        });
    } else {
        register.classList.add('hidden');
        login.classList.remove('hidden');
        anime({
            targets: '#login-section',
            opacity: [0, 1],
            duration: 400,
            easing: 'easeOutQuad'
        });
    }
}

function toggleAccountType() {
    const tipoUsuario = document.getElementById('tipoUsuario')?.value;
    const fotoPerfil = document.getElementById('fotoPerfil');
    const fotoPerfilHint = document.getElementById('fotoPerfilHint');

    if (!fotoPerfil || !fotoPerfilHint) return;

    if (tipoUsuario === 'compania') {
        fotoPerfil.required = false;
        fotoPerfilHint.textContent = 'Foto opcional para empresas.';
    } else {
        fotoPerfil.required = true;
        fotoPerfilHint.textContent = 'Foto opcional para usuarios comunes.';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const fotoPerfil = document.getElementById('fotoPerfil');
    const fotoPerfilStatus = document.getElementById('fotoPerfilStatus');

    if (fotoPerfil && fotoPerfilStatus) {
        fotoPerfil.addEventListener('change', function() {
            const fileName = this.files && this.files.length ? this.files[0].name : 'Ningún archivo seleccionado';
            fotoPerfilStatus.textContent = fileName;
        });
    }
});
