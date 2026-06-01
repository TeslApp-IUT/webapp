<?php
declare(strict_types=1);

require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Service d'envoi d'e-mails via SMTP
 *
 * Encapsule la bibliothèque PHPMailer pour fournir une interface simplifiée
 * d'envoi d'e-mails. Configure automatiquement la connexion SMTP avec les
 * paramètres définis dans le fichier de configuration.
 *
 * Ce service supporte l'envoi d'e-mails HTML avec alternative texte brut,
 * l'authentification SMTP, et la sécurisation via STARTTLS.
 *
 * @package MedBoard\Utils
 * @author MedBoard Team
 */
final class MailService
{
    /**
     * Instance de PHPMailer configurée
     *
     * @var PHPMailer
     */
    private PHPMailer $mail;

    /**
     * Constructeur du service d'envoi d'e-mails
     *
     * Initialise et configure PHPMailer avec les paramètres SMTP définis
     * dans les constantes de configuration (SMTP_HOST, SMTP_USERNAME, etc.).
     *
     * Configuration appliquée :
     * - Activation du mode SMTP avec authentification
     * - Chiffrement STARTTLS sur le port 587
     * - Encodage UTF-8 pour les e-mails
     * - Format HTML activé par défaut
     * - Expéditeur défini via SMTP_FROM_EMAIL et SMTP_FROM_NAME
     *
     * @throws Exception Si la configuration PHPMailer échoue
     */
    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // Configuration SMTP avec authentification
        $this->mail->isSMTP();
        $this->mail->Host       = SMTP_HOST;
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = SMTP_USERNAME;
        $this->mail->Password   = SMTP_PASSWORD;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;

        // Réglages de base : encodage et format
        $this->mail->CharSet = 'UTF-8';
        $this->mail->isHTML(true);
        $this->mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    }

    /**
     * Envoie un e-mail avec contenu HTML et alternative texte brut
     *
     * Envoie un e-mail à un destinataire unique. Le contenu HTML est obligatoire,
     * tandis que le contenu texte est optionnel. Si aucun contenu texte n'est fourni,
     * une version texte sera générée automatiquement en supprimant les balises HTML.
     *
     * La méthode nettoie la liste des destinataires avant chaque envoi pour permettre
     * la réutilisation de l'instance MailService.
     *
     * @param string $to L'adresse e-mail du destinataire
     * @param string $subject Le sujet de l'e-mail
     * @param string $htmlBody Le contenu HTML de l'e-mail
     * @param string $textBody Le contenu texte alternatif (optionnel, auto-généré si vide)
     * @return bool True si l'envoi a réussi, false en cas d'erreur
     */
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): bool {
        try {
            // Nettoyage des destinataires pour permettre la réutilisation
            $this->mail->clearAllRecipients();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $htmlBody;
            // Alternative texte brut : fournie ou générée automatiquement
            $this->mail->AltBody = $textBody ?: strip_tags($htmlBody);
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            // Enregistrement de l'erreur dans les logs serveur
            error_log('MailService error: ' . $e->getMessage());
            return false;
        }
    }
}
