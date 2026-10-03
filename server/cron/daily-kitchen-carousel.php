<?php
declare(strict_types=1);

/** Generate five publish-ready 1080 × 1350 recipe cards for the daily pick. */
function kitchen_daily_carousel(?string $requestedDate = null): string
{
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('Kitchen carousel generation is CLI-only.');
    }
    if (!extension_loaded('gd') || !function_exists('imagettftext')) {
        throw new RuntimeException('PHP GD with FreeType is required for Kitchen carousel images.');
    }

    $root = dirname(__DIR__, 2);
    $catalogPath = $root . '/recipe/data/recipes.json';
    $outputRoot = $root . '/recipe/assets/images/daily';
    $timezone = new DateTimeZone(getenv('BEYOND_KITCHEN_TIMEZONE') ?: 'America/Vancouver');
    $today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');
    $date = $requestedDate ?? $today;
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'));
    if (!$day || $day->format('Y-m-d') !== $date) {
        throw new InvalidArgumentException('Date must be YYYY-MM-DD.');
    }
    $font = kitchen_carousel_font();
    $catalog = json_decode((string)file_get_contents($catalogPath), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($catalog) || !array_is_list($catalog) || $catalog === []) {
        throw new RuntimeException('The Kitchen recipe catalog must contain recipes.');
    }
    $epochDay = (int)floor($day->getTimestamp() / 86400);
    $recipe = $catalog[(($epochDay % count($catalog)) + count($catalog)) % count($catalog)];
    $photoPath = realpath($root . '/recipe/' . $recipe['image']);
    $photoRoot = realpath($root . '/recipe/assets/images/recipes');
    if (!$photoPath || !$photoRoot || !str_starts_with($photoPath, $photoRoot . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('The daily recipe photo is unavailable.');
    }
    $photoBytes = file_get_contents($photoPath);
    $photo = $photoBytes === false ? false : imagecreatefromstring($photoBytes);
    if (!$photo instanceof GdImage) {
        throw new RuntimeException('The daily recipe photo could not be decoded by GD.');
    }

    if (!is_dir($outputRoot) && !mkdir($outputRoot, 0755, true) && !is_dir($outputRoot)) {
        throw new RuntimeException('The Kitchen carousel output directory could not be created.');
    }
    $lock = fopen($outputRoot . '/.render.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Another Kitchen carousel render is running.');
    }
    try {
        $directory = $outputRoot . '/' . $date;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('The dated Kitchen carousel directory could not be created.');
        }
        $ingredients = array_map(static function (array $item): string {
            $amount = rtrim(rtrim(number_format((float)$item['amount'], 2, '.', ''), '0'), '.');
            return implode(' ', array_filter([$amount, $item['unit'], $item['name']], static fn(string $part): bool => $part !== ''));
        }, $recipe['ingredients']);
        $steps = $recipe['steps'];
        $slides = [
            ['label' => 'Today\'s recipe', 'title' => $recipe['name'], 'body' => $recipe['description']],
            ['label' => 'What you\'ll need', 'title' => 'The ingredients', 'body' => implode("\n", $ingredients)],
            ['label' => 'Let\'s make it · 1', 'title' => 'Get started', 'body' => $steps[0]],
            ['label' => 'Let\'s make it · 2', 'title' => 'Bring it together', 'body' => implode("\n\n", array_slice($steps, 1))],
            ['label' => 'Make it your own', 'title' => 'Ready to enjoy', 'body' => 'Save this recipe for later. Find the full method at recipe.beyondimagination.co.technology/'],
        ];
        $relativeDirectory = 'assets/images/daily/' . $date . '/';
        $imagePaths = [];
        foreach ($slides as $index => $slide) {
            $number = $index + 1;
            $filename = sprintf('slide-%02d.jpg', $number);
            $target = $directory . '/' . $filename;
            if (!is_file($target) || filemtime($target) < max(filemtime($catalogPath), filemtime($photoPath), filemtime(__FILE__))) {
                kitchen_render_slide($target, $photo, $font, $recipe, $slide, $number);
            }
            $imagePaths[] = $relativeDirectory . $filename;
        }
        $manifest = [
            'date' => $date,
            'recipeId' => $recipe['id'],
            'recipeName' => $recipe['name'],
            'description' => $recipe['description'],
            'images' => $imagePaths,
            'slides' => $slides,
            'caption' => $recipe['name'] . " — " . $recipe['description'] . "\n\n" . $recipe['timeMinutes'] . ' min · ' . $recipe['servings'] . " servings. Full recipe: https://recipe.beyondimagination.co.technology/\n\n#BeyondKitchen #EverydayCooking #RecipeIdeas",
        ];
        kitchen_write_json($directory . '/manifest.json', $manifest);
        if ($date === $today) kitchen_write_json($outputRoot . '/latest.json', $manifest);
        return 'Kitchen carousel ready: ' . $date . ' · ' . $recipe['name'];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function kitchen_carousel_font(): string
{
    $candidates = array_filter([
        getenv('BEYOND_KITCHEN_FONT_FILE') ?: null,
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
        'C:/Windows/Fonts/arial.ttf',
    ]);
    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) return $candidate;
    }
    throw new RuntimeException('Set BEYOND_KITCHEN_FONT_FILE to a readable TrueType font.');
}

