<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionPokemonsList
{
    public string $type;

    /**
     * @var array<int, array{pokemon: array<string, mixed>, forms: null|array<string, mixed>, types: array<string, mixed>}>
     */
    public array $items;

    /**
     * @param array{type: string, items: array<int, array{pokemon: array<string, mixed>, forms: null|array<string, mixed>, types: array<string, mixed>}>} $values
     */
    public function __construct(array $values)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        /** @var array{type: string, items: array<int, array{pokemon: array<string, mixed>, forms: null|array<string, mixed>, types: array<string, mixed>}>} $options */
        $options = $resolver->resolve($values);

        $this->type = $options['type'];
        $this->items = $options['items'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('type');
        $resolver->setAllowedTypes('type', 'string');

        $resolver->setRequired('items');
        $resolver->setAllowedTypes('items', 'array[]');
    }
}
