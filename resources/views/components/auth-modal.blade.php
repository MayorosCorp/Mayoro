{{--
    Ficha CI-COD-13 (HU-03, escenario "Redireccion inteligente al intentar cotizar").

    Modal informativo que la especificacion exige al presionar "Cotizar Pedido"
    sin sesion: solicita el inicio de sesion y conduce al login conservando el
    returnUrl para devolver al visitante al producto que estaba mirando.

    No se auto-redirige al abrirse: expulsar al usuario sin que pueda decidir
    contradice la palabra "informativo" del escenario. El returnUrl viaja en los
    enlaces del modal, que es donde se cumple la redireccion.

    Dos modos de uso:
      - Pagina de detalle, con una sola URL de retorno:
            <x-auth-modal :return-url="route('products.show', $producto)" />
      - Cuadricula del catalogo, donde cada tarjeta tiene su propia URL. Se pasa
        el returnUrl por el disparador y el script lo inyecta en el modal:
            <button data-modal-abrir="authModal"
                    data-return-url="{{ route('products.show', $prod->id) }}">...</button>
            <x-auth-modal />

    Escenario de seguridad: el returnUrl lo escribe el propio backend al
    renderizar la vista, nunca JavaScript a partir de la barra de direcciones,
    y AuthController lo vuelve a sanear en el servidor.
--}}
@props(['returnUrl' => null])

@php
    $rutaInicial = $returnUrl ?? route('products.index');
@endphp

<div class="modal-overlay" id="authModal" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="authModalTitulo">
        <h2 id="authModalTitulo" style="margin:0 0 .5rem 0; font-size:1.15rem;">
            Inicia sesión para cotizar
        </h2>

        <p style="margin:0 0 1rem 0; color:#4b5563; font-size:.9rem;">
            El catálogo mayorista es público, pero la cotización requiere una cuenta de
            comercio registrada con RUC y WhatsApp. Te devolvemos al producto que estabas
            viendo al terminar.
        </p>

        <div style="display:flex; gap:.6rem; flex-wrap:wrap;">
            {{--
                data-modal-base guarda la ruta SIN query string. El href visible
                ya trae el returnUrl por si el visitante navega sin JavaScript, y
                el script reconstruye la URL desde la base para no duplicar el
                parametro al cambiar de producto.
            --}}
            <a class="btn" data-modal-destino="login" data-modal-base="{{ route('auth.login') }}"
               href="{{ route('auth.login', ['returnUrl' => $rutaInicial]) }}">
                Iniciar sesión
            </a>
            <a class="btn ghost" data-modal-destino="register" data-modal-base="{{ route('auth.register') }}"
               href="{{ route('auth.register', ['returnUrl' => $rutaInicial]) }}">
                Crear cuenta de bodega
            </a>
            <button class="btn ghost" type="button" data-modal-close>Seguir explorando</button>
        </div>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, .55);
        display: flex; align-items: center; justify-content: center;
        padding: 1.5rem; z-index: 50;
    }
    .modal-overlay[hidden] { display: none; }
    .modal {
        background: #fff; border-radius: .6rem; padding: 1.5rem;
        max-width: 460px; width: 100%;
        box-shadow: 0 20px 45px rgba(0, 0, 0, .3);
    }
</style>

<script>
    (function () {
        var modal = document.getElementById('authModal');
        if (! modal) { return; }

        var enlaces = {
            login: modal.querySelector('[data-modal-destino="login"]'),
            register: modal.querySelector('[data-modal-destino="register"]')
        };

        function abrir(disparador) {
            // En la cuadricula cada tarjeta declara su propio destino. Se
            // reconstruye la URL desde la ruta base para no acumular un segundo
            // returnUrl sobre el que ya trae el href.
            var destino = disparador.getAttribute('data-return-url');

            if (destino) {
                Object.keys(enlaces).forEach(function (clave) {
                    var base = enlaces[clave].getAttribute('data-modal-base');
                    enlaces[clave].setAttribute('href', base + '?returnUrl=' + encodeURIComponent(destino));
                });
            }

            modal.hidden = false;
            var foco = modal.querySelector('a, button');
            if (foco) { foco.focus(); }
        }

        function cerrar() {
            modal.hidden = true;
        }

        document.querySelectorAll('[data-modal-abrir="authModal"]').forEach(function (disparador) {
            disparador.addEventListener('click', function (evento) {
                evento.preventDefault();
                abrir(disparador);
            });
        });

        modal.querySelectorAll('[data-modal-close]').forEach(function (boton) {
            boton.addEventListener('click', cerrar);
        });

        // Cierra al pulsar fuera del recuadro, sin tratar el clic dentro de el.
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) { cerrar(); }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && ! modal.hidden) { cerrar(); }
        });
    })();
</script>
