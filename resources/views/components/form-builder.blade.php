<?php

use App\Models\Form;
use Livewire\Component;
use App\Services\GeminiService;

new class extends Component {
    public string $title = '';

    public string $description = '';

    public array $fields = [];

    public string $newFieldType = 'text';

    public string $aiPrompt = '';

    public bool $isGenerating = false;

    public ?int $formId = null;

    public function addField(): void
    {
        $fieldNumber = count($this->fields) + 1;

        $this->fields[] = [
            'label' => 'Field ' . $fieldNumber,
            'name' => 'field_' . $fieldNumber,
            'type' => $this->newFieldType,
            'placeholder' => '',
            'description' => '',
            'is_required' => false,
            'options' => [],
        ];
    }

    public function removeField(int $index): void
    {
        unset($this->fields[$index]);

        $this->fields = array_values($this->fields);
    }

    public function addOption(int $fieldIndex): void
    {
        $this->fields[$fieldIndex]['options'][] = 'Option ' . (count($this->fields[$fieldIndex]['options']) + 1);
    }

    public function removeOption(int $fieldIndex, int $optionIndex): void
    {
        unset($this->fields[$fieldIndex]['options'][$optionIndex]);

        $this->fields[$fieldIndex]['options'] = array_values($this->fields[$fieldIndex]['options']);
    }

    public function generateWithAI(GeminiService $gemini): void
    {
        $this->validate([
            'aiPrompt' => 'required|string|min:10',
        ]);

        $this->isGenerating = true;

        try {
            $generatedFields = $gemini->generateForm($this->aiPrompt);

            $this->fields = [];

            foreach ($generatedFields as $field) {
                // Convert AI option objects into simple strings
                $options = [];

                foreach ($field['options'] ?? [] as $option) {
                    if (is_array($option)) {
                        $options[] = $option['label'] ?? ($option['value'] ?? '');
                    } else {
                        $options[] = $option;
                    }
                }

                $this->fields[] = [
                    'label' => $field['label'] ?? '',

                    'name' => $field['name'] ?? '',

                    'type' => strtolower($field['type'] ?? 'text'),

                    'placeholder' => $field['placeholder'] ?? '',

                    'description' => $field['description'] ?? '',

                    'is_required' => $field['required'] ?? false,

                    'options' => $options,
                ];
            }

            session()->flash('success', 'AI generated the form successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'AI generation failed. ' . $e->getMessage());
        }

        $this->isGenerating = false;
    }

    public function saveForm()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/'],
            'fields.*.type' => 'required|string',
            'fields.*.options.*' => 'nullable|string|max:255',
        ]);

        if ($this->formId) {
            $form = Form::where('user_id', auth()->id())->findOrFail($this->formId);

            $form->update([
                'title' => $this->title,
                'description' => $this->description,
            ]);

            $form->fields()->delete();
        } else {
            $form = Form::create([
                'user_id' => auth()->id(),
                'title' => $this->title,
                'description' => $this->description,
                'status' => 'draft',
                'source' => 'manual',
            ]);
        }

        foreach ($this->fields as $index => $field) {
            $options = null;

            if (in_array($field['type'], ['select', 'radio', 'checkbox'])) {
                $options = array_values(array_filter($field['options'] ?? []));
            }

            $form->fields()->create([
                'label' => $field['label'],
                'name' => $field['name'],
                'type' => $field['type'],
                'placeholder' => $field['placeholder'] ?? null,
                'description' => $field['description'] ?? null,
                'is_required' => $field['is_required'] ?? false,
                'sort_order' => $index,
                'options' => $options,
            ]);
        }

        session()->flash('success', $this->formId ? 'Form updated successfully!' : 'Form created successfully!');

        return redirect()->route('forms.index');
    }

    public function mount(?int $formId = null): void
    {
        $this->formId = $formId;

        if ($formId) {
            $form = Form::with('fields')
                ->where('user_id', auth()->id())
                ->findOrFail($formId);

            $this->title = $form->title;
            $this->description = $form->description;

            $this->fields = [];

            foreach ($form->fields as $field) {
                $this->fields[] = [
                    'label' => $field->label,
                    'name' => $field->name,
                    'type' => $field->type,
                    'placeholder' => $field->placeholder ?? '',
                    'description' => $field->description ?? '',
                    'is_required' => $field->is_required,
                    'options' => $field->options ?? [],
                ];
            }

            return;
        }

        if (session()->has('imported_fields')) {
            $this->fields = [];

            foreach (session('imported_fields') as $field) {
                $this->fields[] = [
                    'label' => $field['label'],
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'placeholder' => $field['placeholder'] ?? '',
                    'description' => $field['description'] ?? '',
                    'is_required' => $field['required'] ?? true,
                    'options' => $field['options'] ?? [],
                ];
            }

            session()->forget('imported_fields');
        }
    }
};
?>

