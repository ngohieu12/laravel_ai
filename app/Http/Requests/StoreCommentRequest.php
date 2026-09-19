<?php

namespace App\Http\Requests;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:2000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $post = $this->route('post');
            if (! $post instanceof Post) {
                return;
            }

            $parentId = $this->input('parent_id');
            if ($parentId === null) {
                return;
            }

            $parent = Comment::find($parentId);
            if (! $parent) {
                $validator->errors()->add('parent_id', 'Bình luận gốc không tồn tại.');

                return;
            }

            if ($parent->post_id !== $post->id) {
                $validator->errors()->add('parent_id', 'Bình luận gốc không thuộc bài viết này.');

                return;
            }

            // Compute depth by walking parents.
            $depth = 0;
            $node = $parent;
            while ($node->parent_id !== null) {
                $depth++;
                if ($depth > Comment::MAX_DEPTH + 1) {
                    break;
                }
                $node = Comment::find($node->parent_id);
                if (! $node) {
                    break;
                }
            }

            if ($depth >= Comment::MAX_DEPTH) {
                $validator->errors()->add('parent_id', 'Đã đạt giới hạn mức lồng bình luận.');
            }
        });
    }
}
