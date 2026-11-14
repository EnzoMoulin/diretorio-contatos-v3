@php
    $c = $selecionada;
    $unidades = count($c['locais']);
    $temForuns = ! empty($c['foruns']);
@endphp

<div class="px-1.5 pt-1">
    <p class="rotulo">{{ $c['sede'] ? 'estrutura central' : 'comarca' }}</p>
    <h1 class="mt-1.5 text-[1.375rem] font-medium leading-tight tracking-tight">{{ $c['nome'] }}</h1>
    <p class="num mt-1 text-[0.6875rem] text-muted">
        {{ $c['sede'] ? 'sem polígono · contatos da administração central' : 'IBGE '.$c['ibge'] }}
    </p>

    <div class="mt-3 grid grid-cols-3 gap-1.5">
        <div class="rounded-lg border border-accent/10 bg-base/35 px-2 py-1.5">
            <div class="num text-[0.9375rem] leading-none">{{ $temForuns ? count($c['foruns']) : '—' }}</div>
            <div class="rotulo mt-1">{{ $temForuns ? 'fóruns' : 'fóruns' }}</div>
        </div>
        <div class="rounded-lg border border-accent/10 bg-base/35 px-2 py-1.5">
            <div class="num text-[0.9375rem] leading-none">{{ $unidades }}</div>
            <div class="rotulo mt-1">unidades</div>
        </div>
        <div class="rounded-lg border border-accent/10 bg-base/35 px-2 py-1.5">
            <div class="num text-[0.9375rem] leading-none text-accent-soft">{{ $c['contatos'] }}</div>
            <div class="rotulo mt-1">contatos</div>
        </div>
    </div>

    @if ($c['end'])
        <p class="mt-3 flex items-start gap-1.5 text-[0.75rem] leading-snug text-muted">
            <x-icone nome="local" classe="mt-px h-3.5 w-3.5 shrink-0" />
            <span>{{ $c['end'] }}</span>
        </p>
    @endif
</div>

@if ($temForuns)
    <div class="secao px-1.5">
        <span class="rotulo">fóruns</span>
    </div>
    <div class="space-y-1.5 px-1.5">
        @foreach ($c['foruns'] as $f)
            <a href="{{ route('local', $f['chave']) }}" class="cartao">
                <div class="flex items-start justify-between gap-2">
                    <span class="min-w-0">
                        <span class="block truncate text-[0.875rem]">{{ $f['nome'] }}</span>
                        <span class="mt-0.5 block line-clamp-2 text-[0.6875rem] leading-snug text-muted">{{ $f['end'] ?: 'endereço não declarado' }}</span>
                    </span>
                    <span class="num shrink-0 text-[0.8125rem] text-accent-soft">{{ $f['contatos'] }}</span>
                </div>
                <div class="mt-2 flex items-center gap-3 text-[0.6875rem] text-muted">
                    <span class="num">{{ $f['nos'] }} unidades</span>
                    @if ($f['email'])
                        <span class="num truncate">{{ $f['email'] }}</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

@if ($unidades > 0 && ! $temForuns)
    <div class="secao px-1.5">
        <span class="rotulo">unidades e setores</span>
        <span class="num text-[0.6875rem] text-muted/70">{{ $unidades }}</span>
    </div>
    <div class="space-y-px px-0.5">
        @foreach ($c['locais'] as $n)
            <a href="{{ route('local', $n['chave']) }}" class="linha">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md border border-accent/12 bg-base/35 text-muted">
                    <x-icone nome="camadas" classe="h-3.5 w-3.5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[0.8125rem]">{{ $n['nome'] }}</span>
                    <span class="block truncate text-[0.625rem] uppercase tracking-[0.12em] text-muted/70">{{ $n['tipo'] }}</span>
                </span>
                <span class="linha-contagem">{{ $n['contatos'] }}</span>
                <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/40" />
            </a>
        @endforeach
    </div>
@endif

@if (! empty($c['contatos_diretos']))
    <div class="secao px-1.5">
        <span class="rotulo">contatos diretos</span>
    </div>
    <div class="space-y-px px-0.5">
        @foreach (array_slice($c['contatos_diretos'], 0, 8) as $contato)
            <x-contato-linha :contato="$contato" compacto />
        @endforeach
    </div>
@endif
