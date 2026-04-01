# CLAUDE.md - Guide du projet Cluny (EHNC Concours)

## Présentation

Application web de **gestion de concours équestres** (EHNC - Equivallée Haras National de Cluny). Gère les inscriptions cavaliers/chevaux, les modifications d'engagements, la facturation, les championnats et les ventes lors de compétitions équestres.

## Stack technique

- **Backend** : Laravel 11.31, PHP 8.4+
- **Base de données** : MySQL (mysql)
- **Frontend** : Blade + Alpine.js 3.4.2 + Tailwind CSS 3.1
- **Build** : Vite 6.0.11
- **Déploiement** : Fortrabbit (hébergeur Laravel spécialisé, Allemagne)
- **Auth** : Laravel Breeze

## Commandes essentielles

```bash
# Développement
npm run dev              # Serveur Vite dev
php artisan serve        # Serveur Laravel dev

# Build
npm run build            # Build assets production

# Base de données
php artisan migrate      # Exécuter les migrations
php artisan db:seed      # Peupler la base

# Tests
php artisan test         # Exécuter les tests PHPUnit

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Architecture du projet

### Structure des dossiers clés

```
app/
├── Enums/                    # Enums PHP 8.4 (Role, Discipline, ModificationType, ModificationStatut)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/            # Gestion utilisateurs et produits (admin only)
│   │   ├── Api/              # Endpoints AJAX (recherche cavaliers/chevaux/clients, prix épreuves)
│   │   ├── Auth/             # Authentication Laravel Breeze
│   │   ├── ConcoursController.php       # CRUD concours
│   │   ├── EpreuveController.php        # Liste épreuves d'un concours
│   │   ├── EngageController.php         # Liste engagés d'un concours
│   │   ├── ModificationController.php   # Changements cheval/cavalier/épreuve, invitations, NP
│   │   ├── ChampionnatController.php    # Championnats, classements, résultats
│   │   ├── FactureController.php        # Facturation par client
│   │   ├── VenteController.php          # Ventes (buvette, produits)
│   │   ├── StatistiqueController.php    # Stats et exports CSV
│   │   ├── ImportController.php         # Import CSV format SIF (FFE)
│   │   ├── FacturationEtController.php  # Facturation ET (engagements tardifs)
│   │   └── CommandeRetraitController.php # Gestion retraits commandes
│   ├── Middleware/
│   │   ├── EnsureConcoursAccess.php     # Vérifie accès utilisateur au concours
│   │   └── EnsureUserHasRole.php        # Contrôle d'accès par rôle
│   └── Requests/                        # Validation des formulaires
├── Models/                   # 16 modèles Eloquent
│   ├── Concours.php          # Entité principale (concours/compétition)
│   ├── Epreuve.php           # Épreuve au sein d'un concours
│   ├── Cavalier.php          # Cavalier (nom, prénom, club, licence)
│   ├── Cheval.php            # Cheval (nom, SIRE)
│   ├── Engagement.php        # Inscription cavalier+cheval dans une épreuve
│   ├── Modification.php      # Modification d'engagement (chgt cheval/cavalier/épreuve, invitation, NP)
│   ├── Championnat.php       # Championnat (combine 1-2 épreuves, classement)
│   ├── Vente.php             # Vente avec lignes de produits
│   ├── VenteLigne.php        # Ligne de vente (produit, quantité, prix)
│   ├── Produit.php           # Catalogue produits
│   ├── ClientFacturation.php # Client pour facturation
│   └── ...
├── Services/
│   ├── SifCsvImportService.php  # Import format SIF (standard FFE)
│   └── CsvImportService.php     # Import CSV générique
└── View/Components/             # Composants Blade réutilisables
```

### Routes principales (`routes/web.php`)

| Préfixe                            | Contrôleur                | Accès                |
|-------------------------------------|---------------------------|----------------------|
| `/concours`                         | ConcoursController        | Tous authentifiés    |
| `/concours/{c}/epreuves`           | EpreuveController         | Tous + accès concours|
| `/concours/{c}/engages`            | EngageController          | Tous + accès concours|
| `/concours/{c}/modifications`      | ModificationController    | Tous + accès concours|
| `/concours/{c}/championnats`       | ChampionnatController     | Admin                |
| `/concours/{c}/factures`           | FactureController         | Admin                |
| `/concours/{c}/ventes`             | VenteController           | Admin                |
| `/concours/{c}/statistiques`       | StatistiqueController     | Admin                |
| `/concours/{c}/facturation-et`     | FacturationEtController   | Admin                |
| `/concours/{c}/commande-retraits`  | CommandeRetraitController | Admin + Vendeur      |
| `/admin/users`                      | Admin\UserController      | Admin                |
| `/admin/produits`                   | Admin\ProduitController   | Admin                |
| `/api/cavaliers/search`            | Api\CavalierSearch        | Authentifié          |
| `/api/chevaux/search`              | Api\ChevalSearch          | Authentifié          |

### Modèle de données (relations clés)

```
Concours (1) ──── (N) Epreuve
Concours (1) ──── (N) Engagement
Epreuve  (1) ──── (N) Engagement
Cavalier (1) ──── (N) Engagement
Cheval   (1) ──── (N) Engagement
Engagement (1) ── (N) Modification
Concours (1) ──── (N) Modification
Concours (1) ──── (N) Championnat
Championnat (1) ─ (N) ChampionnatResultat
Championnat (1) ─ (N) ChampionnatExclusion
Concours (1) ──── (N) Vente
Vente    (1) ──── (N) VenteLigne
VenteLigne ───── (1) Produit
Vente    ──────── (1) ClientFacturation
```

### Rôles utilisateurs (Enum `Role`)

- **admin** : Accès complet (CRUD concours, facturation, stats, championnats, gestion utilisateurs)
- **chronometreur** : Consultation épreuves, engagés, modifications
- **vendeur** : Ventes et retraits commandes

### Types de modifications (Enum `ModificationType`)

- `CHANGEMENT_CHEVAL` : Changement de cheval sur un engagement
- `CHANGEMENT_CAVALIER` : Changement de cavalier
- `CHANGEMENT_EPREUVE` : Changement d'épreuve (déplacement)
- `AJOUT_ENGAGEMENT` : Invitation (nouvel engagement)
- `NON_PARTANT` : Déclaration non-partant

### Disciplines (Enums `Discipline` / `DisciplineChampionnat`)

- CSO (Concours de Saut d'Obstacles)
- Dressage
- Hunter
- Open (mixte)

## Logique métier importante

### Tarification des modifications
- Les prix des modifications varient selon le type (invitation, changement épreuve)
- **Invitation** : prix de l'épreuve + PF (participation frais)
- **Changement épreuve** : différence de prix + PF si positif
- **Grand National** : PF spécifique (4.80€ si différence positive)
- **FFE SIF** : invitation +10€ PF 9.90€, changement épreuve diff+10€ PF 9.90€

### Championnats
- Combine 1 ou 2 épreuves selon la discipline
- CSO/Hunter : 2 épreuves (manche 1 + manche 2)
- Dressage : 1 épreuve
- Calcul de classement par points/temps
- Gestion des exclusions et résultats libres

### Import CSV (format SIF FFE)
- Format standard de la Fédération Française d'Équitation
- Import des cavaliers, chevaux, épreuves et engagements
- Détection automatique du séparateur (`;` ou `,`)
- Encodage auto-détecté (UTF-8, ISO-8859-1)

### Facturation
- Factures par client (regroupement des modifications payantes)
- Moyens de paiement : CB, espèces, chèque, internet, virement
- TVA configurable par produit (défaut 5.5%)
- Export CSV et impression

## Conventions de code

- **Langue** : Code en anglais (Laravel), labels/messages en français
- **Enums** : PHP 8.4 backed enums pour les constantes métier
- **Vues** : Blade avec Alpine.js pour l'interactivité côté client
- **CSS** : Classes Tailwind (pas de CSS custom sauf `app.css`)
- **Nommage routes** : `concours.epreuves.index`, `admin.users.create`
- **Contrôleurs** : Logique métier souvent directement dans les contrôleurs (pas toujours en services)
- **Validation** : Inline dans les contrôleurs (`$request->validate([...])`)

## Points d'attention (code review)

### Sécurité
- ~~Les endpoints API (`/api/*`) n'ont pas de vérification de rôle~~ → `updatePrix` protégé par middleware `role:admin`
- ~~Génération de mots de passe dans `UserController` utilise `str_shuffle()`~~ → Remplacé par `Str::random()`
- ~~Upload CSV : pas de vérification MIME type~~ → Ajouté `mimes:csv,txt`
- ~~Vérification d'autorisation inline dans les contrôleurs~~ → Déplacée en middleware route
- ~~Middleware retournait 403 au lieu de 401 pour utilisateur non authentifié~~ → Corrigé

### Performance
- Problèmes N+1 potentiels dans `ModificationController`, `ChampionnatController`, `FactureController`
- ~~Requêtes SQL brutes spécifiques PostgreSQL~~ → Migrées vers MySQL (`CAST AS UNSIGNED`, `SUBSTRING_INDEX`, collation case-insensitive)
- Index manquants sur certaines clés étrangères

### Qualité de code
- Contrôleurs volumineux : `ChampionnatController` (829 lignes), `ModificationController` (525 lignes)
- Logique métier dans les contrôleurs plutôt que dans des services dédiés
- ~~Calculs de prix/tarification hardcodés~~ → Extraits dans `config/ehnc.php`
- ~~Constante TVA magique `1.055`~~ → Remplacée par `config('ehnc.tva_modifications')`
- Duplication de code (création `ClientFacturation`, calculs de prix) entre méthodes
- Pas de FormRequest classes pour la validation (validation inline)
- Pas de localisation (chaînes françaises hardcodées dans le code)

### Modèles
- ~~`CommandeRetrait` utilise l'ancien style `$casts`~~ → Migré vers `casts()` method
- ~~`Championnat` et `ChampionnatResultat` manquent le trait `HasFactory`~~ → Ajouté
- ~~`ChampionnatExclusion` manque les relations vers `Cavalier` et `Cheval`~~ → Ajoutées
- ~~Certains modèles manquent de casts sur les champs numériques~~ → Ajoutés sur `ImportLog`, `CommandeRetrait`, `VenteLigne`

## Variables d'environnement requises

```
APP_NAME=EHNC_Concours
APP_ENV=production
APP_KEY=                    # Généré avec php artisan key:generate
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

## PWA

L'application est configurée comme Progressive Web App :
- `public/manifest.json` : Configuration PWA
- `public/sw.js` : Service worker
- `public/icons/` : Icônes PWA (Equivallée)
