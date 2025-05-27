<?php declare(strict_types = 1);

namespace App\Actions\Finance;

use App\DTO\Finance\AccountDTO;
use App\Models\Finance\Account;

class AccountAction
{
    public function execute(AccountDTO $dto): Account
    {
        return Account::query()
            ->create($dto->toArray());
    }
}
