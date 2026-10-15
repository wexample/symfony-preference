<?php

namespace Wexample\SymfonyPreference\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyPreference\Repository\PreferenceRepository;

/**
 * One preference kept in the database (PreferenceStorage::User or ::Shared).
 *
 * The owner is kept by identifier, not by relation: it works with any user
 * class, and with none at all when shared.
 */
#[ORM\Entity(repositoryClass: PreferenceRepository::class)]
#[ORM\Table(name: 'preference')]
#[ORM\UniqueConstraint(name: 'preference_owner_key', columns: ['owner', 'preference_key'])]
class Preference extends AbstractEntity
{
    /**
     * The identifier of the user it belongs to, empty when shared by everybody.
     */
    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $owner;

    #[ORM\Column(name: 'preference_key', type: Types::STRING, length: 255)]
    private string $key;

    #[ORM\Column(name: 'preference_value', type: Types::JSON)]
    private mixed $value = null;

    public function __construct(
        string $owner,
        string $key,
    ) {
        parent::__construct();

        $this->owner = $owner;
        $this->key = $key;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
