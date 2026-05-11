<?php

namespace Tests\Unit;

use App\Models\Character;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CharacterLevelTest extends TestCase
{
    #[DataProvider('levelDataProvider')]
    public function test_level_is_derived_from_experience(int $experience, int $expectedLevel): void
    {
        $character = new Character(['experience' => $experience]);

        $this->assertSame($expectedLevel, $character->level);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function levelDataProvider(): array
    {
        return [
            'level 1 at 0 xp' => [0, 1],
            'level 1 at 44 xp' => [44, 1],
            'level 2 at 45 xp' => [45, 2],
            'level 2 at 94 xp' => [94, 2],
            'level 3 at 95 xp' => [95, 3],
            'level 4 at 150 xp' => [150, 4],
            'level 5 at 210 xp' => [210, 5],
            'level 6 at 275 xp' => [275, 6],
            'level 7 at 345 xp' => [345, 7],
            'level 8 at 420 xp' => [420, 8],
            'level 9 at 500 xp' => [500, 9],
            'level 9 at 999 xp' => [999, 9],
        ];
    }
}
