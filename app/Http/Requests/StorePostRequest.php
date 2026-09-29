<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Services\PostAudio;
use App\Support\VideoUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'content' => ['nullable', 'string', $this->contentRequiredRule()],
            // Audio posts need an audio file on create.
            'content_type' => ['nullable', 'string', 'in:'.implode(',', Post::CONTENT_TYPES), $this->audioRequiredRule()],
            'video_url' => [
                'nullable',
                'string',
                'max:2048',
                'required_if:content_type,'.Post::CONTENT_TYPE_VIDEO,
                $this->embeddableVideoRule(),
            ],
            'audio' => ['nullable', 'file', 'mimes:'.implode(',', PostAudio::ALLOWED_EXTENSIONS), 'max:20480'],
            'audio_title' => ['nullable', 'string', 'max:255'],
            'series_id' => ['nullable', 'integer', Rule::exists('series', 'id')],
            'is_long_form' => ['nullable', 'boolean'],
            'series_part' => ['nullable', 'integer', 'min:1', 'max:100000', 'required_with:series_id', $this->uniqueSeriesPartRule()],
            'category' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'is_published' => ['boolean'],
            'tags' => ['nullable', 'string', 'max:1000', $this->tagListRule()],
        ];
    }

    /**
     * The written body is mandatory for text posts and optional as a short
     * description for video / audio posts.
     */
    private function contentRequiredRule(): RequiredIf
    {
        $type = $this->input('content_type') ?: Post::CONTENT_TYPE_TEXT;

        return new RequiredIf($type === Post::CONTENT_TYPE_TEXT, 'Vui lòng nhập nội dung.');
    }

    /**
     * An audio post needs an audio file.
     */
    private function audioRequiredRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->input('content_type') === Post::CONTENT_TYPE_AUDIO && ! $this->hasFile('audio')) {
                $fail('Bài viết audio cần tệp âm thanh (MP3).');
            }
        };
    }

    /**
     * Video posts may only embed players we support (YouTube / Vimeo).
     */
    private function embeddableVideoRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            // An empty value is reported by required_if instead.
            if (blank($value)) {
                return;
            }

            if (! VideoUrl::isEmbeddable((string) $value)) {
                $fail('Hiện chỉ hỗ trợ đường dẫn video từ YouTube hoặc Vimeo.');
            }
        };
    }

    /**
     * Each part number may be used only once inside the same series.
     */
    private function uniqueSeriesPartRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $seriesId = (int) $this->input('series_id');
            $part = (int) $value;

            if ($seriesId < 1 || $part < 1) {
                return;
            }

            $query = Post::query()
                ->where('series_id', $seriesId)
                ->where('series_part', $part);

            $current = $this->route('post');

            if ($current instanceof Post) {
                $query->where('id', '!=', $current->id);
            }

            if ($query->exists()) {
                $seriesTitle = Series::query()->whereKey($seriesId)->value('title');

                $fail("Phần {$part} đã tồn tại trong chuỗi bài viết \"{$seriesTitle}\".");
            }
        };
    }

    /**
     * Each comma separated tag must be short enough and a post may only carry
     * a limited number of tags.
     */
    private function tagListRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $names = Tag::parseNames((string) $value);

            if (count($names) > Tag::MAX_PER_POST) {
                $fail('Mỗi bài viết chỉ được gắn tối đa '.Tag::MAX_PER_POST.' tag.');

                return;
            }

            foreach ($names as $name) {
                if (mb_strlen($name) > Tag::MAX_NAME_LENGTH) {
                    $fail('Mỗi tag tối đa '.Tag::MAX_NAME_LENGTH.' ký tự: "'.$name.'".');

                    return;
                }
            }
        };
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'summary.required' => 'Vui lòng nhập tóm tắt.',
            'content.required' => 'Vui lòng nhập nội dung.',
            'category.max' => 'Tên danh mục tối đa 100 ký tự.',
            'image.file' => 'Ảnh đại diện phải là một tệp tin hợp lệ.',
            'image.image' => 'Tệp tải lên phải là ảnh.',
            'image.mimes' => 'Ảnh đại diện phải có định dạng JPG, PNG, WEBP hoặc GIF.',
            'image.max' => 'Kích thước ảnh đại diện tối đa là 4MB.',
            'image_alt.max' => 'Mô tả ảnh tối đa 255 ký tự.',
            'tags.max' => 'Danh sách tag quá dài.',
            'content.required_unless' => 'Vui lòng nhập nội dung.',
            'content.required_if' => 'Vui lòng nhập nội dung.',
            'content_type.in' => 'Loại nội dung không hợp lệ.',
            'video_url.required_if' => 'Bài viết video cần đường dẫn video (YouTube hoặc Vimeo).',
            'video_url.max' => 'Đường dẫn video quá dài.',
            'audio.file' => 'Tệp âm thanh phải là một tệp tin hợp lệ.',
            'audio.mimes' => 'Tệp âm thanh phải có định dạng MP3, M4A, WAV hoặc OGG.',
            'audio.max' => 'Kích thước tệp âm thanh tối đa là 20MB.',
            'audio_title.max' => 'Tiêu đề âm thanh tối đa 255 ký tự.',
            'series_title.max' => 'Tên chuỗi bài viết tối đa 150 ký tự.',
            'series_id.exists' => 'Chuỗi bài viết không tồn tại.',
            'series_part.required_with' => 'Chuỗi bài viết dài kỳ cần số thứ tự của phần.',
            'series_part.integer' => 'Số thứ tự phần phải là một số.',
            'series_part.min' => 'Số thứ tự phần phải lớn hơn 0.',
        ];
    }
}
