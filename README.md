# DataPilot AI

DataPilot AI est une plateforme d'analyse et de nettoyage de données sans code permettant aux utilisateurs de télécharger, profiler, nettoyer et visualiser des jeux de données CSV et Excel.

## Fonctionnalités

- **Téléversement sécurisé** : Validation de la taille (max 10 Mo), du type MIME et de l'extension
- **Profilage automatisé** : Statistiques de base, détection des types de données, comptage des valeurs vides, détection des valeurs aberrantes (méthode IQR), calcul d'un score de qualité
- **Recommandations de nettoyage** : Suggestions basées sur le profil (valeurs vides, types mixtes, valeurs aberrantes)
- **Nettoyage interactif** : Suppression des doublons, remplissage des valeurs manquantes, gestion des valeurs aberrantes, conversion de types, suppression des espaces blancs
- **Tableau de bord analytique** : Graphiques et métriques pour explorer les données
- **Génération de rapports** : Export PDF/Excel des profils, analytique et résultats de nettoyage
- **Authentification complète** : Inscription, connexion, réinitialisation de mot de passe, gestion de profil
- **Interface responsive** : Utilisable sur desktop et mobile

## Installation

### Prérequis
- PHP >= 8.2
- Composer
- Node.js & NPM (pour les assets)
- Une base de données (SQLite par défaut, configurable pour MySQL/PostgreSQL)

### Étapes d'installation

1. **Cloner le dépôt**
   ```bash
   git clone <repository-url>
   cd data-pilot-ai
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Installer les dépendances JavaScript**
   ```bash
   npm install && npm run dev
   ```

4. **Copier le fichier d'environnement**
   ```bash
   cp .env.example .env
   ```

5. **Générer la clé d'application**
   ```bash
   php artisan key:generate
   ```

6. **Configurer la base de données**
   - Par défaut, SQLite est utilisé (fichier `database/database.sqlite`)
   - Pour utiliser MySQL/PostgreSQL, modifiez les variables dans `.env` :
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=datapilot
     DB_USERNAME=root
     DB_PASSWORD=
     ```

7. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

8. **Lancer le serveur de développement**
   ```bash
   php artisan serve
   ```
   Puis visitez `http://127.0.0.1:8000` dans votre navigateur.

## Utilisation

### 1. Création de compte
- Cliquez sur "Register" pour créer un compte
- Connectez-vous avec vos identifiants

### 2. Téléversement d'un jeu de données
- Depuis le tableau de bord, cliquez sur "Import Data"
- Sélectionnez un fichier CSV ou Excel (max 10 Mo)
- Validez le téléchargement

### 3. Exploration du jeu de données
- Après téléchargement, vous êtes redirigé vers la page de détails du jeu de données
- Vous voyez :
  * Informations de base (nom, type, lignes, colonnes, statut)
  * Score de qualité (0-1) avec indicateur couleur
  * Recommandations de nettoyage (le cas échéant)
  * Boutons pour :
    - Nettoyer le jeu de données
    - Voir le tableau de bord analytique
    - Exporter des rapports (PDF/Excel)

### 4. Tableau de bord analytique
- Cliquez sur "Analytics Dashboard" pour voir :
  * Graphique des valeurs manquantes par colonne
  * Répartition des types de données (camembert)
  * Statistiques des colonnes numériques (min, max, moyenne, valeurs aberrantes)

### 5. Nettoyage des données
- Cliquez sur "Clean Dataset" pour accéder au formulaire de nettoyage
- Sélectionnez une ou plusieurs opérations :
  * Suppression des doublons
  * Remplissage des valeurs manquantes (choisissez la colonne et la méthode)
  * Gestion des valeurs aberrantes (choisissez la colonne et la méthode : winsorize, remove, cap)
  * Conversion de types de colonnes mixtes
  * Suppression des espaces blancs
- Les colonnes disponibles pour chaque opération sont filtrées selon leur pertinence (ex: seules les colonnes avec valeurs vides apparaissent pour le remplissage)
- Cliquez sur "Apply Cleaning" pour créer un nouveau jeu de données nettoyé
- Vous êtes redirigé vers la page de détails du jeu de données nettoyé

### 6. Export de rapports
- Depuis la page de détails d'un jeu de données, cliquez sur le menu déroulant "Export Report"
- Choisissez le type de rapport :
  * Profile Report (PDF/Excel)
  * Analytics Report (PDF/Excel)
  * Cleaning Report (PDF uniquement - nécessite un jeu de données nettoyé associé)
- Le fichier sera téléchargé automatiquement

### 7. Gestion du profil
- Cliquez sur votre nom dans la barre de navigation supérieure droite
- Sélectionnez "Profile" pour modifier :
  * Nom
  * Email
  * Mot de passe (optionnel)

### 8. Déconnexion
- Cliquez sur votre nom dans la barre de navigation puis sur "Logout"

## Structure du projet

```
app/
├─ Http/
│  ├─ Controllers/
│  │  ├─ DatasetImportController.php     # Téléversement et affichage des jeux de données
│  │  ├─ DatasetCleaningController.php   # Interface de nettoyage
│  │  ├─ DatasetAnalyticsController.php  # Tableau de bord analytique
│  │  ├─ Auth/                          # Contrôleurs d'authentification
│  │  └─ ReportController.php           # Génération de rapports
├─ Models/
│  ├─ Dataset.php                       # Modèle de jeu de données
│  ├─ DatasetProfile.php                # Métriques et score de qualité
│  └─ User.php                          # Utilisateur Laravel
├─ Services/
│  ├─ DatasetImportService.php          # Téléversement, validation, profilage
│  ├─ DatasetCleaningRecommendationService.php # Génération de recommandations
│  ├─ DatasetCleaningService.php        # Opérations de nettoyage
│  └─ ReportGenerationService.php       # Génération PDF/Excel
├─ database/
│  ├─ migrations/                       # Schéma de la base de données
│  └─ factories/                        # Factories de test
resources/
├─ views/
│  ├─ dataset/                          # Vues liées aux jeux de données
│  ├─ auth/                             # Vues d'authentification
│  ├─ reports/                          # Templates de rapports PDF
│  └─ layouts/                          # Layout principal
routes/
└─ web.php                              # Définition des routes
tests/
└─ Unit/                                # Tests unitaires
```

## Configuration supplémentaire

### Filesystem
Les fichiers téléchargés sont stockés dans `storage/app/private/datasets/` et les jeux de données nettoyés dans `storage/app/private/datasets_cleaned/`.

### Filesystem Links
Pour accéder aux fichiers stockés via le web (si nécessaire), vous pouvez créer un lien symbolique :
```bash
php artisan storage:link
```

### Queue
Certaines opérations (comme la génération de gros rapports) pourraient bénéficier d'être traitées en arrière-plan. Configurez un driver de queue dans `.env` (ex: database, redis) et exécutez :
```bash
php artisan queue:work
```

## Tests

Exécuter tous les tests unitaires :
```bash
php artisan test
```
ou
```bash
vendor/bin/phpunit
```

## Licence

Ce projet est sous licence MIT - voir le fichier `LICENSE` pour plus de détails.

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur le dépôt GitHub.