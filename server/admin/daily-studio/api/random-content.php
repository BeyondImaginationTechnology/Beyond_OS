<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$type = strtolower((string)($_GET['type'] ?? ''));
$reset = isset($_GET['reset']) && $_GET['reset'] === '1';
$root = dirname(__DIR__, 4);
$storage = dirname(__DIR__) . '/storage';
if (!is_dir($storage)) { @mkdir($storage, 0775, true); }

function jsonOut(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function readHistory(string $file): array {
    if (!is_file($file)) return [];
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? array_values(array_unique(array_map('strval', $data))) : [];
}
function writeHistory(string $file, array $history): void {
    file_put_contents($file, json_encode(array_values($history), JSON_PRETTY_PRINT), LOCK_EX);
}
function chooseUnused(array $items, array $history, callable $keyFn): array {
    $used = array_fill_keys($history, true);
    $available = array_values(array_filter($items, fn($item) => !isset($used[(string)$keyFn($item)])));
    $cycled = false;
    if (!$available) { $available = array_values($items); $history = []; $cycled = true; }
    if (!$available) throw new RuntimeException('The content bank is empty.');
    $item = $available[random_int(0, count($available) - 1)];
    $history[] = (string)$keyFn($item);
    return [$item, $history, $cycled, count($available) - 1];
}
function bibleCodeNames(): array {
    return [
        'GEN'=>'GENESIS','EXO'=>'EXODUS','LEV'=>'LEVITICUS','NUM'=>'NUMBERS','DEU'=>'DEUTERONOMY','JOS'=>'JOSHUA','JDG'=>'JUDGES','RUT'=>'RUTH',
        '1SA'=>'1 SAMUEL','2SA'=>'2 SAMUEL','1KI'=>'1 KINGS','2KI'=>'2 KINGS','1CH'=>'1 CHRONICLES','2CH'=>'2 CHRONICLES','EZR'=>'EZRA','NEH'=>'NEHEMIAH',
        'EST'=>'ESTHER','JOB'=>'JOB','PSA'=>'PSALM','PRO'=>'PROVERBS','ECC'=>'ECCLESIASTES','SOL'=>'SONG OF SOLOMON','ISA'=>'ISAIAH','JER'=>'JEREMIAH',
        'LAM'=>'LAMENTATIONS','EZE'=>'EZEKIEL','DAN'=>'DANIEL','HOS'=>'HOSEA','JOE'=>'JOEL','AMO'=>'AMOS','OBA'=>'OBADIAH','JON'=>'JONAH','MIC'=>'MICAH',
        'NAH'=>'NAHUM','HAB'=>'HABAKKUK','ZEP'=>'ZEPHANIAH','HAG'=>'HAGGAI','ZEC'=>'ZECHARIAH','MAL'=>'MALACHI','MAT'=>'MATTHEW','MAR'=>'MARK',
        'LUK'=>'LUKE','JOH'=>'JOHN','ACT'=>'ACTS','ROM'=>'ROMANS','1CO'=>'1 CORINTHIANS','2CO'=>'2 CORINTHIANS','GAL'=>'GALATIANS','EPH'=>'EPHESIANS',
        'PHI'=>'PHILIPPIANS','COL'=>'COLOSSIANS','1TH'=>'1 THESSALONIANS','2TH'=>'2 THESSALONIANS','1TI'=>'1 TIMOTHY','2TI'=>'2 TIMOTHY','TIT'=>'TITUS',
        'PHM'=>'PHILEMON','HEB'=>'HEBREWS','JAM'=>'JAMES','1PE'=>'1 PETER','2PE'=>'2 PETER','1JO'=>'1 JOHN','2JO'=>'2 JOHN','3JO'=>'3 JOHN','JUD'=>'JUDE','REV'=>'REVELATION',
    ];
}
function canonicalBibleCode(string $code): string {
    return ['JOH'=>'JHN','MAR'=>'MRK','PHI'=>'PHP'][$code] ?? $code;
}
function bibleIdAliases(string $id): array {
    $id = strtoupper(trim($id));
    $aliases = [$id];
    foreach (['JHN'=>'JOH','JOH'=>'JHN','MRK'=>'MAR','MAR'=>'MRK','PHP'=>'PHI','PHI'=>'PHP'] as $from => $to) {
        if (str_starts_with($id, $from . ' ')) $aliases[] = $to . substr($id, strlen($from));
    }
    return array_values(array_unique($aliases));
}
function loadEnglishBibleBank(string $source): array {
    $names = bibleCodeNames();
    if (!is_file($source)) return [];
    $items = [];
    $handle = fopen($source, 'rb');
    if (!$handle) return [];
    while (($line = fgets($handle)) !== false) {
        if (!preg_match('/^([1-3]?[A-Z]{2,3})\s+(\d+):(\d+)\s+(.+)$/u', trim($line), $match)) continue;
        $code = $match[1];
        if (!isset($names[$code])) continue;
        $chapter = (int)$match[2];
        $verse = (int)$match[3];
        $items[] = [
            canonicalBibleCode($code) . ' ' . $chapter . ':' . $verse,
            trim($match[4]),
            $names[$code] . ' ' . $chapter . ':' . $verse,
        ];
    }
    fclose($handle);
    return $items;
}

try {
    if ($type === 'bible') {
        $language = strtolower((string)($_GET['language'] ?? 'en'));
        if (!in_array($language, ['en','fr','es','jm','ht'], true)) $language = 'en';

        // Let the generator use the dated Content Manager entry first. Public
        // pages still expose published rows only; authenticated Studio users
        // may preview either a draft or a published entry here.
        if (isset($_GET['managed']) && $_GET['managed'] === '1') {
            $managedDate = (string)($_GET['date'] ?? date('Y-m-d'));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $managedDate)) {
                jsonOut(['ok'=>false, 'error'=>'A valid managed-content date is required.'], 422);
            }
            require $root . '/beyond-id/includes/db.php';
            $managedLocale = $language;
            $statement = $pdo->prepare("SELECT publish_date,locale,translation_code,heading,verse_text,scripture_reference,footer_message,background_asset_url,status FROM verse_day_posts WHERE publish_date=? AND locale=? ORDER BY CASE WHEN status='published' THEN 0 ELSE 1 END, id DESC LIMIT 1");
            $statement->execute([$managedDate, $managedLocale]);
            $managed = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$managed || trim((string)($managed['verse_text'] ?? '')) === '') {
                jsonOut(['ok'=>true, 'item'=>null, 'source'=>'generator_placeholder']);
            }
            $managedReference = (string)$managed['scripture_reference'];
            $managedId = '';
            foreach (['GEN'=>'GENESIS','PSA'=>'PSALM','PRO'=>'PROVERBS','ISA'=>'ISAIAH','MAT'=>'MATTHEW','MRK'=>'MARK','JHN'=>'JOHN','ROM'=>'ROMANS','1CO'=>'1 CORINTHIANS','2CO'=>'2 CORINTHIANS','GAL'=>'GALATIANS','PHP'=>'PHILIPPIANS','COL'=>'COLOSSIANS','1TH'=>'1 THESSALONIANS','HEB'=>'HEBREWS'] as $code => $bookName) {
                if (preg_match('/^' . preg_quote($bookName, '/') . '\s+(\d+):(\d+)/i', $managedReference, $parts)) {
                    $managedId = $code . ' ' . $parts[1] . ':' . $parts[2];
                    break;
                }
            }
            $backgroundAsset = (string)($managed['background_asset_url'] ?? '');
            $templateStyle = str_contains($backgroundAsset, 'olive-sanctuary') ? 'olive'
                : (str_contains($backgroundAsset, 'heritage-botanical') ? 'botanical'
                : (str_contains($backgroundAsset, 'modern-aurora') ? 'forest'
                : (str_contains($backgroundAsset, 'living-sanctuary') ? 'advanced' : 'forest')));
            jsonOut(['ok'=>true, 'source'=>'content_manager', 'item'=>[
                'id'=>$managedId,
                'verse'=>(string)$managed['verse_text'],
                'reference'=>$managedReference,
                'translation'=>(string)($managed['translation_code'] ?: 'KJV'),
                'heading'=>(string)($managed['heading'] ?: 'VERSE OF THE DAY'),
                'footer'=>(string)($managed['footer_message'] ?: 'WALK IN FAITH TODAY.'),
                'template_style'=>$templateStyle,
                'publish_date'=>(string)$managed['publish_date'],
                'locale'=>(string)$managed['locale'],
                'status'=>(string)$managed['status'],
            ]]);
        }
        $historyFile = $storage . '/bible-history-' . $language . '.json';
        if ($reset) { writeHistory($historyFile, []); jsonOut(['ok'=>true,'reset'=>true,'language'=>$language]); }
        // English generation is intentionally recovery-focused for Daily
        // Breath users working through cravings, dependence, and habit change.
        $englishRecovery = [
          ['PSA 34:17','The righteous cry, and the LORD hears, and delivers them out of all their troubles.','PSALM 34:17'],
          ['PSA 46:1','God is our refuge and strength, a very present help in trouble.','PSALM 46:1'],
          ['PSA 46:10','Be still, and know that I am God.','PSALM 46:10'],
          ['PSA 50:15','Call on me in the day of trouble. I will deliver you, and you will honor me.','PSALM 50:15'],
          ['PSA 55:22','Cast your burden on the LORD and he will sustain you.','PSALM 55:22'],
          ['PSA 107:13','Then they cried to the LORD in their trouble, and he saved them out of their distresses.','PSALM 107:13'],
          ['PSA 107:14','He brought them out of darkness and the shadow of death, and broke away their chains.','PSALM 107:14'],
          ['PRO 3:5','Trust in the LORD with all your heart, and don’t lean on your own understanding.','PROVERBS 3:5'],
          ['PRO 3:6','In all your ways acknowledge him, and he will make your paths straight.','PROVERBS 3:6'],
          ['ISA 40:29','He gives power to the weak. He increases the strength of him who has no might.','ISAIAH 40:29'],
          ['ISA 41:10','Don’t you be afraid, for I am with you. Don’t be dismayed, for I am your God. I will strengthen you. Yes, I will help you.','ISAIAH 41:10'],
          ['ISA 43:2','When you pass through the waters, I will be with you, and through the rivers, they will not overflow you.','ISAIAH 43:2'],
          ['LAM 3:22','It is because of the LORD’s loving kindnesses that we are not consumed, because his mercies don’t fail.','LAMENTATIONS 3:22'],
          ['LAM 3:23','They are new every morning. Great is your faithfulness.','LAMENTATIONS 3:23'],
          ['MAT 11:28','Come to me, all you who labor and are heavily burdened, and I will give you rest.','MATTHEW 11:28'],
          ['JHN 8:36','If therefore the Son makes you free, you will be free indeed.','JOHN 8:36'],
          ['ROM 6:14','For sin will not have dominion over you, for you are not under law, but under grace.','ROMANS 6:14'],
          ['ROM 12:2','Don’t be conformed to this world, but be transformed by the renewing of your mind.','ROMANS 12:2'],
          ['ROM 12:21','Don’t be overcome by evil, but overcome evil with good.','ROMANS 12:21'],
          ['1CO 6:19','Don’t you know that your body is a temple of the Holy Spirit who is in you, whom you have from God?','1 CORINTHIANS 6:19'],
          ['1CO 6:20','For you were bought with a price. Therefore glorify God in your body and in your spirit, which are God’s.','1 CORINTHIANS 6:20'],
          ['1CO 9:27','I beat my body and bring it into submission.','1 CORINTHIANS 9:27'],
          ['1CO 10:13','God is faithful, who will not allow you to be tempted above what you are able, but will with the temptation also make the way of escape.','1 CORINTHIANS 10:13'],
          ['2CO 12:9','My grace is sufficient for you, for my power is made perfect in weakness.','2 CORINTHIANS 12:9'],
          ['GAL 5:1','Stand firm therefore in the liberty by which Christ has made us free, and don’t be entangled again with a yoke of bondage.','GALATIANS 5:1'],
          ['GAL 5:16','Walk by the Spirit, and you won’t fulfill the lust of the flesh.','GALATIANS 5:16'],
          ['GAL 5:22','The fruit of the Spirit is love, joy, peace, patience, kindness, goodness, faith.','GALATIANS 5:22'],
          ['GAL 5:23','Gentleness, and self-control. Against such things there is no law.','GALATIANS 5:23'],
          ['EPH 6:11','Put on the whole armor of God, that you may be able to stand against the wiles of the devil.','EPHESIANS 6:11'],
          ['PHP 4:6','In nothing be anxious, but in everything, by prayer and petition with thanksgiving, let your requests be made known to God.','PHILIPPIANS 4:6'],
          ['PHP 4:7','And the peace of God, which surpasses all understanding, will guard your hearts and your thoughts in Christ Jesus.','PHILIPPIANS 4:7'],
          ['PHP 4:13','I can do all things through Christ who strengthens me.','PHILIPPIANS 4:13'],
          ['2TI 1:7','For God didn’t give us a spirit of fear, but of power, love, and self-control.','2 TIMOTHY 1:7'],
          ['TIT 2:12','Denying ungodliness and worldly lusts, we would live soberly, righteously, and godly in this present age.','TITUS 2:12'],
          ['HEB 12:1','Let’s lay aside every weight and the sin which so easily entangles us, and run with perseverance the race that is set before us.','HEBREWS 12:1'],
          ['JAM 4:7','Be subject therefore to God. Resist the devil, and he will flee from you.','JAMES 4:7'],
          ['1PE 5:7','Casting all your worries on him, because he cares for you.','1 PETER 5:7'],
          ['1JO 5:4','This is the victory that has overcome the world: your faith.','1 JOHN 5:4']
        ];
        $englishBank = $web ?: $kjvFallback;
        $french = [
          ['GEN 28:15','Voici, je suis avec toi, je te garderai partout où tu iras.','GENÈSE 28:15'],
          ['PSA 23:1','L’Éternel est mon berger: je ne manquerai de rien.','PSAUME 23:1'],
          ['PSA 34:8','Sentez et voyez combien l’Éternel est bon! Heureux l’homme qui cherche en lui son refuge!','PSAUME 34:8'],
          ['PSA 46:10','Arrêtez, et sachez que je suis Dieu.','PSAUME 46:10'],
          ['PSA 56:3','Quand je suis dans la crainte, en toi je me confie.','PSAUME 56:3'],
          ['PSA 118:24','C’est ici la journée que l’Éternel a faite: qu’elle soit pour nous un sujet d’allégresse et de joie!','PSAUME 118:24'],
          ['PRO 3:5','Confie-toi en l’Éternel de tout ton cœur, et ne t’appuie pas sur ta sagesse.','PROVERBES 3:5'],
          ['PRO 16:3','Recommande à l’Éternel tes œuvres, et tes projets réussiront.','PROVERBES 16:3'],
          ['ISA 43:2','Si tu traverses les eaux, je serai avec toi.','ÉSAÏE 43:2'],
          ['MAT 6:33','Cherchez premièrement le royaume et la justice de Dieu; et toutes ces choses vous seront données par-dessus.','MATTHIEU 6:33'],
          ['MAT 11:28','Venez à moi, vous tous qui êtes fatigués et chargés, et je vous donnerai du repos.','MATTHIEU 11:28'],
          ['JHN 14:27','Je vous laisse la paix, je vous donne ma paix. Que votre cœur ne se trouble point, et ne s’alarme point.','JEAN 14:27'],
          ['JHN 15:5','Je suis le cep, vous êtes les sarments. Celui qui demeure en moi et en qui je demeure porte beaucoup de fruit.','JEAN 15:5'],
          ['ROM 15:13','Que le Dieu de l’espérance vous remplisse de toute joie et de toute paix dans la foi.','ROMAINS 15:13'],
          ['2CO 5:7','Car nous marchons par la foi et non par la vue.','2 CORINTHIENS 5:7'],
          ['GAL 6:9','Ne nous lassons pas de faire le bien; car nous moissonnerons au temps convenable, si nous ne nous relâchons pas.','GALATES 6:9'],
          ['PHP 4:13','Je puis tout par celui qui me fortifie.','PHILIPPIENS 4:13'],
          ['COL 3:15','Que la paix de Christ règne dans vos cœurs.','COLOSSIENS 3:15'],
          ['1TH 5:17','Priez sans cesse.','1 THESSALONICIENS 5:17']
        ];
        $spanish = [
          ['GEN 28:15','He aquí, yo estoy contigo, y te guardaré por dondequiera que fueres.','GÉNESIS 28:15'],
          ['PSA 23:1','Jehová es mi pastor; nada me faltará.','SALMOS 23:1'],
          ['PSA 34:8','Gustad, y ved que es bueno Jehová; dichoso el hombre que confía en él.','SALMOS 34:8'],
          ['PSA 46:10','Estad quietos, y conoced que yo soy Dios.','SALMOS 46:10'],
          ['PSA 56:3','En el día que temo, yo en ti confío.','SALMOS 56:3'],
          ['PSA 118:24','Este es el día que hizo Jehová; nos gozaremos y alegraremos en él.','SALMOS 118:24'],
          ['PRO 3:5','Fíate de Jehová de todo tu corazón, y no te apoyes en tu propia prudencia.','PROVERBIOS 3:5'],
          ['PRO 16:3','Encomienda a Jehová tus obras, y tus pensamientos serán afirmados.','PROVERBIOS 16:3'],
          ['ISA 43:2','Cuando pases por las aguas, yo estaré contigo.','ISAÍAS 43:2'],
          ['MAT 6:33','Mas buscad primeramente el reino de Dios y su justicia, y todas estas cosas os serán añadidas.','MATEO 6:33'],
          ['MAT 11:28','Venid a mí todos los que estáis trabajados y cargados, y yo os haré descansar.','MATEO 11:28'],
          ['JHN 14:27','La paz os dejo, mi paz os doy; no se turbe vuestro corazón, ni tenga miedo.','JUAN 14:27'],
          ['JHN 15:5','Yo soy la vid, vosotros los pámpanos; el que permanece en mí, y yo en él, éste lleva mucho fruto.','JUAN 15:5'],
          ['ROM 15:13','Y el Dios de esperanza os llene de todo gozo y paz en el creer.','ROMANOS 15:13'],
          ['2CO 5:7','Porque por fe andamos, no por vista.','2 CORINTIOS 5:7'],
          ['GAL 6:9','No nos cansemos, pues, de hacer bien; porque a su tiempo segaremos, si no desmayamos.','GÁLATAS 6:9'],
          ['PHP 4:13','Todo lo puedo en Cristo que me fortalece.','FILIPENSES 4:13'],
          ['COL 3:15','Y la paz de Dios gobierne en vuestros corazones.','COLOSENSES 3:15'],
          ['1TH 5:17','Orad sin cesar.','1 TESALONICENSES 5:17']
        ];
        $patois = [
          ['GEN 28:15','Look yah, mi deh wid yuh, an mi will keep yuh everyweh yuh go.','GENESIS 28:15'],
          ['PSA 23:1','Di LORD a mi shepherd; mi nah go want.','PSALM 23:1'],
          ['PSA 34:8','Taste an see seh di LORD good; blessed is di one weh trust inna him.','PSALM 34:8'],
          ['PSA 46:10','Keep still, an know seh mi a God.','PSALM 46:10'],
          ['PSA 56:3','Any time mi fraid, a you mi put mi trust inna.','PSALM 56:3'],
          ['PSA 118:24','A dis di day weh di LORD make; mek wi rejoice an glad inna it.','PSALM 118:24'],
          ['PRO 3:5','Trust inna di LORD wid all yuh heart, an nuh lean pon yuh own understanding.','PROVERBS 3:5'],
          ['PRO 16:3','Commit yuh work to di LORD, an yuh plans will set firm.','PROVERBS 16:3'],
          ['ISA 43:2','When yuh pass through di water, mi will deh wid yuh.','ISAIAH 43:2'],
          ['MAT 6:33','Seek God kingdom first, an him righteousness, an all dem things yah will add to yuh.','MATTHEW 6:33'],
          ['MAT 11:28','Come to me, all a unnu weh tired an carry heavy load, an mi will gi unnu rest.','MATTHEW 11:28'],
          ['JHN 14:27','Peace mi leave wid unnu; a mi peace mi give unnu. Nuh mek unnu heart trouble or fraid.','JOHN 14:27'],
          ['JHN 15:5','Mi a di vine, unnu a di branch dem. Who stay inna me will bear nuff fruit.','JOHN 15:5'],
          ['ROM 15:13','May di God of hope full unnu wid joy an peace as unnu believe.','ROMANS 15:13'],
          ['2CO 5:7','For wi walk by faith, not by sight.','2 CORINTHIANS 5:7'],
          ['GAL 6:9','Mek wi nuh get tired fi do good, cause in due season wi will reap if wi nuh give up.','GALATIANS 6:9'],
          ['PHP 4:13','Mi can do all things through Christ weh strengthen mi.','PHILIPPIANS 4:13'],
          ['COL 3:15','Mek di peace of God rule inna unnu heart.','COLOSSIANS 3:15'],
          ['1TH 5:17','Pray without stopping.','1 THESSALONIANS 5:17']
        ];
        $kreyol = [
          ['GEN 28:15','Men mwen la avèk ou, m ap pwoteje ou tout kote ou prale.','JENÈZ 28:15'],
          ['PSA 23:1','Seyè a se gadò mwen; mwen p ap manke anyen.','SÒM 23:1'],
          ['PSA 34:8','Goute, epi wè jan Seyè a bon; benediksyon pou moun ki mete konfyans li nan li.','SÒM 34:8'],
          ['PSA 46:10','Rete trankil, epi konnen se mwen menm ki Bondye.','SÒM 46:10'],
          ['PSA 56:3','Lè mwen pè, se nan ou mwen mete konfyans mwen.','SÒM 56:3'],
          ['PSA 118:24','Se jou sa a Seyè a fè; ann rejwi epi ann kontan ladan li.','SÒM 118:24'],
          ['PRO 3:5','Mete tout konfyans ou nan Seyè a; pa konte sou pwòp konprann pa ou.','PWOVÈB 3:5'],
          ['PRO 16:3','Mete travay ou yo nan men Seyè a, epi plan ou yo va kanpe fèm.','PWOVÈB 16:3'],
          ['ISA 43:2','Lè w ap pase nan dlo, mwen va la avèk ou.','EZAYI 43:2'],
          ['MAT 6:33','Chèche wayòm Bondye a ak jistis li an premye, epi tout bagay sa yo ap ajoute pou nou.','MATYE 6:33'],
          ['MAT 11:28','Vini jwenn mwen, nou tout ki fatige e ki chaje, epi m ap ban nou repo.','MATYE 11:28'],
          ['JHN 14:27','Mwen kite lapè pou nou; mwen ban nou lapè mwen. Pa kite kè nou boulvèse ni pè.','JAN 14:27'],
          ['JHN 15:5','Mwen se pye rezen an; nou se branch yo. Moun ki rete nan mwen ap bay anpil fwi.','JAN 15:5'],
          ['ROM 15:13','Se pou Bondye espwa a ranpli nou ak tout kè kontan ak lapè pandan n ap kwè.','WOMEN 15:13'],
          ['2CO 5:7','Paske n ap mache grasa lafwa, pa grasa sa nou wè.','2 KORENTYEN 5:7'],
          ['GAL 6:9','Pa kite nou bouke fè sa ki byen; paske lè tan an rive, n ap rekòlte si nou pa lage.','GALAT 6:9'],
          ['PHP 4:13','Mwen kapab fè tout bagay grasa Kris la ki ban mwen fòs.','FILIPYEN 4:13'],
          ['COL 3:15','Se pou lapè Kris la dirije kè nou.','KOLOSYEN 3:15'],
          ['1TH 5:17','Priye san rete.','1 TESALONISYEN 5:17']
        ];
        $banks = ['en'=>[$englishRecovery,'WEB'], 'fr'=>[$french,'LSG'], 'es'=>[$spanish,'RVR'], 'jm'=>[$patois,'PATOIS'], 'ht'=>[$kreyol,'KREYÒL']];
        [$bank,$translation] = $banks[$language];
        $items = array_map(function ($v) use ($translation) {
            preg_match('/^(.+)\s+(\d+):(\d+)$/u', $v[2], $parts);
            return ['id'=>$v[0], 'verse'=>$v[1], 'reference'=>$v[2], 'translation'=>$translation, 'book'=>$parts[1] ?? $v[2], 'chapter'=>(int)($parts[2] ?? 1), 'verse_number'=>(int)($parts[3] ?? 1)];
        }, $bank);
        $footerBanks = [
          'en'=>['BREATHE THROUGH THE CRAVING. GOD IS WITH YOU.','ONE FREE BREATH AT A TIME.','LET PEACE GUIDE YOUR NEXT STEP.','TRUST GOD WITH THIS MOMENT.','GRACE IS STRONGER THAN THE URGE.'],
          'fr'=>['RESPIREZ PROFONDÉMENT. DIEU EST AVEC VOUS.','MARCHEZ DANS LA FOI AUJOURD’HUI.','LAISSEZ LA PAIX GUIDER VOS PAS.','CONFIEZ CETTE JOURNÉE À DIEU.'],
          'es'=>['RESPIRA PROFUNDO. DIOS ESTÁ CONTIGO.','CAMINA EN FE HOY.','DEJA QUE LA PAZ GUÍE TUS PASOS.','CONFÍA ESTE DÍA A DIOS.'],
          'jm'=>['BREATHE DEEP. GOD DEH WID YUH.','WALK INNA FAITH TODAY.','MEK PEACE GUIDE YUH NEXT STEP.','TRUST GOD WID TODAY.'],
          'ht'=>['RESPIRE FON. BONDYE AVÈ W.','MACHE NAN LAFWA JODI A.','KITE LAPÈ GIDE PWOCHEN PA W.','KONFYE JOUNEN SA A NAN BONDYE.']
        ];
        $footers = $footerBanks[$language];
        if (isset($_GET['catalog']) && $_GET['catalog'] === '1') {
            $catalog = array_map(fn($i) => ['id'=>$i['id'],'book'=>$i['book'],'chapter'=>$i['chapter'],'verse_number'=>$i['verse_number'],'reference'=>$i['reference']], $items);
            jsonOut(['ok'=>true,'language'=>$language,'translation'=>$translation,'items'=>$catalog]);
        }
        $selectedId = strtoupper(trim((string)($_GET['id'] ?? '')));
        $selectedBook = strtoupper(trim((string)($_GET['book'] ?? '')));
        $selectedChapter = (int)($_GET['chapter'] ?? 0);
        $selectedVerse = (int)($_GET['verse'] ?? 0);
        $cycled = false;
        $remaining = count($items);
        if ($selectedId !== '') {
            $selectedAliases = array_fill_keys(bibleIdAliases($selectedId), true);
            $matches = array_values(array_filter($items, fn($i) => isset($selectedAliases[strtoupper($i['id'])])));
            if (!$matches) jsonOut(['error'=>'The selected verse is not available in this language bank.'], 404);
            $item = $matches[0];
        } elseif ($selectedBook !== '' && $selectedChapter > 0 && $selectedVerse > 0) {
            $matches = array_values(array_filter($items, fn($i) => strtoupper($i['book']) === $selectedBook && $i['chapter'] === $selectedChapter && $i['verse_number'] === $selectedVerse));
            if (!$matches) jsonOut(['error'=>'The selected verse is not available in this language bank.'], 404);
            $item = $matches[0];
        } else {
            $history = readHistory($historyFile);
            [$item,$history,$cycled,$remaining] = chooseUnused($items,$history,fn($i)=>$i['id']);
            writeHistory($historyFile,$history);
        }
        $item['footer'] = $footers[random_int(0,count($footers)-1)];
        jsonOut(['ok'=>true,'item'=>$item,'total'=>count($items),'remaining'=>$remaining,'cycle_restarted'=>$cycled]);
    }

    if ($type === 'french') {
        $historyFile = $storage . '/french-history.json';
        if ($reset) { writeHistory($historyFile, []); jsonOut(['ok'=>true,'reset'=>true]); }
        $source = $root . '/beyond-french/data/lessons.json';
        $items = json_decode((string)file_get_contents($source), true);
        if (!is_array($items)) throw new RuntimeException('French lesson bank is invalid.');
        $items = array_values(array_filter($items, static fn($item): bool => trim((string)($item['date'] ?? '')) === ''));
        if (!$items) throw new RuntimeException('No unscheduled French draft ideas remain. Add an undated lesson before generating another draft.');
        $history = readHistory($historyFile);
        [$item,$history,$cycled,$remaining] = chooseUnused($items,$history,fn($i)=>$i['id'] ?? sha1(json_encode($i)));
        writeHistory($historyFile,$history);
        jsonOut(['ok'=>true,'item'=>$item,'total'=>count($items),'remaining'=>$remaining,'cycle_restarted'=>$cycled]);
    }
    jsonOut(['error'=>'Unknown content type.'], 400);
} catch (Throwable $e) {
    jsonOut(['error'=>$e->getMessage()], 500);
}
