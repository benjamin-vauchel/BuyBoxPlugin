<?php

declare(strict_types=1);

namespace Sylius\BuyboxPlugin\Processor;

interface LocaleProcessorInterface
{
    public function process(string $locale): string;
}
