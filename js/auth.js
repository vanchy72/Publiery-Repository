// Auth utilities for admin panel
const Auth = {
    setTokens(data) {
        localStorage.setItem('token', data.token);
        localStorage.setItem('csrf_token', data.csrf_token);
        localStorage.setItem('admin_data', JSON.stringify(data.user));
        sessionStorage.setItem('admin_user_id', data.user.id);
    },

    getTokens() {
        return {
            token: localStorage.getItem('token'),
            csrf: localStorage.getItem('csrf_token')
        };
    },

    clearTokens() {
        localStorage.removeItem('token');
        localStorage.removeItem('csrf_token');
        localStorage.removeItem('admin_data');
        sessionStorage.removeItem('admin_user_id');
    },

    isAuthenticated() {
        const token = localStorage.getItem('token');
        const adminData = localStorage.getItem('admin_data');
        return !!(token && adminData);
    },

    getUser() {
        const adminData = localStorage.getItem('admin_data');
        return adminData ? JSON.parse(adminData) : null;
    },

    logout() {
        this.clearTokens();
    },

    // Add auth headers to fetch requests
    async fetch(url, options = {}) {
        const tokens = this.getTokens();
        if (!tokens.token) {
            throw new Error('No auth token found');
        }

        const headers = {
            'Authorization': `Bearer ${tokens.token}`,
            'X-CSRF-TOKEN': tokens.csrf,
            'Content-Type': 'application/json',
            ...(options.headers || {})
        };

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            // Check for auth errors
            if (response.status === 401) {
                this.clearTokens();
                window.location.href = 'admin-login.html';
                return null;
            }

            return response;
        } catch (error) {
            console.error('Auth fetch error:', error);
            throw error;
        }
    }
};