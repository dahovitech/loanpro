# Oragon - Interface d'Administration Multilingue

Application Symfony 7.3 développée par **Prudence ASSOGBA** pour la gestion de contenu multilingue avec une interface d'administration verticale, simple et hautement ergonomique.

## 🌟 Fonctionnalités

### Interface d'Administration
- **Tableau de bord** avec statistiques des services et traductions
- **Gestion des langues** : ajout, désactivation, définition de langue par défaut
- **Gestion des services multilingues** avec éditeur par onglets
- **Interface responsive** avec mode sombre/clair
- **Système de navigation** ergonomique et intuitif

### Gestion Multilingue
- **Support multi-langues** avec fallback automatique
- **Éditeur de traductions** avec onglets par langue
- **Indicateurs visuels** de complétude des traductions
- **Sélecteur de langue** dynamique côté utilisateur

### Interface Publique
- **Affichage adaptatif** selon la langue sélectionnée
- **Recherche** dans les contenus multilingues
- **Changement de langue** en temps réel
- **Fallback intelligent** vers la langue de référence

## 🛠 Technologies Utilisées

### Backend
- **Symfony 7.3** (PHP 8.2+)
- **Doctrine ORM** avec Doctrine Migrations
- **Symfony Forms & Validator**
- **SQLite** pour la persistance

### Frontend
- **Twig** pour le templating
- **Bootstrap 5.3** + Bootstrap Icons
- **Symfony UX** (Turbo et Stimulus)
- **Axios** pour les appels API
- **SCSS** pour les styles personnalisés

### Outils de Développement
- **Webpack Encore** pour la compilation des assets
- **Doctrine Fixtures** pour les données de test
- **Symfony UX Autocomplete** pour l'interactivité

## 📦 Installation

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- Node.js et npm (pour les assets)
- SQLite

### Installation
```bash
# Cloner le projet
git clone https://github.com/dahovitech/oragon.git
cd oragon

# Installer les dépendances PHP
composer install

# Installer les dépendances JavaScript
npm install

# Configurer la base de données
cp .env .env.local
# Ajuster la configuration DATABASE_URL dans .env.local si nécessaire

# Créer la base de données et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Charger les données de test
php bin/console doctrine:fixtures:load

# Compiler les assets
npm run build

# Lancer le serveur de développement
php bin/console server:run
```

## 🗄 Structure de la Base de Données

### Entités Principales

#### Language
- Code langue (ISO 639-1)
- Nom et nom natif de la langue
- Statut actif/inactif
- Langue par défaut
- Ordre d'affichage

#### Service
- Slug unique
- Statut actif/inactif
- Ordre d'affichage
- Timestamps de création/modification

#### ServiceTranslation
- Relation vers Service et Language
- Titre, description et détail traduits
- Contrainte d'unicité (service_id, language_id)

## 🎨 Interface Utilisateur

### Administration
- **Sidebar verticale** avec navigation claire
- **Mode sombre/clair** avec basculement
- **Tableau de bord** avec statistiques en temps réel
- **Éditeur par onglets** pour les traductions
- **Actions rapides** et boutons contextuels

### Interface Publique
- **Sélecteur de langue** avec noms natifs
- **Recherche en temps réel** dans les contenus
- **Cartes de services** avec design responsive
- **Indicateurs de fallback** linguistique

## 🔧 Configuration

### Langues Supportées (par défaut)
- **Français** (fr) - Langue par défaut
- **Anglais** (en)
- **Espagnol** (es)
- **Allemand** (de)

### Services d'Exemple
1. **Consultation Web** - Services de consultation pour le développement web
2. **Formation Symfony** - Formation complète au framework Symfony
3. **Support Technique** - Support et maintenance d'applications
4. **Intégration API** - Services d'intégration d'APIs tierces
5. **Audit de Sécurité** - Audit de sécurité des applications web

## 🚀 Utilisation

### Administration
1. Accédez à `/admin` pour l'interface d'administration
2. Gérez les langues depuis `/admin/languages`
3. Créez et modifiez les services depuis `/admin/services`
4. Utilisez l'éditeur par onglets pour les traductions

### Fonctionnalités Avancées
- **Duplication de services** avec copie des traductions
- **Copie depuis langue par défaut** pour accélérer la traduction
- **Indicateurs visuels** de complétude des traductions
- **Statistiques** de traduction en temps réel

## 🎯 Points Forts

### Ergonomie
- **Interface verticale** optimisée pour l'administration
- **Navigation intuitive** avec indicateurs de statut
- **Édition fluide** des traductions avec onglets
- **Feedback visuel** constant sur l'état des contenus

### Performance
- **Fallback intelligent** pour les contenus manquants
- **Chargement optimisé** des relations Doctrine
- **Mise en cache** des langues actives
- **Recherche efficace** dans les traductions

### Maintenance
- **Architecture modulaire** avec entités séparées
- **Migrations Doctrine** pour évolution de la base
- **Fixtures** pour environnements de développement
- **Validation** stricte des données

## 👨‍💻 Auteur

**Prudence ASSOGBA** (jprud67@gmail.com)
- Développeur Full Stack Symfony
- Expert en architecture multilingue
- Spécialisé en interfaces d'administration ergonomiques

## 📄 License

Ce projet a été développé dans le cadre d'une démonstration technique et reste propriétaire.

---

*Développé avec ❤️ en Symfony 7.3 par MiniMax Agent*
