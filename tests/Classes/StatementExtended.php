<?php

namespace Tests\Classes;

use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableAttribute('statement_extended')]
class StatementExtended extends StatementEntity
{
    #[FieldAttribute(fieldName: 'extra_property')]
    protected ?string $extraProperty = null;

    public function getExtraProperty(): ?string
    {
        return $this->extraProperty;
    }

    public function setExtraProperty(?string $extraProperty): void
    {
        $this->extraProperty = $extraProperty;
    }
}