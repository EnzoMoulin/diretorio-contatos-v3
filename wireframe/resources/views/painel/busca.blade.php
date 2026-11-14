@php
    $r = $resultado;
    $nComarcas = count($r['comarcas']);
    $nLocais = count($r['locais']);
    $nContatos = $r['total_contatos'];
    $vazio = $r['q'] !== '' && $nComarcas === 0 && $nLocais === 0 && $nContatos === 0;
@endphp

<div class="px-1.5 pt-1">
    <p class="rotulo">busca</p>
    <h1 class="mt-1.5 break-words text-[1.25rem] font-medium leading-tight tracking-tight">
        @if ($r['q'] === '')
            O que você procura?
        @else
            “{{ $r['q'] }}”
        @endif
    </h1>
    <p class="num mt-1 text-[0.6875rem] text-muted">
        @if ($r['q'] === '')
            comarca, unidade, setor, telefone, ramal ou e-mail
        @else
            {{ $nComarcas }} comarcas · {{ $r['total_locais'] }} locais · {{ $nContatos }} contatos
        @endif
    </p>
</div>

@if ($r['q'] === '')
    <div class="mt-4 px-1.5">
        <p class="rotulo">tente por</p>
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach (['cartório', 'vara cível', 'protocolo', 'secretaria', 'distribuição'] as $sugestao)
                <a href="{{ route('busca', ['q' => $sugestao]) }}"
                    class="rounded-full border border-accent/20 px-2.5 py-1 text-[0.75rem] text-ink transition-colors hover:border-accent/50 hover:text-accent-soft">
                    {{ $sugestao }}
                </a>
            @endforeach
        </div>
        <p class="mt-4 text-[0.75rem] leading-snug text-muted">
            A busca ignora acento e maiúscula, e olha nome de comarca, unidade, setor e o valor do contato.
        </p>
    </div>
@elseif ($vazio)
    <div class="mt-4 px-1.5">
        <div class="cartao">
            <div class="flex items-center gap-2 text-[0.8125rem]">
                <x-icone nome="alerta" classe="h-4 w-4 text-warn" />
                Nenhum resultado para “{{ $r['q'] }}”
            </div>
            <p class="mt-1.5 text-[0.75rem] leading-snug text-muted">
                Vale tentar sem acento, só parte do nome, ou pelo código da unidade.
            </p>
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach (['cartório', 'vara', 'secretaria'] as $sugestao)
                    <a href="{{ route('busca', ['q' => $sugestao]) }}"
                        class="rounded-full border border-accent/20 px-2.5 py-1 text-[0.75rem] transition-colors hover:border-accent/50 hover:text-accent-soft">
                        {{ $sugestao }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@else
    @if ($nComarcas > 0)
        <div class="secao px-1.5">
            <span class="rotulo">comarcas</span>
        </div>
        <div class="space-y-px px-0.5">
            @foreach ($r['comarcas'] as $c)
                <a href="{{ route('comarca', $c['chave']) }}" class="linha">
                    <span class="min-w-0 flex-1 truncate text-[0.875rem]">{{ $c['nome'] }}</span>
                    <span class="linha-contagem">{{ $c['contatos'] }}</span>
                    <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/40" />
                </a>
            @endforeach
        </div>
    @endif

    @if ($nLocais > 0)
        <div class="secao px-1.5">
            <span class="rotulo">unidades e setores</span>
        </div>
        <div class="space-y-px px-0.5">
            @foreach ($r['locais'] as $l)
                <a href="{{ route('local', $l['chave']) }}" class="linha">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[0.8125rem]">{{ $l['nome'] }}</span>
                        <span class="block truncate text-[0.625rem] uppercase tracking-[0.12em] text-muted/70">
                            {{ $l['tipo'] }}@if ($l['comarca']) · {{ $l['comarca'] }}@endif
                        </span>
                    </span>
                    <span class="linha-contagem">{{ $l['contatos'] }}</span>
                    <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/40" />
                </a>
            @endforeach
        </div>
    @endif

    @if ($nContatos > 0)
        <div class="secao px-1.5">
            <span class="rotulo">contatos</span>
            <span class="num text-[0.6875rem] text-muted/70">{{ $nContatos }}</span>
        </div>
        <div class="space-y-px px-0.5 pb-2">
            @foreach ($r['contatos'] as $contato)
                <a href="{{ route('contato', $contato['id']) }}"
                    class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-accent/[0.06]">
                    @php
                        $icone = match (mb_strtolower($contato['tipo'])) {
                            'ramal' => 'ramal',
                            'e-mail' => 'email',
                            'whatsapp' => 'whatsapp',
                            default => 'telefone',
                        };
                    @endphp
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-accent/15 bg-base/40 text-accent-soft">
                        <x-icone :nome="$icone" classe="h-3.5 w-3.5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="num block truncate text-[0.875rem]">{{ $contato['valor'] }}</span>
                        <span class="block truncate text-[0.6875rem] text-muted">
                            {{ $contato['tipo'] }}@if (! empty($contato['_ctx']))
                                · {{ $contato['_ctx'] }}
                            @endif
                        </span>
                    </span>
                    <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/40" />
                </a>
            @endforeach
        </div>
    @endif

    @if ($nContatos > count($r['contatos']))
        <p class="px-2 pb-2 text-[0.6875rem] text-muted">
            Mostrando {{ count($r['contatos']) }} de {{ $nContatos }} contatos — refine a busca para ver o resto.
        </p>
    @endif
@endif
