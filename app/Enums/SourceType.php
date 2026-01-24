<?php

namespace App\Enums;

enum SourceType: string
{
    case GENERATED = 'generated';
    case MANUAL = 'manual';
}
