<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class GeminiService
{
    public function generateForm(string $prompt): array
    {
        $apiKey = env('OPENROUTER_API_KEY');
        $model = env('OPENROUTER_MODEL', 'openai/gpt-4o-mini');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])->post('https://openrouter.ai/api/v1/chat/completions', [

            'model' => $model,

            'messages' => [

                [
                    'role' => 'system',

                    'content' => <<<PROMPT
You are an AI Form Generator.

Return ONLY valid JSON.

Return an array.

Each object must contain:

label
name
type
required
placeholder
description
options

IMPORTANT RULES:

1. Return ONLY valid JSON.
2. Do NOT wrap JSON inside markdown.
3. The "options" field MUST always be an array of STRINGS.

Correct:

"options": ["Male","Female","Other"]

Correct:

"options": ["1","2","3","4","5"]

Wrong:

"options": [
  {
    "label":"Male",
    "value":"Male"
  }
]

Allowed field types:

text
email
number
phone
date
textarea
select
radio
checkbox

Example:

[
  {
    "label":"Full Name",
    "name":"full_name",
    "type":"text",
    "required":true,
    "options":[]
  }
]
PROMPT
                ],

                [
                    'role' => 'user',
                    'content' => $prompt
                ]

            ]

        ]);

        if (!$response->successful()) {
            throw new Exception($response->body());
        }

        $text = data_get(
            $response->json(),
            'choices.0.message.content'
        );

        $text = trim($text);

        $text = preg_replace('/```json/i', '', $text);
        $text = preg_replace('/```/', '', $text);

        $json = json_decode($text, true);

        if (!is_array($json)) {
            throw new Exception("AI returned invalid JSON:\n\n".$text);
        }

        return $json;
    }
}