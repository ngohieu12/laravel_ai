{{--
    Live preview for cover-image file inputs on a page that holds several
    forms at once (the series list has one form per series).

    Each form is wrapped in [data-image-preview-scope]; the preview elements
    are looked up inside that wrapper instead of by page-wide id, so every
    input drives its own thumbnail.
--}}
<script>
    document.querySelectorAll('[data-image-preview-scope]').forEach(function (scope) {
        const input = scope.querySelector('[data-image-preview]');

        if (!input) {
            return;
        }

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            const preview = scope.querySelector('[data-image-preview-img]');
            const placeholder = scope.querySelector('[data-image-preview-empty]');

            if (!file || !preview) {
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            placeholder?.classList.add('hidden');
        });
    });
</script>
