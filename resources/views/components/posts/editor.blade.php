{{--
    Trình soạn thảo rich text cho nội dung bài viết.

    Quill render nội dung; một <textarea name="content"> ẩn đi vẫn giữ giá trị
    HTML để form submit đúng như trước và để server validate được. Nếu thư viện
    không tải được (mạng chặn CDN) thì textarea hiện nguyên để nhập HTML thuần —
    bài viết vẫn soạn được.

    Props:
      name        tên field (mặc định: content)
      value       HTML khởi tạo
      placeholder gợi ý hiển thị
      label       nhãn ở trên (tuỳ chọn)
--}}

@props([
    'name' => 'content',
    'value' => '',
    'placeholder' => 'Viết nội dung bài viết của bạn ở đây...',
    'label' => null,
])

<div data-rich-editor data-editor-name="{{ $name }}">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }} <span data-editor-required-mark class="text-red-500">*</span>
        </label>
    @endif

    <div data-editor-frame class="editor-frame rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-slate-400 focus-within:border-slate-400">
        <div data-editor-canvas></div>

        <textarea id="{{ $name }}" name="{{ $name }}" rows="15" required data-mention-input
            class="w-full px-4 py-3 font-mono text-sm focus:outline-none focus:ring-0 border-0"
            placeholder="{{ $placeholder }}">{{ $value }}</textarea>
    </div>

    <p class="text-xs text-gray-500 mt-1">
        Gõ <span class="font-mono">@</span> để nhắc tên một thành viên — họ sẽ nhận được thông báo.
    </p>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
    <script>
        // Rich text editor (Quill) cho ô nội dung bài viết.
        //
        // - Thanh công cụ: tiêu đề, đậm / nghiêng / gạch chân / gạch ngang, trích
        //   dẫn, khối code, danh sách, thụt lề, căn lề, liên kết, ảnh, xoá định dạng.
        // - HTML luôn được đồng bộ về <textarea name="content"> khi submit.
        // - Nếu Quill không tải được thì textarea hiện nguyên để nhập HTML thuần.
        (function () {
            const ENDPOINT = {!! json_encode(route('mentions.index'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
            const MAX_QUERY = 30;

            const LABELS = {
                bold: 'In đậm',
                italic: 'In nghiêng',
                underline: 'Gạch chân',
                strike: 'Gạch ngang',
                blockquote: 'Trích dẫn',
                'code-block': 'Khối mã',
                link: 'Chèn liên kết',
                image: 'Chèn ảnh (bằng URL)',
                clean: 'Xoá định dạng',
            };

            const CONTROLS = [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ indent: '-1' }, { indent: '+1' }],
                [{ align: [] }],
                ['link', 'image'],
                ['clean'],
            ];

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-rich-editor]').forEach(mount);
            });

            function mount(wrapper) {
                if (wrapper.dataset.editorMounted === '1') return;
                wrapper.dataset.editorMounted = '1';

                const name = wrapper.dataset.editorName || 'content';
                const textarea = wrapper.querySelector('textarea[name="' + name + '"]');
                const canvas = wrapper.querySelector('[data-editor-canvas]');
                const frame = wrapper.querySelector('[data-editor-frame]');
                const mark = wrapper.querySelector('[data-editor-required-mark]');

                if (!textarea) return;

                const form = textarea.closest('form');
                const hasQuill = !!canvas && typeof window.Quill !== 'undefined';

                // Bài video / audio không bắt buộc có nội dung chữ.
                function isRequired() {
                    const checked = document.querySelector('input[name="content_type"]:checked');
                    return !checked || checked.value === 'text';
                }

                if (!hasQuill) {
                    // Không có Quill: giữ nguyên ô nhập HTML thuần.
                    document.querySelectorAll('input[name="content_type"]').forEach(function (radio) {
                        radio.addEventListener('change', function () {
                            textarea.required = isRequired();
                        });
                    });

                    return;
                }

                const quill = new window.Quill(canvas, {
                    theme: 'snow',
                    placeholder: textarea.getAttribute('placeholder') || '',
                    modules: {
                        toolbar: {
                            container: CONTROLS,
                            handlers: { image: insertImageByUrl },
                        },
                        history: { delay: 800, maxStack: 200, userOnly: true },
                    },
                });

                // Nội dung cũ có thể do soạn thảo HTML tay tạo ra: nhét vào để
                // Quill chuẩn hoá lại thay vì giữ nguyên.
                quill.clipboard.dangerouslyPasteHTML(textarea.value || '', 'silent');

                textarea.classList.add('hidden');
                frame.classList.add('editor-ready');
                localizeToolbar(frame);

                function sync() {
                    textarea.value = quill.getText().trim() === '' ? '' : quill.getSemanticHTML();
                }

                quill.on('text-change', sync);

                // Textarea đang ẩn nên không dùng được required của HTML5 (trình
                // duyệt không focus được) — thay bằng kiểm tra lúc submit.
                function syncRequired() {
                    const required = isRequired();

                    textarea.required = false;

                    if (mark) mark.classList.toggle('hidden', !required);
                    quill.root.setAttribute('aria-required', required ? 'true' : 'false');
                    frame.classList.toggle('editor-compact', !required);
                }

                if (form) {
                    form.addEventListener('submit', sync);

                    form.addEventListener('submit', function (event) {
                        if (!isRequired() || quill.getText().trim() !== '') return;

                        event.preventDefault();
                        quill.focus();
                        showError(wrapper, 'Vui lòng nhập nội dung bài viết.');
                    });
                }

                document.querySelectorAll('input[name="content_type"]').forEach(function (radio) {
                    radio.addEventListener('change', syncRequired);
                });

                syncRequired();

                setupMentions(quill);
            }

            /** Chèn ảnh bằng URL — không có API upload nội dung nên hỏi URL. */
            function insertImageByUrl(range) {
                if (range === null) return;

                const url = window.prompt('Chèn ảnh — nhập URL (https://…):', 'https://');

                if (url === null) return;

                if (!/^https?:\/\/.+/i.test(url.trim())) {
                    window.alert('URL ảnh phải bắt đầu bằng http:// hoặc https://');
                    return;
                }

                this.quill.insertEmbed(range.index, 'image', url.trim(), 'user');
                this.quill.setSelection(range.index + 1, 0, 'silent');
            }

            /** Nhãn tiếng Việt cho nút / lựa chọn do Quill dựng sẵn. */
            function localizeToolbar(container) {
                if (!container) return;

                container.querySelectorAll('button').forEach(function (button) {
                    const format = Array.from(button.classList).find(function (name) {
                        return name.indexOf('ql-') === 0;
                    });

                    if (!format) return;

                    const label = LABELS[format.slice(3)];

                    if (label) {
                        button.setAttribute('aria-label', label);
                        button.setAttribute('title', label);
                    }
                });

                const header = container.querySelector('select.ql-header');

                if (header) {
                    const names = { 1: 'Tiêu đề 1', 2: 'Tiêu đề 2', 3: 'Tiêu đề 3', '': 'Đoạn văn' };

                    Array.from(header.options).forEach(function (option) {
                        if (names[option.value] !== undefined) {
                            option.textContent = names[option.value];
                        }
                    });
                }

                const align = container.querySelector('select.ql-align');

                if (align) {
                    const names = { '': 'Căn trái', center: 'Căn giữa', right: 'Căn phải', justify: 'Căn đều' };

                    Array.from(align.options).forEach(function (option) {
                        if (names[option.value] !== undefined) {
                            option.textContent = names[option.value];
                        }
                    });
                }
            }

            function showError(host, message) {
                let error = host.querySelector('[data-editor-error]');

                if (!error) {
                    error = document.createElement('p');
                    error.dataset.editorError = '1';
                    error.className = 'text-sm text-red-600 mt-1';
                    host.appendChild(error);
                }

                error.textContent = message;
            }

            /**
             * Gợi ý @mention bên trong editor, dùng chung endpoint với ô bình luận.
             *
             * Quill không kèm sẵn module mention nên phần "đang gõ @..." được đọc
             * từ ký tự trước con trỏ, rồi chèn ngược lại bằng API của Quill.
             */
            function setupMentions(quill) {
                const dropdown = document.createElement('div');
                dropdown.className = 'mention-dropdown hidden fixed z-50 w-72 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg';
                dropdown.setAttribute('role', 'listbox');
                document.body.appendChild(dropdown);

                let items = [];
                let active = 0;
                let timer = null;
                let lastQuery = null;

                function activeQuery() {
                    const range = quill.getSelection();

                    if (!range) return null;

                    const upto = quill.getText(0, range.index);
                    const match = upto.match(/(?:^|\s)@([A-Za-z0-9_-]*)$/);

                    if (!match) return null;

                    return {
                        fragment: match[1],
                        start: range.index - match[1].length - 1,
                        end: range.index,
                    };
                }

                function place() {
                    const query = activeQuery();

                    if (!query) return;

                    const bounds = quill.getBounds(query.start);

                    if (!bounds) return;

                    dropdown.style.left = Math.max(8, Math.min(bounds.left, window.innerWidth - 304)) + 'px';
                    dropdown.style.top = Math.min(bounds.bottom + 6, window.innerHeight - 280) + 'px';
                }

                function close() {
                    dropdown.classList.add('hidden');
                    items = [];
                    lastQuery = null;
                }

                function highlight() {
                    dropdown.querySelectorAll('.mention-option').forEach(function (row, index) {
                        row.classList.toggle('bg-slate-100', index === active);
                    });
                }

                function select(index) {
                    const item = items[index];
                    const query = activeQuery();

                    if (!item || !query) return close();

                    quill.deleteText(query.start, query.end - query.start, 'user');
                    quill.insertText(query.start, '@' + item.username + ' ', 'user');
                    quill.setSelection(query.start + item.username.length + 2, 0, 'silent');
                    close();
                }

                function render(data) {
                    items = data;
                    active = 0;
                    dropdown.innerHTML = '';

                    if (!items.length) return close();

                    items.forEach(function (item, index) {
                        const row = document.createElement('button');
                        row.type = 'button';
                        row.className = 'mention-option flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-50';
                        row.innerHTML =
                            '<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600"></span>' +
                            '<span class="min-w-0"><span class="block truncate text-sm text-slate-800"></span>' +
                            '<span class="block truncate text-xs text-slate-400"></span></span>';
                        row.querySelector('span').textContent = (item.name || '?').trim().charAt(0).toUpperCase();
                        row.querySelectorAll('span')[1].firstElementChild.textContent = item.name;
                        row.querySelectorAll('span')[1].lastElementChild.textContent = '@' + item.username + ' · ' + item.role;
                        row.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            select(index);
                        });
                        dropdown.appendChild(row);
                    });

                    dropdown.classList.remove('hidden');
                    place();
                    highlight();
                }

                function search(fragment) {
                    if (fragment === lastQuery) return;
                    lastQuery = fragment;

                    fetch(ENDPOINT + '?q=' + encodeURIComponent(fragment), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    })
                        .then(function (response) { return response.ok ? response.json() : null; })
                        .then(function (payload) { render(payload && payload.data ? payload.data : []); })
                        .catch(close);
                }

                function sync() {
                    const query = activeQuery();

                    if (!query || query.fragment.length > MAX_QUERY) return close();

                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () { search(query.fragment); }, 150);
                }

                quill.on('text-change', sync);
                quill.on('selection-change', sync);
                quill.root.addEventListener('blur', function () { window.setTimeout(close, 150); });

                quill.root.addEventListener('keydown', function (event) {
                    if (dropdown.classList.contains('hidden') || !items.length) {
                        if (event.key === 'Escape') close();
                        return;
                    }

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        active = (active + 1) % items.length;
                        highlight();
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        active = (active - 1 + items.length) % items.length;
                        highlight();
                    } else if (event.key === 'Enter' || event.key === 'Tab') {
                        event.preventDefault();
                        select(active);
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        close();
                    }
                });
            }
        })();
    </script>
@endpush