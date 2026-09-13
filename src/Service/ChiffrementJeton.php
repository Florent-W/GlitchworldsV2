<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ChiffrementJeton
{
    private readonly string $cle;

    public function __construct(#[Autowire(env: 'APP_SECRET')] string $secret)
    {
        $this->cle = hash('sha256', $secret, true);
    }

    public function chiffrer(string $valeur): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce.sodium_crypto_secretbox($valeur, $nonce, $this->cle));
    }

    public function dechiffrer(string $valeur): string
    {
        $contenu = base64_decode($valeur, true);
        if ($contenu === false || strlen($contenu) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('Le jeton Search Console enregistré est invalide.');
        }

        $clair = sodium_crypto_secretbox_open(
            substr($contenu, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($contenu, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->cle,
        );
        if ($clair === false) {
            throw new \RuntimeException('Le jeton Search Console ne peut pas être déchiffré.');
        }

        return $clair;
    }
}
