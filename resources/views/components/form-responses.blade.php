<?php

use App\Models\Form;
use Livewire\Component;

new class extends Component {
    public Form $form;

    public function mount(int $formId): void
    {
        $this->form = Form::with(['responses.values.formField', 'fields'])
            ->where('id', $formId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }
};

?>

<div class="max-w-6xl mx-auto p-6">

    {{-- Header --}}
    <div class="mb-8">

        <a href="{{ route('forms.index') }}"
            class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
            ← Back to My Forms
        </a>

        <h1 class="mt-4 text-3xl font-bold text-gray-800">
            {{ $form->title }}
        </h1>

        @if ($form->description)
            <p class="mt-2 text-gray-600">
                {{ $form->description }}
            </p>
        @endif

    </div>

    {{-- Statistics --}}
    <div class="mb-8 rounded-xl bg-white p-6 shadow">

        <div class="flex items-center justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Total Responses
                </h2>

                <p class="text-gray-500">
                    Number of submissions received
                </p>

            </div>

            <div class="text-4xl font-bold text-blue-600">

                {{ $form->responses->count() }}

            </div>

        </div>

    </div>

    {{-- Response List --}}
    @forelse($form->responses as $response)

        <div class="mb-8 rounded-xl bg-white p-6 shadow">

            <div class="mb-6 flex items-center justify-between">

                <div>

                    <h2 class="text-xl font-semibold text-gray-800">
                        Response #{{ $loop->iteration }}
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Submitted on
                        {{ $response->created_at->format('d M Y, h:i A') }}
                    </p>

                </div>

            </div>

            <div class="space-y-4">
                @foreach ($response->values as $value)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-200 pb-4">

                        <div class="font-semibold text-gray-700">
                            {{ $value->formField->label }}
                        </div>

                        <div class="md:col-span-2 text-gray-900">

                            @php
                                $decoded = json_decode($value->value, true);
                            @endphp

                            @if (is_array($decoded))
                                {{ implode(', ', $decoded) }}
                            @elseif(empty($value->value))
                                <span class="text-gray-400">
                                    —
                                </span>
                            @else
                                {{ $value->value }}
                            @endif

                        </div>

                    </div>
                @endforeach

            </div>

        </div>

    @empty

        <div class="rounded-xl bg-white p-12 shadow text-center">

            <div class="text-6xl mb-4">
                📋
            </div>

            <h2 class="text-2xl font-bold text-gray-700">

                No Responses Yet

            </h2>

            <p class="mt-3 text-gray-500">

                This form hasn't received any submissions yet.

            </p>

        </div>

    @endforelse

</div>
