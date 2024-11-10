<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class NameGenerator
{
    public static function Funny(): string
    {
        $adjectives = ['Crazy', 'Wacky', 'Silly', 'Funky', 'Quirky', 'Goofy', 'Zany', 'Loopy'];
        $nouns = ['Banana', 'Penguin', 'Noodle', 'Pickle', 'Unicorn', 'Marshmallow', 'Wombat', 'Sardine'];

        // Randomly pick an adjective and noun
        $adjective = $adjectives[array_rand($adjectives)];
        $noun = $nouns[array_rand($nouns)];

        $data = $adjective . $noun . rand(1, 9999);

        return Str::slug($data);
    }
}
