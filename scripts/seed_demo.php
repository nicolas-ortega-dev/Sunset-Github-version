<?php
declare(strict_types=1);
/*
 * Genera datos de demostración para Sunset.
 * Uso: C:\xampp\php\php.exe C:\xampp\htdocs\sunset\scripts\seed_demo.php
 * Se puede ejecutar varias veces: no duplica registros.
 */
if (PHP_SAPI !== 'cli') exit("Solo desde consola.\n");
require dirname(__DIR__) . '/src/bootstrap.php';

use Sunset\Core\Database;
use Sunset\Models\Book;

set_time_limit(0);
$pdo = Database::pdo();

echo "=== Sunset: seed de demostración ===\n\n";

// ── 1) Usuarios demo ─────────────────────────────────────────────────
$demoUsers = [
    ['lector_ana',    'ana@demo.sunset',    'Apasionada por la fantasía y el manga. 📚✨', 'Santiago, Chile'],
    ['bookworm_leo',  'leo@demo.sunset',    'Leo un libro por semana desde 2018. 🏆',     'Valparaíso, Chile'],
    ['manga_otaku',   'otaku@demo.sunset',  'Coleccionista de manga y novelas ligeras. 🎌','Concepción, Chile'],
    ['clasicos_club', 'clasicos@demo.sunset','Solo los mejores clásicos de la literatura. 📖','Buenos Aires, AR'],
    ['nico_reader',   'nico@demo.sunset',   'Demo user principal para presentaciones.',    'Chile'],
];
$userIds = [];
foreach ($demoUsers as [$username, $email, $bio, $location]) {
    $st = $pdo->prepare('SELECT id FROM users WHERE username = :u');
    $st->execute(['u' => $username]);
    $existing = $st->fetchColumn();
    if ($existing) {
        $userIds[$username] = (int)$existing;
        echo "  Usuario '$username' ya existe (id $existing)\n";
        continue;
    }
    $pdo->prepare('INSERT INTO users (username,email,password_hash,bio,location) VALUES(:u,:e,:p,:b,:l)')
        ->execute(['u'=>$username,'e'=>$email,'p'=>password_hash('demo1234',$PASSWORD_DEFAULT??PASSWORD_DEFAULT),'b'=>$bio,'l'=>$location]);
    $id = (int)$pdo->lastInsertId();
    $userIds[$username] = $id;
    echo "  + Usuario '$username' (id $id)\n";
}

// ── 2) Libros de referencia ──────────────────────────────────────────
$st = $pdo->query('SELECT id,title,category,language FROM books ORDER BY edition_count DESC, rating_count DESC LIMIT 20');
$books = $st->fetchAll();
if (!$books) { echo "\nNo hay libros en la base. Ejecuta seed_catalog.php primero.\n"; exit(1); }
echo "\n  " . count($books) . " libros de referencia cargados.\n";

// ── 3) Follows entre usuarios demo ──────────────────────────────────
$followPairs = [
    ['lector_ana','bookworm_leo'],['lector_ana','manga_otaku'],
    ['bookworm_leo','lector_ana'],['bookworm_leo','clasicos_club'],
    ['manga_otaku','lector_ana'],['clasicos_club','nico_reader'],
    ['nico_reader','lector_ana'],['nico_reader','bookworm_leo'],
];
$follows = 0;
foreach ($followPairs as [$from, $to]) {
    if (!isset($userIds[$from], $userIds[$to])) continue;
    $f = $userIds[$from]; $t = $userIds[$to];
    $st = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id=:f AND followed_id=:t');
    $st->execute(['f'=>$f,'t'=>$t]);
    if (!$st->fetchColumn()) {
        $pdo->prepare('INSERT INTO follows(follower_id,followed_id) VALUES(:f,:t)')->execute(['f'=>$f,'t'=>$t]);
        $follows++;
    }
}
echo "  + $follows follows creados.\n";

