{{--
    Comma separated tag field for the post create / edit forms, with
    clickable suggestions of existing tags.

    @param  string  $value  Current value ("laravel, php")
    @param  \Illuminate\Support\Collection<int, string>|array<int, string>  $suggestions  Existing tag names
--}}
@php
    $tagSuggestionList = collect($suggestions ?? [])->values();
    $tagMaxPerPost = \App\Models\Tag::MAX_PER_POST;
@endphp
<div data-tag-input>
    <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tag</label>
    <input type="text" id="tags" name="tags" value="{{ $value ?? '' }}" maxlength="1000" autocomplete="off"
        class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
        placeholder="VD: laravel, php, trí tuệ nhân tạo">
    @error('tags')
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
    <p class="text-xs text-gray-500 mt-1">
        Nhiều tag cách nhau bằng dấu phẩy, tối đa {{ $tagMaxPerPost }} tag. Tag chưa có sẽ được tạo tự động.
    </p>

    @if($tagSuggestionList->isNotEmpty())
        <div class="mt-2 flex flex-wrap gap-1.5 items-center max-h-24 overflow-y-auto">
            <span class="text-xs text-gray-500 mr-1">Tag có sẵn:</span>
            @foreach($tagSuggestionList as $suggestion)
                <button type="button" data-tag-suggestion="{{ $suggestion }}"
                    class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                    #{{ $suggestion }}
                </button>
            @endforeach
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            (function () {
                document.querySelectorAll('[data-tag-input]').forEach(function (wrapper) {
                    var input = wrapper.querySelector('input[name="tags"]');
                    if (!input) return;

                    var normalize = function (name) {
                        return name.trim().replace(/^#/, '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
                    };

                    var currentTags = function () {
                        return input.value.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
                    };

                    var refresh = function () {
                        var selected = currentTags().map(normalize);
                        wrapper.querySelectorAll('[data-tag-suggestion]').forEach(function (button) {
                            var active = selected.indexOf(normalize(button.dataset.tagSuggestion)) !== -1;
                            button.classList.toggle('bg-indigo-600', active);
                            button.classList.toggle('text-white', active);
                            button.classList.toggle('bg-indigo-50', !active);
                            button.classList.toggle('text-indigo-700', !active);
                        });
                    };

                    wrapper.querySelectorAll('[data-tag-suggestion]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            var tags = currentTags();
                            var key = normalize(button.dataset.tagSuggestion);
                            var index = tags.map(normalize).indexOf(key);

                            if (index === -1) {
                                tags.push(button.dataset.tagSuggestion);
                            } else {
                                tags.splice(index, 1);
                            }

                            input.value = tags.join(', ');
                            refresh();
                        });
                    });

                    input.addEventListener('input', refresh);
                    refresh();
                });
            })();
        </script>
    @endpush
@endonce
