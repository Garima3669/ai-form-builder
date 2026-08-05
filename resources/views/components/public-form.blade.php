<?php

use App\Models\Form;
use Livewire\Component;
use App\Models\FormResponse;
use App\Models\FormResponseValue;

new class extends Component {
    public Form $form;

    public array $answers = [];

    public function mount(int $formId): void
    {
        $this->form = Form::with('fields')->where('id', $formId)->where('status', 'published')->firstOrFail();

        foreach ($this->form->fields as $field) {
            if ($field->type === 'checkbox') {
                $this->answers[$field->name] = [];
            } else {
                $this->answers[$field->name] = '';
            }
        }
    }

    public function submitForm()
    {
        $rules = [];

        foreach ($this->form->fields as $field) {
            $rule = [];

            if ($field->is_required) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            if ($field->type === 'email') {
                $rule[] = 'email';
            }

            if ($field->type === 'number') {
                $rule[] = 'numeric';
            }

            $rules['answers.' . $field->name] = $rule;
        }

        $this->validate($rules);

        $response = FormResponse::create([
            'form_id' => $this->form->id,
            'submitted_by_ip' => request()->ip(),
        ]);

        foreach ($this->form->fields as $field) {
            $value = $this->answers[$field->name] ?? null;

            if (is_array($value)) {
                $value = json_encode($value);
            }

            FormResponseValue::create([
                'form_response_id' => $response->id,
                'form_field_id' => $field->id,
                'value' => $value,
            ]);
        }

        session()->flash('success', 'Thank you! Your response has been submitted successfully.');

        foreach ($this->form->fields as $field) {
            if ($field->type === 'checkbox') {
                $this->answers[$field->name] = [];
            } else {
                $this->answers[$field->name] = '';
            }
        }
    }
};

?>

<div class="min-h-screen bg-gray-100 py-10">

    <div class="max-w-3xl mx-auto px-4">

        <div class="mb-6">

            <a href="{{ route('forms.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                ← Back to My Forms
            </a>

        </div>

        <div class="rounded-xl bg-white p-8 shadow-md">

            <div class="mb-8">

                <h1 class="text-4xl font-bold text-gray-900">

                    {{ $form->title }}

                </h1>

                @if ($form->description)
                    <p class="mt-3 text-lg text-gray-600">
                        {{ $form->description }}
                    </p>
                @endif

            </div>

            @if (session()->has('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-green-700 shadow">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())

                <div class="mb-6 rounded-lg bg-red-100 px-4 py-3 text-red-700">

                    <ul class="list-disc ml-5">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif

            <div class="mt-8 rounded-2xl border border-gray-100 bg-white p-8 shadow-xl">

            <form wire:submit="submitForm">

                @foreach ($form->fields as $field)
                    <div class="mb-6">

                        <label class="block font-medium text-gray-700 mb-2">

                            {{ $field->label }}

                            @if ($field->is_required)
                                <span class="ml-1 text-red-500">*</span>
                            @endif

                        </label>
                        {{-- Textarea --}}
                        @if ($field->type === 'textarea')
                            <textarea wire:model="answers.{{ $field->name }}" rows="4" placeholder="{{ $field->placeholder }}"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"></textarea>

                            {{-- Select --}}
                        @elseif($field->type === 'select')
                            <select wire:model="answers.{{ $field->name }}"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">

                                <option value="">
                                    Select an option
                                </option>

                                @foreach ($field->options ?? [] as $option)
                                    <option value="{{ $option }}">
                                        {{ $option }}
                                    </option>
                                @endforeach

                            </select>

                            {{-- Radio --}}
                        @elseif($field->type === 'radio')
                            <div class="space-y-2">

                                @foreach ($field->options ?? [] as $option)
                                    <label class="flex items-center gap-2">

                                        <input type="radio" wire:model="answers.{{ $field->name }}"
                                            value="{{ $option }}">

                                        <span>
                                            {{ $option }}
                                        </span>

                                    </label>
                                @endforeach

                            </div>

                            {{-- Checkbox --}}
                        @elseif($field->type === 'checkbox')
                            <div class="space-y-2">

                                @foreach ($field->options ?? [] as $option)
                                    <label class="flex items-center gap-2">

                                        <input type="checkbox" wire:model="answers.{{ $field->name }}"
                                            value="{{ $option }}">

                                        <span>
                                            {{ $option }}
                                        </span>

                                    </label>
                                @endforeach

                            </div>

                            {{-- Default Inputs --}}
                        @else
                            <input type="{{ $field->type }}" wire:model="answers.{{ $field->name }}"
                                placeholder="{{ $field->placeholder }}"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                        @endif

                        @if ($field->description)
                            <p class="mt-2 text-sm text-gray-500">
                                {{ $field->description }}
                            </p>
                        @endif

                    </div>
                @endforeach

                <div class="flex justify-end gap-3 mt-8">

                    <a href="{{ route('forms.index') }}"
                        class="rounded-lg bg-gray-200 px-6 py-3 font-medium hover:bg-gray-300">
                        Back
                    </a>

                    <button type="submit"
                        class="rounded-xl bg-blue-600 px-8 py-3 font-semibold text-white shadow-lg transition hover:bg-blue-700 hover:scale-105">
                        Submit Response
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</div>
