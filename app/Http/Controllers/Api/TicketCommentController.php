<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TicketCommentController extends Controller
{
    /**
     * Display a listing of comments for a specific ticket
     */
    public function index(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can view this ticket
        if (!$ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        // Get comments with user information
        $comments = $ticket->comments()
            ->with('user:id,name,email')
            ->topLevel() // Only top-level comments for now (no threading)
            ->get();
        
        // Filter internal comments based on user permissions
        if (!$user->hasAnyPermission(['admin.read', 'tickets.manage', 'tickets.view.internal'])) {
            $comments = $comments->where('is_internal', false);
        }
        
        return response()->json([
            'data' => $comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'is_internal' => $comment->is_internal,
                    'was_edited' => $comment->wasEdited(),
                    'created_at' => $comment->created_at,
                    'edited_at' => $comment->edited_at,
                    'user' => $comment->user ? [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'email' => $comment->user->email
                    ] : null,
                ];
            })
        ]);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can comment on this ticket
        if (!$ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'is_internal' => 'boolean',
            'parent_id' => 'nullable|exists:ticket_comments,id'
        ]);
        
        // Verify parent comment belongs to same ticket if specified
        if ($validated['parent_id']) {
            $parentComment = TicketComment::find($validated['parent_id']);
            if (!$parentComment || $parentComment->ticket_id !== $ticket->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Invalid parent comment.'
                ]);
            }
        }
        
        // Create the comment
        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? false,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);
        
        $comment->load('user:id,name,email');
        
        return response()->json([
            'data' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'is_internal' => $comment->is_internal,
                'was_edited' => false,
                'created_at' => $comment->created_at,
                'edited_at' => null,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email
                ],
            ],
            'message' => 'Comment added successfully.'
        ], 201);
    }

    /**
     * Update an existing comment
     */
    public function update(Request $request, Ticket $ticket, TicketComment $comment): JsonResponse
    {
        $user = $request->user();
        
        // Verify comment belongs to this ticket
        if ($comment->ticket_id !== $ticket->id) {
            return response()->json(['error' => 'Comment not found.'], 404);
        }
        
        // Check if user can edit this comment
        if (!$comment->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this comment.'], 403);
        }
        
        $validated = $request->validate([
            'content' => 'required|string|max:10000'
        ]);
        
        $comment->update([
            'content' => $validated['content'],
            'edited_at' => now()
        ]);
        
        $comment->load('user:id,name,email');
        
        return response()->json([
            'data' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'is_internal' => $comment->is_internal,
                'was_edited' => true,
                'created_at' => $comment->created_at,
                'edited_at' => $comment->edited_at,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email
                ],
            ],
            'message' => 'Comment updated successfully.'
        ]);
    }

    /**
     * Delete a comment
     */
    public function destroy(Request $request, Ticket $ticket, TicketComment $comment): JsonResponse
    {
        $user = $request->user();
        
        // Verify comment belongs to this ticket
        if ($comment->ticket_id !== $ticket->id) {
            return response()->json(['error' => 'Comment not found.'], 404);
        }
        
        // Check if user can delete this comment
        if (!$comment->canBeDeletedBy($user)) {
            return response()->json(['error' => 'Cannot delete this comment.'], 403);
        }
        
        $comment->delete();
        
        return response()->json([
            'message' => 'Comment deleted successfully.'
        ]);
    }
}
