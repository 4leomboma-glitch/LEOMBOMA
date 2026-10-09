# InnoVibe — site vitrine et espace apprenant (maquettes)

Deux maquettes front-end, prêtes à être hébergées telles quelles ou intégrées dans le projet de l'équipe technique.

| Dossier | Contenu | État |
|---|---|---|
| `site/` | Site vitrine InnoVibe : accueil, qui sommes-nous, pôles, programmes, partenaires, contact, espace apprenant (porte d'entrée), page du Sommet 2026 | Maquette validée en interne, formulaires non connectés |
| `elearning/` | Espace apprenant InnoVibe Academy : connexion, tableau de bord, catalogue, programme, leçons, quiz, attestations, paiement mobile money, espace formateur | Maquette fonctionnelle avec données d'exemple, aucun serveur |
| `docs/` | Notes de passation pour l'équipe technique (déploiement, réglages, sécurité) | — |

## Ouvrir en local

Chaque dossier est autonome : ouvrir `site/index.html` ou `elearning/index.html` dans un navigateur suffit.
Pour un serveur local : `npx serve site` (ou `python3 -m http.server` dans le dossier).

## Pile technique

- HTML, CSS et JavaScript sans framework, un fichier par application, images dans `img/`.
- Animations : GSAP 3.12.5 (et ScrollTrigger pour le site), auto-hébergé dans `vendor/`.
- Polices : Syne et Manrope via Google Fonts, avec police de repli système.
- Charte : rouge `#a70036`, noir `#111111`, blanc ; mode sombre automatique.

Voir `docs/PASSATION.md` avant toute mise en ligne.
