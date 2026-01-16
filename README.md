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

| Catégorie | Technologies |
|-----------|-------------|
| **Backend** | PHP 8.x (MVC from scratch) |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Base de données** | PostgreSQL |
| **API** | Tesla Fleet API, OAuth2 |
| **Qualité** | SonarCloud, Prettier |
| **CI/CD** | GitHub Actions |
| **Infrastructure** | Cloudflare DNS, HTTPS |

## 🏗️ Architecture

Le projet suit une architecture **MVC (Model-View-Controller)** sans framework PHP.
```
teslapp/
├── public/                 # Point d'entrée web
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
├── app/
│   ├── Controllers/        # Logique de traitement
│   ├── Models/             # Entités métier
│   ├── Views/              # Classes de rendu
│   └── Services/           # API Tesla, OAuth...
├── templates/
│   ├── layouts/            # Structure HTML globale
│   ├── components/         # Éléments réutilisables
│   ├── auth/               # Pages d'authentification
│   └── vehicle/            # Pages véhicule
├── config/                 # Configuration
└── vendor/                 # Dépendances Composer
```

## 🔀 Workflow Git

Nous utilisons un **Git Flow simplifié** :

| Branche | Description |
|---------|-------------|
| `main` | Version stable en production |
| `preprod` | Validation avant production |
| `develop` | Intégration des développements |
| `test` | Environnement de test pour l'enseignant responsable |

## ✅ Qualité de code

- **SonarCloud** : Analyse continue (bugs, vulnérabilités, dette technique)
- **Prettier** : Formatage automatique du code
- **GitHub Actions** : CI/CD automatisé à chaque push/PR

## 👥 Équipe

| Membre | Rôle |
|--------|------|
| **Alexis BARBERIS** | 
| **Mathis FAUTSCH** | 
| **Mathis LAURIOL-TORCQ** | 
| **Oriane MEJEAN** |
| **Jérémy WATRIPONT** |

## 📚 Documentation

- [Documentation API Tesla Fleet](https://developer.tesla.com/)

## 📄 Licence

Projet académique - IUT Aix-Marseille © 2026

---

<div align="center">

**S3.A&B.01 – TeslApp** • BUT Informatique 2ème année alternance

Enseignant responsable : **Olivier GÉRARD**

</div>