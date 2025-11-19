import axios from 'axios';

const BASE_URL = '/api/fournisseurs';

export default {
    async getAll(filters = {}) {
        const response = await axios.get(BASE_URL, { params: filters });
        return response.data;
    },

    async getById(id) {
        const response = await axios.get(`${BASE_URL}/${id}`);
        return response.data;
    },

    async create(data) {
        const response = await axios.post(BASE_URL, data);
        return response.data;
    },

    async update(id, data) {
        const response = await axios.put(`${BASE_URL}/${id}`, data);
        return response.data;
    },

    async delete(id) {
        const response = await axios.delete(`${BASE_URL}/${id}`);
        return response.data;
    }
};
