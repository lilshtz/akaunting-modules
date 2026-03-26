<x-layouts.admin>
    <x-slot name="title">
        {{ trans('receipts::general.bulk_upload') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form method="POST" action="{{ route('receipts.receipts.bulk-store') }}" enctype="multipart/form-data" id="bulk-upload-form">
                    @csrf

                    {{-- Drag & Drop Zone --}}
                    <div id="drop-zone"
                         class="border-2 border-dashed border-gray-300 rounded-xl p-12 text-center cursor-pointer hover:border-purple-500 hover:bg-purple-50 transition-colors">
                        <span class="material-icons text-5xl text-gray-400">cloud_upload</span>
                        <p class="mt-4 text-gray-600">{{ trans('receipts::general.messages.drag_drop') }}</p>
                        <p class="mt-2 text-sm text-gray-400">Select multiple images (max 10MB each)</p>
                        <input type="file" name="images[]" id="images-input" accept="image/*"
                               class="hidden" multiple required>
                    </div>

                    {{-- Preview Grid --}}
                    <div id="preview-grid" class="mt-4 grid grid-cols-4 gap-3 hidden"></div>

                    {{-- File Count --}}
                    <p id="file-count" class="mt-2 text-sm text-gray-600 hidden"></p>

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
                            {{ trans('receipts::general.actions.bulk_upload') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const dropZone = document.getElementById('drop-zone');
                const imagesInput = document.getElementById('images-input');
                const previewGrid = document.getElementById('preview-grid');
                const fileCount = document.getElementById('file-count');
                const submitBtn = document.getElementById('submit-btn');

                dropZone.addEventListener('click', () => imagesInput.click());

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
                        imagesInput.files = e.dataTransfer.files;
                        showPreviews(e.dataTransfer.files);
                    }
                });

                imagesInput.addEventListener('change', () => {
                    if (imagesInput.files.length > 0) {
                        showPreviews(imagesInput.files);
                    }
                });

                function showPreviews(files) {
                    previewGrid.innerHTML = '';
                    previewGrid.classList.remove('hidden');
                    fileCount.classList.remove('hidden');
                    fileCount.textContent = files.length + ' file(s) selected';

                    Array.from(files).forEach(file => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const div = document.createElement('div');
                            div.className = 'aspect-square bg-gray-100 rounded-lg overflow-hidden';
                            div.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
                            previewGrid.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    });

                    submitBtn.disabled = false;
                }
            });
        </script>
    </x-slot>
</x-layouts.admin>
