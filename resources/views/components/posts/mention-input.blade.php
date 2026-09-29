{{--
    Wires an @mention autocomplete into a textarea.

    Type "@" followed by a few letters to get matching accounts; ↑/↓ to move,
    Enter/Tab to insert, Esc to dismiss. Include this once per page: it wires
    every textarea carrying `data-mention-input`, so the comment box, the reply
    forms and the post editor all share the same dropdown.
--}}

@push('scripts')
    <script>
            // @mention autocomplete: one delegated handler for every textarea
            // that opts in with data-mention-input.
            (function () {
                const ENDPOINT = {!! json_encode(route('mentions.index'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
                const MAX_QUERY = 30;

                // Text before the caret that ends with an at-sign plus a
                // fragment means a mention is being typed. The fragment may
                // not contain whitespace.
                function activeQuery(textarea) {
                    const upto = textarea.value.slice(0, textarea.selectionStart);
                    const match = upto.match(/(?:^|\s)@([A-Za-z0-9_-]*)$/);

                    if (!match) {
                        return null;
                    }

                    return { fragment: match[1], start: upto.length - match[1].length - 1 };
                }

                function buildDropdown(textarea) {
                    const dropdown = document.createElement('div');
                    dropdown.className = 'mention-dropdown hidden absolute z-50 mt-1 w-72 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg';
                    dropdown.setAttribute('role', 'listbox');

                    const anchor = textarea.closest('[data-mention-root]') || textarea.parentElement;
                    if (getComputedStyle(anchor).position === 'static') {
                        anchor.style.position = 'relative';
                    }
                    anchor.appendChild(dropdown);

                    return dropdown;
                }

                function setup(textarea) {
                    if (textarea.dataset.mentionReady === '1') return;
                    textarea.dataset.mentionReady = '1';

                    const dropdown = buildDropdown(textarea);
                    let items = [];
                    let active = 0;
                    let timer = null;
                    let lastQuery = null;

                    function close() {
                        dropdown.classList.add('hidden');
                        items = [];
                        lastQuery = null;
                    }

                    function select(index) {
                        const item = items[index];
                        if (!item) return;

                        const query = activeQuery(textarea);
                        if (!query) return close();

                        const before = textarea.value.slice(0, query.start);
                        const after = textarea.value.slice(textarea.selectionStart);
                        textarea.value = before + '@' + item.username + ' ' + after;

                        const caret = before.length + item.username.length + 2;
                        textarea.setSelectionRange(caret, caret);
                        textarea.focus();
                        close();
                    }

                    function render(data) {
                        items = data;
                        active = 0;
                        dropdown.innerHTML = '';

                        if (!items.length) {
                            return close();
                        }

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
                        highlight();
                    }

                    function highlight() {
                        dropdown.querySelectorAll('.mention-option').forEach(function (row, index) {
                            row.classList.toggle('bg-slate-100', index === active);
                        });
                    }

                    function search(fragment) {
                        if (fragment === lastQuery) return;
                        lastQuery = fragment;

                        fetch(ENDPOINT + '?q=' + encodeURIComponent(fragment), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        })
                            .then(function (response) { return response.ok ? response.json() : null; })
                            .then(function (payload) { render(payload && payload.data ? payload.data : []); })
                            .catch(function () { close(); });
                    }

                    function sync() {
                        const query = activeQuery(textarea);

                        if (!query || query.fragment.length > MAX_QUERY) {
                            return close();
                        }

                        window.clearTimeout(timer);
                        timer = window.setTimeout(function () { search(query.fragment); }, 150);
                    }

                    textarea.addEventListener('input', sync);
                    textarea.addEventListener('click', sync);
                    textarea.addEventListener('keyup', sync);
                    textarea.addEventListener('blur', function () { window.setTimeout(close, 150); });
                    textarea.addEventListener('keydown', function (event) {
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

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('[data-mention-input]').forEach(setup);
                });
            })();
    </script>
@endpush
