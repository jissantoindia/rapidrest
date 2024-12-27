// API Client class for making HTTP requests
class ApiClient {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
        this.headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    }

    // Set authorization token
    setToken(token) {
        this.headers['Authorization'] = `Bearer ${token}`;
    }

    // Remove authorization token
    removeToken() {
        delete this.headers['Authorization'];
    }

    // Make HTTP request
    async request(method, endpoint, data = null) {
        const url = this.baseUrl + endpoint;
        const options = {
            method,
            headers: this.headers,
            credentials: 'include'
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const contentType = response.headers.get('content-type');
            
            if (contentType && contentType.includes('application/json')) {
                const json = await response.json();
                if (!response.ok) {
                    throw new Error(json.message || 'API request failed');
                }
                return json;
            }
            
            if (!response.ok) {
                throw new Error('API request failed');
            }
            
            return await response.text();
        } catch (error) {
            console.error('API request error:', error);
            throw error;
        }
    }

    // HTTP method shortcuts
    async get(endpoint) {
        return this.request('GET', endpoint);
    }

    async post(endpoint, data) {
        return this.request('POST', endpoint, data);
    }

    async put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    }

    async delete(endpoint) {
        return this.request('DELETE', endpoint);
    }
}

// Create API client instance
const api = new ApiClient('/api');

// Authentication helper
const auth = {
    async login(email, password) {
        try {
            const response = await api.post('/auth/login', { email, password });
            if (response.token) {
                api.setToken(response.token);
                localStorage.setItem('token', response.token);
            }
            return response;
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    },

    async logout() {
        try {
            await api.post('/auth/logout');
            api.removeToken();
            localStorage.removeItem('token');
        } catch (error) {
            console.error('Logout error:', error);
            throw error;
        }
    },

    isAuthenticated() {
        return !!localStorage.getItem('token');
    }
};

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication status
    if (auth.isAuthenticated()) {
        const token = localStorage.getItem('token');
        api.setToken(token);
    }

    // Setup form handlers
    setupForms();
});

// Setup form handlers
function setupForms() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = loginForm.querySelector('[name="email"]').value;
            const password = loginForm.querySelector('[name="password"]').value;

            try {
                await auth.login(email, password);
                window.location.href = '/dashboard';
            } catch (error) {
                showError(error.message);
            }
        });
    }

    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await auth.logout();
                window.location.href = '/login';
            } catch (error) {
                showError(error.message);
            }
        });
    }
}

// Show error message
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    document.body.appendChild(errorDiv);
    setTimeout(() => errorDiv.remove(), 5000);
}
