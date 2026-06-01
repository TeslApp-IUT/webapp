<?php
/**
 * Fichier de configuration principale de l'application MedBoard
 *
 * Ce fichier définit toutes les constantes globales nécessaires au fonctionnement de l'application :
 * - Chemins du système de fichiers (modules, racine du projet)
 * - Paramètres de connexion à la base de données PostgreSQL
 * - Paramètres du serveur SMTP pour l'envoi d'emails
 *
 * Toutes les valeurs sensibles (identifiants, mots de passe) sont récupérées
 * depuis les variables d'environnement pour des raisons de sécurité.
 */

/**
 * Définition des chemins du projet
 *
 * MODULES_PATH : Chemin absolu vers le dossier contenant l'architecture MVC (controllers, models, views)
 * BASE_PATH : Chemin absolu vers la racine du projet (contient private/ et www/)
 */
define('MODULES_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR);
define('BASE_PATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);

/**
 * Configuration de la connexion à la base de données
 *
 * Ces constantes définissent les paramètres de connexion à PostgreSQL.
 * Les valeurs sont récupérées depuis les variables d'environnement (.env)
 * Si une variable n'existe pas, une chaîne vide est utilisée par défaut.
 *
 * DB_HOST : Adresse du serveur de base de données
 * DB_PORT : Port du serveur de base de données
 * DB_USER : Nom d'utilisateur pour la connexion
 * DB_PASS : Mot de passe pour la connexion
 * DB_NAME : Nom de la base de données
 * DB_CHARSET : Encodage des caractères (généralement UTF-8)
 */
define('DB_HOST', getenv('DB_HOST') ?? '');
define('DB_PORT', getenv('DB_PORT') ?? '');
define('DB_USER', getenv('DB_USER') ?? '');
define('DB_PASS', getenv('DB_PASS') ?? '');
define('DB_NAME', getenv('DB_NAME') ?? '');
define('DB_CHARSET', getenv('DB_CHARSET') ?? '');

/**
 * Configuration du serveur SMTP pour l'envoi d'emails
 *
 * Ces constantes définissent les paramètres du serveur SMTP utilisé pour l'envoi d'emails.
 * Utilisé notamment pour la réinitialisation de mot de passe et les notifications.
 *
 * SMTP_HOST : Adresse du serveur SMTP
 * SMTP_USERNAME : Nom d'utilisateur pour l'authentification SMTP
 * SMTP_PASSWORD : Mot de passe pour l'authentification SMTP
 * SMTP_FROM_EMAIL : Adresse email de l'expéditeur
 * SMTP_FROM_NAME : Nom affiché comme expéditeur
 */
define('SMTP_HOST', getenv('SMTP_HOST') ?? '');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?? '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?? '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?? '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?? '');
