<?php

namespace App\Http\Controllers\Api\Network;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Network\NetworkPost;
use App\Models\Network\NetworkPostComment;
use App\Models\Network\NetworkPostReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends BaseApiController
{
    protected string $cacheTag = 'network_posts';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $posts = NetworkPost::with(['author:id,name'])
            ->where('visibility', 'public')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'body' => 'required|string',
            'type' => 'nullable|string|in:text,image,link,video',
            'visibility' => 'nullable|string|in:public,connections,private',
            'attachment' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,mov',
        ]);

        if ($error) {
            return $error;
        }

        $attachmentData = [];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentData = [
                'attachment_path' => $file->store('network/attachments', 'local'),
                'attachment_name' => $file->getClientOriginalName(),
                'attachment_mime' => $file->getMimeType(),
                'attachment_size' => $file->getSize(),
            ];
        }

        $post = NetworkPost::create(array_merge([
            'user_id' => $request->user()->id,
            'body' => $request->input('body'),
            'type' => $request->input('type', 'text'),
            'visibility' => $request->input('visibility', 'public'),
            'likes_count' => 0,
            'comments_count' => 0,
        ], $attachmentData));

        $this->cacheFlush();

        return $this->respondCreated($post);
    }

    public function removeAttachment(Request $request, int $id): JsonResponse
    {
        $post = NetworkPost::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$post) {
            return $this->respondNotFound('Post not found or you do not have permission to edit it');
        }

        if ($post->attachment_path) {
            Storage::disk('local')->delete($post->attachment_path);
            $post->update([
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_mime' => null,
                'attachment_size' => null,
            ]);
            $this->cacheFlush();
        }

        return $this->respondSuccess('Attachment removed', $post->fresh());
    }

    public function show(int $id): JsonResponse
    {
        $post = NetworkPost::with(['author:id,name', 'comments.author:id,name'])
            ->find($id);

        if (!$post) {
            return $this->respondNotFound('Post not found');
        }

        return $this->respond(['success' => true, 'data' => $post]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = NetworkPost::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$post) {
            return $this->respondNotFound('Post not found or you do not have permission to edit it');
        }

        $error = $this->validate($request->all(), [
            'body' => 'required|string',
        ]);

        if ($error) {
            return $error;
        }

        $post->update(['body' => $request->input('body')]);
        $this->cacheFlush();

        return $this->respondSuccess('Post updated successfully', $post);
    }

    public function destroy(int $id): JsonResponse
    {
        $post = NetworkPost::where('id', $id)
            ->where('user_id', request()->user()->id)
            ->first();

        if (!$post) {
            return $this->respondNotFound('Post not found or you do not have permission to delete it');
        }

        if ($post->attachment_path) {
            Storage::disk('local')->delete($post->attachment_path);
        }

        $post->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Post deleted successfully');
    }

    public function react(Request $request, int $id): JsonResponse
    {
        $post = NetworkPost::find($id);

        if (!$post) {
            return $this->respondNotFound('Post not found');
        }

        $error = $this->validate($request->all(), [
            'type' => 'required|string|in:like,love,laugh,sad,angry',
        ]);

        if ($error) {
            return $error;
        }

        $existingReaction = NetworkPostReaction::where('post_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $request->input('type')) {
                // Remove reaction (toggle off)
                $existingReaction->delete();
                $post->decrement('likes_count');
                return $this->respondSuccess('Reaction removed');
            }
            // Update reaction type
            $existingReaction->update(['type' => $request->input('type')]);
            return $this->respondSuccess('Reaction updated', $existingReaction);
        }

        // New reaction
        $reaction = NetworkPostReaction::create([
            'post_id' => $id,
            'user_id' => $request->user()->id,
            'type' => $request->input('type'),
        ]);

        $post->increment('likes_count');

        return $this->respondSuccess('Reaction added', $reaction);
    }

    public function addComment(Request $request, int $id): JsonResponse
    {
        $post = NetworkPost::find($id);

        if (!$post) {
            return $this->respondNotFound('Post not found');
        }

        $error = $this->validate($request->all(), [
            'body' => 'required|string',
            'parent_id' => 'nullable|exists:network_post_comments,id',
        ]);

        if ($error) {
            return $error;
        }

        $comment = NetworkPostComment::create([
            'post_id' => $id,
            'user_id' => $request->user()->id,
            'body' => $request->input('body'),
            'parent_id' => $request->input('parent_id'),
        ]);

        $post->increment('comments_count');

        return $this->respondCreated($comment);
    }

    public function deleteComment(Request $request, int $postId, int $commentId): JsonResponse
    {
        $comment = NetworkPostComment::where('id', $commentId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$comment) {
            return $this->respondNotFound('Comment not found or you do not have permission to delete it');
        }

        $comment->delete();

        $post = NetworkPost::find($postId);
        if ($post && $post->comments_count > 0) {
            $post->decrement('comments_count');
        }

        return $this->respondSuccess('Comment deleted successfully');
    }
}
