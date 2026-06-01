<?php
/**
 * Contrôleur du tableau de bord utilisateur
 *
 * Ce contrôleur gère l'affichage du tableau de bord principal
 * accessible uniquement aux utilisateurs authentifiés.
 */

/**
 * Class DashboardController
 *
 * Affiche les informations de l'utilisateur connecté sur sa page de tableau de bord
 */
final class DashboardController
{
    /**
     * Affiche le tableau de bord de l'utilisateur
     *
     * Cette méthode récupère les informations de l'utilisateur depuis la session,
     * formate les données (noms en capitales initiales) et affiche la vue du dashboard.
     *
     * Les informations affichées incluent :
     * - Prénom et nom
     * - Spécialisation médicale (si définie)
     *
     * @return void
     */
    public function index(): void
    {
        // Récupération des données utilisateur depuis la session
        $user = $_SESSION['user'] ?? [];

        // Formatage des noms avec majuscule initiale
        $firstname = mb_convert_case($user['firstname'] ?? '', MB_CASE_TITLE, 'UTF-8');
        $lastname = mb_convert_case($user['lastname'] ?? '', MB_CASE_TITLE, 'UTF-8');
        $specialization = mb_convert_case($user['specialization'] ?? '', MB_CASE_TITLE, 'UTF-8');

        // Inclusion de la vue du tableau de bord
        require __DIR__ . '/../views/dashboard.php';
    }
}
