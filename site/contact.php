<?php
/**
 * InnoVibe — réception des formulaires du site.
 * Reçoit les envois (contact, sessions, sommet, espace apprenant) et les transmet par e-mail
 * à Contact@innovibe.cd. Protections : même origine, champ piège anti-robots, délai minimum,
 * limitation du nombre d'envois par adresse IP, nettoyage des en-têtes, longueurs bornées.
 */
declare(strict_types=1);

const DESTINATAIRE = 'Contact@innovibe.cd';
const EXPEDITEUR   = 'Contact@innovibe.cd';   // doit être une boîte du domaine pour passer les filtres anti-spam
const DOMAINE      = 'innovibe.cd';
const MAX_PAR_IP   = 5;                        // envois autorisés par IP…
const FENETRE_SEC  = 600;                      // …par tranche de 10 minutes

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function repondre(bool $ok, string $msg = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
function champ(string $cle, int $max): string {
    $v = isset($_POST[$cle]) ? (string)$_POST[$cle] : '';
    $v = trim(str_replace(["\r", "\0"], ['', ''], $v));
    if (mb_strlen($v) > $max) { $v = mb_substr($v, 0, $max); }
    return $v;
}
function ligne(string $v): string {           // valeur sur une seule ligne (jamais d'en-tête injecté)
    return trim(preg_replace('/[\r\n\t]+/', ' ', $v) ?? '');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    repondre(false, 'Méthode non autorisée.', 405);
}

// 1. Même origine : l'envoi doit venir du site lui-même.
$origine = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
if ($origine !== '' && !preg_match('#^https?://(www\.)?' . preg_quote(DOMAINE, '#') . '(:\d+)?(/|$)#i', $origine)) {
    repondre(false, 'Origine refusée.', 403);
}

// 2. Champ piège : un humain ne le voit pas, un robot le remplit.
if (champ('website', 200) !== '') {
    repondre(true, 'Merci.');                 // on fait croire au robot que tout va bien
}

// 3. Délai minimum entre l'affichage du formulaire et l'envoi (3 secondes).
$t = (int)champ('t', 20);
if ($t > 0 && (time() * 1000 - $t) < 3000) {
    repondre(false, 'Envoi trop rapide, réessayez.', 429);
}

// 4. Limitation par adresse IP.
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$fichier = sys_get_temp_dir() . '/innovibe_form_' . hash('sha256', $ip . DOMAINE) . '.json';
$horodatages = [];
if (is_file($fichier)) {
    $horodatages = json_decode((string)file_get_contents($fichier), true) ?: [];
}
$maintenant = time();
$horodatages = array_values(array_filter($horodatages, fn($h) => is_int($h) && $maintenant - $h < FENETRE_SEC));
if (count($horodatages) >= MAX_PAR_IP) {
    repondre(false, 'Trop d\'envois en peu de temps. Réessayez dans quelques minutes ou écrivez-nous directement.', 429);
}

// 5. Lecture et validation des champs.
$formulaires = [
    'contact'   => 'Message depuis la page Contact',
    'sessions'  => 'Demande d\'information sur les prochaines sessions',
    'sie'       => 'Pré-inscription au Sommet 2026',
    'elearning' => 'Demande d\'accès à l\'espace apprenant',
];
$type = champ('form', 20);
if (!isset($formulaires[$type])) { $type = 'contact'; }

$nom     = ligne(champ('nom', 120));
$tel     = ligne(champ('tel', 40));
$email   = ligne(champ('email', 120));
$contact = ligne(champ('contact', 120));   // champ "e-mail ou téléphone" du formulaire Contact
$sujet   = ligne(champ('sujet', 120));
$pole    = ligne(champ('pole', 120));
$profil  = ligne(champ('profil', 120));
$interet = ligne(champ('type', 120));
$message = champ('message', 4000);

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = ''; }
$reponseA = $email !== '' ? $email : (filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : '');

if ($nom === '' || ($tel === '' && $email === '' && $contact === '')) {
    repondre(false, 'Indiquez au moins votre nom et un moyen de vous joindre.', 422);
}

// 6. Composition du message.
$lignes = [
    $formulaires[$type],
    str_repeat('-', 48),
    'Nom : ' . $nom,
];
if ($tel !== '')     { $lignes[] = 'Téléphone / WhatsApp : ' . $tel; }
if ($email !== '')   { $lignes[] = 'E-mail : ' . $email; }
if ($contact !== '') { $lignes[] = 'Contact indiqué : ' . $contact; }
if ($sujet !== '')   { $lignes[] = 'Sujet : ' . $sujet; }
if ($pole !== '')    { $lignes[] = 'Pôle : ' . $pole; }
if ($profil !== '')  { $lignes[] = 'Profil : ' . $profil; }
if ($interet !== '') { $lignes[] = 'Intérêt : ' . $interet; }
if ($message !== '') { $lignes[] = ''; $lignes[] = 'Message :'; $lignes[] = $message; }
$lignes[] = '';
$lignes[] = str_repeat('-', 48);
$lignes[] = 'Reçu le ' . date('d/m/Y à H:i') . ' depuis ' . ($_SERVER['HTTP_HOST'] ?? DOMAINE) . ' (IP ' . $ip . ')';
$corps = implode("\n", $lignes);

$objet = '[Site InnoVibe] ' . $formulaires[$type] . ' - ' . $nom;
$entetes = [
    'From: Site InnoVibe <' . EXPEDITEUR . '>',
    'Reply-To: ' . ($reponseA !== '' ? $nom . ' <' . $reponseA . '>' : EXPEDITEUR),
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'X-Mailer: innovibe-site',
];

$envoye = @mail(
    DESTINATAIRE,
    '=?UTF-8?B?' . base64_encode($objet) . '?=',
    $corps,
    implode("\r\n", $entetes),
    '-f' . EXPEDITEUR
);

if (!$envoye) {
    repondre(false, 'L\'envoi a échoué. Écrivez-nous directement à ' . DESTINATAIRE . '.', 500);
}

$horodatages[] = $maintenant;
@file_put_contents($fichier, json_encode($horodatages), LOCK_EX);
repondre(true, 'Merci, votre message a bien été envoyé.');
