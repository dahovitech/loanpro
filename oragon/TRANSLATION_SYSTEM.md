# Système de traduction avancé - Oragon

Ce document explique le système de traduction ergonomique et dynamique intégré dans l'application Oragon.

## 🌍 Fonctionnalités

### 1. **Gestion multi-langue automatisée**
- Synchronisation automatique avec les langues configurées dans l'entité `Language`
- Interface admin multilingue avec sélecteur de langue
- Routes localisées pour l'interface d'administration

### 2. **Interface ergonomique de gestion**
- **Liste des traductions** : Vue d'ensemble de tous les domaines et langues
- **Éditeur de traductions** : Interface intuitive pour éditer les traductions clé par clé
- **Statistiques en temps réel** : Progression des traductions, clés manquantes
- **Référence contextuelle** : Affichage des traductions de référence pendant l'édition

### 3. **Synchronisation intelligente**
- **Commande CLI** : `app:translations:sync` pour synchroniser automatiquement
- **Interface web** : Boutons de synchronisation dans l'admin
- **Détection automatique** : Nouvelles clés ajoutées automatiquement

## 📁 Structure des fichiers

### Fichiers de traduction
```
translations/
├── admin.fr.yaml    # Français (par défaut)
├── admin.en.yaml    # Anglais
├── admin.es.yaml    # Espagnol
└── admin.de.yaml    # Allemand
```

### Organisation des clés
```yaml
navigation:
  dashboard: Tableau de bord
  services: Services
  languages: Langues
  translations: Traductions

dashboard:
  title: Tableau de bord
  welcome: Bienvenue dans l'administration
  statistics: Statistiques

services:
  title: Gestion des services
  list_title: Liste des services
  actions:
    new: Nouveau service
    edit: Modifier
```

## 🛠 Utilisation

### 1. **Accès à l'interface de traduction**
```
/fr/admin/translations  # Interface en français
/en/admin/translations  # Interface en anglais
```

### 2. **Commandes CLI**
```bash
# Synchroniser toutes les traductions
php bin/console app:translations:sync --all

# Synchroniser un domaine spécifique
php bin/console app:translations:sync admin

# Afficher les statistiques
php bin/console app:translations:sync admin --force
```

### 3. **Dans les templates**
```twig
{{ 'admin.navigation.dashboard'|trans }}
{{ 'admin.services.actions.new'|trans }}
{{ 'admin.common.messages.success'|trans }}
```

## 🎨 Fonctionnalités avancées

### 1. **Sélecteur de langue dans l'admin**
- Dropdown dans la barre de navigation
- Préservation de la route courante
- Indication de la langue par défaut

### 2. **Éditeur de traductions**
- **Recherche en temps réel** : Filtrage des clés
- **Validation visuelle** : Indication des traductions manquantes
- **Référence contextuelle** : Affichage optionnel des traductions de référence
- **Sauvegarde automatique** : Indication visuelle des modifications non sauvées
- **Raccourcis clavier** :
  - `Ctrl+S` : Sauvegarder
  - `Ctrl+R` : Basculer l'affichage des références
  - `Esc` : Annuler les modifications

### 3. **Statistiques détaillées**
- **Progression par langue** : Pourcentage de traductions complétées
- **Clés manquantes** : Nombre et liste des traductions à compléter
- **Barres de progression** : Visualisation colorée selon le niveau de complétion

## 🔧 Architecture technique

### Services principaux

#### **TranslationManagerService**
```php
// Récupérer les traductions
$translations = $translationManager->getTranslations('admin', 'fr');

// Sauvegarder les traductions
$translationManager->saveTranslations('admin', 'fr', $data);

// Synchroniser avec les langues
$translationManager->synchronizeWithLanguages('admin');

// Statistiques
$stats = $translationManager->getTranslationStats('admin');
```

#### **LocaleListener**
- Détection automatique de la locale pour l'admin
- Fallback vers la langue par défaut
- Validation des locales disponibles

#### **TranslationExtension (Twig)**
```twig
{% for language in get_admin_languages() %}
    {{ language.name }}
{% endfor %}

{% set progress = translation_progress('admin', 'fr') %}
Progression: {{ progress.percentage }}%
```

### Contrôleurs

#### **TranslationController**
- `index()` : Liste des fichiers de traduction
- `edit()` : Éditeur de traductions
- `update()` : Sauvegarde AJAX
- `synchronize()` : Synchronisation par domaine
- `export()` : Export YAML

## 🚀 Mise en œuvre

### 1. **Ajouter une nouvelle langue**
1. Créer la langue dans l'interface admin (`/admin/languages`)
2. La synchronisation créera automatiquement les fichiers de traduction
3. Éditer les traductions via l'interface web

### 2. **Ajouter un nouveau domaine de traduction**
1. Créer le fichier `translations/nouveau-domaine.fr.yaml`
2. Exécuter la synchronisation : `app:translations:sync nouveau-domaine`
3. Éditer via l'interface admin

### 3. **Utiliser les traductions dans le code**
```php
// Dans un contrôleur
$this->addFlash('success', $translator->trans('admin.messages.success'));

// Dans un template
{{ 'admin.navigation.dashboard'|trans }}

// Avec des paramètres
{{ 'admin.messages.items_count'|trans({'%count%': items|length}) }}
```

## 📊 Exemple d'utilisation complète

### 1. **Configuration d'une nouvelle langue**
```yaml
# Ajouter dans admin.it.yaml (Italien)
navigation:
  dashboard: Cruscotto
  services: Servizi
  languages: Lingue
  translations: Traduzioni
```

### 2. **Template avec traductions**
```twig
<h1>{{ 'admin.dashboard.title'|trans }}</h1>
<p>{{ 'admin.dashboard.welcome'|trans }}</p>

{% for service in services %}
    <a href="{{ path('admin_service_edit', {id: service.id}) }}">
        {{ 'admin.services.actions.edit'|trans }}
    </a>
{% endfor %}
```

### 3. **Contrôleur avec messages traduits**
```php
class ServiceController extends AbstractController
{
    #[Route('/services/{id}/edit', name: 'admin_service_edit')]
    public function edit(Service $service, Request $request): Response
    {
        // ...
        
        $this->addFlash('success', 'admin.services.messages.updated');
        
        return $this->redirectToRoute('admin_service_index');
    }
}
```

## 🎯 Avantages du système

1. **Ergonomique** : Interface intuitive pour les traducteurs
2. **Automatisé** : Synchronisation avec les langues configurées
3. **Flexible** : Support de nouveaux domaines et langues
4. **Maintenable** : Organisation claire des clés de traduction
5. **Performant** : Cache des traductions, chargement optimisé
6. **Robuste** : Fallback automatique, validation des données

Ce système permet une gestion complète et ergonomique des traductions, s'adaptant automatiquement aux langues configurées dans l'application et offrant une interface moderne pour les éditeurs de contenu.
