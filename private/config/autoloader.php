<?php
/**
 * Autoloader personnalisé pour le chargement automatique des classes
 *
 * Ce fichier enregistre une fonction d'autoload qui cherche et inclut automatiquement
 * les fichiers de classes lorsqu'elles sont utilisées dans le code.
 * Les classes sont recherchées dans les dossiers controllers, models et utils.
 */

/**
 * Fonction d'autoload pour charger automatiquement les classes
 *
 * Parcourt les dossiers définis pour trouver le fichier correspondant au nom de la classe.
 * Dès qu'un fichier correspondant est trouvé, il est inclus et la recherche s'arrête.
 *
 * @param string $class Nom de la classe à charger
 * @return void
 */
spl_autoload_register(function (string $class): void {
    // Récupération des chemins de base définis dans config.php
    $baseDir = BASE_PATH;
    $modulesPath = MODULES_PATH;

    /**
     * Liste des chemins possibles pour trouver la classe
     * L'ordre de recherche est important :
     * 1. Contrôleurs (gèrent les requêtes HTTP)
     * 2. Modèles (interactions avec la base de données)
     * 3. Utilitaires (classes helper)
     */
    $paths = [
        $modulesPath . 'controllers/' . $class . '.php',
        $modulesPath . 'models/' . $class . '.php',
        $baseDir . 'private/utils/' . $class . '.php',
    ];

    // Inclusion du premier fichier trouvé correspondant au nom de classe
    foreach ($paths as $file) {
        if (is_file($file)) {
            require $file;
            return; // Arrêt dès que la classe est chargée
        }
    }
});
