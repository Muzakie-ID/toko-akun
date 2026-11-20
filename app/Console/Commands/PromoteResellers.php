<?php

namespace App\Console\Commands;

use App\Services\ResellerPromotion;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('resellers:promote')]
#[Description('Naikkan customer yang total belanja lunasnya sudah mencapai batas menjadi reseller.')]
class PromoteResellers extends Command
{
    public function handle(ResellerPromotion $promotion): int
    {
        $count = $promotion->promoteAllEligible();

        $this->info("{$count} customer dinaikkan menjadi reseller.");

        return self::SUCCESS;
    }
}
