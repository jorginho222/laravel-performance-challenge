<?php

namespace Src\Catalog\Domain;

enum ProductStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
