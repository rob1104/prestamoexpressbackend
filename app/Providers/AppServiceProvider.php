<?php

namespace App\Providers;

use App\Listeners\LogSessionActivity;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function($user, $ability) {
           return $user->hasRole('Administrador') ? true : null;
        });

        Event::listen(Login::class, [LogSessionActivity::class, 'handle']);
        Event::listen(Logout::class, [LogSessionActivity::class, 'handle']);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Schema::defaultStringLength(191);

        try {
            $connection = DB::connection();

            $customGrammar = new class($connection) extends MySqlGrammar {
                // Añadimos el constructor para recibir la conexión y pasarla al padre
                public function __construct($connection)
                {
                    // En Laravel 11, algunas gramáticas requieren la conexión en el constructor
                    // Si tu versión no la pide, esto no romperá nada.
                    parent::__construct($connection);
                }

                public function compileColumns($schema, $table)
                {
                    return sprintf(
                        'select column_name as `name`, data_type as `type_name`, column_type as `type`, ' .
                        'collation_name as `collation`, is_nullable as `nullable`, ' .
                        'column_default as `default`, column_comment as `comment`, ' .
                        'NULL as `expression`, ' . // <--- Agregamos NULL con el alias 'expression'
                        'extra as `extra` from information_schema.columns ' .
                        'where table_schema = schema() and table_name = %s ' .
                        'order by ordinal_position asc',
                        $this->quoteString($table)
                    );
                }
            };

            $connection->setSchemaGrammar($customGrammar);
        } catch (\Exception $e) {
            // Silenciamos errores si se ejecuta desde consola sin DB
        }
    }
}
