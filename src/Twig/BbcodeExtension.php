<?php

namespace App\Twig;

use App\Service\BbcodeConverter;
use App\Service\DecoupeurSectionsJeu;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class BbcodeExtension extends AbstractExtension
{
    public function __construct(
        private readonly BbcodeConverter $bbcodeConverter,
        private readonly DecoupeurSectionsJeu $decoupeurSectionsJeu,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('bbcode', $this->bbcodeConverter->toHtml(...), ['is_safe' => ['html']]),
            new TwigFilter('bbcode_commentaire', $this->bbcodeConverter->toCommentHtml(...), ['is_safe' => ['html']]),
            new TwigFilter('bbcode_texte', $this->bbcodeConverter->toText(...)),
            new TwigFilter('sections_jeu', $this->decoupeurSectionsJeu->decouper(...)),
        ];
    }
}
