import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { ref } from 'vue'
import axios from 'axios'

export function useImportQueries() {
    const queryClient = useQueryClient()

    // Import Profiles Query
    const {
        data: profiles,
        isLoading: profilesLoading,
        error: profilesError,
        refetch: refetchProfiles
    } = useQuery({
        queryKey: ['import-profiles'],
        queryFn: async () => {
            const response = await axios.get('/api/import/profiles')
            return response.data.data || response.data
        },
        staleTime: 5 * 60 * 1000, // 5 minutes
    })

    // Import Jobs Query
    const {
        data: jobs,
        isLoading: jobsLoading,
        error: jobsError,
        refetch: refetchJobs
    } = useQuery({
        queryKey: ['import-jobs'],
        queryFn: async () => {
            const response = await axios.get('/api/import/jobs')
            return response.data.data || response.data
        },
        staleTime: 30 * 1000, // 30 seconds - jobs change frequently
        refetchInterval: 10 * 1000, // Auto-refresh every 10 seconds for running jobs
    })

    // Create Profile Mutation
    const createProfileMutation = useMutation({
        mutationFn: async (profileData) => {
            const response = await axios.post('/api/import/profiles', profileData)
            return response.data
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['import-profiles'] })
        },
    })

    // Update Profile Mutation
    const updateProfileMutation = useMutation({
        mutationFn: async ({ id, profileData }) => {
            const response = await axios.put(`/api/import/profiles/${id}`, profileData)
            return response.data
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['import-profiles'] })
        },
    })

    // Delete Profile Mutation
    const deleteProfileMutation = useMutation({
        mutationFn: async (profileId) => {
            await axios.delete(`/api/import/profiles/${profileId}`)
            return profileId
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['import-profiles'] })
        },
    })

    // Test Connection Mutation
    const testConnectionMutation = useMutation({
        mutationFn: async (connectionData) => {
            try {
                const response = await axios.post('/api/import/profiles/test-connection', connectionData)
                return response.data
            } catch (error) {
                // For connection test, extract data from error response if available
                if (error.response?.data?.connection_test) {
                    return error.response.data
                }
                throw error
            }
        },
    })

    // Get Schema Mutation
    const getSchemaMutation = useMutation({
        mutationFn: async (profileId) => {
            const response = await axios.get(`/api/import/profiles/${profileId}/schema`)
            return response.data
        },
    })

    // Preview Import Mutation
    const previewImportMutation = useMutation({
        mutationFn: async ({ profileId, filters }) => {
            const url = `/api/import/profiles/${profileId}/preview`
            const params = new URLSearchParams()
            
            // Add filter parameters if provided
            if (filters?.selected_tables?.length > 0) {
                params.append('tables', filters.selected_tables.join(','))
            }
            
            if (filters?.import_filters) {
                Object.entries(filters.import_filters).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        params.append(key, value)
                    }
                })
            }
            
            const queryString = params.toString()
            const fullUrl = queryString ? `${url}?${queryString}` : url
            
            const response = await axios.get(fullUrl)
            return response.data
        },
    })

    // Execute Import Mutation
    const executeImportMutation = useMutation({
        mutationFn: async ({ profileId, options }) => {
            const response = await axios.post('/api/import/jobs', {
                profile_id: profileId,
                options: options || {}
            })
            return response.data
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['import-jobs'] })
        },
    })

    // Cancel Job Mutation
    const cancelJobMutation = useMutation({
        mutationFn: async (jobId) => {
            const response = await axios.post(`/api/import/jobs/${jobId}/cancel`)
            return response.data
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['import-jobs'] })
        },
    })

    // Get Job Status Query (for individual job monitoring)
    const useJobStatus = (jobId) => {
        return useQuery({
            queryKey: ['import-job-status', jobId],
            queryFn: async () => {
                const response = await axios.get(`/api/import/jobs/${jobId}/status`)
                return response.data
            },
            enabled: !!jobId,
            refetchInterval: (data) => {
                // Only refetch if job is still running
                return data?.status === 'running' ? 2000 : false
            },
            staleTime: 1000, // Very short stale time for live updates
        })
    }

    // Helper functions
    const refreshProfiles = () => refetchProfiles()
    const refreshJobs = () => refetchJobs()

    const createProfile = (profileData) => {
        return createProfileMutation.mutateAsync(profileData)
    }

    const updateProfile = (id, profileData) => {
        return updateProfileMutation.mutateAsync({ id, profileData })
    }

    const deleteProfile = (profileId) => {
        return deleteProfileMutation.mutateAsync(profileId)
    }

    const testConnection = (connectionData) => {
        return testConnectionMutation.mutateAsync(connectionData)
    }

    const getSchema = (profileId) => {
        return getSchemaMutation.mutateAsync(profileId)
    }

    const previewImport = (profileId, filters = null) => {
        // Handle both old API (profileId only) and new API (profileId + filters)
        if (typeof profileId === 'string' && !filters) {
            // Old API: just profileId
            return previewImportMutation.mutateAsync({ profileId })
        } else {
            // New API: profileId + filters
            return previewImportMutation.mutateAsync({ profileId, filters })
        }
    }

    const executeImport = (profileId, options = {}) => {
        return executeImportMutation.mutateAsync({ profileId, options })
    }

    const cancelJob = (jobId) => {
        return cancelJobMutation.mutateAsync(jobId)
    }

    // Field Mappings API
    const getFieldMappings = async (profileId) => {
        const response = await axios.get(`/api/import/profiles/${profileId}/mappings`)
        return response.data
    }

    const saveFieldMappings = async (profileId, mappings) => {
        const response = await axios.post(`/api/import/profiles/${profileId}/mappings`, {
            mappings: mappings
        })
        return response.data
    }

    // Templates Query
    const {
        data: templates,
        isLoading: templatesLoading,
        error: templatesError,
        refetch: refetchTemplates
    } = useQuery({
        queryKey: ['import-templates'],
        queryFn: async () => {
            const response = await axios.get('/api/import/templates')
            return response.data.data || response.data
        },
        staleTime: 10 * 60 * 1000, // 10 minutes - templates don't change often
    })

    // Get Template by ID
    const getTemplate = async (templateId) => {
        const response = await axios.get(`/api/import/templates/${templateId}`)
        return response.data
    }

    return {
        // Data
        profiles,
        jobs,
        templates,
        
        // Loading states
        profilesLoading,
        jobsLoading,
        templatesLoading,
        
        // Errors
        profilesError,
        jobsError,
        templatesError,
        
        // Refresh functions
        refreshProfiles,
        refreshJobs,
        refetchTemplates,
        
        // Mutation functions
        createProfile,
        updateProfile,
        deleteProfile,
        testConnection,
        getSchema,
        previewImport,
        executeImport,
        cancelJob,
        getFieldMappings,
        saveFieldMappings,
        getTemplate,
        
        // Mutation states
        isCreatingProfile: createProfileMutation.isPending,
        isUpdatingProfile: updateProfileMutation.isPending,
        isDeletingProfile: deleteProfileMutation.isPending,
        isTestingConnection: testConnectionMutation.isPending,
        isGettingSchema: getSchemaMutation.isPending,
        isPreviewingImport: previewImportMutation.isPending,
        isExecutingImport: executeImportMutation.isPending,
        isCancellingJob: cancelJobMutation.isPending,
        
        // Specialized query hooks
        useJobStatus,
    }
}