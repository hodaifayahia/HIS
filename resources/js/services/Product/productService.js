import axios from 'axios';

const BASE_URL = '/api/products';

export default {
    async getAll(filters = {}) {
        const response = await axios.get(BASE_URL, { params: filters });
        return response.data;
    },

    async getById(id) {
        const response = await axios.get(`${BASE_URL}/${id}`);
        return response.data;
    },

    async search(query) {
        const response = await axios.get(`${BASE_URL}/search`, { params: { q: query } });
        return response.data;
    }
};
