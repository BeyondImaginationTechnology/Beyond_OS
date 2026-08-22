<?php
declare(strict_types=1);

function bt_db(): PDO
{
    static $pdo;
    if (!$pdo instanceof PDO) $pdo = beyond_db();
    return $pdo;
}

function bt_current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function bt_current_user(): ?array
{
    $userId = bt_current_user_id();
    if ($userId < 1) return null;
    $stmt = bt_db()->prepare(
        'SELECT u.id,u.name,u.first_name,u.last_name,u.email,u.role AS beyond_role,'
        . 'tp.account_type,tp.city,tp.bio,tp.styles,tp.experience,tp.studio_name,tp.budget,tp.availability,tp.onboarding_complete '
        . 'FROM users u LEFT JOIN tattoo_profiles tp ON tp.user_id=u.id WHERE u.id=? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($user) ? $user : null;
}

function bt_save_profile(int $userId, array $profile): void
{
    $allowed = ['client', 'artist', 'owner'];
    $accountType = in_array($profile['account_type'] ?? '', $allowed, true) ? $profile['account_type'] : 'client';
    $values = [
        $userId,
        $accountType,
        mb_substr(trim((string)($profile['city'] ?? '')), 0, 160),
        mb_substr(trim((string)($profile['bio'] ?? '')), 0, 4000),
        mb_substr(trim((string)($profile['styles'] ?? '')), 0, 500),
        mb_substr(trim((string)($profile['experience'] ?? '')), 0, 160),
        mb_substr(trim((string)($profile['studio_name'] ?? '')), 0, 200),
        mb_substr(trim((string)($profile['budget'] ?? '')), 0, 160),
        mb_substr(trim((string)($profile['availability'] ?? '')), 0, 255),
    ];
    $pdo = bt_db();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $sql = 'INSERT INTO tattoo_profiles (user_id,account_type,city,bio,styles,experience,studio_name,budget,availability,onboarding_complete) '
            . 'VALUES (?,?,?,?,?,?,?,?,?,1) ON CONFLICT(user_id) DO UPDATE SET account_type=excluded.account_type,city=excluded.city,'
            . 'bio=excluded.bio,styles=excluded.styles,experience=excluded.experience,studio_name=excluded.studio_name,budget=excluded.budget,'
            . 'availability=excluded.availability,onboarding_complete=1,updated_at=CURRENT_TIMESTAMP';
    } else {
        $sql = 'INSERT INTO tattoo_profiles (user_id,account_type,city,bio,styles,experience,studio_name,budget,availability,onboarding_complete) '
            . 'VALUES (?,?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE account_type=VALUES(account_type),city=VALUES(city),bio=VALUES(bio),'
            . 'styles=VALUES(styles),experience=VALUES(experience),studio_name=VALUES(studio_name),budget=VALUES(budget),'
            . 'availability=VALUES(availability),onboarding_complete=1,updated_at=CURRENT_TIMESTAMP';
    }
    $pdo->prepare($sql)->execute($values);
}

