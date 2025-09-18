# 🏦 LoanPro - Plateforme de Gestion de Prêts

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-6.4+-green.svg)](https://symfony.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://docker.com)

## 📋 Table des Matières

- [À Propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Architecture Technique](#architecture-technique)
- [Installation](#installation)
- [Configuration](#configuration)
- [Déploiement](#déploiement)
- [Utilisation](#utilisation)
- [API Documentation](#api-documentation)
- [Monitoring](#monitoring)
- [Contribution](#contribution)
- [Support](#support)

## 🎯 À Propos

LoanPro est une plateforme complète de gestion de prêts développée avec Symfony 6.4+, offrant une solution moderne et sécurisée pour la gestion des demandes de prêt, l'évaluation des risques, et le suivi en temps réel.

### 🚀 Fonctionnalités Principales

#### 👥 Gestion des Utilisateurs
- ✅ Inscription et authentification sécurisées
- ✅ Profils utilisateurs complets
- ✅ Gestion des rôles et permissions
- ✅ Authentification à deux facteurs (2FA)

#### 💰 Gestion des Prêts
- ✅ Demandes de prêt en ligne
- ✅ Calculateur de prêt intelligent
- ✅ Évaluation automatique des risques
- ✅ Workflow d'approbation configurable
- ✅ Suivi en temps réel des statuts

#### 🏛️ Interface d'Administration
- ✅ Dashboard complet avec métriques
- ✅ Gestion CRUD complète (EasyAdmin)
- ✅ Système d'audit et logging
- ✅ Rapports et exports avancés
- ✅ Gestion des utilisateurs et permissions

#### 👤 Espace Client
- ✅ Dashboard personnel interactif
- ✅ Suivi en temps réel des demandes
- ✅ Système de messagerie intégré
- ✅ Notifications push en temps réel
- ✅ Historique complet des transactions

#### 📊 Analytics et Reporting
- ✅ Dashboard analytique avancé
- ✅ KPIs et métriques en temps réel
- ✅ Visualisations interactives (Chart.js)
- ✅ Rapports exportables (PDF, Excel, CSV)
- ✅ Analyse prédictive et benchmarking

#### 💬 Communication
- ✅ Système de messagerie interne
- ✅ Notifications multi-canaux
- ✅ Templates d'emails personnalisables
- ✅ Intégration SMS (Twilio)

## 🏗️ Architecture Technique

### Stack Technologique

**Backend:**
- 🐘 **PHP 8.2+** - Langage principal
- 🎼 **Symfony 6.4+** - Framework web
- 🗄️ **Doctrine ORM** - Mapping objet-relationnel
- 🔐 **Symfony Security** - Authentification et autorisation

**Frontend:**
- 🎨 **Bootstrap 5** - Framework CSS
- ⚡ **JavaScript ES6+** - Interactivité
- 📈 **Chart.js** - Visualisations
- 🎯 **Webpack Encore** - Build des assets

**Base de Données:**
- 🐬 **MySQL 8.0+** - Base de données principale
- 🔴 **Redis** - Cache et sessions
- 🔍 **Elasticsearch** - Recherche et analytics

**Infrastructure:**
- 🐳 **Docker** - Containerisation
- 🔄 **Docker Compose** - Orchestration
- 🚀 **Nginx** - Serveur web
- 📊 **Prometheus + Grafana** - Monitoring

### Architecture des Services

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   Load Balancer │  │    Reverse      │  │      CDN        │
│   (Traefik)     │  │    Proxy        │  │   (Assets)      │
└─────────────────┘  └─────────────────┘  └─────────────────┘
         │                     │                     │
         └─────────────────────┼─────────────────────┘
                               │
         ┌─────────────────────▼─────────────────────┐
         │            Application Layer              │
         │  ┌─────────────┐  ┌─────────────────────┐ │
         │  │    App      │  │     Workers         │ │
         │  │  (Symfony)  │  │  (Messenger Queue) │ │
         │  └─────────────┘  └─────────────────────┘ │
         └─────────────────────┬─────────────────────┘
                               │
         ┌─────────────────────▼─────────────────────┐
         │              Data Layer                   │
         │  ┌─────────┐ ┌─────────┐ ┌─────────────┐ │
         │  │  MySQL  │ │  Redis  │ │Elasticsearch│ │
         │  └─────────┘ └─────────┘ └─────────────┘ │
         └───────────────────────────────────────────┘
```

## 🛠️ Installation

### Prérequis

- **PHP 8.2+** avec extensions : `pdo_mysql`, `redis`, `gd`, `intl`, `zip`, `curl`
- **Composer 2.0+**
- **Node.js 18+** et **npm**
- **Docker** et **Docker Compose** (recommandé)
- **MySQL 8.0+** ou **PostgreSQL 13+**
- **Redis 6+**

### Installation Rapide avec Docker

```bash
# 1. Cloner le repository
git clone https://github.com/your-org/loanpro.git
cd loanpro

# 2. Copier et configurer l'environnement
cp .env.example .env
# Éditer .env avec vos paramètres

# 3. Déployer l'application
sudo ./deploy.sh --production

# 4. Accéder à l'application
# Application: http://localhost
# Admin: http://localhost/admin
# Analytics: http://localhost/analytics
```

### Installation Manuelle

```bash
# 1. Installer les dépendances PHP
composer install --optimize-autoloader --no-dev

# 2. Installer les dépendances JavaScript
npm install

# 3. Construire les assets
npm run build

# 4. Configurer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Charger les données de test (optionnel)
php bin/console doctrine:fixtures:load

# 6. Configurer les permissions
chmod -R 777 var/ public/uploads/
```

## ⚙️ Configuration

### Variables d'Environnement

Copiez `.env.example` vers `.env` et configurez :

```bash
# Application
APP_ENV=prod
APP_SECRET=votre_secret_tres_long_et_aleatoire

# Base de données
DATABASE_URL=mysql://user:password@localhost:3306/loanpro

# Redis
REDIS_URL=redis://localhost:6379

# Email
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# Domaine
DOMAIN_NAME=votre-domaine.com
```

### Configuration Avancée

#### Sécurité
- Configurez les CORS dans `config/packages/nelmio_cors.yaml`
- Ajustez les CSP dans `config/packages/security.yaml`
- Configurez les limites de taux dans Nginx

#### Performance
- Activez OPcache en production
- Configurez Redis pour les sessions
- Optimisez les requêtes Doctrine

#### Monitoring
- Configurez Prometheus pour les métriques
- Paramétrez Grafana pour les dashboards
- Configurez les alertes Slack/Email

## 🚀 Déploiement

### Déploiement avec Docker (Recommandé)

```bash
# Production
./deploy.sh --production

# Développement
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Mise à jour
git pull origin main
./deploy.sh --skip-backup
```

### Déploiement Manuel

```bash
# 1. Mise à jour du code
git pull origin main

# 2. Mise à jour des dépendances
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# 5. Assets
php bin/console assets:install --symlink
```

### Déploiement sur AWS/GCP/Azure

Le projet inclut des configurations pour :
- **AWS ECS** avec Fargate
- **Google Cloud Run**
- **Azure Container Instances**
- **Kubernetes** (Helm charts)

## 📱 Utilisation

### Interface Utilisateur

#### Espace Client (`/client`)
- **Dashboard** : Vue d'ensemble des prêts et métriques
- **Mes Prêts** : Gestion et suivi des demandes
- **Messages** : Communication avec les conseillers
- **Notifications** : Alertes et mises à jour

#### Administration (`/admin`)
- **Dashboard** : Métriques et activité système
- **Gestion des Prêts** : CRUD complet
- **Utilisateurs** : Gestion des comptes
- **Rapports** : Analytics et exports

#### Analytics (`/analytics`)
- **KPIs** : Indicateurs clés de performance
- **Tendances** : Évolution temporelle
- **Segmentation** : Analyse par catégories
- **Prédictions** : Modèles prédictifs

### API REST

#### Authentification

```bash
# Obtenir un token JWT
curl -X POST /api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Utiliser le token
curl -H "Authorization: Bearer YOUR_TOKEN" /api/loans
```

#### Endpoints Principaux

```bash
# Prêts
GET    /api/loans              # Liste des prêts
POST   /api/loans              # Créer un prêt
GET    /api/loans/{id}         # Détails d'un prêt
PUT    /api/loans/{id}         # Modifier un prêt
DELETE /api/loans/{id}         # Supprimer un prêt

# Utilisateurs
GET    /api/users              # Liste des utilisateurs
POST   /api/users              # Créer un utilisateur
GET    /api/users/{id}         # Profil utilisateur

# Analytics
GET    /api/analytics/kpis     # KPIs en temps réel
GET    /api/analytics/trends   # Données de tendance
GET    /api/analytics/reports  # Rapports générés
```

## 📊 Monitoring

### Métriques Disponibles

#### Application
- **Performance** : Temps de réponse, throughput
- **Erreurs** : Taux d'erreur, exceptions
- **Utilisation** : Utilisateurs actifs, sessions

#### Business
- **Prêts** : Volume, montants, taux d'approbation
- **Utilisateurs** : Acquisition, rétention, activité
- **Revenue** : Revenus, marges, prédictions

### Dashboards Grafana

1. **System Overview** : Métriques système et infrastructure
2. **Application Performance** : Performance de l'application
3. **Business Metrics** : KPIs métier
4. **User Activity** : Activité utilisateur
5. **Financial Analytics** : Métriques financières

### Alertes

Les alertes sont configurées pour :
- CPU/Mémoire > 80%
- Taux d'erreur > 5%
- Temps de réponse > 2s
- Espace disque < 10%

## 🧪 Tests

### Exécution des Tests

```bash
# Tests unitaires
./vendor/bin/phpunit

# Tests d'intégration
./vendor/bin/phpunit --group integration

# Tests fonctionnels
./vendor/bin/phpunit --group functional

# Couverture de code
./vendor/bin/phpunit --coverage-html coverage/
```

### Types de Tests

- **Unit Tests** : Logique métier
- **Integration Tests** : Intégration base de données
- **Functional Tests** : Tests end-to-end
- **API Tests** : Tests des endpoints REST

## 🛡️ Sécurité

### Mesures Implémentées

- **Authentication** : JWT + Sessions sécurisées
- **Authorization** : RBAC avec Symfony Security
- **HTTPS** : TLS 1.3 obligatoire en production
- **CSRF Protection** : Tokens CSRF sur tous les formulaires
- **Rate Limiting** : Protection contre les attaques par force brute
- **Input Validation** : Validation stricte des données
- **SQL Injection** : Protection via Doctrine ORM
- **XSS Protection** : Échappement automatique des données

### Audit de Sécurité

```bash
# Audit des dépendances
composer audit

# Analyse statique
./vendor/bin/psalm

# Vérification de sécurité Symfony
symfony check:security
```

## 📚 Documentation API

### Swagger/OpenAPI

L'API est documentée avec OpenAPI 3.0 :
- **Documentation interactive** : `/api/doc`
- **Spécification JSON** : `/api/doc.json`
- **Postman Collection** : `docs/api/postman_collection.json`

### Exemples d'Usage

#### Créer une demande de prêt

```javascript
const response = await fetch('/api/loans', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    amount: 10000,
    duration: 24,
    purpose: 'Achat véhicule'
  })
});

const loan = await response.json();
```

#### Obtenir les métriques

```javascript
const kpis = await fetch('/api/analytics/kpis', {
  headers: {
    'Authorization': 'Bearer ' + token
  }
}).then(r => r.json());

console.log('Taux d\'approbation:', kpis.approval_rate);
```

## 🤝 Contribution

### Guide de Contribution

1. **Fork** le repository
2. **Créer** une branche feature (`git checkout -b feature/AmazingFeature`)
3. **Commit** vos changements (`git commit -m 'Add AmazingFeature'`)
4. **Push** vers la branche (`git push origin feature/AmazingFeature`)
5. **Ouvrir** une Pull Request

### Standards de Code

- **PSR-12** pour le code PHP
- **ESLint** pour JavaScript
- **Tests** obligatoires pour toute nouvelle fonctionnalité
- **Documentation** mise à jour

### Structure des Commits

```
type(scope): description

body (optionnel)

footer (optionnel)
```

Types : `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

## 📞 Support

### Resources

- **Documentation** : [docs.loanpro.example.com](https://docs.loanpro.example.com)
- **Issues** : [GitHub Issues](https://github.com/your-org/loanpro/issues)
- **Discussions** : [GitHub Discussions](https://github.com/your-org/loanpro/discussions)

### Contact

- **Email** : support@loanpro.example.com
- **Slack** : [LoanPro Workspace](https://loanpro.slack.com)
- **Forum** : [Community Forum](https://forum.loanpro.example.com)

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🙏 Remerciements

- [Symfony](https://symfony.com) pour le framework
- [Doctrine](https://doctrine-project.org) pour l'ORM
- [Bootstrap](https://getbootstrap.com) pour l'UI
- [Chart.js](https://chartjs.org) pour les graphiques
- [Docker](https://docker.com) pour la containerisation

---

**Made with ❤️ by the LoanPro Team**

*Dernière mise à jour : $(date +%Y-%m-%d)*
