<template>
  <div class="bg-white shadow rounded-lg">
    <!-- Header with filters -->
    <div class="border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Invoices</h3>
        <div class="flex items-center space-x-3">
          <!-- Status Filter -->
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
            <option value="cancelled">Cancelled</option>
          </select>

          <!-- Date Filter -->
          <input
            v-model="filters.dateFrom"
            @change="applyFilters"
            type="date"
            class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="From Date"
          />
          
          <button
            @click="resetFilters"
            class="text-sm text-gray-500 hover:text-gray-700"
          >
            Clear Filters
          </button>
        </div>
      </div>
    </div>

    <!-- TanStack Table -->
    <div class="overflow-hidden">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>
      <div v-else-if="filteredInvoices.length === 0" class="text-center py-12 text-gray-500">
        No invoices found.
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="header in table.getHeaderGroups()[0].headers"
              :key="header.id"
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
              @click="header.column.getToggleSortingHandler()?.($event)"
            >
              <div class="flex items-center space-x-1">
                <span>{{ header.renderHeader() }}</span>
                <span v-if="header.column.getCanSort()">
                  <ChevronUpDownIcon v-if="!header.column.getIsSorted()" class="h-4 w-4" />
                  <ChevronUpIcon v-else-if="header.column.getIsSorted() === 'asc'" class="h-4 w-4" />
                  <ChevronDownIcon v-else class="h-4 w-4" />
                </span>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            class="hover:bg-gray-50"
          >
            <td
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              class="px-6 py-4 whitespace-nowrap"
            >
              <component :is="cell.renderCell()" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.total > pagination.per_page" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
      <div class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="table.previousPage()"
            :disabled="!table.getCanPreviousPage()"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Previous
          </button>
          <button
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing
              {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }}
              to
              {{ Math.min((table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize, filteredInvoices.length) }}
              of
              {{ filteredInvoices.length }}
              results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button
                @click="table.previousPage()"
                :disabled="!table.getCanPreviousPage()"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              >
                Previous
              </button>
              <button
                @click="table.nextPage()"
                :disabled="!table.getCanNextPage()"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              >
                Next
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, h } from 'vue'
import { 
  useVueTable, 
  getCoreRowModel, 
  getSortedRowModel, 
  getPaginationRowModel,
  createColumnHelper 
} from '@tanstack/vue-table'
import { 
  ChevronUpDownIcon, 
  ChevronUpIcon, 
  ChevronDownIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  invoices: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['refresh', 'view-invoice', 'send-invoice', 'mark-paid'])

// Filters
const filters = ref({
  status: '',
  dateFrom: '',
  dateTo: ''
})

// Computed
const filteredInvoices = computed(() => {
  let result = props.invoices

  if (filters.value.status) {
    result = result.filter(invoice => invoice.status === filters.value.status)
  }

  if (filters.value.dateFrom) {
    result = result.filter(invoice => 
      new Date(invoice.date) >= new Date(filters.value.dateFrom)
    )
  }

  if (filters.value.dateTo) {
    result = result.filter(invoice => 
      new Date(invoice.date) <= new Date(filters.value.dateTo)
    )
  }

  return result
})

// Column helper
const columnHelper = createColumnHelper()

// Column definitions
const columns = [
  columnHelper.accessor('invoice_number', {
    header: 'Invoice',
    cell: info => h('div', { class: 'space-y-1' }, [
      h('div', { class: 'text-sm font-medium text-gray-900' }, info.getValue()),
      h('div', { class: 'text-sm text-gray-500' }, `ID: ${info.row.original.id.substring(0, 8)}...`)
    ])
  }),
  columnHelper.accessor('account', {
    header: 'Account',
    cell: info => h('div', { class: 'text-sm text-gray-900' }, info.getValue()?.name || 'N/A'),
    enableSorting: false
  }),
  columnHelper.accessor('total_amount', {
    header: 'Amount',
    cell: info => h('div', { class: 'space-y-1' }, [
      h('div', { class: 'text-sm font-medium text-gray-900' }, `$${formatCurrency(info.getValue())}`),
      h('div', { class: 'text-xs text-gray-500' }, `Due: $${formatCurrency(info.getValue() - (info.row.original.paid_amount || 0))}`)
    ])
  }),
  columnHelper.accessor('status', {
    header: 'Status',
    cell: info => h('span', {
      class: `inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadgeClass(info.getValue())}`
    }, formatStatus(info.getValue()))
  }),
  columnHelper.accessor('date', {
    header: 'Date',
    cell: info => h('div', { class: 'text-sm text-gray-900' }, formatDate(info.getValue()))
  }),
  columnHelper.accessor('due_date', {
    header: 'Due Date',
    cell: info => h('div', { class: 'text-sm text-gray-900' }, formatDate(info.getValue()))
  }),
  columnHelper.display({
    id: 'actions',
    header: 'Actions',
    cell: ({ row }) => {
      const invoice = row.original
      return h('div', { class: 'flex items-center space-x-2' }, [
        h('button', {
          onClick: () => emit('view-invoice', invoice),
          class: 'text-indigo-600 hover:text-indigo-900 text-sm'
        }, 'View'),
        invoice.status === 'draft' ? h('button', {
          onClick: () => emit('send-invoice', invoice),
          class: 'text-green-600 hover:text-green-900 text-sm'
        }, 'Send') : null,
        ['sent', 'overdue'].includes(invoice.status) ? h('button', {
          onClick: () => emit('mark-paid', invoice),
          class: 'text-blue-600 hover:text-blue-900 text-sm'
        }, 'Mark Paid') : null,
        h('a', {
          href: `/api/billing/invoices/${invoice.id}/pdf`,
          target: '_blank',
          class: 'text-gray-600 hover:text-gray-900 text-sm'
        }, 'PDF')
      ].filter(Boolean))
    }
  })
]

// Table instance
const table = useVueTable({
  get data() {
    return filteredInvoices.value
  },
  columns,
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  initialState: {
    pagination: {
      pageSize: 20
    }
  }
})

// Pagination computed
const pagination = computed(() => {
  const state = table.getState().pagination
  const totalRows = filteredInvoices.value.length
  return {
    total: totalRows,
    per_page: state.pageSize,
    from: state.pageIndex * state.pageSize + 1,
    to: Math.min((state.pageIndex + 1) * state.pageSize, totalRows)
  }
})

// Methods
const applyFilters = () => {
  emit('refresh')
}

const resetFilters = () => {
  filters.value = {
    status: '',
    dateFrom: '',
    dateTo: ''
  }
  emit('refresh')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getStatusBadgeClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>