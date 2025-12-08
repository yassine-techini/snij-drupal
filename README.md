# SNIJ Drupal - Portail National d'Information Juridique

Drupal 10 Headless CMS pour le système SNIJ (Système National d'Information Juridique) de la Tunisie.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      SNIJ Architecture                       │
├─────────────────────────────────────────────────────────────┤
│  Frontend (Next.js)  →  Studio (Workers)  →  Foundry        │
│         ↓                     ↓                  ↓          │
│  snij-frontend.pages.dev  snij-studio   snij-foundry        │
│                               ↓                              │
│                    ┌──────────────────────┐                 │
│                    │   SNIJ DRUPAL        │                 │
│                    │   (This Project)     │                 │
│                    │                      │                 │
│                    │  • JSON:API          │                 │
│                    │  • Content Types     │                 │
│                    │  • Trilingual i18n   │                 │
│                    └──────────────────────┘                 │
└─────────────────────────────────────────────────────────────┘
```

## Content Types

### Loi (قانون)
- `field_numero`: Numéro de la loi
- `field_date_promulgation`: Date de promulgation
- `field_contenu`: Contenu intégral (traduit)
- `field_resume_ia`: Résumé généré par IA (traduit)
- `field_domaine`: Domaine juridique (taxonomie)
- `field_statut`: En vigueur / Abrogé / Modifié
- `field_jort_reference`: Référence JORT

### Décret (مرسوم)
Mêmes champs que Loi

### Jurisprudence (اجتهاد قضائي)
Mêmes champs que Loi

## API Endpoints

```
GET /api/node/loi                    # Liste des lois
GET /api/node/loi/{uuid}             # Détail d'une loi
GET /api/node/decret                 # Liste des décrets
GET /api/node/jurisprudence          # Liste des jurisprudences
GET /api/taxonomy_term/domaine_juridique  # Domaines juridiques

# Filtres
GET /api/node/loi?filter[field_statut]=en_vigueur
GET /api/node/loi?sort=-field_date_promulgation
GET /api/node/loi?include=field_domaine
```

## Langues

- **ar** (Arabic) - Langue par défaut, RTL
- **fr** (French)
- **en** (English)

## Déploiement sur Render

1. Connecter le repo GitHub à Render
2. Render détecte automatiquement `render.yaml`
3. Créer les services (web + database)
4. Après le premier déploiement, exécuter:
   ```bash
   ./scripts/setup.sh
   ```

## Développement Local

```bash
# Installer les dépendances
composer install

# Lancer avec Docker
docker-compose up -d

# Configuration initiale
./scripts/setup.sh

# Accéder à l'admin
http://localhost/user/login
```

## Structure du Projet

```
snij-drupal/
├── assets/
│   ├── settings.php     # Configuration Drupal
│   └── services.yml     # Configuration CORS
├── config/
│   └── sync/            # Configurations exportées
├── data/
│   └── documents.json   # Données de démo
├── docker/
│   ├── apache.conf      # Configuration Apache
│   └── php.ini          # Configuration PHP
├── scripts/
│   ├── setup.sh         # Script d'installation
│   └── seed-content.php # Import des données
├── web/                  # Drupal web root
├── composer.json
├── Dockerfile
└── render.yaml
```

## Maintenance

```bash
# Mettre à jour Drupal
composer update drupal/core-* --with-dependencies

# Exporter la configuration
drush config:export

# Importer la configuration
drush config:import

# Vider le cache
drush cr
```