function bt_list_studios(string $query = ''): array
{
    $query = trim($query);
    $sql = "SELECT s.*,(SELECT COUNT(*) FROM tattoo_artists a WHERE a.studio_id=s.id AND a.status='active') AS artist_count "
        . "FROM tattoo_studios s WHERE s.status='active'";
    $params = [];
    if ($query !== '') {
        $sql .= ' AND (LOWER(s.name) LIKE ? OR LOWER(s.city) LIKE ? OR LOWER(s.province) LIKE ? OR LOWER(s.country) LIKE ? OR LOWER(s.services) LIKE ?)';
        $needle = '%' . strtolower($query) . '%';
        $params = [$needle, $needle, $needle, $needle, $needle];
    }
    $sql .= " ORDER BY CASE WHEN s.city='Ottawa' THEN 0 ELSE 1 END,s.city,s.name";
    $stmt = bt_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function bt_get_studio(string $slug): ?array
{
    $stmt = bt_db()->prepare("SELECT * FROM tattoo_studios WHERE slug=? AND status='active' LIMIT 1");
    $stmt->execute([$slug]);
    $studio = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($studio) ? $studio : null;
}

function bt_list_artists(?int $studioId = null): array
{
    $sql = "SELECT a.*,s.name AS studio_name,s.slug AS studio_slug FROM tattoo_artists a LEFT JOIN tattoo_studios s ON s.id=a.studio_id WHERE a.status='active'";
    $params = [];
    if ($studioId !== null) {
        $sql .= ' AND a.studio_id=?';
        $params[] = $studioId;
    }
    $sql .= ' ORDER BY a.display_name';
    $stmt = bt_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function bt_get_artist(string $slug): ?array
{
    $stmt = bt_db()->prepare(
        "SELECT a.*,s.name AS studio_name,s.slug AS studio_slug,s.address_line1 AS studio_address,s.city AS studio_city,s.province AS studio_province "
        . "FROM tattoo_artists a LEFT JOIN tattoo_studios s ON s.id=a.studio_id WHERE a.slug=? AND a.status='active' LIMIT 1"
    );
    $stmt->execute([$slug]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($artist) ? $artist : null;
}

function bt_create_tattoo(int $userId, array $data): int
{
    $name = mb_substr(trim((string)($data['name'] ?? '')), 0, 200);
    if ($name === '') throw new InvalidArgumentException('Tattoo name is required.');
    $date = DateTimeImmutable::createFromFormat('Y-m-d', (string)($data['start_date'] ?? ''));
    $startDate = $date ? $date->format('Y-m-d') : date('Y-m-d');
    $pdo = bt_db();
    $stmt = $pdo->prepare(
        'INSERT INTO tattoo_tattoos (user_id,name,artist_name,studio_name,placement,style,start_date,healing_days,status,notes) '
        . "VALUES (?,?,?,?,?,?,?,28,'active',?)"
    );
    $stmt->execute([
        $userId,
        $name,
        mb_substr(trim((string)($data['artist'] ?? '')), 0, 200),
        mb_substr(trim((string)($data['studio'] ?? '')), 0, 200),
        mb_substr(trim((string)($data['placement'] ?? '')), 0, 160),
        mb_substr(trim((string)($data['style'] ?? '')), 0, 160),
        $startDate,
        mb_substr(trim((string)($data['notes'] ?? '')), 0, 4000),
    ]);
    return (int)$pdo->lastInsertId();
}

function bt_list_tattoos(int $userId): array
{
    $stmt = bt_db()->prepare(
        "SELECT t.*,(SELECT COUNT(*) FROM tattoo_healing_entries h WHERE h.tattoo_id=t.id) AS healing_entry_count "
        . 'FROM tattoo_tattoos t WHERE t.user_id=? ORDER BY t.start_date DESC,t.id DESC'
    );
    $stmt->execute([$userId]);
    $tattoos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $today = new DateTimeImmutable('today');
    foreach ($tattoos as &$tattoo) {
        try {
            $start = new DateTimeImmutable((string)$tattoo['start_date']);
            $day = max(1, (int)$start->diff($today)->format('%r%a') + 1);
        } catch (Throwable $e) {
            $day = 1;
        }
        $healingDays = max(1, (int)($tattoo['healing_days'] ?? 28));
        $tattoo['healing_day'] = min($day, $healingDays);
        $tattoo['progress'] = min(100, (int)round(($day / $healingDays) * 100));
        if ($day > $healingDays && ($tattoo['status'] ?? '') === 'active') $tattoo['status'] = 'healed';
    }
    unset($tattoo);
    return $tattoos;
}

function bt_owned_tattoo(int $userId, int $tattooId): ?array
{
    $stmt = bt_db()->prepare('SELECT * FROM tattoo_tattoos WHERE id=? AND user_id=? LIMIT 1');
    $stmt->execute([$tattooId, $userId]);
    $tattoo = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($tattoo) ? $tattoo : null;
}

function bt_add_healing_entry(int $userId, ?int $tattooId, array $entry): int
{
    if ($tattooId !== null && !bt_owned_tattoo($userId, $tattooId)) throw new InvalidArgumentException('Tattoo not found.');
    $pdo = bt_db();
    $stmt = $pdo->prepare(
        'INSERT INTO tattoo_healing_entries (user_id,tattoo_id,file_path,mime,bytes,width,height,notes) VALUES (?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $userId,
        $tattooId,
        (string)$entry['file_path'],
        (string)$entry['mime'],
        (int)$entry['bytes'],
        (int)$entry['width'],
        (int)$entry['height'],
        mb_substr(trim((string)($entry['notes'] ?? '')), 0, 2000),
    ]);
    return (int)$pdo->lastInsertId();
}

function bt_list_healing_entries(int $userId): array
{
    $stmt = bt_db()->prepare(
        'SELECT h.id,h.tattoo_id,h.mime,h.notes,h.created_at,t.name AS tattoo_name '
        . 'FROM tattoo_healing_entries h LEFT JOIN tattoo_tattoos t ON t.id=h.tattoo_id '
        . 'WHERE h.user_id=? ORDER BY h.created_at DESC,h.id DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function bt_delete_healing_entry(int $userId, int $entryId): ?string
{
    $pdo = bt_db();
    $stmt = $pdo->prepare('SELECT file_path FROM tattoo_healing_entries WHERE id=? AND user_id=? LIMIT 1');
    $stmt->execute([$entryId, $userId]);
    $path = $stmt->fetchColumn();
    if (!is_string($path)) return null;
    $delete = $pdo->prepare('DELETE FROM tattoo_healing_entries WHERE id=? AND user_id=?');
    $delete->execute([$entryId, $userId]);
    return $delete->rowCount() === 1 ? $path : null;
}

function bt_add_beta_signup(string $name, string $email, string $interest): bool
{
    $pdo = bt_db();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $sql = 'INSERT OR IGNORE INTO tattoo_beta_signups (name,email,interest) VALUES (?,?,?)';
    } else {
        $sql = 'INSERT IGNORE INTO tattoo_beta_signups (name,email,interest) VALUES (?,?,?)';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $interest]);
    return $stmt->rowCount() === 1;
}

function bt_create_job(int $ownerUserId, string $studioName, string $title, string $type, string $details): void
{
    $stmt = bt_db()->prepare('INSERT INTO tattoo_jobs (owner_user_id,studio_name,title,opportunity_type,details) VALUES (?,?,?,?,?)');
    $stmt->execute([$ownerUserId, $studioName, $title, $type, $details]);
}

function bt_create_invite(int $ownerUserId, ?int $artistId, string $targetLabel, string $message): void
{
    $stmt = bt_db()->prepare('INSERT INTO tattoo_invites (owner_user_id,artist_id,target_label,message) VALUES (?,?,?,?)');
    $stmt->execute([$ownerUserId, $artistId, $targetLabel, $message]);
}

function bt_owner_metrics(int $userId): array
{
    $pdo=bt_db(); $result=['open_jobs'=>0,'invites'=>0,'listed_artists'=>0];
    $stmt=$pdo->prepare("SELECT COUNT(*) FROM tattoo_jobs WHERE owner_user_id=? AND status='open'");$stmt->execute([$userId]);$result['open_jobs']=(int)$stmt->fetchColumn();
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM tattoo_invites WHERE owner_user_id=?');$stmt->execute([$userId]);$result['invites']=(int)$stmt->fetchColumn();
    $result['listed_artists']=(int)$pdo->query("SELECT COUNT(*) FROM tattoo_artists WHERE status='active'")->fetchColumn();
    return $result;
}

function bt_list_open_jobs(): array
{
    $stmt=bt_db()->query("SELECT id,studio_name,title,opportunity_type,details,created_at FROM tattoo_jobs WHERE status='open' ORDER BY created_at DESC,id DESC LIMIT 12");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
