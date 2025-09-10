import axios from 'axios';

const BASE = '/api/portal/remise-approvers'; // Updated to match your routes

const extract = (res) => res?.data?.data ?? res?.data ?? null;

const ApproverRemiseService = {
  async index(params = {}) {
    try {
      const res = await axios.get(BASE, { params });
      return { success: true, data: extract(res), meta: res.data?.meta ?? null };
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message };
    }
  },

  async show(id) {
    try {
      const res = await axios.get(`${BASE}/${id}`);
      return { success: true, data: extract(res) };
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message };
    }
  },

  async store(payload = {}) {
    try {
      const res = await axios.post(BASE, payload);
      return { success: true, data: extract(res) };
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message, errors: err.response?.data?.errors ?? null };
    }
  },

  async update(id, payload = {}) {
    try {
      const res = await axios.patch(`${BASE}/${id}`, payload);
      return { success: true, data: extract(res) };
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message, errors: err.response?.data?.errors ?? null };
    }
  },

  async toggle(id, comments = '') {
    try {
      const res = await axios.post(`${BASE}/${id}/toggle`, comments ? { comments } : {});
      return { success: true, data: extract(res) };
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message };
    }
  },

  async bulkUpdate(payload = {}) {
    try {
      const res = await axios.post(`${BASE}/bulk-update`, payload);
      return { success: true, data: extract(res) };
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message, errors: err.response?.data?.errors ?? null };
    }
  },

  async destroy(id) {
    try {
      const res = await axios.delete(`${BASE}/${id}`);
      return { success: true, data: extract(res) };
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message };
    }
  }
};

export default ApproverRemiseService;