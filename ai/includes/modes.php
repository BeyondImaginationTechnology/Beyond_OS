<?php
declare(strict_types=1);

/** Jaguar Thinking mode catalog shared by the chat UI and API. */
function jaguar_mode_catalog(): array
{
    return [
        'core' => ['label' => 'Explain', 'description' => 'Fast utility answers, lookups, and concise built-in guidance without GPU inference.', 'runtime' => 'explain', 'status' => 'live', 'plan' => 'free', 'credits' => 0, 'instruction' => 'Answer with concise built-in guidance only; do not invoke deep model reasoning.'],
        'build' => ['label' => 'Build', 'description' => 'Brainstorm software ideas, design technical and user experiences, and get coding guidance or clearly labeled example code. Repository changes stay in admin Code Thinking; image and video generation use their own modes.', 'runtime' => 'build', 'status' => 'preview', 'plan' => 'builder', 'credits' => 0, 'instruction' => 'Help brainstorm, design, and plan software projects or provide grounded coding guidance without repository access. This mode is text-only and does not generate images or video.'],
        'draw' => ['label' => 'Draw', 'description' => 'Shape an image brief, then generate only with an explicit credit-backed action.', 'status' => 'planned', 'plan' => 'creative', 'credits' => 1, 'instruction' => 'Turn the user intent into a precise visual brief before generation.'],
        'video' => ['label' => 'Video', 'description' => 'Develop a storyboard and video brief before generation.', 'status' => 'planned', 'plan' => 'creative', 'credits' => 10, 'instruction' => 'Turn the user intent into a concise, safe video storyboard before generation.'],
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
