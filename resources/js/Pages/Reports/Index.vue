<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      
      <!-- Page Header -->
      <div class="mb-8">
        <div class="md:flex md:items-center md:justify-between">
          <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
              Reports & Analytics
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Comprehensive insights into your business performance
            </p>
          </div>
          <div class="mt-4 flex md:ml-4 md:mt-0">
            <button
              @click="exportReports"
              type="button"
              class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              <ArrowDownTrayIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
              Export Reports
            </button>
          </div>
        </div>
      </div>

      <!-- Date Range Selector -->
      <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Report Parameters</h3>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
            <div>
              <label for="date_from" class="block text-sm font-medium text-gray-700">
                From Date
              </label>
              <input
                id="date_from"
                v-model="filters.dateFrom"
                @change="loadReportData"
                type="date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label for="date_to" class="block text-sm font-medium text-gray-700">
                To Date
              </label>
              <input
                id="date_to"
                v-model="filters.dateTo"
                @change="loadReportData"
                type="date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label for="account_filter" class="block text-sm font-medium text-gray-700">
                Account
              </label>
              <select
                id="account_filter"
                v-model="filters.accountId"
                @change="loadReportData"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">All Accounts</option>
                <option v-for="account in accounts" :key="account.id" :value="account.id">
                  {{ account.name }}
                </option>
              </select>
            </div>
            <div>
              <label for="preset_ranges" class="block text-sm font-medium text-gray-700">
                Quick Ranges
              </label>
              <select
                id="preset_ranges"
                @change="applyPresetRange"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Custom Range</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="this_week">This Week</option>
                <option value="last_week">Last Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="this_year">This Year</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Key Performance Indicators -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CurrencyDollarIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      ${{ formatCurrency(kpis.total_revenue) }}
                    </div>
                    <div v-if="kpis.revenue_change !== undefined" 
                         :class="kpis.revenue_change >= 0 ? 'text-green-600' : 'text-red-600'"
                         class="ml-2 flex items-baseline text-sm font-semibold">
                      <component :is="kpis.revenue_change >= 0 ? ArrowUpIcon : ArrowDownIcon" 
                                class="self-center flex-shrink-0 h-3 w-3" />
                      {{ Math.abs(kpis.revenue_change) }}%
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ClockIcon class="h-6 w-6 text-blue-600" aria-hidden="true" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Hours</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ formatHours(kpis.total_hours) }}
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <DocumentTextIcon class="h-6 w-6 text-purple-600" aria-hidden="true" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Active Tickets</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ kpis.active_tickets || 0 }}
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ChartBarIcon class="h-6 w-6 text-orange-600" aria-hidden="true" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Utilization Rate</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ Math.round(kpis.utilization_rate || 0) }}%
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
        <!-- Revenue Trend Chart -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Revenue Trend</h3>
          </div>
          <div class="p-6">
            <div v-if="loading" class="flex justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
            <div v-else class="h-64 flex items-center justify-center text-gray-500">
              <div class="text-center">
                <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm">Revenue chart will be displayed here</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Time Distribution Chart -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Time Distribution</h3>
          </div>
          <div class="p-6">
            <div v-if="loading" class="flex justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
            <div v-else class="h-64 flex items-center justify-center text-gray-500">
              <div class="text-center">
                <ChartPieIcon class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm">Time distribution chart will be displayed here</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Reports Tables -->
      <div class="space-y-8">
        <!-- Account Performance Report -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Account Performance</h3>
          </div>
          <AccountPerformanceTable 
            :data="reports.account_performance" 
            :loading="loading"
          />
        </div>

        <!-- Service Delivery Performance Report -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Service Delivery Performance</h3>
          </div>
          <ServiceDeliveryTable 
            :data="reports.service_delivery" 
            :loading="loading"
          />
        </div>

        <!-- User Productivity Report -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">User Productivity</h3>
          </div>
          <UserProductivityTable 
            :data="reports.user_productivity" 
            :loading="loading"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import AccountPerformanceTable from '@/Components/Reports/AccountPerformanceTable.vue'
