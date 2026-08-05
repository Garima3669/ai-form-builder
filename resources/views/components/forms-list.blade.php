<?php

use App\Models\Form;
use Livewire\Component;

new class extends Component {
    public $search = '';
    public $totalForms = 0;
    public $publishedForms = 0;
    public $draftForms = 0;
    public $totalResponses = 0;
    public function publishForm(int $formId): void
    {
        $form = Form::where('id', $formId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($form->fields()->count() === 0) {
            session()->flash('error', 'You cannot publish a form without fields.');

            return;
        }

        $form->update([
            'status' => 'published',
        ]);

        session()->flash('success', 'Form published successfully!');
    }

    public function deleteForm(int $formId): void
    {
        $form = Form::where('id', $formId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $form->delete();

        session()->flash('success', 'Form deleted successfully!');
    }

    public function duplicateForm(int $formId): void
    {
        $form = Form::with('fields')
            ->where('user_id', auth()->id())
            ->findOrFail($formId);

        $newForm = Form::create([
            'user_id' => auth()->id(),
            'title' => $form->title . ' (Copy)',
            'description' => $form->description,
            'status' => 'draft',
            'source' => $form->source,
        ]);

        foreach ($form->fields as $field) {
            $newForm->fields()->create([
                'label' => $field->label,
                'name' => $field->name,
                'type' => $field->type,
                'placeholder' => $field->placeholder,
                'description' => $field->description,
                'is_required' => $field->is_required,
                'sort_order' => $field->sort_order,
                'options' => $field->options,
            ]);
        }

        session()->flash('success', 'Form duplicated successfully!');
    }

    public function render()
    {
        $forms = Form::with(['responses', 'fields'])
            ->where('user_id', auth()->id())
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->get();

        $this->totalForms = $forms->count();

        $this->publishedForms = $forms->where('status', 'published')->count();

        $this->draftForms = $forms->where('status', 'draft')->count();

        $this->totalResponses = $forms->sum(function ($form) {
            return $form->responses->count();
        });

        return $this->view([
            'forms' => $forms,
        ]);
    }
};
?>

<div class="min-h-screen bg-gray-100 py-8">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}

        <div
            class="flex flex-col
               md:flex-row
               md:items-center
               md:justify-between
               gap-4 mb-8">

            <div>

                <h1 class="text-4xl font-bold text-gray-900 flex items-center gap-3">
                    📋 My Forms
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Manage your AI-generated, imported and manually created forms in one place.
                </p>

            </div>


            <div class="flex gap-3">

                <a href="{{ route('forms.import') }}"
                    class="inline-flex
               items-center
               justify-center
               rounded-lg
               bg-purple-600
               px-5 py-3
               font-semibold
               text-white
               hover:bg-purple-700">
                    📄 Import Form
                </a>

                <a href="{{ route('form-builder') }}"
                    class="inline-flex
               items-center
               justify-center
               rounded-lg
               bg-blue-600
               px-5 py-3
               font-semibold
               text-white
               hover:bg-blue-700">
                    + Create New Form
                </a>

            </div>

        </div>


        {{-- Success Message --}}

        @if (session()->has('success'))
            <div
                class="mb-6
                                                           rounded-lg
                                                           bg-green-100
                                                           px-4 py-3
                                                           text-green-800">

                {{ session('success') }}

            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

            <div
                class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

                <div class="flex justify-between items-center">

                    <div>
                        <p class="text-sm text-gray-500">
                            📄 Total Forms
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-gray-900">
                            {{ $totalForms }}
                        </h2>

                        <p class="mt-2 text-xs text-gray-400">
                            Forms created
                        </p>
                    </div>

                    <div class="text-5xl">
                        📋
                    </div>

                </div>

            </div>

            <div
                class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

                <div class="flex justify-between items-center">

                    <div>

                        <p class="text-sm text-gray-500">
                            🚀 Published
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-green-600">
                            {{ $publishedForms }}
                        </h2>

                        <p class="mt-2 text-xs text-gray-400">
                            Ready to collect responses
                        </p>

                    </div>

                    <div class="text-5xl">
                        🚀
                    </div>

                </div>

            </div>

            <div
                class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

                <div class="flex justify-between items-center">

                    <div>

                        <p class="text-sm text-gray-500">
                            📝 Drafts
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-yellow-600">
                            {{ $draftForms }}
                        </h2>

                        <p class="mt-2 text-xs text-gray-400">
                            Waiting to be published
                        </p>

                    </div>

                    <div class="text-5xl">
                        📝
                    </div>

                </div>

            </div>

            <div
                class="bg-white rounded-xl shadow p-6 border-l-4 border-purple-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

                <div class="flex justify-between items-center">

                    <div>

                        <p class="text-sm text-gray-500">
                            📬 Responses
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-purple-600">
                            {{ $totalResponses }}
                        </h2>

                        <p class="mt-2 text-xs text-gray-400">
                            Total submissions received
                        </p>

                    </div>

                    <div class="text-5xl">
                        📬
                    </div>

                </div>

            </div>

        </div>

        <div class="mb-8">
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="🔍 Search forms by title or description..."
                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
        </div>

        {{-- Forms --}}

        @if ($forms->count())

            <div
                class="grid
                                                           grid-cols-1
                                                           md:grid-cols-2
                                                           lg:grid-cols-3
                                                           gap-6">

                @foreach ($forms as $form)
                    <div
                        class="rounded-xl bg-white p-6 shadow-md hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 border border-gray-100">

                        {{-- Form Title --}}

                        <h2
                            class="text-xl
                                                                                                                   font-semibold
                                                                                                                   text-gray-800">
                            {{ $form->title }}
                        </h2>


                        {{-- Description --}}

                        <p class="mt-2 text-sm text-gray-600">
                            {{ $form->description ?: 'No description available.' }}
                        </p>

                        <p class="mt-3 text-xs text-gray-400">
                            📅 Created {{ $form->created_at->format('d M Y') }}
                        </p>

                        {{-- Metadata --}}

                        <div class="mt-4 flex items-center gap-2">

                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold
        {{ $form->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">

                                {{ ucfirst($form->status) }}

                            </span>

                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">

                                {{ ucfirst($form->source) }}

                            </span>

                        </div>


                        {{-- Fields Count --}}

                        <p
                            class="mt-4
                                                                                                                   text-sm
                                                                                                                   text-gray-500">
                            {{ $form->fields->count() }}
                            {{ $form->fields->count() === 1 ? 'Field' : 'Fields' }}
                        </p>


                        {{-- Actions --}}

                        <div
                            class="mt-6
                                                                                                                   flex
                                                                                                                   flex-wrap
                                                                                                                   gap-2">

                            <a href="{{ route('forms.preview', $form->id) }}"
                                class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
                                Preview
                            </a>

                            <a href="{{ route('form-builder', ['formId' => $form->id]) }}"
                                class="rounded-lg bg-blue-100 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-200 transition">
                                Edit
                            </a>

                            <button wire:click="duplicateForm({{ $form->id }})" wire:confirm="Duplicate this form?"
                               class="rounded-lg bg-indigo-100 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-200 transition">
                                Duplicate
                            </button>

                            <a href="{{ route('forms.responses', $form->id) }}"
                                class="rounded-lg
                                                       bg-purple-100
                                                       px-4 py-2
                                                       text-sm
                                                       font-medium
                                                       text-purple-700
                                                       hover:bg-purple-200">
                                Responses
                            </a>

                            @if ($form->status === 'draft')
                                <button type="button" wire:click="publishForm({{ $form->id }})"
                                    wire:confirm="Are you sure you want to publish this form?"
                                    class="rounded-lg bg-green-100 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-200 transition">
                                    Publish
                                </button>
                            @else
                                <span
                                    class="rounded-lg
                                                                                                               bg-green-100
                                                                                                               px-4 py-2
                                                                                                               text-sm
                                                                                                               font-medium
                                                                                                               text-green-700">
                                    Published
                                </span>
                            @endif



                            <button type="button" wire:click="deleteForm({{ $form->id }})"
                                wire:confirm="Are you sure you want to delete this form?"
                               class="rounded-lg bg-red-100 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-200 transition">
                                Delete
                            </button>

                        </div>

                        @if ($form->status === 'published')
                            <div class="mt-4">

                                <div class="mt-4 flex items-center gap-3">

                                    <a href="{{ route('forms.public', $form->id) }}" target="_blank"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                        🌐 Open Form
                                    </a>

                                </div>

                            </div>
                        @endif

                    </div>
                @endforeach

            </div>
        @else
            {{-- Empty State --}}

            <div
                class="rounded-xl
                                                           bg-white
                                                           p-12
                                                           text-center
                                                           shadow-md">

                <h2
                    class="text-xl
                                                               font-semibold
                                                               text-gray-800">
                    No Forms Yet
                </h2>

                <p class="mt-2
                                                               text-gray-500">
                    Create your first form to get started.
                </p>


                <a href="{{ route('form-builder') }}"
                    class="inline-block
                                                               mt-6
                                                               rounded-lg
                                                               bg-blue-600
                                                               px-5 py-3
                                                               font-semibold
                                                               text-white
                                                               hover:bg-blue-700">
                    Create Your First Form
                </a>

            </div>

        @endif

    </div>

</div>
