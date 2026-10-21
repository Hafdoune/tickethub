# TicketHub

Application de support client (helpdesk) construite avec **Symfony 7.4**, **Doctrine**, **MySQL** et **Vue.js**.

Les clients ouvrent des tickets et échangent avec le support. Les agents traitent la file d'attente, s'assignent les tickets, changent leur statut et suivent l'activité depuis un tableau de bord en Vue.js. Une API REST permet d'intégrer TicketHub à d'autres outils.

![CI](https://github.com/Hafdoune/tickethub/actions/workflows/ci.yml/badge.svg)

## Fonctionnalités

- Authentification par formulaire, inscription, rôles hiérarchiques (`ROLE_USER` < `ROLE_AGENT` < `ROLE_ADMIN`)
- Tickets avec catégorie, priorité et cycle de vie (`ouvert → en cours → résolu → fermé`)
- Fil d'échanges client / support ; une réponse du client rouvre un ticket résolu
- Règles d'accès centralisées dans un **Voter** (`TicketVoter`) : un client ne voit que ses tickets et ne peut modifier un ticket qu'avant sa prise en charge
- Liste filtrable et paginée (statut, priorité, recherche plein texte)
- Tableau de bord agent en **Vue.js 3 + TypeScript** (composants `.vue` en `<script setup>`) : compteurs, file d'attente triable, « me l'assigner », rafraîchissement automatique
- **API REST JSON** authentifiée par jeton Bearer
- Tests unitaires et fonctionnels, intégration continue GitHub Actions
- Image Docker basée sur FrankenPHP

## Stack

| Couche | Choix |
|---|---|
| Back-end | PHP 8.3, Symfony 7.4 (LTS) |
| Données | Doctrine ORM 3, Doctrine Migrations, MySQL 8.4 |
| Front-end | Twig, Bootstrap 5, Vue.js 3 (Composition API, `<script setup lang="ts">`), TypeScript, Vite |
| Sécurité | Security bundle, form login, access token authenticator, voters, CSRF |
| Tests | PHPUnit 12, WebTestCase, DAMA Doctrine Test Bundle |
| Qualité | PHPStan (niveau 6, extensions Symfony et Doctrine), vue-tsc |
| Infra | Docker, FrankenPHP, GitHub Actions |

## Démarrer avec Docker

```bash
git clone https://github.com/Hafdoune/tickethub.git
cd tickethub
docker compose up -d --build
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

L'application est disponible sur http://localhost:8080. Les migrations sont exécutées au démarrage du conteneur.

## Démarrer en local

Prérequis : PHP 8.2+, Composer, Node.js 20+, Docker (pour MySQL) ou un MySQL local.

```bash
composer install
npm install
npm run build
docker compose up -d database
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
symfony serve
```

Pendant le développement du front, `npm run dev` lance le serveur Vite avec rechargement à chaud ; le bundle Symfony bascule automatiquement dessus.

La base Docker écoute sur le port `3307` pour ne pas entrer en conflit avec un MySQL local. Pour utiliser une autre base, surcharger `DATABASE_URL` dans `.env.local`.

## Comptes de démonstration

Mot de passe pour tous les comptes : `password`

| Rôle | Email |
|---|---|
| Admin | `admin@tickethub.test` |
| Agent | `agent@tickethub.test` |
| Agent | `agent2@tickethub.test` |
| Client | `client@tickethub.test` |

## API

Générer un jeton (il n'est affiché qu'une fois, seule son empreinte SHA-256 est stockée) :

```bash
php bin/console app:api-token client@tickethub.test
```

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/me` | Utilisateur courant |
| `GET` | `/api/tickets` | Liste paginée (`page`, `limit`, `status`, `priority`, `category`, `q`) |
| `GET` | `/api/tickets/{id}` | Détail avec les échanges |
| `POST` | `/api/tickets` | Créer un ticket |
| `PATCH` | `/api/tickets/{id}` | Modifier le contenu (auteur) ou le statut / l'agent (agents) |
| `POST` | `/api/tickets/{id}/close` | Fermer |
| `POST` | `/api/tickets/{id}/comments` | Répondre |

```bash
curl -X POST http://localhost:8080/api/tickets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Box sans connexion","description":"Plus d internet depuis ce matin.","categoryId":1,"priority":"high"}'
```

Les erreurs suivent le format `application/problem+json`. Une requête invalide renvoie `422` avec la liste des violations par champ.

## Choix techniques

- **Voter plutôt que des `if` dans les contrôleurs** : toutes les règles d'accès à un ticket sont au même endroit et testées unitairement. Les contrôleurs n'utilisent que `#[IsGranted(TicketVoter::EDIT, 'ticket')]`.
- **DTO + `#[MapRequestPayload]`** pour l'API : le JSON est désérialisé et validé avant d'entrer dans le contrôleur ; les entités ne sont jamais exposées à la désérialisation.
- **Groupes de sérialisation** (`ticket:list`, `ticket:item`) pour contrôler les champs exposés (pas d'email ni de rôles dans les réponses publiques).
- **Logique métier dans les entités** : `Ticket::assignTo()` fait passer un ticket ouvert « en cours », `Ticket::close()` horodate la fermeture.
- **Requêtes avec jointures explicites** (`addSelect`) et `Paginator` Doctrine pour éviter le problème N+1 sur les listes.
- **Jetons d'API hachés** : une fuite de la base ne donne pas accès à l'API.
- **Composable `useDashboard`** : l'état, le chargement et l'assignation du tableau de bord sont isolés de l'affichage ; les composants ne reçoivent que des props typées.
- **Tests isolés par transaction** (DAMA) : chaque test crée ses propres données et la base est restaurée automatiquement.

## Tests

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
npm run build
php bin/phpunit
vendor/bin/phpstan analyse
npm run typecheck
```

## Structure

```
src/
├── Command/            Commande console (génération de jeton API)
├── Controller/         Contrôleurs web, tableau de bord et API (Api/)
├── DataFixtures/       Données de démonstration
├── Dto/                Payloads validés de l'API
├── Entity/             User, Ticket, Category, Comment
├── Enum/               TicketStatus, TicketPriority
├── Form/               Formulaires Symfony
├── Repository/         Requêtes Doctrine et filtres
└── Security/           Voter et authentification par jeton
assets/
├── dashboard/          Composants Vue (.vue), composable, client API et types TypeScript
└── styles/             Feuille de style de l'application
tests/                  Tests unitaires et fonctionnels
```
