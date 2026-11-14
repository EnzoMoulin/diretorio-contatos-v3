<div class="px-1.5 pt-1">
    <p class="rotulo">município</p>
    <h1 class="mt-1.5 text-[1.25rem] font-medium leading-tight tracking-tight">
        {{ $nome ?? 'Município '.$ibge }}
        @if ($nome)
            <span class="text-muted">· ES</span>
        @endif
    </h1>
    <p class="num mt-1 text-[0.6875rem] text-muted">IBGE {{ $ibge }}</p>

    <h2 class="mt-4 text-[0.9375rem] leading-snug">Sem comarca declarada nesta base</h2>

    <div class="mt-2 flex items-start gap-2 rounded-lg border border-warn/25 bg-warn/[0.06] px-2.5 py-2 text-[0.75rem] leading-snug">
        <x-icone nome="alerta" classe="mt-0.5 h-4 w-4 shrink-0 text-warn" />
        <p>
            O município tem polígono no mapa, mas a base não registra qual comarca o atende.
            O diretório não completa esse dado por conta própria — é melhor não responder do que responder errado.
        </p>
    </div>

    <p class="mt-3 px-0.5 text-[0.75rem] leading-snug text-muted">
        O Espírito Santo tem 78 municípios; a base reúne 69 comarcas. Nove municípios não têm
        comarca própria no registro — em alguns casos o atendimento é feito por comarca vizinha,
        mas esse vínculo não está declarado.
    </p>

    <div class="nota-wireframe mt-4">
        <span class="rotulo">nota do wireframe</span>
        <p>
            A tela existe para provar a regra “não inventar dado”: quando a comarca não está no seed,
            o estado vazio mostra o que falta, com o código do IBGE à vista. Nenhum nome de comarca
            foi deduzido aqui.
        </p>
    </div>

    <a href="{{ route('inicio') }}"
        class="mt-4 inline-flex h-9 items-center gap-2 rounded-lg border border-accent/20 px-3 text-[0.8125rem] transition-colors hover:border-accent/50 hover:text-accent-soft">
        <x-icone nome="voltar" classe="h-3.5 w-3.5" />
        Ver todas as comarcas
    </a>
</div>
