<?php
declare(strict_types=1);

/**
 * Modèle de gestion des spécialisations médicales
 *
 * Cette classe gère les opérations relatives aux spécialisations médicales
 * dans l'application MedBoard. Elle permet de vérifier l'existence,
 * récupérer la liste complète et obtenir les paires clé-valeur pour
 * les formulaires de sélection.
 *
 * @package MedBoard\Models
 * @author  MedBoard Team
 * @version 1.0.0
 */
final class SpecializationModel
{
  /**
   * Instance PDO pour les interactions avec la base de données
   *
   * @var PDO
   */
  private PDO $pdo;

  /**
   * Constructeur - Initialise la connexion PDO
   *
   * Récupère automatiquement l'instance PDO via la classe Database.
   */
  public function __construct()
  {
    $this->pdo = (new Database())->getPdo();
  }

  /**
   * Vérifie l'existence d'une spécialisation par son ID
   *
   * Méthode utile pour valider qu'un ID de spécialisation fourni
   * lors de l'inscription d'un utilisateur existe bien en base.
   *
   * @param int $id ID de la spécialisation à vérifier
   *
   * @return bool True si la spécialisation existe, false sinon
   */
  public function existsById(int $id): bool
  {
    $sql = 'SELECT 1 FROM specializations WHERE specialization_id = :id LIMIT 1';
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return (bool) $stmt->fetchColumn();
  }

  /**
   * Retourne la liste complète des spécialisations
   *
   * Récupère toutes les spécialisations disponibles, triées par ordre
   * alphabétique du nom français. Utile pour afficher une liste complète
   * ou générer un menu de sélection.
   *
   * @return array Tableau de tableaux associatifs au format :
   *               [['specialization_id' => 1, 'name_fr' => 'Cardiologie'], ...]
   *               Retourne un tableau vide si aucune spécialisation n'existe
   */
  public function getAll(): array
  {
    $sql = "SELECT specialization_id, name_fr
                 FROM specializations
                 ORDER BY name_fr ASC";
    $stmt = $this->pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $rows ?: [];
  }

  /**
   * Retourne les spécialisations sous forme de paires clé-valeur
   *
   * Transforme la liste des spécialisations en un tableau associatif
   * simple [id => nom], particulièrement adapté pour remplir les éléments
   * HTML <select> ou pour des opérations de mapping rapide.
   *
   * @return array Tableau associatif au format :
   *               ['1' => 'Cardiologie', '2' => 'Dermatologie', ...]
   *               Les clés sont des chaînes (cast depuis les IDs)
   */
  public function getPairs(): array
  {
    $pairs = [];
    foreach ($this->getAll() as $row) {
      $pairs[(string) $row['specialization_id']] = (string) $row['name_fr'];
    }
    return $pairs;
  }
}
