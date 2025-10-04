/**
 * Configuración API para Publiery
 * Maneja URLs locales y de producción automáticamente
 */

class ApiConfig {
    constructor() {
        // Detectar entorno automáticamente
        this.isLocal = window.location.hostname === 'localhost' || 
                      window.location.hostname === '127.0.0.1' ||
                      window.location.hostname.includes('localhost');
                      
        this.isProd = window.location.hostname.includes('vercel.app') ||
                     window.location.hostname.includes('netlify.app') ||
                     window.location.hostname.includes('github.io') ||
                     window.location.hostname.includes('railway.app');

        // URLs base según entorno
        if (this.isLocal) {
            this.baseURL = 'http://localhost/publiery';
            this.apiURL = 'http://localhost/publiery/api';
        } else {
            // Producción - APIs en Railway/Heroku, Frontend en GitHub Pages
            this.baseURL = window.location.origin;
            this.apiURL = 'https://publiery-api.railway.app/api'; // Cambiar por tu URL real
        }
    }

    // Construir URL completa de API
    getApiUrl(endpoint) {
        return `${this.apiURL}/${endpoint}`;
    }

    // Construir URL base
    getBaseUrl(path = '') {
        return `${this.baseURL}/${path}`;
    }

    // Hacer request con configuración automática
    async request(endpoint, options = {}) {
        const url = this.getApiUrl(endpoint);
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            ...options
        };

        try {
            const response = await fetch(url, defaultOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API Error (${endpoint}):`, error);
            throw error;
        }
    }

    // Métodos de conveniencia
    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

// Instancia global
window.apiConfig = new ApiConfig();

// Función de conveniencia para mantener compatibilidad
window.API_BASE = window.apiConfig.getApiUrl('');

console.log('🚀 API Config initialized:', {
    isLocal: window.apiConfig.isLocal,
    baseURL: window.apiConfig.baseURL,
    apiURL: window.apiConfig.apiURL
});