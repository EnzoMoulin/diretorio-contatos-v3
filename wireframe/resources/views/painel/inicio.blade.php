@php
    $sede = collect($comarcas)->firstWhere('sede', true);
    $lista = collect($comarcas)->reject(fn ($c) => $c['sede'])->values();
@endphp

<div class="flex items-center justify-between px-1.5 pb-1">
    <span class="rotulo">comarcas</span>
    <span class="rotulo">contatos</span>
</div>

<div class="space-y-px">
    @if ($sede)
        <a href="{{ route('comarca', $sede['chave']) }}" class="linha" data-comarca="{{ $sede['chave'] }}">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md border border-accent/20 bg-accent/[0.08] text-accent-soft">
                <x-icone nome="predio" classe="h-3.5 w-3.5" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-[0.875rem]">{{ $sede['nome'] }}</span>
                <span class="block truncate text-[0.6875rem] text-muted">estrutura central · sem polígono no mapa</span>
            </span>
            <span class="linha-contagem">{{ $sede['contatos'] }}</span>
            <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/40" />
        </a>
    @endif

    @foreach ($lista as $c)
        <a href="{{ route('comarca', $c['chave']) }}" class="linha" data-comarca="{{ $c['chave'] }}">
            <span class="min-w-0 flex-1">
                <span class="block truncate text-[0.875rem]">{{ $c['nome'] }}</span>
                <span class="num block truncate text-[0.6875rem] text-muted">
                    {{ $c['foruns'] }} {{ $c['foruns'] === 1 ? 'fórum' : 'fóruns' }} ·
                    {{ $c['nos'] }} {{ $c['nos'] === 1 ? 'unidade' : 'unidades' }}
                </span>
            </span>
            <span class="linha-contagem">{{ $c['contatos'] }}</span>
            <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/40" />
        </a>
    @endforeach
</div>

<div class="mt-3 px-1.5">
    <details class="cartao group">
        <summary class="flex list-none cursor-pointer items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-[0.8125rem]">
                <x-icone nome="telefone" classe="h-3.5 w-3.5 text-accent-soft" />
                Telefones úteis
            </span>
            <span class="flex items-center gap-1.5">
                <span class="num text-[0.75rem] text-muted">{{ count($uteis) }}</span>
                <x-icone nome="chevron" classe="h-3 w-3 text-muted/50 transition-transform group-open:rotate-90" />
            </span>
        </summary>
        <ul class="mt-2.5 space-y-1.5 border-t border-accent/10 pt-2.5">
            @foreach ($uteis as $u)
                <li class="flex items-baseline justify-between gap-3">
                    <span class="truncate text-[0.75rem] text-muted">{{ $u['nome'] }}</span>
                    <span class="num shrink-0 text-[0.75rem]">{{ $u['valor'] }}</span>
                </li>
            @endforeach
        </ul>
    </details>
</div>
