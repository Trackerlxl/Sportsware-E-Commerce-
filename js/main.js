// ============================================================
//  SPORTSWARE – main.js
// ============================================================

const CLAVE_CARRITO_LS = 'carrito_sportsware';

document.addEventListener('DOMContentLoaded', () => {
    actualizarContadorCarrito();

    const modal = document.getElementById('loginModal');
    const userIcon = document.getElementById('userIcon');
    const closeBtn = modal?.querySelector('.close') ?? null;

    if (userIcon && modal) userIcon.addEventListener('click', (e) => { e.preventDefault(); modal.classList.add('activo'); });
    closeBtn?.addEventListener('click', () => modal.classList.remove('activo'));
    modal?.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('activo'); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal?.classList.remove('activo'); });

    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.addEventListener('click', async () => {
            const email = document.getElementById('loginEmail')?.value.trim();
            const password = document.getElementById('loginPassword')?.value;
            if (!email || !password) { mostrarToast('Por favor completa todos los campos', 'error'); return; }
            loginBtn.disabled = true; loginBtn.textContent = 'Verificando…';
            try {
                const base = window.baseUrl || '';
                const res = await fetch(base + 'php/login.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, password }) });
                const data = await res.json();
                if (data.success) {
                    window.usuarioLogueado = true; window.sesionUsuario = data.usuario;
                    if (typeof sincronizarCarritoConBackend === 'function') await sincronizarCarritoConBackend();
                    mostrarToast(data.mensaje || '¡Bienvenido!');
                    modal?.classList.remove('activo');
                    setTimeout(() => window.location.reload(), 1200);
                } else { mostrarToast(data.mensaje || 'Credenciales incorrectas.', 'error'); }
            } catch (err) { mostrarToast('Error de conexión. Intenta de nuevo.', 'error'); console.error('[login]', err); }
            finally { loginBtn.disabled = false; loginBtn.textContent = 'Iniciar Sesión'; }
        });
    }

    const irLogin = document.getElementById('irLogin');
    if (irLogin && modal) irLogin.addEventListener('click', (e) => { e.preventDefault(); modal.classList.add('activo'); });

    document.querySelectorAll('img').forEach(img => { img.addEventListener('error', function () { this.onerror = null; this.src = 'https://placehold.co/500x500?text=SPORTSWARE'; }); });
});

async function cerrarSesion() {
    try {
        const base = window.baseUrl || '';
        const res = await fetch(base + 'php/logout.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        localStorage.removeItem(CLAVE_CARRITO_LS);
        window.usuarioLogueado = false; window.sesionUsuario = null;
        actualizarContadorCarrito();
        mostrarToast('Sesión cerrada. ¡Hasta pronto!');
        const destino = data?.redirect ?? (base + 'html/home.php');
        setTimeout(() => { window.location.href = destino; }, 1400);
    } catch (err) {
        console.error('[cerrarSesion]', err);
        localStorage.removeItem(CLAVE_CARRITO_LS);
        window.location.href = (window.baseUrl || '') + 'php/logout.php';
    }
}

function actualizarContadorCarrito() {
    const carrito = JSON.parse(localStorage.getItem(CLAVE_CARRITO_LS) || '[]');
    const total = carrito.reduce((sum, item) => sum + (item.cantidad || 1), 0);
    const badge = document.getElementById('contadorCarrito');
    if (badge) badge.textContent = total;
}

function mostrarToast(mensaje, tipo = 'success') {
    let toast = document.getElementById('toastGlobal');
    if (!toast) { toast = document.createElement('div'); toast.id = 'toastGlobal'; toast.className = 'toast'; document.body.appendChild(toast); }
    toast.textContent = mensaje;
    toast.style.background = tipo === 'error' ? '#ef4444' : '#1e293b';
    toast.classList.add('visible');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove('visible'), 3000);
}

window.cerrarSesion = cerrarSesion;
window.actualizarContadorCarrito = actualizarContadorCarrito;
window.mostrarToast = mostrarToast;