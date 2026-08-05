<?php

use App\Models\Form;
use Livewire\Component;

new class extends Component
{
    public Form $form;

    public array $answers = [];

    public function mount(int $formId): void
    {
        $this->form = Form::with('fields')
            ->where('id', $formId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        foreach ($this->form->fields as $field) {
            $this->answers[$field->name] = '';
        }
    }

    public function submitPreview(): void
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

            $rules[
                'answers.' . $field->name
            ] = $rule;
        }

        $this->validate($rules);

        session()->flash(
            'success',
            'Preview form submitted successfully!'
        );
    }
};
?>

<div class="min-h-screen bg-gray-100 py-10">

<div class="max-w-3xl mx-auto px-4">

    {{-- Back --}}

    <div class="rounded-xl bg-gray-200 px-6 py-3 font-semibold transition hover:bg-gray-300">

        <a
            href="{{ route('forms.index') }}"
            class="text-blue-600
                   hover:text-blue-800
                   font-medium"
        >
            ← Back to My Forms
        </a>

    </div>


    {{-- Form Card --}}

    <div
        class="rounded-xl
               bg-white
               p-8
               shadow-md"
    >

        {{-- Form Header --}}

        <div class="mb-8 rounded-2xl bg-white p-8 shadow-lg border border-gray-100">

            <h1
                class="text-3xl
                       font-bold
                       text-gray-800"
            >
                {{ $form->title }}
            </h1>

            @if ($form->description)

                <p
                    class="mt-3
                           text-gray-600"
                >
                    {{ $form->description }}
                </p>

            @endif

        </div>


        {{-- Success Message --}}

        @if (session()->has('success'))

            <div
                class="mb-6
                       rounded-lg
                       bg-green-100
                       px-4 py-3
                       text-green-800"
            >
                {{ session('success') }}
            </div>

        @endif


        {{-- Validation Errors --}}

        @if ($errors->any())

            <div
                class="mb-6
                       rounded-lg
                       bg-red-100
                       px-4 py-3
                       text-red-700"
            >

                <ul class="list-disc ml-5">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- Dynamic Form --}}

        <form wire:submit="submitPreview">

            @foreach ($form->fields->sortBy('sort_order') as $field)

                <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">

                    <label
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >

                        {{ $field->label }}

                        @if ($field->is_required)

                            <span class="ml-1 text-red-500">*</span>

                        @endif

                    </label>


                    {{-- Textarea --}}

                    @if ($field->type === 'textarea')

                        <textarea
                            wire:model="answers.{{ $field->name }}"
                            placeholder="{{ $field->placeholder }}"
                            rows="4"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                        ></textarea>


                    {{-- Select --}}

                    @elseif ($field->type === 'select')

                        <select
                            wire:model="answers.{{ $field->name }}"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                        >

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

                    @elseif ($field->type === 'radio')

                        <div class="space-y-2">

                            @foreach ($field->options ?? [] as $option)

                                <label
                                    class="flex
                                           items-center
                                           gap-2"
                                >

                                    <input
                                        type="radio"
                                        wire:model="answers.{{ $field->name }}"
                                        value="{{ $option }}"
                                    >

                                    <span>
                                        {{ $option }}
                                    </span>

                                </label>

                            @endforeach

                        </div>


                    {{-- Checkbox --}}

                    @elseif ($field->type === 'checkbox')

                        <div class="space-y-2">

                            @foreach ($field->options ?? [] as $option)

                                <label
                                    class="flex
                                           items-center
                                           gap-2"
                                >

                                    <input
                                        type="checkbox"
                                        wire:model="answers.{{ $field->name }}"
                                        value="{{ $option }}"
                                    >

                                    <span>
                                        {{ $option }}
                                    </span>

                                </label>

                            @endforeach

                        </div>


                    {{-- Default Input --}}

                    @else

                        <input
                            type="{{ $field->type }}"
                            wire:model="answers.{{ $field->name }}"
                            placeholder="{{ $field->placeholder }}"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                        >

                    @endif


                    {{-- Field Description --}}

                    @if ($field->description)

                        <p
                            class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-500"
                        >
                            {{ $field->description }}
                        </p>

                    @endif

                </div>

            @endforeach


            {{-- Submit --}}

            <div class="flex justify-end">

                <button
                    type="submit"
                    class="rounded-lg
                           bg-blue-600
                           px-6 py-3
                           font-semibold
                           text-white
                           hover:bg-blue-700"
                >
                    Submit Form
                </button>

            </div>

        </form>

    </div>

</div>

</div>
