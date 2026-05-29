# 🚗 TeslApp

<div align="center">

![Tesla](https://img.shields.io/badge/Tesla-CC0000?style=for-the-badge&logo=tesla&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=TeslApp-IUT_webapp&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=TeslApp-IUT_webapp)
[![Code Style: Prettier](https://img.shields.io/badge/code_style-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

**Application web de gestion de véhicules Tesla via l'API Fleet**

</div>

---

## 📋 Description

TeslApp est une application web permettant de reproduire les principales fonctionnalités de l'application mobile Tesla. Elle offre aux propriétaires de véhicules Tesla un accès à distance via n'importe quel navigateur web, sans dépendre des applications natives iOS ou Android.

> 🎓 Projet réalisé dans le cadre du BUT Informatique 2ème année - IUT Aix-Marseille

## 🛠️ Stack Technique

| Catégorie           | Technologies                                     |
| ------------------- | ------------------------------------------------ |
| **Backend**         | PHP 8.2+ (MVC en couches plates artisanal + éléments de Clean Architecture dans `Models/`) |
| **Frontend**        | HTML5, CSS3, JavaScript                          |
| **Base de données** | PostgreSQL                                       |
| **API**             | Tesla Fleet API, OAuth2                          |
| **Qualité**         | SonarCloud, PHPStan, PHP_CodeSniffer / PHP-CS-Fixer (PSR-12), PHPUnit, Prettier |
| **CI/CD**           | GitHub Actions                                   |
| **Infrastructure**  | Cloudflare (DNS) · Feyli (Docker) · HTTPS Let's Encrypt |

## 🏗️ Architecture

Le projet applique une **architecture MVC en couches plates** (validée par l'enseignant), enrichie de quelques éléments de Clean Architecture dans la couche `Models/` :

- **MVC artisanal en couches plates** (Chap 1 R4.01) — Front controller PHP `www/index.php`, couches top-level `Controllers/` / `Models/` / `Views/` / `Utils/`
- **Éléments de Clean Architecture dans `Models/`** (R. C. Martin, Chap 2) — Repositories + interfaces (ports), Value Objects readonly, ports Tesla ségrégés (ISP), hiérarchie d'exceptions ; dépendances orientées vers l'intérieur de la couche modèle
- **Application Service Pattern** (Evans / Vernon) — 1 Service par feature (`Models/<Feature>/<Feature>Service.php`) regroupant les cas d'utilisation

```
teslapp/
├── www/                          # DocumentRoot (point d'entrée web)
│   ├── index.php                 # Front controller
│   └── _assets/                  # CSS, JS, images
├── private/                      # Code applicatif (inaccessible HTTP)
│   ├── config/                   # Configuration + container DI
│   ├── Controllers/              # Couche Controller (1 Controller par feature, à plat)
│   ├── Models/                   # Couche Model : sous-dossiers par feature (Auth, Vehicle, Climate, Charging, Trips) + Shared/{ValueObjects, TeslaApi, Exceptions} + Database.php
│   ├── Views/                    # Couche View : templates PHP par feature + layout + partials/
│   └── Utils/                    # Helpers transverses (Auth, Csrf, Flash, Http, Inputs, RateLimit)
├── db/                           # Schéma BDD : script_creation_app.sql (DDL) + script_insertion_app.sql (données de référence)
├── docker/                       # Compose Docker (Postgres, Kafka, ...)
├── bin/                          # Scripts CLI
├── tests/                        # PHPUnit (Unit, Integration, Acceptance)
└── vendor/                       # Dépendances Composer (gitignored)
```

Chaque feature est répartie à travers les couches MVC (pas de sous-dossiers Domain/Infrastructure par feature) :

```
private/Controllers/<Feature>Controller.php   # orchestration HTTP de la feature
private/Models/<Feature>/                      # entités + <Feature>Service + repository PDO + interfaces (ports)
private/Views/<Feature>/                       # templates PHP de la feature
```

## 🔀 Workflow Git

Nous utilisons un **Git Flow simplifié** :

| Branche   | Description                                         |
| --------- | --------------------------------------------------- |
| `main`    | Version stable en production                        |
| `preprod` | Validation avant production                         |
| `development` | Intégration des développements                  |
| `test`    | Environnement de test pour l'enseignant responsable |

## ✅ Qualité de code

- **SonarCloud** : Analyse continue (bugs, vulnérabilités, dette technique)
- **PHPStan** : Analyse statique du code PHP (typage, erreurs potentielles)
- **PHP_CodeSniffer / PHP-CS-Fixer** : Conformité au standard PSR-12
- **PHPUnit** : Tests unitaires, d'intégration et d'acceptance
- **Prettier** : Formatage automatique du code
- **GitHub Actions** : CI/CD automatisé à chaque push/PR

## 👥 Équipe

| Membre                   | Rôle          |
| ------------------------ | ------------- |
| **Alexis BARBERIS**      | Scrum Master  |
| **Mathis FAUTSCH**       | Développeur   |
| **Mathis LAURIOL-TORCQ** | Développeur   |
| **Oriane MEJEAN**        | Product Owner |
| **Jérémy WATRIPONT**     | Développeur   |

## 📚 Documentation

- [Documentation API Tesla Fleet](https://developer.tesla.com/)

## 📄 Licence

Projet académique - IUT Aix-Marseille © 2026

---

<div align="center">

**R4.01 Architecture Logicielle – TeslApp** • BUT Informatique 2ème année alternance

Enseignant responsable : **Olivier GÉRARD**

</div>
