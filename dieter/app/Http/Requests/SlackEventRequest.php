<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlackEventRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string'],
            'sender' => ['required', 'string'],
            'channel' => ['required', 'string'],
            'text' => ['required', 'string'],
            'timestamp' => ['nullable', 'string'],
            'thread_timestamp' => ['nullable', 'string'],
        ];
    }

    public function messageTs(): ?string
    {
        return $this->validated('timestamp');
    }

    public function threadTs(): ?string
    {
        return $this->validated('thread_timestamp');
    }

    public function threadKey(): ?string
    {
        return $this->threadTs() ?? $this->messageTs();
    }
}