function kitchen_write_json(string $path, array $value): void
{
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $temporary = $path . '.' . bin2hex(random_bytes(5)) . '.tmp';
    try {
        if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !chmod($temporary, 0644) || !rename($temporary, $path)) {
            throw new RuntimeException('Could not publish Kitchen carousel manifest.');
        }
    } finally {
        if (is_file($temporary)) unlink($temporary);
    }
}

function kitchen_color(GdImage $canvas, int $red, int $green, int $blue): int
{
    return imagecolorallocate($canvas, $red, $green, $blue);
}

function kitchen_lines(string $text, string $font, int $size, int $maxWidth): array
{
    $lines = [];
    foreach (explode("\n", $text) as $paragraph) {
        if (trim($paragraph) === '') {
            $lines[] = '';
            continue;
        }
        $line = '';
        foreach (preg_split('/\s+/u', trim($paragraph)) as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            $bounds = imagettfbbox($size, 0, $font, $candidate);
            if ($line !== '' && ($bounds[2] - $bounds[0]) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }
        $lines[] = $line;
    }
    return $lines;
}

function kitchen_text(GdImage $canvas, string $text, string $font, int $size, int $x, int $y, int $width, int $leading, int $color): int
{
    foreach (kitchen_lines($text, $font, $size, $width) as $line) {
        if ($line !== '') imagettftext($canvas, $size, 0, $x, $y, $color, $font, $line);
        $y += $leading;
    }
    return $y;
}

function kitchen_photo_crop(GdImage $canvas, GdImage $photo, int $x, int $y, int $width, int $height): void
{
    $sourceWidth = imagesx($photo);
    $sourceHeight = imagesy($photo);
    $scale = max($width / $sourceWidth, $height / $sourceHeight);
    $cropWidth = (int)round($width / $scale);
    $cropHeight = (int)round($height / $scale);
    imagecopyresampled($canvas, $photo, $x, $y, (int)(($sourceWidth - $cropWidth) / 2), (int)(($sourceHeight - $cropHeight) / 2), $width, $height, $cropWidth, $cropHeight);
}

function kitchen_render_slide(string $path, GdImage $photo, string $font, array $recipe, array $slide, int $number): void
{
    $canvas = imagecreatetruecolor(1080, 1350);
    if (!$canvas instanceof GdImage) throw new RuntimeException('Could not create Kitchen carousel canvas.');
    try {
        $paper = kitchen_color($canvas, 246, 245, 239);
        $ink = kitchen_color($canvas, 40, 49, 38);
        $green = kitchen_color($canvas, 64, 91, 66);
        $muted = kitchen_color($canvas, 103, 111, 99);
        $white = kitchen_color($canvas, 255, 254, 250);
        $gold = kitchen_color($canvas, 219, 189, 117);
        imagefill($canvas, 0, 0, $paper);
        if ($number === 1 || $number === 5) {
            kitchen_photo_crop($canvas, $photo, 0, 0, 1080, 820);
            imagefilledrectangle($canvas, 0, 820, 1080, 1350, $green);
            kitchen_text($canvas, strtoupper($slide['label']), $font, 21, 76, 904, 910, 34, $gold);
            kitchen_text($canvas, $slide['title'], $font, 54, 76, 992, 910, 69, $white);
            kitchen_text($canvas, $slide['body'], $font, 23, 76, 1115, 910, 38, $white);
        } else {
            imagefilledrectangle($canvas, 0, 0, 1080, 124, $green);
            kitchen_text($canvas, 'BEYOND KITCHEN', $font, 23, 76, 79, 900, 34, $white);
            kitchen_text($canvas, strtoupper($slide['label']), $font, 21, 76, 221, 920, 34, $muted);
            kitchen_text($canvas, $slide['title'], $font, 56, 76, 316, 900, 72, $ink);
            imagefilledrectangle($canvas, 76, 361, 1004, 365, $gold);
            $bodySize = $number === 2 ? 25 : 32;
            $leading = $number === 2 ? 75 : 54;
            kitchen_text($canvas, $slide['body'], $font, $bodySize, 76, 445, 925, $leading, $ink);
            imagefilledrectangle($canvas, 76, 1138, 1004, 1140, $gold);
            kitchen_photo_crop($canvas, $photo, 76, 1168, 136, 110);
            kitchen_text($canvas, $recipe['name'], $font, 23, 238, 1212, 760, 34, $ink);
            kitchen_text($canvas, $recipe['timeMinutes'] . ' min · ' . $recipe['servings'] . ' servings', $font, 18, 238, 1252, 760, 28, $muted);
        }
        kitchen_text($canvas, sprintf('%02d / 05', $number), $font, 18, 910, 1315, 140, 26, $number === 1 || $number === 5 ? $white : $muted);
        $temporary = $path . '.' . bin2hex(random_bytes(5)) . '.tmp';
        try {
            if (!imagejpeg($canvas, $temporary, 88) || !chmod($temporary, 0644) || !rename($temporary, $path)) {
                throw new RuntimeException('Could not publish Kitchen carousel image.');
            }
        } finally {
            if (is_file($temporary)) unlink($temporary);
        }
    } finally {
        unset($canvas);
    }
}

try {
    echo kitchen_daily_carousel($argv[1] ?? null), PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Kitchen carousel: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
