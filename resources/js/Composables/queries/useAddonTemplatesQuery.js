import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { initializeCSRF } from '@/Services/api'
import { queryKeys } from '@/Services/queryClient'
import axios from 'axios'

// Initialize CSRF on module load
initializeCSRF()

// Addon Templates API
const addonTemplatesApi = {
  getAll: () => axios.get('/api/addon-templates').then(res => res.data),
  getById: (id) => axios.get(`/api/addon-templates/${id}`).then(res => res.data),
  create: (data) => axios.post('/api/addon-templates', data).then(res => res.data),
  update: (id, data) => axios.put(`/api/addon-templates/${id}`, data).then(res => res.data),
  delete: (id) => axios.delete(`/api/addon-templates/${id}`).then(res => res.data),
}

// Addon Templates Query
export function useAddonTemplatesQuery() {
  return useQuery({
    queryKey: queryKeys.addonTemplates.all,
    queryFn: () => addonTemplatesApi.getAll().then(res => res.data),
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

// Single Addon Template Query
export function useAddonTemplateQuery(id) {
  return useQuery({
    queryKey: queryKeys.addonTemplates.byId(id),
    queryFn: () => addonTemplatesApi.getById(id).then(res => res.data),
    enabled: !!id,
  })
}

// Create Addon Template Mutation
export function useCreateAddonTemplateMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (data) => addonTemplatesApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.addonTemplates.all })
    },
    onError: (error) => {
      console.error('Failed to create addon template:', error)
    }
  })
}

// Update Addon Template Mutation
export function useUpdateAddonTemplateMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, data }) => addonTemplatesApi.update(id, data),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.addonTemplates.byId(id) })
      queryClient.invalidateQueries({ queryKey: queryKeys.addonTemplates.all })
    },
    onError: (error) => {
      console.error('Failed to update addon template:', error)
    }
  })
}

// Delete Addon Template Mutation
export function useDeleteAddonTemplateMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (id) => addonTemplatesApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.addonTemplates.all })
    },
    onError: (error) => {
      console.error('Failed to delete addon template:', error)
    }
  })
}