import { ref, computed } from 'vue'
import {
  getCoreRowModel,
  getFilteredRowModel,
  getSortedRowModel,
  getPaginationRowModel,
  createColumnHelper,
} from '@tanstack/table-core'
import { useVueTable } from '@tanstack/vue-table'
import { useColumnVisibility } from './useColumnVisibility'

export function useTicketsTable(tickets, user, canViewAllAccounts) {
  const columnHelper = createColumnHelper()
  
  // Table state with default sort by created_at descending
  const sorting = ref([{ id: 'created_at', desc: true }])
  const globalFilter = ref('')
  const columnFilters = ref([])
  const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
  })

  // Column visibility management with defaults
  const defaultVisibility = {
    updated_at: false  // Hide updated column by default
  }
  
  const { 
    columnVisibility, 
    toggleColumn, 
    resetVisibility, 
    isColumnVisible 
  } = useColumnVisibility('tickets', user.value, defaultVisibility)

  // Available columns metadata
  const availableColumns = computed(() => {
    const cols = [
      { id: 'ticket_number', label: 'Ticket Details', required: true },
      { id: 'actions', label: 'Actions', required: true },
      { id: 'created_at', label: 'Created Date', required: false },
      { id: 'due_date', label: 'Due Date', required: false },
      { id: 'category', label: 'Category', required: false },
      { id: 'assigned_agent', label: 'Assigned Agent', required: false },
      { id: 'updated_at', label: 'Updated', required: false, defaultVisible: false }
    ]

    if (canViewAllAccounts) {
      cols.splice(1, 0, { id: 'account', label: 'Account/Customer', required: false })
    }

    return cols
  })

  // Define columns
  const columns = computed(() => {
    const cols = [
      columnHelper.accessor('ticket_number', {
        header: 'Ticket Details',
        cell: info => info.getValue(),
        enableSorting: true,
        // Main content column - no fixed width, will expand to fill space
      }),
    ]

    // Add account column if service provider and visible
    if (canViewAllAccounts && isColumnVisible.value('account')) {
      cols.push(
        columnHelper.accessor(row => row.account?.name || '', {
          id: 'account',
          header: 'Account/Customer',
          enableSorting: true,
          size: 200, // Fixed width for account column
          minSize: 150,
          maxSize: 250,
        })
      )
    }

    // Add created date column if visible
    if (isColumnVisible.value('created_at')) {
      cols.push(
        columnHelper.accessor('created_at', {
          header: 'Created Date',
          cell: info => info.getValue(),
          enableSorting: true,
          size: 110,
          minSize: 90,
          maxSize: 130,
        })
      )
    }

    // Add due date column if visible
    if (isColumnVisible.value('due_date')) {
      cols.push(
        columnHelper.accessor('due_date', {
          header: 'Due Date',
          cell: info => info.getValue(),
          enableSorting: true,
          size: 110,
          minSize: 90,
          maxSize: 130,
        })
      )
    }

    // Add category column if visible
    if (isColumnVisible.value('category')) {
      cols.push(
        columnHelper.accessor(row => row.category?.name || 'Uncategorized', {
          id: 'category',
          header: 'Category',
          enableSorting: true,
          size: 120,
          minSize: 100,
          maxSize: 150,
        })
      )
    }

    // Add assigned agent column if visible
    if (isColumnVisible.value('assigned_agent')) {
      cols.push(
        columnHelper.accessor(row => row.assigned_to?.name || 'Unassigned', {
          id: 'assigned_agent',
          header: 'Assigned Agent',
          enableSorting: true,
          size: 140,
          minSize: 120,
          maxSize: 180,
        })
      )
    }

    // Add actions column (always visible)
    cols.push(
      columnHelper.display({
        id: 'actions',
        header: 'Actions',
        size: 120, // Fixed width for actions
        minSize: 100,
        maxSize: 150,
      })
    )

    // Add updated column if visible (hidden by default)
    if (isColumnVisible.value('updated_at')) {
      cols.push(
        columnHelper.accessor('updated_at', {
          header: 'Updated',
          cell: info => info.getValue(),
          enableSorting: true,
          size: 100, // Fixed width for date
          minSize: 80,
          maxSize: 120,
        })
      )
    }

    return cols
  })

  // Create table instance
  const table = useVueTable({
    get data() {
      return tickets.value || []
    },
    get columns() {
      return columns.value
    },
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    state: {
      get sorting() {
        return sorting.value
      },
      get globalFilter() {
        return globalFilter.value
      },
      get columnFilters() {
        return columnFilters.value
      },
      get pagination() {
        return pagination.value
      },
    },
    onSortingChange: updaterOrValue => {
      sorting.value = typeof updaterOrValue === 'function'
        ? updaterOrValue(sorting.value)
        : updaterOrValue
    },
    onGlobalFilterChange: updaterOrValue => {
      globalFilter.value = typeof updaterOrValue === 'function'
        ? updaterOrValue(globalFilter.value)
        : updaterOrValue
    },
    onColumnFiltersChange: updaterOrValue => {
      columnFilters.value = typeof updaterOrValue === 'function'
        ? updaterOrValue(columnFilters.value)
        : updaterOrValue
    },
    onPaginationChange: updaterOrValue => {
      pagination.value = typeof updaterOrValue === 'function'
        ? updaterOrValue(pagination.value)
        : updaterOrValue
    },
    globalFilterFn: 'includesString',
  })

  // Helper functions for filtering
  // Note: Status and priority filtering now handled through global search
  // since they are combined into the ticket_number column display

  const setAssignmentFilter = (assignment, userId) => {
    if (assignment === 'mine') {
      table.setColumnFilters([
        ...columnFilters.value.filter(f => f.id !== 'account'),
        { 
          id: 'account', 
          value: user.value?.name,
        }
      ])
    } else if (assignment === 'unassigned') {
      table.setColumnFilters([
        ...columnFilters.value.filter(f => f.id !== 'account'),
        { 
          id: 'account', 
          value: 'Unassigned',
        }
      ])
    } else {
      table.setColumnFilters(columnFilters.value.filter(f => f.id !== 'account'))
    }
  }

  const setAccountFilter = (accountId) => {
    if (accountId) {
      // Find account name from the ticket data
      const accountName = tickets.value.find(t => t.account_id === parseInt(accountId))?.account?.name
      if (accountName) {
        table.setColumnFilters([
          ...columnFilters.value.filter(f => f.id !== 'account'),
          { id: 'account', value: accountName }
        ])
      }
    } else {
      table.setColumnFilters(columnFilters.value.filter(f => f.id !== 'account'))
    }
  }

  const clearAllFilters = () => {
    table.resetGlobalFilter()
    table.resetColumnFilters()
  }

  return {
    table,
    sorting,
    globalFilter,
    columnFilters,
    pagination,
    setAssignmentFilter,
    setAccountFilter,
    clearAllFilters,
    // Column visibility
    availableColumns,
    columnVisibility,
    toggleColumn,
    resetVisibility,
    isColumnVisible
  }
}