<?php

namespace App\Enums;

enum RowLockMode: string
{
    case Shared = 'shared';
    case ForUpdate = 'for_update';
}