// ── 4) Reseñas y publicaciones demo ─────────────────────────────────
$reviewBodies = [
    "Una obra que te atrapa desde la primera página. La construcción del mundo es magistral y los personajes tienen una profundidad que pocas veces he encontrado. Recomendadísimo para cualquier amante de la lectura. 🌟",
    "Empecé a leerlo sin muchas expectativas y terminé devorándomelo en un fin de semana. La narrativa fluye de manera natural y cada capítulo te deja con ganas de más. 📖",
    "El autor logra equilibrar perfectamente la acción con los momentos más íntimos. Me dejó pensando durante días después de terminar la última página. Definitivamente lo volvería a leer. ✨",
    "Una historia que mezcla emoción, aventura y una crítica social muy inteligente. Se nota que el autor investigó mucho. Para mí es un 5 de 5 sin dudas. 🏆",
    "No es el tipo de libro que suelo leer, pero me lo recomendaron y debo admitir que me encantó. La traducción al español está muy bien lograda. 👏",
    "El manga tiene un ritmo excelente, el arte es precioso y la historia te engancha desde el capítulo uno. Ya pedí los siguientes tomos. 🎌",
    "Un clásico que nunca envejece. Cada vez que lo releo descubro algo nuevo. La edición que tengo tiene notas al margen que enriquecen aún más la experiencia. 📚",
    "Me sorprendió gratamente. La portada no hace justicia al contenido. Es de esas lecturas que te cambian la perspectiva. Súper recomendado. 🌙",
];
$posts = 0; $reviews = 0;
$rng = new \Random\Randomizer();
foreach ($demoUsers as $idx => [$username]) {
    $uid = $userIds[$username] ?? null;
    if (!$uid) continue;
    // 1 publicacion sin libro
    $st = $pdo->prepare('SELECT 1 FROM posts WHERE user_id=:u AND book_id IS NULL LIMIT 1');
    $st->execute(['u'=>$uid]);
    if (!$st->fetchColumn()) {
        $plain = [
            "¡Acabo de terminar mi lista de lecturas pendientes! Ahora a empezar una nueva. ¿Qué me recomiendan? 📚",
            "Nada mejor que un café y un buen libro en la mañana. ¿Cuál están leyendo ahora? ☕📖",
            "Recién llegué de la feria del libro y vine con las manos llenas 😂 El problema del buen lector: nunca hay suficiente espacio en el estante.",
            "Recomendación del día: si no han leído nada de fantasía épica, este es el momento. No se van a arrepentir. 🔥",
            "Empezando el año con nuevas lecturas. Meta: 24 libros en 12 meses. ¿Quién se suma al reto? 💪",
        ];
        $pdo->prepare('INSERT INTO posts(user_id,body) VALUES(:u,:b)')->execute(['u'=>$uid,'b'=>$plain[$idx % count($plain)]]);
        $posts++;
    }
    // 2 reseñas con libros distintos
    $bookSample = array_slice($books, $idx * 2, 2);
    foreach ($bookSample as $bi => $book) {
        $st = $pdo->prepare('SELECT 1 FROM posts WHERE user_id=:u AND review_book_id=:b LIMIT 1');
        $st->execute(['u'=>$uid,'b'=>$book['id']]);
        if ($st->fetchColumn()) continue;
        $rating = min(5, max(3, 4 + ($idx + $bi) % 2));
        $body   = $reviewBodies[($idx * 2 + $bi) % count($reviewBodies)];
        $pdo->prepare('INSERT INTO posts(user_id,book_id,rating,body,review_book_id) VALUES(:u,:b,:r,:body,:rb)')
            ->execute(['u'=>$uid,'b'=>$book['id'],'r'=>$rating,'body'=>$body,'rb'=>$book['id']]);
        $pdo->prepare('UPDATE books SET rating_sum=rating_sum+:r,rating_count=rating_count+1 WHERE id=:id')
            ->execute(['r'=>$rating,'id'=>$book['id']]);
        $reviews++;
    }
}
echo "  + $posts publicaciones y $reviews reseñas creadas.\n";

// ── 5) Likes entre publicaciones ─────────────────────────────────────
$st = $pdo->query('SELECT id,user_id FROM posts ORDER BY id LIMIT 30');
$allPosts = $st->fetchAll();
$likes = 0;
foreach ($allPosts as $post) {
    foreach (array_slice($userIds, 0, 3) as $uid) {
        if ($uid === (int)$post['user_id']) continue;
        $st = $pdo->prepare('SELECT 1 FROM post_likes WHERE user_id=:u AND post_id=:p');
        $st->execute(['u'=>$uid,'p'=>$post['id']]);
        if (!$st->fetchColumn()) {
            $pdo->prepare('INSERT IGNORE INTO post_likes(user_id,post_id) VALUES(:u,:p)')->execute(['u'=>$uid,'p'=>$post['id']]);
            $likes++;
        }
    }
}
echo "  + $likes likes creados.\n";

