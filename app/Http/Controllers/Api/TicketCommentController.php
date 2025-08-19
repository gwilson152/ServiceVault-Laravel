<?php

namespace App\Http\Controllers\Api;

use App\Events\TicketCommentCreated;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Build query for comments
        $query = $ticket->comments()
            ->with('user:id,name,email')
            ->topLevel() // Only top-level comments for now (no threading)
            ->orderBy('created_at', 'asc');

        // If 'since' parameter is provided, only get comments after that timestamp
        if ($request->has('since') && $request->since) {
            $query->where('created_at', '>', $request->since);
        }

        $comments = $query->get();

        // Filter internal comments based on user permissions
        if (! $user->hasAnyPermission(['admin.read', 'tickets.manage', 'tickets.view.internal'])) {
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
                    'attachments' => $comment->attachments,
                    'user' => $comment->user ? [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'email' => $comment->user->email,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        // Check if user can comment on this ticket
        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Get attachment settings with defaults
        $maxFiles = Setting::getValue('tickets.attachments.max_files', 10);
        $maxFileSize = Setting::getValue('tickets.attachments.max_file_size_kb', 10240); // 10MB default

        // Use basic validation first, then we can add more restrictive rules later
        $validated = $request->validate([
            'content' => 'string|max:10000',
            'is_internal' => 'boolean',
            'parent_id' => 'nullable|exists:ticket_comments,id',
            'attachments' => "nullable|array|max:{$maxFiles}",
            'attachments.*' => "file|max:{$maxFileSize}",
        ]);

        // Ensure content is provided if no attachments
        if (empty($validated['content']) && (! isset($validated['attachments']) || empty($validated['attachments']))) {
            return response()->json([
                'message' => 'Either content or attachments must be provided.',
                'errors' => [
                    'content' => ['Content is required when no files are attached.'],
                ],
            ], 422);
        }

        // Verify parent comment belongs to same ticket if specified
        if (! empty($validated['parent_id'])) {
            $parentComment = TicketComment::find($validated['parent_id']);
            if (! $parentComment || $parentComment->ticket_id !== $ticket->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Invalid parent comment.',
                ]);
            }
        }

        // Handle file uploads if provided
        $attachmentMetadata = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = $file->getClientOriginalName();
                $path = $file->store('ticket-attachments/'.$ticket->id, 'public');

                $attachmentMetadata[] = [
                    'filename' => $filename,
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString(),
                ];
            }
        }

        // Create the comment
        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? false,
            'parent_id' => ! empty($validated['parent_id']) ? $validated['parent_id'] : null,
            'attachments' => count($attachmentMetadata) > 0 ? $attachmentMetadata : null,
        ]);

        $comment->load('user:id,name,email');

        // Broadcast the new comment to real-time listeners
        broadcast(new TicketCommentCreated($comment));

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'is_internal' => $comment->is_internal,
                'was_edited' => false,
                'created_at' => $comment->created_at,
                'edited_at' => null,
                'attachments' => $comment->attachments,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email,
                ],
            ],
            'message' => 'Comment added successfully.',
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
        if (! $comment->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this comment.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $comment->update([
            'content' => $validated['content'],
            'edited_at' => now(),
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
                    'email' => $comment->user->email,
                ],
            ],
            'message' => 'Comment updated successfully.',
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
        if (! $comment->canBeDeletedBy($user)) {
            return response()->json(['error' => 'Cannot delete this comment.'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
        ]);
    }
}
