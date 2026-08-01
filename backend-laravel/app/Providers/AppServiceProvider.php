<?php
namespace App\Providers;
use App\Models\Incidencia;
use App\Policies\IncidenciaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void
    {
        Gate::policy(Incidencia::class, IncidenciaPolicy::class);
    }
}