// ── 6) Foros demo ────────────────────────────────────────────────────
$demoForums = [
    ['Club de Fantasía Épica',   'Todo sobre mundos de magia, dragones y aventuras épicas. ¡Bienvenidos!', null],
    ['Manga & Anime Readers',    'Para los que aman el manga y quieren hablar de sus series favoritas.', null],
    ['Clásicos Inmortales',      'Discusión de literatura clásica: Dostoievski, Borges, García Márquez y más.', null],
    ['Recomendaciones Express',  'Post tu mejor recomendación en 3 líneas. Sin spoilers. 📚', null],
];
// vincular el primero a un libro
if ($books) {
    $demoForums[0][2] = (int)$books[0]['id'];
    if (isset($books[5])) $demoForums[1][2] = (int)$books[5]['id'];
}
$forumIds = [];
foreach ($demoForums as $i => [$name, $desc, $bookId]) {
    $st = $pdo->prepare('SELECT id FROM forums WHERE name=:n LIMIT 1');
    $st->execute(['n'=>$name]);
    $existing = $st->fetchColumn();
    if ($existing) { $forumIds[] = (int)$existing; echo "  Foro '$name' ya existe.\n"; continue; }
    $creatorKey = array_keys($userIds)[$i % count($userIds)];
    $creatorId  = $userIds[$creatorKey];
    $pdo->prepare('INSERT INTO forums(name,description,book_id,creator_id) VALUES(:n,:d,:b,:c)')
        ->execute(['n'=>$name,'d'=>$desc,'b'=>$bookId,'c'=>$creatorId]);
    $fid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT IGNORE INTO forum_members(forum_id,user_id,role) VALUES(:f,:u,"admin")')->execute(['f'=>$fid,'u'=>$creatorId]);
    // agregar 2-3 miembros mas
    foreach (array_slice(array_values($userIds), 1, 3) as $uid) {
        $pdo->prepare('INSERT IGNORE INTO forum_members(forum_id,user_id) VALUES(:f,:u)')->execute(['f'=>$fid,'u'=>$uid]);
    }
    $forumIds[] = $fid;
    echo "  + Foro '$name' (id $fid)\n";
}

// ── 7) Mensajes de chat demo ─────────────────────────────────────────
$chatSamples = [
    ["¡Hola a todos! ¿Qué están leyendo actualmente? 👋", "Acabo de terminar el primer libro y quedé con la boca abierta 😱", "¿Alguien más siente que necesita más horas del día para leer? 😂"],
    ["Este foro era necesario, gracias por crearlo! 🙌", "Recomiendo empezar por los tomos más recientes, son los mejores.", "¿Hay algún evento de manga en Santiago próximamente?"],
    ["Los clásicos nunca fallan. Relectura número 3 de Crimen y Castigo. 📖", "Borges tiene una forma de ver el mundo que no tiene comparación.", "¿Empezamos un club de lectura mensual? 💡"],
    ["Mi recomendación: cualquier cosa de Sanderson. Imposible defraudar.", "Leí 'El nombre del viento' en 2 días. No pude soltarlo. 🔥", "Para los que les gusta el terror psicológico: 'No me abandones' es brutal."],
];
$msgs = 0;
foreach ($forumIds as $fi => $fid) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM forum_messages WHERE forum_id=:f');
    $st->execute(['f'=>$fid]);
    if ((int)$st->fetchColumn() > 0) continue;
    $sample = $chatSamples[$fi % count($chatSamples)];
    $uids   = array_values($userIds);
    foreach ($sample as $mi => $body) {
        $pdo->prepare('INSERT INTO forum_messages(forum_id,user_id,body) VALUES(:f,:u,:b)')
            ->execute(['f'=>$fid,'u'=>$uids[$mi % count($uids)],'b'=>$body]);
        $msgs++;
    }
}
echo "  + $msgs mensajes de chat creados.\n";

// ── 8) Librerías demo ────────────────────────────────────────────────
$demoStores = [
    ['Buscalibre', 'virtual', null, null, 'https://www.buscalibre.cl', 'https://www.buscalibre.cl/libros/search?q={q}', 0],
    ['Open Library', 'virtual', null, null, 'https://openlibrary.org', null, 0],
    ['Librería Demo Centro', 'fisica', 'Santiago', 'Av. Providencia 1234, Santiago', 'https://libreriademo.cl', null, 1],
    ['Librería Demo Norte', 'fisica', 'Santiago', 'Av. Recoleta 567, Santiago', null, null, 1],
    ['Tienda Demo Online', 'virtual', null, null, 'https://tiendademo.cl', 'https://tiendademo.cl/buscar?q={q}', 1],
];
$stores = 0;
foreach ($demoStores as [$name,$type,$city,$addr,$web,$search,$isDemo]) {
    $st = $pdo->prepare('SELECT 1 FROM bookstores WHERE name=:n LIMIT 1');
    $st->execute(['n'=>$name]);
    if ($st->fetchColumn()) continue;
    $pdo->prepare('INSERT INTO bookstores(name,type,city,address,website,search_url,is_demo) VALUES(:n,:t,:c,:a,:w,:s,:d)')
        ->execute(['n'=>$name,'t'=>$type,'c'=>$city,'a'=>$addr,'w'=>$web,'s'=>$search,'d'=>$isDemo]);
    $stores++;
}
echo "  + $stores librerías insertadas.\n";

echo "\n=== Seed completado ===\n";
echo "Contraseña de todos los usuarios demo: demo1234\n";
