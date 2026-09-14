<?php
declare(strict_types=1);

/** Jaguar Thinking mode catalog shared by the chat UI and API. */
function jaguar_mode_catalog(): array
{
    return [
        'core' => ['label' => 'Core', 'description' => 'Chat, code, planning, learning, and everyday Jaguar help.', 'status' => 'live', 'plan' => 'free', 'credits' => 0, 'instruction' => 'Teach clearly with plain language, useful analogies, and a practical next step.'],
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
