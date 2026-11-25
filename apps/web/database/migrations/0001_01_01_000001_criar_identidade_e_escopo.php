<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Identidade e escopo são do app, no mesmo banco do diretório (ADR-010).
     * O papel decide *se entra no painel*; `acessos` decide *quais linhas vê*.
     * As chaves de escopo são as chaves lógicas do baseline — nunca `id`.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('email', 180)->unique();
            $table->timestampTz('email_verificado_em')->nullable();
            $table->string('senha');                       // hash do Laravel; vazio no login só OAuth
            $table->enum('papel', ['ADMIN', 'GESTOR_COMARCA', 'EDITOR'])->default('EDITOR');
            $table->boolean('ativo')->default(true);
            $table->rememberToken();                       // nome exigido pelo framework
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
        });

        // Tabela padrão do broker de senha do Laravel; o Filament usa a mesma.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('acessos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->enum('nivel', ['comarca', 'local', 'no']);
            $table->string('alvo_chave', 40);
            $table->timestampTz('criado_em')->useCurrent();
            $table->unique(['usuario_id', 'nivel', 'alvo_chave']);
            $table->index(['nivel', 'alvo_chave']);
        });

        DB::statement(<<<'SQL'
ALTER TABLE acessos ADD CONSTRAINT acessos_alvo_formato CHECK (
  (nivel = 'comarca' AND alvo_chave ~ '^MUN[0-9]{4}$|^MUN-[0-9A-HJKMNP-TV-Z]{26}$')
  OR (nivel = 'local' AND alvo_chave ~ '^FOR[0-9]{4}$|^MUN[0-9]{4}$|^LOC-[0-9A-HJKMNP-TV-Z]{26}$')
  OR (nivel = 'no' AND alvo_chave ~ '^UNI[0-9]{4}$|^NO-[0-9A-HJKMNP-TV-Z]{26}$')
)
SQL);

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('email', 180);
            $table->string('motivo', 500)->default('');
            $table->enum('nivel', ['comarca', 'local', 'no'])->default('comarca');
            $table->string('alvo_chave', 40)->nullable(); // nulo = pedido de acesso amplo, sem escopo
            $table->enum('status', ['pendente', 'aprovado', 'recusado'])->default('pendente');
            $table->foreignId('decidido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampTz('decidido_em')->nullable();
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
            $table->index(['status', 'criado_em']);
        });

        DB::statement(<<<'SQL'
ALTER TABLE pedidos ADD CONSTRAINT pedidos_alvo_formato CHECK (
  alvo_chave IS NULL
  OR (nivel = 'comarca' AND alvo_chave ~ '^MUN[0-9]{4}$|^MUN-[0-9A-HJKMNP-TV-Z]{26}$')
  OR (nivel = 'local' AND alvo_chave ~ '^FOR[0-9]{4}$|^MUN[0-9]{4}$|^LOC-[0-9A-HJKMNP-TV-Z]{26}$')
  OR (nivel = 'no' AND alvo_chave ~ '^UNI[0-9]{4}$|^NO-[0-9A-HJKMNP-TV-Z]{26}$')
)
SQL);

        // Mesmo trigger do baseline para `atualizado_em` (a função nasce lá).
        foreach (['usuarios', 'pedidos'] as $tabela) {
            DB::statement("CREATE TRIGGER tg_{$tabela}_atualiza BEFORE UPDATE ON {$tabela}
                           FOR EACH ROW EXECUTE FUNCTION marca_atualizacao()");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('acessos');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
