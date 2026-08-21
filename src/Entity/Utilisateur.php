<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse e-mail est déjà utilisée.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** Ambiances visuelles disponibles, indépendantes du mode clair/sombre. */
    public const PALETTES = ['glitchworlds', 'wii', 'ps3', 'legacy', 'ds', 'dsi', '3ds'];

    /** Mode d'affichage : « system » suit le réglage de l'appareil. */
    public const MODES = ['system', 'light', 'dark'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $pseudo = '';

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    #[Assert\Email(message: 'Cette adresse e-mail n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $emailLegacy = null;

    #[ORM\Column(nullable: true)]
    private ?string $motDePasse = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banniere = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $biographie = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $localisation = null;

    #[ORM\Column(length: 160, nullable: true)]
    #[Assert\Length(max: 160)]
    private ?string $statutProfil = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column]
    private \DateTimeImmutable $inscritLe;

    #[ORM\Column(options: ['default' => 0])]
    private int $experience = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $points = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $derniereActivite = null;

    #[ORM\Column(length: 32, options: ['default' => 'system'])]
    private string $theme = 'glitchworlds:system';

    #[ORM\Column(options: ['default' => 0])]
    private bool $reductionAnimations = false;

    #[ORM\Column(type: Types::JSON)]
    private array $notifications = ['email' => true, 'messages' => true, 'communaute' => true,];

    #[ORM\Column(options: ['default' => 0])]
    private bool $profilPrive = false;

    #[ORM\Column(options: ['default' => 0])]
    private bool $contrasteRenforce = false;

    #[ORM\Column(length: 16, options: ['default' => 'normal'])]
    private string $tailleTexte = 'normal';

    #[ORM\Column(options: ['default' => 1])]
    private bool $videoBackgroundActive = true;

    #[ORM\Column(options: ['default' => 0])]
    private bool $videoBackgroundSoundActive = false;

    #[ORM\Column(type: Types::JSON)]
    private array $sessionsConnectees = [];

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $jetonReinitialisation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expirationJetonReinitialisation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ArticleBoutique $titreEquipe = null;

    /** @var Collection<int, Jeu> */
    #[ORM\ManyToMany(targetEntity: Jeu::class, inversedBy: 'ajouteAuxFavorisPar')]
    #[ORM\JoinTable(name: 'utilisateur_jeu_favori')]
    private Collection $jeuxFavoris;

    /** @var Collection<int, self> */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'abonnes')]
    #[ORM\JoinTable(name: 'utilisateur_abonnement')]
    #[ORM\JoinColumn(name: 'abonne_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'suivi_id', onDelete: 'CASCADE')]
    private Collection $abonnements;

    /** @var Collection<int, self> */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'abonnements')]
    private Collection $abonnes;

    public function __construct()
    {
        $this->jeuxFavoris = new ArrayCollection();
        $this->abonnements = new ArrayCollection();
        $this->abonnes = new ArrayCollection();
        $this->inscritLe = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = trim($pseudo);

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email === null ? null : strtolower(trim($email));

        return $this;
    }

    public function getEmailLegacy(): ?string
    {
        return $this->emailLegacy;
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? $this->pseudo;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }

    public function setPassword(?string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBanniere(): ?string { return $this->banniere; }
    public function setBanniere(?string $banniere): static { $this->banniere = $banniere; return $this; }
    public function getBiographie(): ?string { return $this->biographie; }
    public function setBiographie(?string $biographie): static { $this->biographie = $biographie === null ? null : trim($biographie); return $this; }
    public function getLocalisation(): ?string { return $this->localisation; }
    public function setLocalisation(?string $localisation): static { $this->localisation = $localisation === null ? null : trim($localisation); return $this; }
    public function getStatutProfil(): ?string { return $this->statutProfil; }
    public function setStatutProfil(?string $statutProfil): static { $this->statutProfil = $statutProfil === null ? null : trim($statutProfil); return $this; }
    public function getDateNaissance(): ?\DateTimeImmutable { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeImmutable $dateNaissance): static { $this->dateNaissance = $dateNaissance; return $this; }
    public function getInscritLe(): \DateTimeImmutable { return $this->inscritLe; }
    public function setInscritLe(\DateTimeImmutable $inscritLe): static { $this->inscritLe = $inscritLe; return $this; }
    public function getExperience(): int { return $this->experience; }
    public function setExperience(int $experience): static { $this->experience = max(0, $experience); return $this; }
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): static { $this->points = max(0, $points); return $this; }
    public function getDerniereActivite(): ?\DateTimeImmutable { return $this->derniereActivite; }
    public function setDerniereActivite(?\DateTimeImmutable $date): static { $this->derniereActivite = $date; return $this; }
    /** Valeur stockée : « palette:mode », par exemple « wii:dark ». */
    public function getTheme(): string { return implode(':', self::decomposerTheme($this->theme)); }
    public function getPalette(): string { return self::decomposerTheme($this->theme)[0]; }
    public function getMode(): string { return self::decomposerTheme($this->theme)[1]; }
    public function setTheme(string $theme): static {
        $this->theme = implode(':', self::decomposerTheme($theme));
        return $this;
    }
    public function setPalette(string $palette): static { return $this->setTheme($palette . ':' . $this->getMode()); }
    public function setMode(string $mode): static { return $this->setTheme($this->getPalette() . ':' . $mode); }

    /**
     * Sépare les deux axes du thème et rattrape les valeurs mono-axe des anciens comptes,
     * où le thème choisi était soit un mode, soit une palette figée dans une seule teinte.
     *
     * @return array{0: string, 1: string}
     */
    private static function decomposerTheme(string $valeur): array
    {
        [$palette, $mode] = array_pad(explode(':', $valeur, 2), 2, null);
        $palette = match ($palette) {
            'gamecube' => 'wii',
            'dreamcast', 'wave', 'neon' => 'ps3',
            default => $palette,
        };

        if ($mode === null) {
            $mode = match ($palette) {
                'light', 'dark', 'system' => $palette,
                'ps3' => 'dark',
                'wii', 'legacy', 'ds', 'dsi', '3ds' => 'light',
                default => 'system',
            };
            if (in_array($palette, self::MODES, true)) {
                $palette = 'glitchworlds';
            }
        }

        return [
            in_array($palette, self::PALETTES, true) ? $palette : 'glitchworlds',
            in_array($mode, self::MODES, true) ? $mode : 'system',
        ];
    }
    public function isReductionAnimations(): bool { return $this->reductionAnimations; }
    public function setReductionAnimations(bool $reductionAnimations): static { $this->reductionAnimations = $reductionAnimations; return $this; }
    /** @return array{email?: bool, messages?: bool, communaute?: bool} */
    public function isVideoBackgroundActive(): bool
    {
        return $this->videoBackgroundActive;
    }

    public function setVideoBackgroundActive(bool $videoBackgroundActive): static
    {
        $this->videoBackgroundActive = $videoBackgroundActive;
        return $this;
    }

    public function isVideoBackgroundSoundActive(): bool
    {
        return $this->videoBackgroundSoundActive;
    }

    public function setVideoBackgroundSoundActive(bool $videoBackgroundSoundActive): static
    {
        $this->videoBackgroundSoundActive = $videoBackgroundSoundActive;
        return $this;
    }
    
    public function getNotifications(): array { return ['email' => (bool) ($this->notifications['email'] ?? true), 'messages' => (bool) ($this->notifications['messages'] ?? true), 'communaute' => (bool) ($this->notifications['communaute'] ?? true)]; }
    /** @param array<string, mixed> $notifications */
    public function setNotifications(array $notifications): static { $this->notifications = ['email' => (bool) ($notifications['email'] ?? true), 'messages' => (bool) ($notifications['messages'] ?? true), 'communaute' => (bool) ($notifications['communaute'] ?? true)]; return $this; }
    public function isProfilPrive(): bool { return $this->profilPrive; }
    public function setProfilPrive(bool $profilPrive): static { $this->profilPrive = $profilPrive; return $this; }
    public function isContrasteRenforce(): bool { return $this->contrasteRenforce; }
    public function setContrasteRenforce(bool $contrasteRenforce): static { $this->contrasteRenforce = $contrasteRenforce; return $this; }
    public function getTailleTexte(): string { return in_array($this->tailleTexte, ['small', 'normal', 'large', 'xl'], true) ? $this->tailleTexte : 'normal'; }
    public function setTailleTexte(string $tailleTexte): static { $this->tailleTexte = in_array($tailleTexte, ['small', 'normal', 'large', 'xl'], true) ? $tailleTexte : 'normal'; return $this; }
    /** @return array<string, array<string, mixed>> */
    public function getSessionsConnectees(): array { return is_array($this->sessionsConnectees) ? $this->sessionsConnectees : []; }
    /** @param array<string, array<string, mixed>> $sessions */
    public function setSessionsConnectees(array $sessions): static { $this->sessionsConnectees = $sessions; return $this; }
    public function getJetonReinitialisation(): ?string { return $this->jetonReinitialisation; }
    public function getExpirationJetonReinitialisation(): ?\DateTimeImmutable { return $this->expirationJetonReinitialisation; }
    public function definirJetonReinitialisation(?string $hash, ?\DateTimeImmutable $expiration): static { $this->jetonReinitialisation = $hash; $this->expirationJetonReinitialisation = $expiration; return $this; }
    public function isEnLigne(): bool { return $this->derniereActivite !== null && $this->derniereActivite > new \DateTimeImmutable('-5 minutes'); }
    public function getTitreEquipe(): ?ArticleBoutique { return $this->titreEquipe; }
    public function setTitreEquipe(?ArticleBoutique $article): static { $this->titreEquipe = $article; return $this; }

    public function getNiveau(): int
    {
        $niveau = 1;
        $experience = $this->experience;
        while ($experience >= self::experienceRequisePourNiveau($niveau)) {
            $experience -= self::experienceRequisePourNiveau($niveau++);
        }

        return $niveau;
    }

    public function getExperienceNiveau(): int
    {
        $experience = $this->experience;
        for ($niveau = 1; $niveau < $this->getNiveau(); ++$niveau) {
            $experience -= self::experienceRequisePourNiveau($niveau);
        }

        return $experience;
    }

    public function getExperienceNiveauSuivant(): int { return self::experienceRequisePourNiveau($this->getNiveau()); }
    public function getProgressionNiveau(): int { return (int) floor(($this->getExperienceNiveau() / $this->getExperienceNiveauSuivant()) * 100); }

    private static function experienceRequisePourNiveau(int $niveau): int { return 35 + (($niveau - 1) * 25); }

    /** @return Collection<int, self> */
    public function getAbonnements(): Collection { return $this->abonnements; }
    /** @return Collection<int, self> */
    public function getAbonnes(): Collection { return $this->abonnes; }
    public function suivre(self $utilisateur): static { if ($utilisateur !== $this && !$this->abonnements->contains($utilisateur)) { $this->abonnements->add($utilisateur); } return $this; }
    public function nePlusSuivre(self $utilisateur): static { $this->abonnements->removeElement($utilisateur); return $this; }
    public function suit(self $utilisateur): bool { return $this->abonnements->contains($utilisateur); }

    /** @return Collection<int, Jeu> */
    public function getJeuxFavoris(): Collection
    {
        return $this->jeuxFavoris;
    }

    public function ajouterJeuFavori(Jeu $jeu): static
    {
        if (!$this->jeuxFavoris->contains($jeu)) {
            $this->jeuxFavoris->add($jeu);
        }

        return $this;
    }

    public function retirerJeuFavori(Jeu $jeu): static
    {
        $this->jeuxFavoris->removeElement($jeu);

        return $this;
    }

    public function aPourFavori(Jeu $jeu): bool
    {
        return $this->jeuxFavoris->contains($jeu);
    }
}
