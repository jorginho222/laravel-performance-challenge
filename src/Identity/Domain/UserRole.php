<?php

namespace Src\Identity\Domain;

enum UserRole: string
{
    /** Manages the catalog (products and categories). */
    case Admin = 'admin';

    /** Places orders. */
    case Customer = 'customer';
}
