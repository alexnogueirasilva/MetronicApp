<?php declare(strict_types = 1);

it('can perform basic assertions', function (): void {
    expect(true)->toBeTrue()
        ->and(1 + 1)->toBe(2)
        ->and(['apple', 'banana'])->toContain('apple')
        ->and(10)->toBeGreaterThan(5);
});
