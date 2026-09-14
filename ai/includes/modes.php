<?php
declare(strict_types=1);

/** Jaguar Thinking mode catalog shared by the chat UI and API. */
function jaguar_mode_catalog(): array
{
    return [
        'explain' => ['label' => 'Explain', 'description' => 'Learn difficult ideas in plain language.', 'status' => 'live', 'plan' => 'free', 'credits' => 0, 'instruction' => 'Teach clearly with plain language, useful analogies, and a practical next step.'],
        'code' => ['label' => 'Code', 'description' => 'Plan, write, and debug with a careful guide.', 'status' => 'preview', 'plan' => 'free', 'credits' => 0, 'instruction' => 'Act as a careful coding partner. Explain assumptions, show secure maintainable code, and call out how to test it.'],
        'research' => ['label' => 'Research', 'description' => 'A deeper retrieval and synthesis workflow.', 'status' => 'planned', 'plan' => 'plus', 'credits' => 2, 'instruction' => 'When enabled, synthesize sources carefully and distinguish evidence from inference.'],
        'translate' => ['label' => 'Translate', 'description' => 'Move meaning across languages and cultures.', 'status' => 'planned', 'plan' => 'plus', 'credits' => 1, 'instruction' => 'When enabled, preserve meaning, tone, and cultural context rather than translating word for word.'],
        'speak' => ['label' => 'Speak', 'description' => 'Turn Jaguar answers into natural voice.', 'status' => 'planned', 'plan' => 'creative', 'credits' => 5, 'instruction' => 'When enabled, write concise, natural spoken responses with clear pacing.'],
        'draw' => ['label' => 'Draw', 'description' => 'Create visuals with a GPU-assisted workflow.', 'status' => 'planned', 'plan' => 'creative', 'credits' => 10, 'instruction' => 'When enabled, turn the user intent into a precise visual brief before generation.'],
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
