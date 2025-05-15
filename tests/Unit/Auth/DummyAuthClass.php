<?php declare(strict_types = 1);

namespace Tests\Unit\Auth;

use App\Traits\Auth\AuthenticatedUser;

class DummyAuthClass
{
    use AuthenticatedUser;
}
