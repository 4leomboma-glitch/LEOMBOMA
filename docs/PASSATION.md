# Notes de passation — site InnoVibe et espace apprenant

Destinataire : équipe Systèmes d'Information & Digital (DSI). Ce document décrit ce qui est livré, ce qu'il reste à brancher et les règles de sécurité à respecter avant la mise en ligne.

## 1. Ce qui est livré

### `site/` — site vitrine
- Une seule page `index.html` avec navigation par ancres (`#accueil`, `#a-propos`, `#poles`, `#programmes`, `#partenaires`, `#contact`, `#espace`, `#sommet`) : chaque « page » est une vue affichée par le routeur JavaScript, avec transition.
- Animations GSAP : prisme du héros, bandeau des pôles, révélations au défilement, compteurs, transitions de page, compte à rebours du sommet. Si GSAP ne charge pas, le site s'affiche intégralement sans animation.
- Images dans `img/` (logo, affiches du sommet). Bibliothèques dans `vendor/`.

### `elearning/` — espace apprenant (maquette)
- Application mono-page : écran de connexion, tableau de bord, catalogue, programme, leçon, quiz, attestations, profil, paiement, espace formateur.
- Toutes les données sont des exemples définis dans le script (`PROGS`, `QUIZ`, `DEMO_STATE`). La progression de l'utilisateur est mémorisée dans `localStorage` (clé `innovibe_el_demo_v1`), uniquement pour la démonstration.

## 2. Réglages à faire dans `site/index.html`

| Réglage | Où | Quoi |
|---|---|---|
| Adresse de la plateforme e-learning | constante `ELEARNING_URL` dans le script | Mettre l'URL réelle (ex. `https://learn.innovibe.cd`). Les boutons « Espace apprenant » redirigeront vers cette adresse. |
| Compte LinkedIn | tableau `SOCIALS` | Remplacer `https://www.linkedin.com/` par l'adresse du compte. |
| Formulaires (contact, sessions, sommet, espace apprenant) | `form[data-form]` | Aujourd'hui, l'envoi affiche seulement un message. Brancher sur un service de formulaires ou sur l'API maison, avec validation côté serveur et protection anti-robots. |
| Billetterie du sommet | page `#sommet` | Remplacer « ouverture prochaine » par le lien de billetterie le moment venu. |

**Important — aperçu de connexion.** La page « Espace apprenant » contient une maquette de formulaire de connexion. Avant la mise en ligne publique, soit la relier à la vraie plateforme, soit la remplacer par le formulaire « Recevoir mon accès ». Un faux champ de mot de passe ne doit jamais être exposé au public.

## 3. Mise en ligne du site

1. Hébergement statique au choix (Netlify, Vercel, GitHub Pages, OVH, Hostinger…). Aucun serveur applicatif n'est nécessaire pour le site.
2. HTTPS obligatoire (certificat gratuit chez tous les hébergeurs cités).
3. Faire pointer `innovibe.cd` et `www.innovibe.cd` vers l'hébergement (enregistrements DNS chez le registrar du domaine .cd).
4. En-têtes de sécurité recommandés côté hébergeur :
   - `Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; script-src 'self' 'unsafe-inline'`
   - `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN` (ou `frame-ancestors` dans la CSP)
5. GSAP est auto-hébergé dans `vendor/` : ne pas le remplacer par un CDN sans ajouter une empreinte d'intégrité (`integrity="sha384-…"`).

## 4. Transformer la maquette e-learning en plateforme

L'interface est prête ; il manque le moteur. Recommandation : garder cette interface et brancher derrière des briques éprouvées.

| Besoin | Piste | Points de sécurité |
|---|---|---|
| Comptes et connexion | Service d'authentification géré (ex. Supabase Auth, Firebase Auth) ou LMS existant | Mots de passe hachés (argon2/bcrypt), double authentification pour les administrateurs, verrouillage après échecs répétés, sessions expirables |
| Données (programmes, progression, quiz, attestations) | Base de données gérée avec règles d'accès par rôle | Rôles apprenant / formateur / admin, journal des accès, sauvegardes quotidiennes testées |
| Vidéos | Hébergement vidéo dédié (liens signés, débit adapté aux petites connexions) | Pas d'URL publique permanente, 720p maximum conseillé |
| Paiement mobile money | Agrégateur agréé en RDC (M-Pesa, Orange Money, Airtel Money) | Aucune clé secrète dans le code front, vérification du paiement côté serveur (webhook), reçus conservés |
| Attestations | Génération PDF côté serveur + page de vérification par code | Code unique non devinable |
| Données personnelles | Conformité au Code du numérique congolais | Consentement, droit d'accès et de suppression, politique de confidentialité publiée |

Rythme proposé : (1) authentification + catalogue + progression ; (2) paiement ; (3) vidéos et ressources ; (4) attestations et espace formateur.

## 5. Contacts
- Direction : leomboma@innovibe.cd
- Partenariats : partenariats@innovibe.cd
- Infoline / WhatsApp : +243 844 498 497