<div class="min-h-screen bg-gray-100 py-8">

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}

        <div class="mb-8">

            <h1 class="text-3xl font-bold text-gray-800">
                {{ $formId ? 'Edit Form' : 'Create New Form' }}
            </h1>

            <p class="mt-2 text-gray-600">
                {{ $formId ? 'Update your form.' : 'Build your form by adding and configuring fields.' }}
            </p>
        </div>


        {{-- Success Message --}}

        @if (session()->has('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-green-700 shadow-sm">

                ✅ {{ session('success') }}

            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-700 shadow-sm">

                ❌ {{ session('error') }}

            </div>
        @endif


        {{-- Validation Errors --}}

        @if ($errors->any())

            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-700 shadow-sm">

                <ul class="list-disc ml-5">

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>

            </div>

        @endif

        {{-- AI Form Generator --}}
        <div class="rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 p-8 shadow-xl mb-8 text-white">

            <h2 class="text-3xl font-bold">
                🤖 AI Form Generator
            </h2>

            <p class="mt-3 text-purple-100 text-lg">
                Describe the form you want and AI will generate the fields.
            </p>

            <div class="mt-5">

                <textarea wire:model="aiPrompt" rows="4"
                    placeholder="Example: Create a customer feedback form with Name, Email, Rating (1-5), Comments."
                    class="w-full rounded-xl border-0 bg-white text-gray-900 p-4 focus:ring-4 focus:ring-purple-300"></textarea>

            </div>

            <div class="mt-5 flex justify-end">

                <button type="button" wire:click="generateWithAI" wire:loading.attr="disabled"
                    class="rounded-xl bg-white px-6 py-3 font-semibold text-purple-700 hover:bg-gray-100 transition-all duration-300">

                    <span wire:loading.remove>
                        🤖 Generate with AI
                    </span>

                    <span wire:loading>
                        Generating...
                    </span>

                </button>

            </div>

        </div>


        {{-- Form Information --}}

        <div class="rounded-2xl bg-white p-8 shadow-xl border border-gray-100 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                📝 Form Information
            </h2>

            <div class="mb-5">

                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Form Title
                </label>

                <input type="text" wire:model="title" placeholder="Enter form title"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition">

            </div>


            <div>

                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Description
                </label>

                <textarea wire:model="description" rows="4" placeholder="Enter form description"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition"></textarea>

            </div>

        </div>


        {{-- Form Fields --}}

        <div class="rounded-2xl bg-white p-8 shadow-xl border border-gray-100 mb-8">

            <div
                class="flex flex-col
                   md:flex-row
                   md:justify-between
                   md:items-center
                   gap-4 mb-6">

                <div>

                    <h2 class="text-2xl font-bold text-gray-800">
                        📋 Form Fields
                    </h2>

                    <p class="mt-2 text-gray-500">
                        Add, edit and customize each field before publishing your form.
                    </p>

                </div>


                <div class="flex gap-3">

                    <select wire:model="newFieldType"
                        class="rounded-lg
                           border border-gray-300
                           px-3 py-2
                           text-gray-900
                           bg-white">

                        <option value="text">
                            Text
                        </option>

                        <option value="email">
                            Email
                        </option>

                        <option value="number">
                            Number
                        </option>

                        <option value="phone">
                            Phone
                        </option>

                        <option value="date">
                            Date
                        </option>

                        <option value="textarea">
                            Textarea
                        </option>

                        <option value="select">
                            Select
                        </option>

                        <option value="radio">
                            Radio
                        </option>

                        <option value="checkbox">
                            Checkbox
                        </option>

                    </select>


                    <button type="button" wire:click="addField"
                        class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700 transition-all duration-300 hover:scale-105">
                        + Add Field
                    </button>

                </div>

            </div>


            {{-- Dynamic Fields --}}

            @forelse ($fields as $index => $field)

                <div wire:key="field-{{ $index }}"
                    class="mb-6 rounded-2xl border border-gray-200 bg-gray-50 p-6 shadow-sm hover:shadow-lg transition-all duration-300">

                    <div
                        class="flex justify-between
                                                   items-center mb-5">

                        <h3 class="text-xl font-bold text-gray-800">
                            Field {{ $index + 1 }}
                        </h3>

                        <button type="button" wire:click="removeField({{ $index }})"
                            class="rounded-lg bg-red-100 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-200 transition">
                            Remove
                        </button>

                    </div>


                    <div
                        class="grid grid-cols-1
                                                   md:grid-cols-2
                                                   gap-4">

                        <div>

                            <label
                                class="block text-sm
                                                           font-medium
                                                           text-gray-700 mb-1">
                                Label
                            </label>

                            <input type="text" wire:model="fields.{{ $index }}.label"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">

                        </div>


                        <div>

                            <label
                                class="block text-sm
                                                           font-medium
                                                           text-gray-700 mb-1">
                                Field Name
                            </label>

                            <input type="text" wire:model="fields.{{ $index }}.name"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">

                        </div>


                        <div>

                            <label
                                class="block text-sm
                                                           font-medium
                                                           text-gray-700 mb-1">
                                Field Type
                            </label>

                            <select wire:model="fields.{{ $index }}.type"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">

                                <option value="text">
                                    Text
                                </option>

                                <option value="email">
                                    Email
                                </option>

                                <option value="number">
                                    Number
                                </option>

                                <option value="phone">
                                    Phone
                                </option>

                                <option value="date">
                                    Date
                                </option>

                                <option value="textarea">
                                    Textarea
                                </option>

                                <option value="select">
                                    Select
                                </option>

                                <option value="radio">
                                    Radio
                                </option>

                                <option value="checkbox">
                                    Checkbox
                                </option>

                            </select>

                        </div>


                        <div>

                            <label
                                class="block text-sm
                                                           font-medium
                                                           text-gray-700 mb-1">
                                Placeholder
                            </label>

                            <input type="text" wire:model="fields.{{ $index }}.placeholder"
                                placeholder="Enter placeholder"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">

                        </div>

                    </div>


                    <div class="mt-4">

                        <label
                            class="block text-sm
                                                       font-medium
                                                       text-gray-700 mb-1">
                            Field Description
                        </label>

                        <input type="text" wire:model="fields.{{ $index }}.description"
                            placeholder="Optional description"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">

                    </div>


                    <div class="mt-4">

                        <label class="inline-flex
                                                       items-center">

                            <input type="checkbox" wire:model="fields.{{ $index }}.is_required"
                                class="rounded
                                                           border-gray-300">

                            <span
                                class="ml-2 text-sm
                                                           text-gray-700">
                                Required Field
                            </span>

                        </label>

                    </div>


                    {{-- Options --}}

                    @if (in_array($field['type'], ['select', 'radio', 'checkbox']))
                        <div
                            class="mt-5 rounded-lg
                                                                               bg-gray-50 p-4">

                            <div
                                class="flex justify-between
                                                                                   items-center mb-4">

                                <div>

                                    <h4 class="font-semibold">
                                        Field Options
                                    </h4>

                                    <p
                                        class="text-sm
                                                                                          text-gray-500">
                                        Add choices for this field.
                                    </p>

                                </div>


                                <button type="button" wire:click="addOption({{ $index }})"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                                    + Add Option
                                </button>

                            </div>


                            @forelse ($field['options'] ?? []
                                    as $optionIndex => $option)
                                <div class="flex gap-3 mb-3">

                                    <input type="text"
                                        wire:model="fields.{{ $index }}.options.{{ $optionIndex }}"
                                        class="flex-1
                                                                                                                   rounded-lg
                                                                                                                   border
                                                                                                                   border-gray-300
                                                                                                                   px-3 py-2
                                                                                                                   text-gray-900"
                                        placeholder="Enter option">

                                    <button type="button"
                                        wire:click="removeOption({{ $index }}, {{ $optionIndex }})"
                                        class="font-medium
                                                                                                                   text-red-600">
                                        Remove
                                    </button>

                                </div>

                            @empty

                                <p class="text-sm text-gray-500">
                                    No options added yet.
                                </p>
                            @endforelse

                        </div>
                    @endif

                </div>

            @empty

                <div
                    class="rounded-lg
                                               border-2
                                               border-dashed
                                               border-gray-300
                                               p-10
                                               text-center">

                    <p class="text-gray-500">
                        No fields added yet.
                    </p>

                    <p
                        class="text-sm
                                                  text-gray-400
                                                  mt-1">
                        Select a field type and click
                        "Add Field".
                    </p>

                </div>

            @endforelse

        </div>


        {{-- Bottom Action Bar --}}

        <div class="sticky bottom-6 mt-10">

            <div class="flex justify-end">

                <button type="button" wire:click="saveForm" wire:loading.attr="disabled"
                    class="rounded-2xl bg-green-600 px-10 py-4 text-lg font-semibold text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-green-700 disabled:opacity-50">

                    <span wire:loading.remove>

                        💾 Save Form

                    </span>

                    <span wire:loading>

                        Saving Form...

                    </span>

                </button>

            </div>

        </div>

    </div>

</div>
