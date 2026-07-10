<?php

namespace App\Services\AI;

interface AIProviderContract
{
    public function complete(array $messages, AIRequestOptions $options): AIResponse;
}
