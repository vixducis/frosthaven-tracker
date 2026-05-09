<?php

namespace App\Enums;

enum ResourceType: string
{
    case Lumber = 'lumber';
    case Metal = 'metal';
    case Hide = 'hide';
    case Arrowvine = 'arrowvine';
    case Axenut = 'axenut';
    case Corpsecap = 'corpsecap';
    case Flamefruit = 'flamefruit';
    case Rockroot = 'rockroot';
    case Snowthistle = 'snowthistle';

    public function label(): string
    {
        return match ($this) {
            self::Lumber => 'Lumber',
            self::Metal => 'Metal',
            self::Hide => 'Hide',
            self::Arrowvine => 'Arrowvine',
            self::Axenut => 'Axenut',
            self::Corpsecap => 'Corpsecap',
            self::Flamefruit => 'Flamefruit',
            self::Rockroot => 'Rockroot',
            self::Snowthistle => 'Snowthistle',
        };
    }
}
