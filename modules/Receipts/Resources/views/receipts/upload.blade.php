<x-layouts.admin>
    <x-slot name="title">
        {{ trans('receipts::general.upload_receipt') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form method="POST" action="{{ route('receipts.receipts.store') }}" enctype="multipart/form-data" id="upload-form">
                    @csrf

                    {{-- Drag & Drop Zone --}}
                    <div id="drop-zone"
                         class="border-2 border-dashed border-gray-300 rounded-xl p-12 text-center cursor-pointer hover:border-purple-500 hover:bg-purple-50 transition-colors">
                        <span class="material-icons text-5xl text-gray-400" id="drop-icon">cloud_upload</span>
                        <p class="mt-4 text-gray-600">{{ trans('receipts::general.messages.drag_drop') }}</p>
                        <p class="mt-2 text-sm text-gray-400">JPEG, PNG, GIF, BMP, TIFF, WebP (max 10MB)</p>
                        <input type="file" name="image" id="image-input" accept="image/*"
                               class="hidden" required>
                    </div>

                    {{-- Image Preview --}}
                    <div id="image-preview" class="mt-4 hidden">
                        <img id="preview-img" src="" alt="Preview" class="max-h-64 mx-auto rounded-lg shadow">
                        <p id="preview-name" class="text-center mt-2 text-sm text-gray-600"></p>
                    </div>

                    {{-- Notes --}}
                    <div class="mt-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ trans('receipts::general.fields.notes') }}
                        </label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                  placeholder="{{ trans('receipts::general.fields.notes') }}..."></textarea>
                    </div>

                    {{-- Submit --}}
                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('receipts.receipts.index') }}"
                           class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            {{ trans('general.cancel') }}
                        </a>
                        <button type="submit" id="submit-btn"
                                class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 disabled:opacity-50"
                                disabled>
                            <span class="material-icons text-sm align-middle">cloud_upload</span>
                            {{ trans('receipts::general.actions.upload') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const dropZone = document.getElementById('drop-zone');
                const imageInput = document.getElementById('image-input');
                const preview = document.getElementById('image-preview');
                const previewImg = document.getElementById('preview-img');
                const previewName = document.getElementById('preview-name');
                const submitBtn = document.getElementById('submit-btn');

                dropZone.addEventListener('click', () => imageInput.click());

                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropZone.classList.add('border-purple-500', 'bg-purple-50');
                });

                dropZone.addEventListener('dragleave', () => {
                    dropZone.classList.remove('border-purple-500', 'bg-purple-50');
                });

                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('border-purple-500', 'bg-purple-50');
                    if (e.dataTransfer.files.length > 0) {
                        imageInput.files = e.dataTransfer.files;
                        showPreview(e.dataTransfer.files[0]);
                    }
                });

                imageInput.addEventListener('change', () => {
                    if (imageInput.files.length > 0) {
                        showPreview(imageInput.files[0]);
                    }
                });

                function showPreview(file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImg.src = e.target.result;
                        previewName.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                        preview.classList.remove('hidden');
                        submitBtn.disabled = false;
                    };
                    reader.readAsDataURL(file);
                }
            });
        </script>
    </x-slot>
</x-layouts.admin>
