<?php

declare(strict_types=1);

namespace Modules\AI\Database\Seeders;

use Illuminate\Database\Seeder;

class AIDatabaseSeeder extends Seeder
{
<<<<<<< HEAD
   public function run(): void
=======
    public function run(): void
>>>>>>> laraxot/dev
    {
        $this->call([
            AiThreadSeeder::class,
            AiMessageSeeder::class,
            AiActionProposalSeeder::class,
            AiToolLogSeeder::class,
        ]);
    }
}
