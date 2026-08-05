<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\DocumentImportService;

new class extends Component {
    use WithFileUploads;

    public $file;

    public int $uploadKey = 0;

    public function import(DocumentImportService $importService)
    {
        $this->validate([
            'file' => 'required|file|mimes:docx,xlsx,csv|max:10240',
        ]);

        try {
            $fields = $importService->import($this->file->getRealPath());

            session([
                'imported_fields' => $fields,
            ]);

            // Clear uploaded file
            $this->reset('file');

            // Recreate the file input
            $this->uploadKey++;

            return redirect()->route('form-builder');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
};

?>

<div class="max-w-4xl mx-auto p-6">

    <div class="mb-8">

        <h1 class="text-3xl font-bold text-gray-800">
            📄 Import Form
        </h1>

        <p class="mt-2 text-gray-600">
            Upload a Word (.docx), Excel (.xlsx), or CSV (.csv)
            file and we'll automatically detect the form fields.
        </p>

    </div>

    @if (session()->has('error'))
        <div class="mb-6 rounded-lg bg-red-100 px-4 py-3 text-red-700">

            {{ session('error') }}

        </div>
    @endif

    @error('file')
        <div class="mb-6 rounded-lg bg-red-100 px-4 py-3 text-red-700">

            {{ $message }}

        </div>
    @enderror

    <div class="rounded-xl bg-white p-8 shadow">

        <div <div
            class="rounded-2xl border-2 border-dashed border-indigo-300 bg-indigo-50 p-12 text-center transition hover:border-indigo-500 hover:bg-indigo-100">


            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-indigo-100 text-5xl">
                📄
            </div>

            <h2 class="mt-6 text-2xl font-bold text-gray-800">

                Select a File

            </h2>

            <p class="mt-2 text-gray-500">

                Supported formats:
                <strong>.xlsx</strong>,
                <strong>.csv</strong>

            </p>

            <div class="mt-8">

                <input wire:key="upload-{{ $uploadKey }}" type="file" wire:model="file" accept=".docx,.xlsx,.csv"
                    class="block w-full rounded-xl border border-gray-300 bg-white p-4 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-indigo-700">

                @if ($file)
                    <div class="mt-4 rounded-lg bg-green-50 p-3 text-green-700">
                        ✅ Selected File:
                        <strong>{{ $file->getClientOriginalName() }}</strong>
                    </div>
                @endif

            </div>

            <div class="mt-8">

                <button wire:click="import" wire:loading.attr="disabled" wire:target="import,file"
                    class="rounded-xl bg-indigo-600 px-10 py-3 font-semibold text-white shadow-lg transition hover:bg-indigo-700 hover:scale-105 disabled:opacity-50">

                    <span wire:loading.remove wire:target="import">
                        📥 Import Form
                    </span>

                    <span wire:loading wire:target="import">
                        ⏳ Importing...
                    </span>

                </button>

            </div>

            <div wire:loading wire:target="file" class="mt-4 text-sm text-blue-600">
                Uploading file...
            </div>

            <div wire:loading wire:target="import" class="mt-4 text-sm text-green-600">
                📄 Reading your document and extracting fields...
            </div>

        </div>

    </div>

</div>
