@props(['nome' => 'busca', 'classe' => 'h-4 w-4'])

<svg {{ $attributes->merge(['class' => $classe]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($nome)
        @case('busca')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.4-3.4" />
            @break

        @case('telefone')
            <path d="M6.2 3.5h3l1.3 3.6-2 1.4a11.5 11.5 0 0 0 5 5l1.4-2 3.6 1.3v3a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.2 5.7a2 2 0 0 1 2-2.2Z" />
            @break

        @case('ramal')
            <path d="M6 9h12M6 15h12" />
            <path d="M9 4 7 20M17 4l-2 16" />
            @break

        @case('email')
            <rect x="3" y="5" width="18" height="14" rx="2.5" />
            <path d="m4.5 7.5 7.1 5.2a1 1 0 0 0 1.2 0l7.1-5.2" />
            @break

        @case('whatsapp')
            <path d="M20 11.7A8 8 0 0 1 8.4 19L4 20l1-4.3A8 8 0 1 1 20 11.7Z" />
            <path d="M9.2 9.4c.4-.5.9-.4 1.2.1l.6 1a.8.8 0 0 1-.2 1l-.5.4a5.4 5.4 0 0 0 2.4 2.4l.4-.5a.8.8 0 0 1 1-.2l1 .6c.5.3.6.8.1 1.2a2.4 2.4 0 0 1-2.6.3 8 8 0 0 1-3.7-3.7 2.4 2.4 0 0 1 .3-2.6Z" />
            @break

        @case('seta')
            <path d="M5 12h13" />
            <path d="m13 6 6 6-6 6" />
            @break

        @case('chevron')
            <path d="m9 6 6 6-6 6" />
            @break

        @case('copiar')
            <rect x="9" y="9" width="11" height="11" rx="2.2" />
            <path d="M15 5.2A2.2 2.2 0 0 0 12.8 3H6.2A2.2 2.2 0 0 0 4 5.2v6.6A2.2 2.2 0 0 0 6.2 14" />
            @break

        @case('local')
            <path d="M12 21s6.5-6 6.5-11a6.5 6.5 0 0 0-13 0C5.5 15 12 21 12 21Z" />
            <circle cx="12" cy="10" r="2.4" />
            @break

        @case('predio')
            <path d="M4 21V6.5L12 3l8 3.5V21" />
            <path d="M9 21v-5h6v5M8 9h1.5M14.5 9H16M8 12.5h1.5M14.5 12.5H16" />
            @break

        @case('camadas')
            <path d="m12 3 9 5-9 5-9-5 9-5Z" />
            <path d="m3 13 9 5 9-5" />
            @break

        @case('alerta')
            <path d="M12 4.5 21 20H3L12 4.5Z" />
            <path d="M12 10v4.5M12 17.4v.1" />
            @break

        @case('voltar')
            <path d="M19 12H6" />
            <path d="m11 6-6 6 6 6" />
            @break
    @endswitch
</svg>
