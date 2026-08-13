<?php

namespace App\Twig;

use App\Service\BbcodeConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class BbcodeExtension extends AbstractExtension
{
    public function __construct(
        private readonly BbcodeConverter $bbcodeConverter,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('bbcode', $this->bbcodeConverter->toHtml(...), ['is_safe' => ['html']]),
            new TwigFilter('bbcode_texte', $this->bbcodeConverter->toText(...)),
        ];
    }
}
