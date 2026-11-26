<?php

namespace App\Providers;

use App\Escopo\ResolvedorDeEscopo;
use App\Models\Comarca;
use App\Models\Contato;
use App\Models\Local;
use App\Models\No;
use App\Policies\DiretorioPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Uma resolução de escopo por requisição; o `scoped` é limpo entre
        // requests mesmo sob worker persistente (Octane).
        $this->app->scoped(ResolvedorDeEscopo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mass assignment silencioso é escalada: `papel` e `ativo` não são
        // fillable, e qualquer chave descartada por engano vira exceção.
        Model::preventSilentlyDiscardingAttributes();

        foreach ([Comarca::class, Local::class, No::class, Contato::class] as $modelo) {
            Gate::policy($modelo, DiretorioPolicy::class);
        }
    }
}
