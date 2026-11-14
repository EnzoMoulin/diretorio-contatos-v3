@php
    $foco = $foco ?? null;
    $buscaQ = $buscaQ ?? request('q', '');
    $alturaInicial = $alturaInicial ?? 'recolhida';
    $cfg = [
        'geo_url' => route('geo'),
        'comarcas' => $mapa,
        'selecionada' => $foco,
        'foco_ibge' => $focoIbge ?? null,
        'municipio_nome' => $nome ?? null,
        'modo' => request('mapa'),
    ];
@endphp
<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <title>{{ $tituloPagina ?? 'Diretório · comarcas e contatos' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="palco" x-data="shell('{{ $alturaInicial }}')">
        <div id="mapa" class="mapa" role="application" aria-label="Mapa das comarcas do Espírito Santo"></div>

        <div class="flutuante marca vidro">
            <span class="marca-simbolo" aria-hidden="true">ES</span>
            <span class="flex flex-col leading-none">
                <span class="text-[0.8125rem] font-medium tracking-tight">Diretório</span>
                <span class="rotulo mt-[3px]">comarcas e contatos</span>
            </span>
        </div>

        <div class="flutuante chip vidro">
            <span class="ponto" aria-hidden="true"></span>
            <span><span class="num">{{ $totais['comarcas'] }}</span> comarcas e a estrutura central</span>
            <span class="text-muted/70" aria-hidden="true">·</span>
            <span><span class="num">{{ number_format($totais['contatos'], 0, ',', '.') }}</span> contatos</span>
        </div>

        <div id="aviso-mapa" class="flutuante aviso-mapa vidro" hidden role="status">
            O mapa não carregou. A lista de comarcas continua funcionando.
        </div>

        <div class="legenda vidro">
            <span class="rotulo leading-[1.35]">contatos<br>por comarca</span>
            <div class="legenda-itens" aria-hidden="true">
                <div class="legenda-item"><i style="width: 0.5rem; height: 0.5rem"></i><span>5</span></div>
                <div class="legenda-item"><i style="width: 0.8125rem; height: 0.8125rem"></i><span>60</span></div>
                <div class="legenda-item"><i style="width: 1.125rem; height: 1.125rem"></i><span>166</span></div>
            </div>
        </div>

        <aside class="painel vidro" :data-altura="altura" data-altura="{{ $alturaInicial }}" aria-label="Painel do diretório">
            <button type="button" class="puxador" @click="alternarAltura()" aria-label="Expandir ou recolher o painel">
                <span></span>
            </button>

            <div class="painel-topo">
                <x-busca :q="$buscaQ" />

                <div class="mt-2.5 flex items-center justify-between gap-2">
                    <nav class="trilha" aria-label="Trilha de navegação">
                        @forelse ($trilha as $i => $passo)
                            @if ($i > 0)
                                <x-icone nome="chevron" classe="h-2.5 w-2.5 shrink-0 text-muted/40" />
                            @endif
                            @if (! empty($passo['url']))
                                <a href="{{ $passo['url'] }}" class="truncate">{{ $passo['rotulo'] }}</a>
                            @else
                                <span class="atual truncate">{{ $passo['rotulo'] }}</span>
                            @endif
                        @empty
                            <span class="atual truncate">Comarcas</span>
                        @endforelse
                    </nav>
                </div>
            </div>

            <div class="painel-corpo rolagem">
                @include('painel.'.$estado)
            </div>
        </aside>

        <script type="application/json" id="mapa-dados">@json($cfg)</script>
    </div>
</body>
</html>
