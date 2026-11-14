@php
    $l = $local;
    $tipoRotulo = $l['tipo'] === 'FORUM' ? 'fórum' : mb_strtolower($l['tipo']);
    $temLocais = count($l['locais']) > 0;
@endphp

<div class="px-1.5 pt-1">
    <div class="flex items-center gap-2">
        <span class="selo" data-tipo="sede">{{ $tipoRotulo }}</span>
        @if ($l['comarca'])
            <span class="truncate text-[0.6875rem] text-muted">{{ $l['comarca']['nome'] }}</span>
        @endif
    </div>

    <h1 class="mt-2 text-[1.25rem] font-medium leading-tight tracking-tight">{{ $l['nome'] }}</h1>

    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.6875rem] text-muted">
        <span class="num">{{ $l['contatos_total'] }} contatos</span>
        @if ($temLocais)
            <span class="num">{{ count($l['locais']) }} unidades e setores</span>
        @endif
    </div>

    @if ($l['end'])
        <div class="mt-3 flex items-start gap-2 rounded-lg border border-accent/10 bg-base/35 px-2 py-1.5">
            <x-icone nome="local" classe="mt-0.5 h-3.5 w-3.5 shrink-0 text-muted" />
            <p class="min-w-0 flex-1 text-[0.75rem] leading-snug">
                {{ $l['end'] }}
                @if ($l['cep'])
                    <span class="num text-muted">· {{ $l['cep'] }}</span>
                @endif
            </p>
            <button type="button" @click="copiar(@js($l['end']))"
                class="grid h-7 w-7 shrink-0 place-items-center rounded-md text-muted transition-colors hover:bg-accent/10 hover:text-accent-soft"
                aria-label="Copiar endereço">
                <x-icone nome="copiar" classe="h-3.5 w-3.5" x-show="copiado !== @js($l['end'])" x-cloak />
                <svg x-show="copiado === @js($l['end'])" x-cloak class="h-3.5 w-3.5 text-accent" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m5 12.5 4.5 4.5L19 7.5" />
                </svg>
            </button>
        </div>
    @endif
</div>

@if ($temLocais)
    <div class="secao px-1.5">
        <span class="rotulo">unidades e setores</span>
    </div>
    <div class="space-y-px px-0.5">
        @foreach ($l['locais'] as $n)
            <a href="{{ route('local', $n['chave']) }}" class="linha">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md border border-accent/12 bg-base/35 text-muted">
                    <x-icone nome="camadas" classe="h-3.5 w-3.5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block line-clamp-2 text-[0.8125rem] leading-snug">{{ $n['nome'] }}</span>
                    <span class="mt-0.5 block truncate text-[0.625rem] uppercase tracking-[0.12em] text-muted/70">{{ $n['tipo'] }}</span>
                </span>
                <span class="linha-contagem">{{ $n['contatos'] }}</span>
                <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/40" />
            </a>
        @endforeach
    </div>
@endif

@if (! empty($l['contatos']))
    <div class="secao px-1.5">
        <span class="rotulo">contatos</span>
        <span class="num text-[0.6875rem] text-muted/70">{{ count($l['contatos']) }}</span>
    </div>
    <div class="space-y-px px-0.5 pb-2">
        @foreach ($l['contatos'] as $contato)
            <x-contato-linha :contato="$contato" />
        @endforeach
    </div>
@endif

@if (! $temLocais && empty($l['contatos']))
    <div class="mt-4 px-1.5">
        <div class="cartao text-[0.75rem] leading-snug text-muted">
            Este local não tem unidade nem contato declarado na base.
        </div>
    </div>
@endif
