# 🚗 TeslApp

<div align="center">

![Tesla](https://img.shields.io/badge/Tesla-CC0000?style=for-the-badge&logo=tesla&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=teslapp&metric=alert_status)](https://sonarcloud.io/)
[![Code Style: Prettier](https://img.shields.io/badge/code_style-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

**Application web de gestion de véhicules Tesla via l'API Fleet**

</div>

---

## 📋 Description

TeslApp est une application web permettant de reproduire les principales fonctionnalités de l'application mobile Tesla. Elle offre aux propriétaires de véhicules Tesla un accès à distance via n'importe quel navigateur web, sans dépendre des applications natives iOS ou Android.

> 🎓 Projet réalisé dans le cadre du BUT Informatique 2ème année - IUT Aix-Marseille

## 🛠️ Stack Technique

| Catégorie           | Technologies               |
| ------------------- | -------------------------- |
| **Backend**         | PHP 8.2+ (MVC + Clean Architecture from scratch) |
| **Frontend**        | HTML5, CSS3, JavaScript    |
| **Base de données** | PostgreSQL                 |
| **API**             | Tesla Fleet API, OAuth2    |
| **Qualité**         | SonarCloud, Prettier       |
| **CI/CD**           | GitHub Actions             |
| **Infrastructure**  | Cloudflare DNS, HTTPS      |

## 🏗️ Architecture

Le projet combine plusieurs patrons d'architecture étudiés en R4.01 :

- **MVC artisanal** (Chap 1) — Front controller PHP, séparation Controller / Service / View
- **Clean Architecture** (R. C. Martin, Chap 2) — Domain au cœur, Infrastructure en périphérie, dépendances orientées vers l'intérieur
- **Architecture hurlante** — Organisation par **bounded contexts** métier (Auth, Vehicle, Climate, Charging, Trips) plutôt que par couches techniques
- **Application Service Pattern** (Evans / Vernon) — 1 Service par feature regroupant les cas d'utilisation

```
teslapp/
├── www/                          # DocumentRoot (point d'entrée web)
│   ├── index.php                 # Front controller
│   └── _assets/                  # CSS, JS, images
├── private/                      # Code applicatif (inaccessible HTTP)
│   ├── config/                   # Configuration + container DI
│   ├── Auth/                     # Bounded Context : Login with Tesla
│   ├── Vehicle/                  # Bounded Context : commandes véhicule
│   ├── Climate/                  # Bounded Context : climatisation
│   ├── Charging/                 # Bounded Context : recharge
│   ├── Trips/                    # Bounded Context : historique trajets
│   └── Shared/                   # Kernel : VO, ports, infra commune, utils
├── db/                           # Schéma BDD (schema.sql)
├── docker/                       # Compose Docker (Postgres, Kafka, ...)
├── bin/                          # Scripts CLI (workers, crons)
├── tests/                        # PHPUnit (Unit, Integration, Acceptance)
└── vendor/                       # Dépendances Composer (gitignored)
```

Chaque feature suit la même structure interne (Clean Architecture locale) :

```
private/<Feature>/
├── Domain/             # Entités + Application Service + Ports (interfaces)
├── Infrastructure/     # Adapters concrets (PdoRepositories, clients HTTP)
├── Http/               # Controllers + Presenters
└── Views/              # Templates PHP
```

## 🔀 Workflow Git

Nous utilisons un **Git Flow simplifié** :

| Branche   | Description                                         |
| --------- | --------------------------------------------------- |
| `main`    | Version stable en production                        |
| `preprod` | Validation avant production                         |
| `develop` | Intégration des développements                      |
| `test`    | Environnement de test pour l'enseignant responsable |

## ✅ Qualité de code

- **SonarCloud** : Analyse continue (bugs, vulnérabilités, dette technique)
- **Prettier** : Formatage automatique du code
- **GitHub Actions** : CI/CD automatisé à chaque push/PR

## 👥 Équipe

| Membre                   | Rôle |
| ------------------------ | ---- |
| **Alexis BARBERIS**      |
| **Mathis FAUTSCH**       |
| **Mathis LAURIOL-TORCQ** |
| **Oriane MEJEAN**        |
| **Jérémy WATRIPONT**     |

## 📚 Documentation

- [Documentation API Tesla Fleet](https://developer.tesla.com/)

## 📄 Licence

Projet académique - IUT Aix-Marseille © 2026

---

<div align="center">

**S3.A&B.01 – TeslApp** • BUT Informatique 2ème année alternance

Enseignant responsable : **Olivier GÉRARD**

</div>
