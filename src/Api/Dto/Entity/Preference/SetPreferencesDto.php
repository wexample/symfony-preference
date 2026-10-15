<?php

namespace Wexample\SymfonyPreference\Api\Dto\Entity\Preference;

use Symfony\Component\Validator\Constraints\Type;
use Wexample\SymfonyApi\Api\Attribute\RequiredDtoProperty;
use Wexample\SymfonyApi\Api\Dto\AbstractDto;
use Wexample\SymfonyHelpers\Helper\VariableHelper;

/**
 * Preferences written together — a theme setting its axes at once —, keyed as
 * they are kept, a null value forgetting its key. Who they belong to is never
 * sent: it is the visitor's, or everybody's, as the app chose.
 */
class SetPreferencesDto extends AbstractDto
{
    /** @var array<string, mixed> */
    #[Type(VariableHelper::VARIABLE_TYPE_ARRAY)]
    #[RequiredDtoProperty]
    public array $values = [];
}
