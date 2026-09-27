<?php
declare(strict_types=1);

/** Jaguar Thinking mode catalog shared by the chat UI and API. */
function jaguar_mode_catalog(): array
{
    return [
        'core' => ['label' => 'Explain', 'description' => 'General explanations and learning with Jaguar model reasoning; greetings, arithmetic, and live lookups use the fast lane.', 'runtime' => 'explain', 'status' => 'live', 'plan' => 'free', 'credits' => 0, 'instruction' => 'Explain concepts clearly in plain language, use a helpful analogy when useful, and answer the user’s actual question.'],
        'build' => ['label' => 'Build', 'description' => 'Brainstorm software ideas, design technical and user experiences, and get coding guidance or clearly labeled example code. Repository changes stay in admin Code Thinking.', 'runtime' => 'build', 'status' => 'preview', 'plan' => 'builder', 'credits' => 0, 'instruction' => 'Help brainstorm, design, and plan software projects or provide grounded coding guidance without repository access. This mode is text-only and does not generate images or video.'],
        'draw' => ['label' => 'Draw', 'description' => 'Jaguar’s image-generation mode. It is separate from drawing tools inside the Beyond Tattoo stencil editor.', 'runtime' => 'draw', 'status' => 'preview', 'plan' => 'creative', 'credits' => 10, 'instruction' => 'Create images from a user’s visual prompt when Jaguar’s image-generation worker is connected. Until then, do not claim an image was generated.'],
        'video' => ['label' => 'Video', 'description' => 'Generate video through a dedicated GPU workflow when available.', 'runtime' => 'video', 'status' => 'preview', 'plan' => 'creative', 'credits' => 10, 'instruction' => 'Generate video only when Jaguar’s video worker is connected; until then, do not claim a video was generated.'],
    ];
}

function jaguar_mode(string $mode): ?array
{
    $catalog = jaguar_mode_catalog();
    return $catalog[$mode] ?? null;
}

function jaguar_mode_is_enabled(string $mode): bool
{
    $definition = jaguar_mode($mode);
    return is_array($definition) && in_array($definition['status'] ?? '', ['live', 'preview'], true);
}
