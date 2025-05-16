<?php declare(strict_types = 1);

namespace App\Support;

use Illuminate\Support\Facades\{File, Route};
use JsonException;
use RuntimeException;

class RouteScanner
{
    /**
     * Carrega automaticamente todos os arquivos de rota de um diretório específico.
     *
     * @param  string  $directory  Caminho para o diretório de rotas
     * @param  string  $prefix  Prefixo comum para todas as rotas (opcional)
     * @param  array<string>  $middleware  Middleware comum para todas as rotas (opcional)
     */
    public static function loadRoutesFrom(string $directory, string $prefix = '', array $middleware = []): void
    {
        if (!File::isDirectory($directory)) {
            throw new RuntimeException("Diretório de rotas não encontrado: {$directory}");
        }

        $routeFiles = File::files($directory);

        foreach ($routeFiles as $file) {
            $routeGroup = static function () use ($file): void {
                require $file->getPathname();
            };

            if (($prefix !== '' && $prefix !== '0') || $middleware !== []) {
                Route::group([
                    'prefix'     => $prefix,
                    'middleware' => $middleware,
                ], $routeGroup);
            } else {
                $routeGroup();
            }
        }
    }

    /**
     * Carrega recursivamente todos os arquivos de rota de um diretório e seus subdiretórios.
     *
     * @param  string  $directory  Caminho para o diretório de rotas
     * @param  string  $prefix  Prefixo comum para todas as rotas (opcional)
     * @param  array<string>  $middleware  Middleware comum para todas as rotas (opcional)
     *
     * @throws JsonException
     */
    public static function loadRoutesFromRecursive(string $directory, string $prefix = '', array $middleware = []): void
    {
        if (!File::isDirectory($directory)) {
            throw new RuntimeException("Diretório de rotas não encontrado: {$directory}");
        }

        $routeFiles = File::files($directory);

        foreach ($routeFiles as $file) {
            if ($file->getExtension() === 'php') {
                $routeGroup = static function () use ($file): void {
                    require $file->getPathname();
                };

                if (($prefix !== '' && $prefix !== '0') || $middleware !== []) {
                    Route::group([
                        'prefix'     => $prefix,
                        'middleware' => $middleware,
                    ], $routeGroup);
                } else {
                    $routeGroup();
                }
            }
        }

        $directories = File::directories($directory);

        foreach ($directories as $subDirectory) {
            $subDirName = basename(toString($subDirectory));
            $newPrefix  = $prefix !== '' && $prefix !== '0' ? "{$prefix}/{$subDirName}" : $subDirName;

            self::loadRoutesFromRecursive(toString($subDirectory), $newPrefix, $middleware);
        }
    }
}
