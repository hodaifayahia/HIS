// services/Dashboard/DashboardService.js
import axios from 'axios';

export const dashboardService = {
    /**
     * Get comprehensive dashboard data
     * @param {Object} params - Query parameters including date range
     * @returns {Promise<Object>} Dashboard data
     */
    async getDashboardData(params = {}) {
        try {
            const response = await axios.get('/api/dashboard', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load dashboard data.',
                error
            };
        }
    },

    /**
     * Get system health status
     * @returns {Promise<Object>} System health data
     */
    async getSystemHealth() {
        try {
            const response = await axios.get('/api/dashboard/system-health');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load system health.',
                error
            };
        }
    },

    /**
     * Export dashboard data
     * @param {Object} params - Export parameters
     * @returns {Promise<Object>} Export result
     */
    async exportDashboard(params = {}) {
        try {
            const response = await axios.post('/api/dashboard/export', params, {
                responseType: 'blob'
            });
            
            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `banking-dashboard-${new Date().toISOString().split('T')[0]}.pdf`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            return {
                success: true,
                message: 'Dashboard exported successfully'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to export dashboard.',
                error
            };
        }
    },

    /**
     * Get balance trends data
     * @param {Object} params - Parameters including period and date range
     * @returns {Promise<Object>} Balance trends data
     */
    async getBalanceTrends(params = {}) {
        try {
            const response = await axios.get('/api/dashboard/balance-trends', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load balance trends.',
                error
            };
        }
    },

    /**
     * Get transaction analytics
     * @param {Object} params - Parameters for analytics
     * @returns {Promise<Object>} Transaction analytics data
     */
    async getTransactionAnalytics(params = {}) {
        try {
            const response = await axios.get('/api/dashboard/transaction-analytics', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transaction analytics.',
                error
            };
        }
    }
};
