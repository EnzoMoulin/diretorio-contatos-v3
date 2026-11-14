@props(['vigencia'])

<span class="selo" data-tipo="{{ $vigencia['chave'] }}" title="Procedência do dado: {{ $vigencia['rotulo'] }} · {{ $vigencia['detalhe'] }}">
    <span class="inline-block h-1 w-1 shrink-0 rounded-full bg-current" aria-hidden="true"></span>
    {{ $vigencia['rotulo'] }}
    <span class="opacity-70">· {{ $vigencia['detalhe'] }}</span>
</span>
