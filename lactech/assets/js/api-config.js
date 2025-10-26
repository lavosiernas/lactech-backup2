/**
 * Configuração da API REST Moderna
 * Centraliza todas as chamadas para a nova API
 */

class ApiClient {
    constructor() {
        this.baseUrl = 'api/rest.php';
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    }

    /**
     * Fazer requisição HTTP
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}/${endpoint}`;
        const config = {
            method: 'GET',
            headers: { ...this.defaultHeaders, ...options.headers },
            ...options
        };

        try {
            const response = await fetch(url, config);
            
            // Verificar se a resposta é JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta não é JSON válido');
            }
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || `Erro HTTP ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error(`Erro na API ${endpoint}:`, error);
            throw error;
        }
    }

    // ==================== PASSWORD REQUESTS ====================
    async getPasswordRequests(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`password-requests${queryString ? '?' + queryString : ''}`);
    }

    async createPasswordRequest(data) {
        return this.request('password-requests', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async updatePasswordRequest(id, data) {
        return this.request('password-requests', {
            method: 'PUT',
            body: JSON.stringify({ id, ...data })
        });
    }

    async deletePasswordRequest(id) {
        return this.request('password-requests', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }

    // ==================== NOTIFICATIONS ====================
    async getNotifications(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`notifications${queryString ? '?' + queryString : ''}`);
    }

    async createNotification(data) {
        return this.request('notifications', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async markNotificationRead(id) {
        return this.request('notifications', {
            method: 'PUT',
            body: JSON.stringify({ id })
        });
    }

    async deleteNotification(id) {
        return this.request('notifications', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }

    // ==================== DASHBOARD ====================
    async getDashboardStats() {
        return this.request('dashboard');
    }

    // ==================== USERS ====================
    async getUsers(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`users${queryString ? '?' + queryString : ''}`);
    }

    async createUser(data) {
        return this.request('users', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async updateUser(id, data) {
        return this.request('users', {
            method: 'PUT',
            body: JSON.stringify({ id, ...data })
        });
    }

    async deleteUser(id) {
        return this.request('users', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }

    // ==================== VOLUME ====================
    async getVolumeRecords(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`volume${queryString ? '?' + queryString : ''}`);
    }

    async addVolumeRecord(data) {
        return this.request('volume', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async updateVolumeRecord(id, data) {
        return this.request('volume', {
            method: 'PUT',
            body: JSON.stringify({ id, ...data })
        });
    }

    async deleteVolumeRecord(id) {
        return this.request('volume', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }

    // ==================== QUALITY ====================
    async getQualityTests(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`quality${queryString ? '?' + queryString : ''}`);
    }

    async addQualityTest(data) {
        return this.request('quality', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async updateQualityTest(id, data) {
        return this.request('quality', {
            method: 'PUT',
            body: JSON.stringify({ id, ...data })
        });
    }

    async deleteQualityTest(id) {
        return this.request('quality', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }

    // ==================== FINANCIAL ====================
    async getFinancialRecords(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`financial${queryString ? '?' + queryString : ''}`);
    }

    async addFinancialRecord(data) {
        return this.request('financial', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async updateFinancialRecord(id, data) {
        return this.request('financial', {
            method: 'PUT',
            body: JSON.stringify({ id, ...data })
        });
    }

    async deleteFinancialRecord(id) {
        return this.request('financial', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }
}

// Instância global da API
window.apiClient = new ApiClient();

// Funções de compatibilidade para o código existente
window.api = {
    // Password requests
    getPasswordRequests: (params) => window.apiClient.getPasswordRequests(params),
    createPasswordRequest: (data) => window.apiClient.createPasswordRequest(data),
    updatePasswordRequest: (id, data) => window.apiClient.updatePasswordRequest(id, data),
    deletePasswordRequest: (id) => window.apiClient.deletePasswordRequest(id),
    
    // Notifications
    getNotifications: (params) => window.apiClient.getNotifications(params),
    createNotification: (data) => window.apiClient.createNotification(data),
    markNotificationRead: (id) => window.apiClient.markNotificationRead(id),
    deleteNotification: (id) => window.apiClient.deleteNotification(id),
    
    // Dashboard
    getDashboardStats: () => window.apiClient.getDashboardStats(),
    
    // Users
    getUsers: (params) => window.apiClient.getUsers(params),
    createUser: (data) => window.apiClient.createUser(data),
    updateUser: (id, data) => window.apiClient.updateUser(id, data),
    deleteUser: (id) => window.apiClient.deleteUser(id),
    
    // Volume
    getVolumeRecords: (params) => window.apiClient.getVolumeRecords(params),
    addVolumeRecord: (data) => window.apiClient.addVolumeRecord(data),
    updateVolumeRecord: (id, data) => window.apiClient.updateVolumeRecord(id, data),
    deleteVolumeRecord: (id) => window.apiClient.deleteVolumeRecord(id),
    
    // Quality
    getQualityTests: (params) => window.apiClient.getQualityTests(params),
    addQualityTest: (data) => window.apiClient.addQualityTest(data),
    updateQualityTest: (id, data) => window.apiClient.updateQualityTest(id, data),
    deleteQualityTest: (id) => window.apiClient.deleteQualityTest(id),
    
    // Financial
    getFinancialRecords: (params) => window.apiClient.getFinancialRecords(params),
    addFinancialRecord: (data) => window.apiClient.addFinancialRecord(data),
    updateFinancialRecord: (id, data) => window.apiClient.updateFinancialRecord(id, data),
    deleteFinancialRecord: (id) => window.apiClient.deleteFinancialRecord(id)
};

