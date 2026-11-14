@props(['q' => ''])

<form action="{{ route('busca') }}" method="get" role="search" class="busca">
    <x-icone nome="busca" classe="h-4 w-4 shrink-0 text-muted" />
    <input type="search" name="q" value="{{ $q }}" data-busca autocomplete="off" spellcheck="false"
        placeholder="Buscar comarca, unidade ou município"
        aria-label="Buscar por comarca, unidade, setor, contato ou ramal">
    @if ($q === '')
        <kbd class="tecla shrink-0 max-md:hidden" aria-hidden="true">Ctrl K</kbd>
    @else
        <a href="{{ route('inicio') }}" class="tecla shrink-0 hover:text-accent-soft" aria-label="Limpar busca">limpar</a>
    @endif
</form>
