<?php

namespace App\Enums;

enum ShowType: string
{
    case Ppv = 'ppv';
    case Tv = 'tv';
    case Special = 'special';
    case HouseShow = 'house_show';
}
