# Déployer Strugal Production sur ZimaOS

ZimaOS exécute les applications avec Docker. Ce projet est fourni avec un `Dockerfile` et un `docker-compose.yml`.

## 1. Préparer le projet
1. Appliquez d'abord `strugal-ui-update.zip`, puis copiez le contenu de ce zip à la racine du projet.
2. Ouvrez `docker-compose.yml` et modifiez :
   - `APP_URL` : l'adresse que vous taperez dans le navigateur (ex. `http://192.168.1.50:8088`),
   - `ADMIN_EMAIL` / `ADMIN_PASSWORD` : le premier compte administrateur,
   - `APP_TIMEZONE` : votre fuseau horaire (par défaut `Africa/Casablanca`).

## 2. Copier le projet sur ZimaOS
Envoyez le dossier du projet vers le serveur (application Fichiers ou partage réseau), par exemple dans `/DATA/AppData/strugal-src`.
Inutile d'envoyer `vendor/` ni `node_modules/` : ils sont reconstruits dans l'image.

## 3. Construire et lancer
Dans un terminal sur ZimaOS (SSH ou terminal intégré) :

```
cd /DATA/AppData/strugal-src
docker compose up -d --build
```

La première construction prend plusieurs minutes. Ensuite, ouvrez `http://ADRESSE-DU-SERVEUR:8088` et connectez-vous avec le compte administrateur.
Au premier démarrage, l'application crée la base de données, les départements Laquage et Emballage et le compte administrateur.
Si `ADMIN_PASSWORD` est vide, un mot de passe est généré et affiché dans les journaux : `docker logs strugal`.

## 4. Reprendre vos données actuelles (facultatif)
Arrêtez le conteneur (`docker compose down`), copiez votre ancien `database/database.sqlite` vers
`/DATA/AppData/strugal/data/database.sqlite`, puis relancez (`docker compose up -d`).

## 5. Mettre à jour
Remplacez les fichiers du projet puis relancez `docker compose up -d --build`. Les données (dans `/DATA/AppData/strugal/`) sont conservées.

## 6. Sauvegarde
Sauvegardez le dossier `/DATA/AppData/strugal/` : il contient la base de données, la clé de l'application et les journaux.

## Importer dans l'interface ZimaOS (application personnalisée)
L'import par l'interface a besoin d'une image déjà construite :
1. Sur un ordinateur : `docker build -t VOTRE-COMPTE/strugal:1.0 .` puis `docker push VOTRE-COMPTE/strugal:1.0`.
2. Dans `docker-compose.yml`, remplacez la ligne `build: .` par `image: VOTRE-COMPTE/strugal:1.0`.
3. Dans ZimaOS, ajoutez une application personnalisée et importez ce fichier compose.

Remarque : les clés d'accès (passkeys) exigent HTTPS ; en HTTP sur le réseau local, utilisez le mot de passe.