import ServiceDeliveryTable from '@/Components/Reports/ServiceDeliveryTable.vue'
import UserProductivityTable from '@/Components/Reports/UserProductivityTable.vue'
import {
  ArrowDownTrayIcon,
  CurrencyDollarIcon,
  ClockIcon,
  DocumentTextIcon,
  ChartBarIcon,
  ChartPieIcon,
  ArrowUpIcon,
  ArrowDownIcon
} from '@heroicons/vue/24/outline'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Reactive data
const loading = ref(false)
const accounts = ref([])
const kpis = ref({})
const reports = ref({
  account_performance: [],
  service_delivery: [],
  user_productivity: []
})

// Default to last 30 days
const thirtyDaysAgo = new Date()
thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)

const filters = ref({
  dateFrom: thirtyDaysAgo.toISOString().split('T')[0],
  dateTo: new Date().toISOString().split('T')[0],
  accountId: ''
})

// Methods
const loadAccounts = async () => {
  try {
    const response = await fetch('/api/accounts')
    const data = await response.json()
    accounts.value = data.data || []
  } catch (error) {
    console.error('Error loading accounts:', error)
  }
}

const loadReportData = async () => {
  loading.value = true
  try {
    // Load KPIs
    const kpiResponse = await fetch('/api/reports/kpis?' + new URLSearchParams(filters.value))
    const kpiData = await kpiResponse.json()
    kpis.value = kpiData.data || {}

    // Load detailed reports
    const reportsResponse = await fetch('/api/reports/detailed?' + new URLSearchParams(filters.value))
    const reportsData = await reportsResponse.json()
    reports.value = reportsData.data || {
      account_performance: [],
      service_delivery: [],
      user_productivity: []
    }
  } catch (error) {
    console.error('Error loading report data:', error)
    // Set default values for development
    kpis.value = {
      total_revenue: 125000,
      revenue_change: 12.5,
      total_hours: 2840,
      active_tickets: 18,
      utilization_rate: 82.5
    }
    reports.value = {
      account_performance: [],
      service_delivery: [],
      user_productivity: []
    }
  } finally {
    loading.value = false
  }
}

const applyPresetRange = (event) => {
  const range = event.target.value
  const today = new Date()
  let startDate, endDate

  switch (range) {
    case 'today':
      startDate = endDate = today
      break
    case 'yesterday':
      startDate = endDate = new Date(today)
      startDate.setDate(today.getDate() - 1)
      endDate.setDate(today.getDate() - 1)
      break
    case 'this_week':
      startDate = new Date(today)
      startDate.setDate(today.getDate() - today.getDay())
      endDate = today
      break
    case 'last_week':
      startDate = new Date(today)
      startDate.setDate(today.getDate() - today.getDay() - 7)
      endDate = new Date(today)
      endDate.setDate(today.getDate() - today.getDay() - 1)
      break
    case 'this_month':
      startDate = new Date(today.getFullYear(), today.getMonth(), 1)
      endDate = today
      break
    case 'last_month':
      startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1)
      endDate = new Date(today.getFullYear(), today.getMonth(), 0)
      break
    case 'this_quarter':
      const quarter = Math.floor(today.getMonth() / 3)
      startDate = new Date(today.getFullYear(), quarter * 3, 1)
      endDate = today
      break
    case 'this_year':
      startDate = new Date(today.getFullYear(), 0, 1)
      endDate = today
      break
    default:
      return
  }

  filters.value.dateFrom = startDate.toISOString().split('T')[0]
  filters.value.dateTo = endDate.toISOString().split('T')[0]
  
  // Reset the select
  event.target.value = ''
  
  loadReportData()
}

const exportReports = () => {
  const params = new URLSearchParams(filters.value)
  window.open(`/api/reports/export?${params}`, '_blank')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount || 0)
}

const formatHours = (hours) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 1
  }).format(hours || 0)
}

// Initialize data
onMounted(() => {
  loadAccounts()
  loadReportData()
})
</script>