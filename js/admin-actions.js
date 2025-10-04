// Clase para manejar las acciones del panel de administración
class AdminActions {
    constructor() {
        this.baseUrl = 'api/';
        this.headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'X-CSRF-TOKEN': localStorage.getItem('csrf_token')
        };
    }

    // Método para actualizar los headers (por ejemplo, después de un refresh de token)
    updateHeaders() {
        this.headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'X-CSRF-TOKEN': localStorage.getItem('csrf_token')
        };
    }

    // Método genérico para hacer peticiones HTTP
    async fetchWithAuth(endpoint, options = {}) {
        try {
            // Verificar si tenemos un token
            const token = localStorage.getItem('token');
            if (!token) {
                console.error('No hay token');
                window.location.href = 'admin-login.html';
                return null;
            }

            const response = await fetch(this.baseUrl + endpoint, {
                ...options,
                headers: { 
                    ...this.headers, 
                    ...options.headers,
                    'Authorization': `Bearer ${token}`,
                    'X-CSRF-TOKEN': localStorage.getItem('csrf_token')
                }
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Error en la petición');
            }

            const data = await response.json();

            // Si el token expiró, intentar renovarlo
            if (response.status === 401 && data.error === 'Token expired') {
                const refreshed = await this.refreshToken();
                if (refreshed) {
                    return this.fetchWithAuth(endpoint, options);
                } else {
                    window.location.href = 'admin-login.html';
                    return null;
                }
            }

            return data;
        } catch (error) {
            console.error('Error en la petición:', error);
            throw new Error('Error de conexión');
        }
    }

    // Método para renovar el token
    async refreshToken() {
        try {
            const response = await fetch(this.baseUrl + 'auth/refresh-token.php', {
                method: 'POST',
                headers: this.headers
            });

            const data = await response.json();
            if (data.success && data.token) {
                localStorage.setItem('jwt_token', data.token);
                localStorage.setItem('csrf_token', data.csrf_token);
                this.updateHeaders();
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error al renovar token:', error);
            return false;
        }
    }

    // Métodos para usuarios
    async verUsuario(id) {
        try {
            console.log('Iniciando verUsuario para ID:', id);
            console.log('Token actual:', localStorage.getItem('token'));
            console.log('CSRF Token:', localStorage.getItem('csrf_token'));

            const url = `usuarios/ver_simple.php?id=${id}`;
            console.log('URL de la petición:', this.baseUrl + url);

            const data = await this.fetchWithAuth(url, {
                method: 'GET',
                headers: {
                    ...this.headers,
                    'Cache-Control': 'no-cache'
                }
            });
            
            console.log('Respuesta recibida:', data);

            if (!data.success) {
                console.error('Error en la respuesta:', data.error);
                throw new Error(data.error || 'Error al obtener usuario');
            }

            console.log('Usuario obtenido:', data.usuario);
            return data.usuario;
        } catch (error) {
            console.error('Error detallado en verUsuario:', {
                message: error.message,
                stack: error.stack,
                error: error
            });
            throw new Error('Error al ver usuario: ' + error.message);
        }
    }

    async editarUsuario(id, datos) {
        try {
            const data = await this.fetchWithAuth('usuarios/actualizar.php', {
                method: 'PUT',
                body: JSON.stringify({ id, ...datos })
            });
            if (!data.success) {
                throw new Error(data.error || 'Error al actualizar usuario');
            }
            return data;
        } catch (error) {
            throw new Error('Error al editar usuario: ' + error.message);
        }
    }

    async eliminarUsuario(id) {
        try {
            const data = await this.fetchWithAuth('usuarios/eliminar_simple.php', {
                method: 'DELETE',
                body: JSON.stringify({ id })
            });
            if (!data.success) {
                throw new Error(data.error || 'Error al eliminar usuario');
            }
            return data;
        } catch (error) {
            throw new Error('Error al eliminar usuario: ' + error.message);
        }
    }

    async toggleEstadoUsuario(id, estado) {
        try {
            const data = await this.fetchWithAuth('usuarios/toggle_estado_simple.php', {
                method: 'POST',
                body: JSON.stringify({ id, estado })
            });
            if (!data.success) {
                throw new Error(data.error || 'Error al cambiar estado');
            }
            return data;
        } catch (error) {
            throw new Error('Error al cambiar estado: ' + error.message);
        }
    }

    // Método para mostrar mensajes
    static mostrarMensaje(mensaje, tipo = 'error') {
        const mensajeDiv = document.createElement('div');
        mensajeDiv.className = `mensaje-${tipo}`;
        mensajeDiv.textContent = mensaje;
        mensajeDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            z-index: 9999;
            animation: fadeIn 0.3s, fadeOut 0.3s 2.7s;
        `;

        if (tipo === 'error') {
            mensajeDiv.style.backgroundColor = '#f44336';
        } else if (tipo === 'success') {
            mensajeDiv.style.backgroundColor = '#4CAF50';
        }
        mensajeDiv.style.color = 'white';

        document.body.appendChild(mensajeDiv);
        setTimeout(() => mensajeDiv.remove(), 3000);
    }

    // Método para confirmar acciones
    static async confirmar(mensaje) {
        return new Promise(resolve => {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;

            const contenido = document.createElement('div');
            contenido.style.cssText = `
                background: white;
                padding: 20px;
                border-radius: 5px;
                max-width: 400px;
                text-align: center;
            `;

            contenido.innerHTML = `
                <h3 style="margin-top: 0;">${mensaje}</h3>
                <div style="margin-top: 20px;">
                    <button id="confirmarSi" class="btn btn-danger" style="margin-right: 10px;">Sí</button>
                    <button id="confirmarNo" class="btn btn-secondary">No</button>
                </div>
            `;

            modal.appendChild(contenido);
            document.body.appendChild(modal);

            modal.querySelector('#confirmarSi').onclick = () => {
                modal.remove();
                resolve(true);
            };
            modal.querySelector('#confirmarNo').onclick = () => {
                modal.remove();
                resolve(false);
            };
        });
    }
}

// Exportar la instancia para uso global
window.adminActions = new AdminActions();