<?php declare(strict_types = 1);

use App\Enums\QueueEnum;
use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => env('HORIZON_REDIS_CONNECTION', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:' . QueueEnum::AUTH_CRITICAL->value         => 30,
        'redis:' . QueueEnum::AUTH_DEFAULT->value          => 60,
        'redis:' . QueueEnum::NOTIFICATIONS_HIGH->value    => 45,
        'redis:' . QueueEnum::NOTIFICATIONS_DEFAULT->value => 90,
        'redis:' . QueueEnum::BACKGROUND->value            => 120,
        'redis:' . QueueEnum::MAINTENANCE->value           => 300,
        'redis:' . QueueEnum::DEFAULT->value               => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent'        => 60,     // 1 hour
        'pending'       => 60,     // 1 hour
        'completed'     => 60,     // 1 hour
        'recent_failed' => 10080,  // 1 week
        'failed'        => 10080,  // 1 week
        'monitored'     => 10080,  // 1 week
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    |
    | Silencing a job will instruct Horizon to not place the job in the list
    | of completed jobs within the Horizon dashboard. This setting may be
    | used to fully remove any noisy jobs from the completed jobs list.
    |
    */

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job'   => 48,     // 2 days (previously 24)
            'queue' => 48,     // 2 days (previously 24)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => true,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. For
    | configuring these limits on your workers, see the next section.
    |
    */

    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    'defaults' => [
        // Supervisor para jobs críticos de autenticação (máx 10 processos)
        'auth-critical-supervisor' => [
            'connection'          => env('HORIZON_QUEUE_CONNECTION', 'redis'),
            'queue'               => [QueueEnum::AUTH_CRITICAL->value],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'        => 10,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => 3,
            'timeout'             => 30,
            'nice'                => 0,
        ],

        // Supervisor para jobs padrão de autenticação (máx 5 processos)
        'auth-default-supervisor' => [
            'connection'          => env('HORIZON_QUEUE_CONNECTION', 'redis'),
            'queue'               => [QueueEnum::AUTH_DEFAULT->value],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'        => 5,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => 2,
            'timeout'             => 60,
            'nice'                => 0,
        ],

        // Supervisor para notificações de alta prioridade (máx 8 processos)
        'notifications-high-supervisor' => [
            'connection'          => env('HORIZON_QUEUE_CONNECTION', 'redis'),
            'queue'               => [QueueEnum::NOTIFICATIONS_HIGH->value],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'        => 8,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => 3,
            'timeout'             => 45,
            'nice'                => 0,
        ],

        // Supervisor para notificações padrão (máx 3 processos)
        'notifications-default-supervisor' => [
            'connection'          => env('HORIZON_QUEUE_CONNECTION', 'redis'),
            'queue'               => [QueueEnum::NOTIFICATIONS_DEFAULT->value],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'        => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => 2,
            'timeout'             => 90,
            'nice'                => 0,
        ],

        // Supervisor para processamento em background (máx 2 processos)
        'background-supervisor' => [
            'connection'          => env('HORIZON_QUEUE_CONNECTION', 'redis'),
            'queue'               => [QueueEnum::BACKGROUND->value],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'        => 2,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 256,  // Mais memória para processamento pesado
            'tries'               => 1,
            'timeout'             => 300,  // Tempo maior para tarefas longas
            'nice'                => 10,   // Menor prioridade
        ],

        // Supervisor para tarefas de manutenção (máx 1 processo)
        'maintenance-supervisor' => [
            'connection'          => env('HORIZON_QUEUE_CONNECTION', 'redis'),
            'queue'               => [QueueEnum::MAINTENANCE->value],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'        => 1,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => 1,
            'timeout'             => 600,  // 10 minutos
            'nice'                => 10,   // Menor prioridade
        ],

        // Supervisor para fila padrão (máx 3 processos)
        'default-supervisor' => [
            'connection'          => env('HORIZON_QUEUE_CONNECTION', 'redis'),
            'queue'               => [QueueEnum::DEFAULT->value],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'        => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => 1,
            'timeout'             => 60,
            'nice'                => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'auth-critical-supervisor' => [
                'maxProcesses'    => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'auth-default-supervisor' => [
                'maxProcesses'    => 5,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'notifications-high-supervisor' => [
                'maxProcesses'    => 8,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'notifications-default-supervisor' => [
                'maxProcesses'    => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'background-supervisor' => [
                'maxProcesses'    => 2,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'maintenance-supervisor' => [
                'maxProcesses'    => 1,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'default-supervisor' => [
                'maxProcesses'    => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'staging' => [
            'auth-critical-supervisor' => [
                'maxProcesses'    => 5,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'auth-default-supervisor' => [
                'maxProcesses'    => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'notifications-high-supervisor' => [
                'maxProcesses'    => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'notifications-default-supervisor' => [
                'maxProcesses'    => 2,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'background-supervisor' => [
                'maxProcesses'    => 1,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'maintenance-supervisor' => [
                'maxProcesses'    => 1,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'default-supervisor' => [
                'maxProcesses'    => 2,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'local' => [
            'auth-critical-supervisor' => [
                'maxProcesses' => 3,
            ],
            'auth-default-supervisor' => [
                'maxProcesses' => 2,
            ],
            'notifications-high-supervisor' => [
                'maxProcesses' => 2,
            ],
            'notifications-default-supervisor' => [
                'maxProcesses' => 1,
            ],
            'background-supervisor' => [
                'maxProcesses' => 1,
            ],
            'maintenance-supervisor' => [
                'maxProcesses' => 1,
            ],
            'default-supervisor' => [
                'maxProcesses' => 2,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the notification settings for Horizon.
    | This includes email and Slack notification channels.
    |
    */
    'notifications' => [
        'email'         => env('HORIZON_NOTIFICATION_EMAIL', 'admin@example.com'),
        'slack_webhook' => env('SLACK_WEBHOOK_URL'),
        'slack_channel' => env('SLACK_HORIZON_CHANNEL', '#horizon-notifications'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Access Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure which users have access to the Horizon dashboard.
    | Add emails separated by commas in the environment variable.
    |
    */
    'admin_emails' => env('HORIZON_ADMIN_EMAILS', 'admin@example.com'),
];
