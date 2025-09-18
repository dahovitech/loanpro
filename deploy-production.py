#!/usr/bin/env python3
"""
Script de déploiement automatique pour LoanPro
Déploie l'application via FTP sur le serveur de production
"""

import os
import sys
import ftplib
import zipfile
import subprocess
import shutil
import tempfile
from pathlib import Path
import argparse
from datetime import datetime

class LoanProDeployer:
    def __init__(self):
        # Configuration FTP
        self.ftp_host = "loanpro.achatrembourse.online"
        self.ftp_user = "mrjoker_loanpro"
        self.ftp_pass = "eAaGl6vpl|c7Gv5P9"
        self.ftp_remote_dir = "/home/mrjoker/web/loanpro.achatrembourse.online"
        
        # Dossiers de travail
        self.workspace = Path("/workspace")
        self.project_dir = self.workspace / "loanpro"
        self.temp_dir = None
        
        # Configuration de production
        self.production_config = {
            'APP_ENV': 'prod',
            'APP_DEBUG': 'false',
            'DATABASE_URL': 'mysql://root:password@localhost:3306/loanpro_prod?serverVersion=8.0',
            'MAILER_DSN': 'smtp://localhost',
            'APP_SECRET': 'prod-secret-key-change-this'
        }

    def log(self, message):
        """Affiche un message avec timestamp"""
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        print(f"[{timestamp}] {message}")

    def prepare_production_build(self):
        """Prépare l'application pour la production"""
        self.log("🔧 Préparation de l'application pour la production...")
        
        # Créer un dossier temporaire
        self.temp_dir = tempfile.mkdtemp(prefix="loanpro_deploy_")
        build_dir = Path(self.temp_dir) / "loanpro"
        
        self.log(f"📁 Copie des fichiers vers {build_dir}")
        
        # Copier tous les fichiers nécessaires (exclure certains dossiers)
        exclude_dirs = {'.git', 'var/cache', 'var/log', 'var/sessions', 'node_modules', 'tests'}
        exclude_files = {'.env.dev', '.env.test', 'compose.yaml', 'compose.override.yaml'}
        
        shutil.copytree(
            self.project_dir, 
            build_dir,
            ignore=shutil.ignore_patterns(*exclude_dirs, *exclude_files)
        )
        
        # Créer le fichier .env de production
        env_prod_content = "\n".join([f"{key}={value}" for key, value in self.production_config.items()])
        
        with open(build_dir / ".env", "w") as f:
            f.write(env_prod_content)
        
        self.log("✅ Fichier .env de production créé")
        
        # Créer des dossiers nécessaires
        for dir_name in ['var/cache', 'var/log', 'var/sessions']:
            (build_dir / dir_name).mkdir(parents=True, exist_ok=True)
        
        # Installer les dépendances de production
        self.log("📦 Installation des dépendances Composer (production)...")
        subprocess.run([
            "composer", "install", 
            "--no-dev", 
            "--optimize-autoloader",
            "--no-interaction"
        ], cwd=build_dir, check=True)
        
        # Build des assets
        self.log("🏗️ Construction des assets...")
        try:
            subprocess.run(["npm", "install"], cwd=build_dir, check=True)
            subprocess.run(["npm", "run", "build"], cwd=build_dir, check=True)
        except subprocess.CalledProcessError:
            self.log("⚠️ Erreur lors de la construction des assets (continuer sans)")
        
        # Vider le cache
        subprocess.run([
            "php", "bin/console", "cache:clear", "--env=prod"
        ], cwd=build_dir, check=True)
        
        self.log("✅ Application préparée pour la production")
        return build_dir

    def create_deployment_package(self, build_dir):
        """Créée une archive pour le déploiement"""
        self.log("📦 Création du package de déploiement...")
        
        package_path = Path(self.temp_dir) / "loanpro-deployment.zip"
        
        with zipfile.ZipFile(package_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
            for root, dirs, files in os.walk(build_dir):
                # Exclure certains dossiers de l'archive
                dirs[:] = [d for d in dirs if d not in {'var/cache', 'var/log', 'var/sessions'}]
                
                for file in files:
                    file_path = Path(root) / file
                    arc_path = file_path.relative_to(build_dir)
                    zipf.write(file_path, arc_path)
        
        self.log(f"✅ Package créé: {package_path}")
        return package_path

    def deploy_via_ftp(self, package_path):
        """Déploie le package via FTP"""
        self.log("🚀 Connexion au serveur FTP...")
        
        try:
            # Connexion FTP
            ftp = ftplib.FTP(self.ftp_host)
            ftp.login(self.ftp_user, self.ftp_pass)
            ftp.cwd(self.ftp_remote_dir)
            
            self.log("✅ Connexion FTP établie")
            
            # Créer un backup du déploiement précédent
            backup_name = f"backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
            try:
                ftp.mkd(backup_name)
                self.log(f"📁 Dossier de backup créé: {backup_name}")
                
                # Déplacer les fichiers existants vers le backup
                file_list = ftp.nlst()
                for item in file_list:
                    if item not in [backup_name, '.', '..']:
                        try:
                            ftp.rename(item, f"{backup_name}/{item}")
                        except:
                            pass  # Ignorer les erreurs de déplacement
                            
            except Exception as e:
                self.log(f"⚠️ Impossible de créer le backup: {e}")
            
            # Upload du package
            self.log("📤 Upload du package de déploiement...")
            with open(package_path, 'rb') as f:
                ftp.storbinary('STOR deployment.zip', f)
            
            self.log("✅ Package uploadé avec succès")
            
            # Créer un script d'extraction sur le serveur
            extract_script = '''#!/bin/bash
cd /home/mrjoker/web/loanpro.achatrembourse.online
unzip -o deployment.zip
rm deployment.zip
chmod -R 755 public/
chmod -R 777 var/
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
echo "Déploiement terminé !"
'''
            
            # Upload du script d'extraction
            with tempfile.NamedTemporaryFile(mode='w', delete=False, suffix='.sh') as f:
                f.write(extract_script)
                f.flush()
                
                with open(f.name, 'rb') as script_file:
                    ftp.storbinary('STOR extract.sh', script_file)
                
                os.unlink(f.name)
            
            ftp.quit()
            self.log("✅ Déploiement FTP terminé")
            
        except Exception as e:
            self.log(f"❌ Erreur lors du déploiement FTP: {e}")
            raise

    def create_htaccess(self, build_dir):
        """Créé le fichier .htaccess pour Apache"""
        htaccess_content = """
DirectoryIndex index.php

<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]

    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]

    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
"""
        
        with open(build_dir / "public" / ".htaccess", "w") as f:
            f.write(htaccess_content.strip())
        
        self.log("✅ Fichier .htaccess créé")

    def cleanup(self):
        """Nettoie les fichiers temporaires"""
        if self.temp_dir and os.path.exists(self.temp_dir):
            shutil.rmtree(self.temp_dir)
            self.log("🧹 Fichiers temporaires nettoyés")

    def deploy(self):
        """Lance le processus de déploiement complet"""
        try:
            self.log("🚀 Démarrage du déploiement de LoanPro...")
            
            # Vérifier que le projet existe
            if not self.project_dir.exists():
                raise FileNotFoundError(f"Projet non trouvé: {self.project_dir}")
            
            # Étapes de déploiement
            build_dir = self.prepare_production_build()
            self.create_htaccess(build_dir)
            package_path = self.create_deployment_package(build_dir)
            self.deploy_via_ftp(package_path)
            
            self.log("🎉 Déploiement terminé avec succès !")
            self.log("🌐 Application accessible sur: https://loanpro.achatrembourse.online")
            self.log("ℹ️  Connectez-vous en SSH pour exécuter le script d'extraction:")
            self.log("   ssh mrjoker@46.202.129.197")
            self.log("   cd /home/mrjoker/web/loanpro.achatrembourse.online")
            self.log("   chmod +x extract.sh && ./extract.sh")
            
        except Exception as e:
            self.log(f"❌ Erreur lors du déploiement: {e}")
            raise
        finally:
            self.cleanup()

def main():
    parser = argparse.ArgumentParser(description="Déploiement automatique de LoanPro")
    parser.add_argument("--force", action="store_true", help="Force le déploiement sans confirmation")
    args = parser.parse_args()
    
    deployer = LoanProDeployer()
    
    if not args.force:
        response = input("🤔 Confirmer le déploiement en production ? (y/N): ")
        if response.lower() not in ['y', 'yes', 'oui']:
            print("❌ Déploiement annulé")
            return
    
    deployer.deploy()

if __name__ == "__main__":
    main()
