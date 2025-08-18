import axios from 'axios'

// Ensure axios is configured with CSRF token
const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
})

// Add CSRF token to requests
api.interceptors.request.use((config) => {
  // Get CSRF token from meta tag (for traditional Laravel forms)
  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token.content
  }
  
  // Get XSRF token from cookies (for Sanctum)
  const xsrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
  if (xsrfToken) {
    config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken.split('=')[1])
  }
  
  return config
}, (error) => {
  return Promise.reject(error)
})

// Add response interceptor for error handling
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      window.location.href = '/login'
    } else if (error.response && error.response.status === 419) {
      console.error('CSRF token mismatch - token may have expired')
    }
    return Promise.reject(error)
  }
)

// Initialize CSRF cookie before making any requests
export const initializeCSRF = async () => {
  try {
    await axios.get('/sanctum/csrf-cookie', {
      withCredentials: true
    })
  } catch (error) {
    console.error('Failed to initialize CSRF cookie:', error)
    throw error
  }
}

// Account API functions
export const accountsApi = {
  getAll: (params = {}) => api.get('/accounts', { params }),
  getById: (id) => api.get(`/accounts/${id}`),
  create: (data) => api.post('/accounts', data),
  update: (id, data) => api.put(`/accounts/${id}`, data),
  delete: (id) => api.delete(`/accounts/${id}`),
  getSelector: () => api.get('/accounts/selector'),
}

// Tickets API functions
export const ticketsApi = {
  getAll: (params = {}) => api.get('/tickets', { params }),
  getById: (id) => api.get(`/tickets/${id}`),
  create: (data) => api.post('/tickets', data),
  update: (id, data) => api.put(`/tickets/${id}`, data),
  delete: (id) => api.delete(`/tickets/${id}`),
  getComments: (id) => api.get(`/tickets/${id}/comments`),
  addComment: (id, formData) => api.post(`/tickets/${id}/comments`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  getRelated: (id) => api.get(`/tickets/${id}/related`),
  getTimers: (id) => api.get(`/tickets/${id}/timers`),
  getTimeEntries: (id) => api.get(`/tickets/${id}/time-entries`),
}

// Timers API functions
export const timersApi = {
  getAll: (params = {}) => api.get('/timers', { params }),
  getCurrent: () => api.get('/timers/active/current'),
  create: (data) => api.post('/timers', data),
  stop: (id) => api.post(`/timers/${id}/stop`),
  pause: (id) => api.post(`/timers/${id}/pause`),
  resume: (id) => api.post(`/timers/${id}/resume`),
  delete: (id) => api.delete(`/timers/${id}`),
}

// Users API functions
export const usersApi = {
  getAll: (params = {}) => api.get('/users', { params }),
  getById: (id) => api.get(`/users/${id}`),
  create: (data) => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`),
  getAssignable: () => api.get('/users/assignable'),
  getTickets: (id) => api.get(`/users/${id}/tickets`),
  getTimeEntries: (id) => api.get(`/users/${id}/time-entries`),
  getActivity: (id) => api.get(`/users/${id}/activity`),
  getAccounts: (id) => api.get(`/users/${id}/accounts`),
}

// Role Templates API functions
export const roleTemplatesApi = {
  getAll: (params = {}) => api.get('/role-templates', { params }),
  getById: (id) => api.get(`/role-templates/${id}`),
  create: (data) => api.post('/role-templates', data),
  update: (id, data) => api.put(`/role-templates/${id}`, data),
  delete: (id) => api.delete(`/role-templates/${id}`),
  getPermissions: () => api.get('/role-templates/permissions/available'),
  clone: (id, data) => api.post(`/role-templates/${id}/clone`, data),
  getWidgets: (id) => api.get(`/role-templates/${id}/widgets`),
  updateWidgets: (id, data) => api.put(`/role-templates/${id}/widgets`, data),
}

export default api