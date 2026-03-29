# MiniProjet2A-EventReservation-NadaBIZID

## Description
Application web de gestion de réservations d'événements développée avec Symfony 7.
Permet aux utilisateurs de consulter des événements et de réserver en ligne,
et à un administrateur de gérer les événements via une interface sécurisée.

## Technologies utilisées
- PHP 8.2 / Symfony 7
- MySQL 8.0
- Doctrine ORM
- LexikJWT Bundle (authentification JWT)
- WebAuthn / Passkeys (authentification sans mot de passe)
- Symfony Mailer (confirmation par email)
- Twig + Bootstrap 5 (Bootswatch Flatly)
- Docker / Docker Compose
- PHPUnit (tests)

## Fonctionnalités
### Utilisateur
- Inscription et connexion (email/password + Passkey)
- Consultation de la liste des événements
- Détail d'un événement (description, date, lieu, places)
- Formulaire de réservation
- Confirmation par email automatique

### Administrateur
- Tableau de bord des événements
- CRUD complet sur les événements (créer, modifier, supprimer)
- Consultation des réservations par événement
- Déconnexion sécurisée

## Prérequis
- PHP 8.2+
- Composer 2.x
- Docker + Docker Compose
- Symfony CLI

## Installation

### 1. Cloner le dépôt
```bash
git clone https://github.com/nadanada2/MiniProjet2A-EventReservation-NadaBIZID
cd MiniProjet2A-EventReservation-NadaBIZID
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Configurer l'environnement
```bash
cp .env .env.local
```
Modifier `.env.local` :
```env
DATABASE_URL="mysql://root:root@127.0.0.1:3306/event_db?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://username:password@sandbox.smtp.mailtrap.io:2525
JWT_PASSPHRASE=ta_passphrase
```

### 4. Lancer Docker
```bash
docker compose up -d
```

### 5. Créer la base de données et appliquer les migrations
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Charger les données de test
```bash
php bin/console doctrine:fixtures:load
```

### 7. Lancer le serveur
```bash
symfony server:start
```
Ouvrir `http://127.0.0.1:8000`

## Comptes de test
| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@eventapp.com | admin123 |
| Utilisateur | user@eventapp.com | user123 |

## Lancer les tests
```bash
php bin/phpunit
php bin/phpunit --testdox
```

## Structure du projet
```
src/
├── Controller/
│   ├── AuthApiController.php    # JWT register/login/me
│   ├── EventController.php      # CRUD événements
│   ├── ReservationController.php # Réservations + mail
│   └── LoginController.php      # Page connexion
├── Entity/
│   ├── User.php
│   ├── Event.php
│   ├── Reservation.php
│   └── WebauthnCredential.php
├── Form/
│   ├── EventType.php
│   └── ReservationType.php
├── DataFixtures/
│   └── AppFixtures.php
templates/
├── base.html.twig
├── event/
├── reservation/
├── security/
└── emails/
tests/
├── Controller/
│   ├── AuthApiControllerTest.php
│   └── EventControllerTest.php
```

## Modules obligatoires
- **Sécurité renforcée** : JWT (LexikBundle) + Passkeys (WebAuthn/FIDO2)
- **Confirmation par mail** : Symfony Mailer + template Twig

## Screenshots

### Liste des événements
![Liste événements](screenshots/events-list.png)

### Details d'un évènement
![Détails](screenshots/reservation-details.png)

### Formulaire de réservation
![Réservation](screenshots/reservation-form.png)

### Réservation confirmée
![Réservation confirmée](screenshots/reservation-confirmed.png)

### Mail de confirmation
![Mail](screenshots/mail-confirmation.png)

### Tests PHPUnit
![Tests](screenshots/phpunit-tests.png)


## Auteur
**Nada BIZID** — FIA2-GL — ISSAT Sousse
Année universitaire 2025/2026
Contact : nadabizid8@gmail.com