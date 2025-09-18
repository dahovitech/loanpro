# Déploiement automatique LoanPro

Ce répertoire contient les scripts de déploiement automatique pour l'application LoanPro sur le serveur de production.

## 🗂️ Fichiers de déploiement

### Scripts principaux

- **`deploy-production.py`** - Script Python complet avec gestion avancée du déploiement
- **`deploy-quick.sh`** - Script Bash rapide pour déploiement simple
- **`ssh-helper.sh`** - Assistant SSH pour les opérations post-déploiement

### Configuration serveur

- **Serveur**: `loanpro.achatrembourse.online`
- **URL**: https://loanpro.achatrembourse.online
- **SSH**: `mrjoker@46.202.129.197`
- **Répertoire web**: `/home/mrjoker/web/loanpro.achatrembourse.online`

## 🚀 Déploiement rapide

### Option 1: Script Bash (Recommandé)

```bash
# Rendre le script exécutable
chmod +x deploy-quick.sh

# Lancer le déploiement
./deploy-quick.sh
```

### Option 2: Script Python

```bash
# Installer les dépendances si nécessaire
pip3 install -r requirements.txt

# Lancer le déploiement
python3 deploy-production.py

# Ou avec confirmation automatique
python3 deploy-production.py --force
```

## 🔧 Configuration post-déploiement

Après le déploiement FTP, utilisez l'assistant SSH :

```bash
# Rendre le script exécutable
chmod +x ssh-helper.sh

# Lancer l'assistant
./ssh-helper.sh
```

### Menu de l'assistant SSH

1. **Extraire le déploiement** - Décompresse les fichiers sur le serveur
2. **Configurer la base de données** - Crée la BDD et exécute les migrations
3. **Configurer les permissions** - Définit les bonnes permissions pour Apache
4. **Vider le cache** - Vide le cache Symfony en production
5. **Vérifier l'état** - Contrôle l'état de l'application
6. **Créer un utilisateur admin** - Crée un compte administrateur
7. **Redémarrer les services** - Redémarre Nginx/PHP-FPM
8. **Déploiement complet** - Exécute toutes les étapes automatiquement
9. **Shell SSH interactif** - Ouvre une session SSH

## 📋 Processus de déploiement complet

1. **Préparation locale**
   ```bash
   ./deploy-quick.sh
   ```

2. **Configuration serveur**
   ```bash
   ./ssh-helper.sh
   # Choisir option 8 (Déploiement complet)
   ```

3. **Vérification**
   - Accéder à https://loanpro.achatrembourse.online
   - Vérifier le bon fonctionnement de l'application

## 🔒 Sécurité

### Fichiers sensibles exclus du déploiement

- `.env.dev` et `.env.test`
- Dossier `.git`
- Logs et cache de développement
- Tests unitaires
- Fichiers Docker de développement

### Configuration de production automatique

- `APP_ENV=prod`
- `APP_DEBUG=false`
- Optimisation des assets
- Cache de production
- Autoloader optimisé

## 🛠️ Dépannage

### Erreurs communes

1. **Connexion FTP échouée**
   - Vérifier les identifiants FTP
   - Vérifier la connectivité réseau

2. **Permissions insuffisantes**
   ```bash
   ./ssh-helper.sh
   # Option 3: Configurer les permissions
   ```

3. **Base de données non accessible**
   ```bash
   ./ssh-helper.sh
   # Option 2: Configurer la base de données
   ```

4. **Cache corrompu**
   ```bash
   ./ssh-helper.sh
   # Option 4: Vider le cache
   ```

### Commandes de diagnostic

```bash
# Vérifier l'état du serveur
./ssh-helper.sh
# Option 5: Vérifier l'état

# Accès SSH direct
ssh mrjoker@46.202.129.197

# Vérifier les logs Apache
tail -f /var/log/apache2/error.log

# Vérifier les logs Symfony
tail -f /home/mrjoker/web/loanpro.achatrembourse.online/var/log/prod.log
```

## 📁 Structure de déploiement

```
loanpro.achatrembourse.online/
├── public/              # Point d'entrée web
│   ├── index.php        # Front controller
│   ├── .htaccess        # Configuration Apache
│   └── assets/          # Assets compilés
├── src/                 # Code source PHP
├── templates/           # Templates Twig
├── config/              # Configuration Symfony
├── var/                 # Cache, logs, sessions
│   ├── cache/
│   ├── log/
│   └── sessions/
├── bin/                 # Exécutables (console)
├── migrations/          # Migrations de base de données
└── vendor/              # Dépendances Composer
```

## 🔄 Mise à jour

Pour mettre à jour l'application :

1. Modifier le code en local
2. Tester en environnement de développement
3. Relancer le déploiement :
   ```bash
   ./deploy-quick.sh
   ./ssh-helper.sh  # Option 8
   ```

## 📞 Support

En cas de problème :

1. Vérifier les logs sur le serveur
2. Utiliser l'option de diagnostic du ssh-helper
3. Contacter l'administrateur système si nécessaire

---

**Note**: Ces scripts sont configurés spécifiquement pour le serveur `loanpro.achatrembourse.online`. Adapter les configurations si nécessaire pour d'autres environnements.
