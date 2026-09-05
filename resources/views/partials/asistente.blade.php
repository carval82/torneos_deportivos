@php
    $asistenteOn = config('asistente.enabled') && filled(config('asistente.frame_url'));
    $frameUrl = rtrim((string) config('asistente.frame_url'), '/');
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
        width: 360px;
        max-width: calc(100vw - 24px);
        height: 520px;
        max-height: calc(100vh - 110px);
        display: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(0, 0, 0, .28);
        background: #fff;
    }
    #arena-asistente-box iframe {
        border: 0;
        width: 100%;
        height: 100%;
    }
    @media print {
        #arena-asistente-btn,
        #arena-asistente-box { display: none !important; }
    }
</style>
<button type="button" id="arena-asistente-btn" aria-label="Ayuda Arena Players" aria-expanded="false">¿Cómo se hace?</button>
<div id="arena-asistente-box">
    <iframe src="{{ $frameUrl }}" title="Ayuda Arena Players"></iframe>
</div>
<script>
    (function () {
        var btn = document.getElementById('arena-asistente-btn');
        var box = document.getElementById('arena-asistente-box');
        if (!btn || !box) return;
        btn.addEventListener('click', function () {
            var open = box.style.display !== 'block';
            box.style.display = open ? 'block' : 'none';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    })();
</script>
@endif
