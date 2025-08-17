<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine if the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'admin.read',
            'tickets.view',
            'tickets.view.account',
            'tickets.view.all'
        ]);
    }

    /**
     * Determine if the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Super Admin can view any ticket
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin with system-wide permissions
        if ($user->hasAnyPermission(['admin.read', 'tickets.view.all'])) {
            return true;
        }

        // User can view tickets from their account (including hierarchical access)
        if ($user->hasPermission('tickets.view.account') && $user->account) {
            // Check if user has hierarchical access (Account Manager)
            if ($user->hasPermission('accounts.hierarchy.access')) {
                // Check if ticket belongs to accessible accounts (own + children)
                $accessibleAccountIds = $user->account->getAccessibleAccountIds();
                return $accessibleAccountIds->contains($ticket->account_id);
            } else {
                // Regular account user - only their own account
                return $user->account_id === $ticket->account_id;
            }
        }

        // Ticket assigned to this user
        if ($ticket->agent_id === $user->id) {
            return true;
        }

        // Ticket created by this user
        if ($ticket->created_by_id === $user->id) {
            return true;
        }

        // Customer who created the ticket
        if ($ticket->customer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'admin.write',
            'tickets.create',
            'tickets.create.account'
        ]);
    }

    /**
     * Determine if the user can update the ticket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Super Admin can update any ticket
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin with system-wide permissions
        if ($user->hasAnyPermission(['admin.write', 'tickets.manage'])) {
            return true;
        }

        // User can edit tickets from their account (including hierarchical access)
        if ($user->hasPermission('tickets.edit.account') && $user->account) {
            // Check if user has hierarchical access (Account Manager)
            if ($user->hasPermission('accounts.hierarchy.access')) {
                // Check if ticket belongs to accessible accounts (own + children)
                $accessibleAccountIds = $user->account->getAccessibleAccountIds();
                return $accessibleAccountIds->contains($ticket->account_id);
            } else {
                // Regular account user - only their own account
                return $user->account_id === $ticket->account_id;
            }
        }

        // Ticket assigned to this user
        if ($ticket->agent_id === $user->id && $user->hasPermission('tickets.edit')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Only Super Admin and system admins can delete tickets
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission(['admin.write', 'tickets.delete']);
    }

    /**
     * Determine if the user can assign tickets.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        // Super Admin can assign any ticket
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin with system-wide permissions
        if ($user->hasAnyPermission(['admin.write', 'tickets.assign', 'tickets.manage'])) {
            return true;
        }

        // User can assign tickets within their account (including hierarchical access)
        if ($user->hasPermission('tickets.assign.account') && $user->account) {
            // Check if user has hierarchical access (Account Manager)
            if ($user->hasPermission('accounts.hierarchy.access')) {
                // Check if ticket belongs to accessible accounts (own + children)
                $accessibleAccountIds = $user->account->getAccessibleAccountIds();
                return $accessibleAccountIds->contains($ticket->account_id);
            } else {
                // Regular account user - only their own account
                return $user->account_id === $ticket->account_id;
            }
        }

        return false;
    }

    /**
     * Determine if the user can add comments to the ticket.
     */
    public function comment(User $user, Ticket $ticket): bool
    {
        // If user can view the ticket, they can comment
        return $this->view($user, $ticket);
    }

    /**
     * Determine if the user can add time entries to the ticket.
     */
    public function addTimeEntry(User $user, Ticket $ticket): bool
    {
        // Super Admin can add time to any ticket
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Ticket assigned to this user
        if ($ticket->agent_id === $user->id) {
            return true;
        }

        // User with time entry permissions for their account (including hierarchical access)
        if ($user->hasPermission('time.create.account') && $user->account) {
            // Check if user has hierarchical access (Account Manager)
            if ($user->hasPermission('accounts.hierarchy.access')) {
                // Check if ticket belongs to accessible accounts (own + children)
                $accessibleAccountIds = $user->account->getAccessibleAccountIds();
                return $accessibleAccountIds->contains($ticket->account_id);
            } else {
                // Regular account user - only their own account
                return $user->account_id === $ticket->account_id;
            }
        }

        return $user->hasAnyPermission(['admin.write', 'time.create']);
    }
}