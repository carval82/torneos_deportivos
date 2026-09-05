@php
    $asistenteOn = (bool) config('asistente.enabled');
    $mode = (string) config('asistente.mode', 'local');
    $frameUrl = rtrim((string) config('asistente.frame_url'), '/');
    $useIframe = $mode === 'iframe' && filled($frameUrl);
    $help = $asistenteOn && ! $useIframe ? app(\App\Services\ArenaHelpDesk::class) : null;
@endphp

@if ($asistenteOn)
<style>
    #arena-asistente-btn {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 2147483000;
        height: 52px;
        padding: 0 18px;
        border: 0;
        border-radius: 999px;
        background: #A8E63D;
        color: #0B1F3A;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .28);
        cursor: pointer;
        font: 800 14px/1 system-ui, "Segoe UI", sans-serif;
    }
    #arena-asistente-btn:hover { filter: brightness(1.05); }
    #arena-asistente-box {
        position: fixed;
        right: 18px;
        bottom: 84px;
        z-index: 2147483000;
        width: 380px;
        max-width: calc(100vw - 24px);
        height: 540px;
        max-height: calc(100vh - 110px);
        display: none;
        flex-direction: column;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(0, 0, 0, .28);
        background: #f6f8f7;
        color: #123;
        font: 14px/1.4 system-ui, "Segoe UI", sans-serif;
    }
    #arena-asistente-box.is-open { display: flex; }
    #arena-asistente-box header {
        background: #0B1F3A;
        color: #fff;
        padding: 12px 14px;
        flex: 0 0 auto;
    }
    #arena-asistente-box header small { opacity: .85; display: block; }
    #arena-asistente-log {
        flex: 1;
        overflow: auto;
        padding: 12px;
    }
    #arena-asistente-box .msg {
        max-width: 92%;
        margin: 0 0 10px;
        padding: 8px 10px;
        border-radius: 12px;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    #arena-asistente-box .in { background: #fff; border: 1px solid #e3ecea; }
    #arena-asistente-box .out { background: #0B1F3A; color: #fff; margin-left: auto; }
    #arena-asistente-box .chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 8px; }
    #arena-asistente-box .chip {
        border: 1px solid #cfe0dc;
        background: #fff;
        color: #0B1F3A;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    #arena-asistente-box form {
        display: flex;
        gap: 8px;
        padding: 10px;
        background: #fff;
        border-top: 1px solid #e6eeec;
        flex: 0 0 auto;
    }
    #arena-asistente-box input {
        flex: 1;
        border: 1px solid #cfe0dc;
        border-radius: 20px;
        padding: 10px 12px;
        min-width: 0;
    }
    #arena-asistente-box form button {
        background: #0B1F3A;
        color: #fff;
        border: 0;
        border-radius: 20px;
        padding: 10px 14px;
        cursor: pointer;
        font-weight: 700;
    }
    #arena-asistente-box iframe { border: 0; width: 100%; height: 100%; }
    @media print {
        #arena-asistente-btn,
        #arena-asistente-box { display: none !important; }
    }
</style>
<button type="button" id="arena-asistente-btn" aria-label="Ayuda Arena Players" aria-expanded="false">¿Cómo se hace?</button>
<div id="arena-asistente-box" @unless ($useIframe) data-url="{{ route('help.ask') }}" data-token="{{ csrf_token() }}" @endunless>
    @if ($useIframe)
        <iframe src="{{ $frameUrl }}" title="Ayuda Arena Players"></iframe>
    @else
        <header>
            Ayuda Arena Players
            <small>Preguntá como hables. Te digo quién y cómo.</small>
        </header>
        <div id="arena-asistente-log">
            <div class="msg in">{{ $help->welcome() }}</div>
            <div class="chips" id="arena-asistente-chips">
                @foreach ($help->chips() as $chip)
                    <button type="button" class="chip" data-q="{{ $chip }}">{{ $chip }}</button>
                @endforeach
            </div>
        </div>
        <form id="arena-asistente-form">
            <input id="arena-asistente-input" type="text" maxlength="400" placeholder="Ej. el fixture lo armo yo" autocomplete="off">
            <button type="submit">Enviar</button>
        </form>
    @endif
</div>
<script>
    (function () {
        var btn = document.getElementById('arena-asistente-btn');
        var box = document.getElementById('arena-asistente-box');
        if (!btn || !box) return;
        btn.addEventListener('click', function () {
            var open = !box.classList.contains('is-open');
            box.classList.toggle('is-open', open);
            box.style.display = open ? (box.querySelector('iframe') ? 'block' : 'flex') : 'none';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                var input = document.getElementById('arena-asistente-input');
                if (input) input.focus();
            }
        });

        var form = document.getElementById('arena-asistente-form');
        if (!form) return;
        var log = document.getElementById('arena-asistente-log');
        var input = document.getElementById('arena-asistente-input');
        var url = box.getAttribute('data-url');
        var token = box.getAttribute('data-token');

        function bubble(text, dir) {
            var d = document.createElement('div');
            d.className = 'msg ' + dir;
            d.textContent = text;
            log.appendChild(d);
            log.scrollTop = log.scrollHeight;
        }

        function chips(list) {
            var old = document.getElementById('arena-asistente-chips');
            if (old) old.remove();
            if (!list || !list.length) return;
            var wrap = document.createElement('div');
            wrap.className = 'chips';
            wrap.id = 'arena-asistente-chips';
            list.forEach(function (label) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'chip';
                b.textContent = label;
                b.setAttribute('data-q', label);
                wrap.appendChild(b);
            });
            log.appendChild(wrap);
        }

        async function ask(message) {
            message = (message || '').trim();
            if (!message || !url) return;
            bubble(message, 'out');
            input.value = '';
            try {
                var res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ message: message, _token: token })
                });
                var data = await res.json();
                var text = (data.title ? data.title + '\n\n' : '') + (data.body || '');
                bubble(text, 'in');
                chips(data.suggestions || []);
            } catch (e) {
                bubble('No pude responder ahora. Probá de nuevo o escribí *ayuda*.', 'in');
            }
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            ask(input.value);
        });
        log.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-q]');
            if (chip) ask(chip.getAttribute('data-q'));
        });
    })();
</script>
@endif
