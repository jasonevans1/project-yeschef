<?php

namespace App\Enums;

enum SharePermission: string
{
    case Read = 'read';
    case Write = 'write';
}
