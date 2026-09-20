{{--
    Live preview for the cover-image file input.

    Expects an <input type="file" data-image-preview> plus the elements
    #image-preview-img and #image-preview-empty in the same page.
--}}
<script>
    document.querySelectorAll('[data-image-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            const preview = document.getElementById('image-preview-img');
            const placeholder = document.getElementById('image-preview-empty');

            if (!file || !preview) {
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            placeholder?.classList.add('hidden');
        });
    });
</script>
