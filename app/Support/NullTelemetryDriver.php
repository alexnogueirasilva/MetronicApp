<?php declare(strict_types = 1);

namespace App\Support;

use Infinitypaul\Idempotency\Telemetry\TelemetryDriver;

/**
 * Implementação nula do driver de telemetria para o pacote de idempotência.
 * Esta classe faz nada, apenas atende à interface.
 */
class NullTelemetryDriver implements TelemetryDriver
{
    /**
     * Start a new telemetry segment (like a span or trace).
     *
     * @param string $name
     * @param string|null $description
     * @return mixed
     */
    public function startSegment($name, $description = null)
    {
        return null;
    }

    /**
     * Add context or metadata to a telemetry segment.
     *
     * @param mixed $segment
     * @param string $key
     * @param mixed $value
     */
    public function addSegmentContext($segment, $key, $value): void
    {
        // No-op
    }

    /**
     * End a segment.
     *
     * @param mixed $segment
     */
    public function endSegment($segment): void
    {
        // No-op
    }

    /**
     * Record a numeric metric (e.g., counter).
     *
     * @param string $name
     * @param int $value
     */
    public function recordMetric($name, $value = 1): void
    {
        // No-op
    }

    /**
     * Record a timing metric (e.g., duration).
     *
     * @param string $name
     * @param float $milliseconds
     */
    public function recordTiming($name, $milliseconds): void
    {
        // No-op
    }

    /**
     * Record a size-based metric (e.g., response size).
     *
     * @param string $name
     * @param int $bytes
     */
    public function recordSize($name, $bytes): void
    {
        // No-op
    }
}
