{{--
    Ficha CI-COD-13 (HU-03, escenario "Redireccion inteligente al intentar cotizar").

    Modal informativo opcional para invitar al visitante a crear cuenta y
    conservar su historial. Desde RN-01 la cotizacion ya no exige sesion ni RUC:
    el formulario publico es la via principal y este modal no debe bloquearla.

    No se auto-redirige al abrirse: el visitante decide. El returnUrl viaja en
    los enlaces del modal, que es donde se cumple la redireccion.

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

<div class="modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-6" id="authModal" hidden>
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="authModalTitulo">
        <h2 id="authModalTitulo" class="mt-0 text-xl font-semibold tracking-tight text-slate-900">
            Cotiza sin cuenta o guarda tu historial
        </h2>

        <p class="mb-5 mt-2 text-sm leading-6 text-slate-600">
            El catálogo mayorista es público y cotizar no exige RUC. Si creas una cuenta gratuita
            como Persona natural, conservas tus cotizaciones y puedes convertirte en Empresario
            cuando quieras vender. Te devolvemos al producto que estabas viendo.
        </p>

        <div class="flex flex-wrap gap-2">
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
                Crear cuenta gratuita
            </a>
            <button class="btn ghost" type="button" data-modal-close>Seguir explorando</button>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('authModal');
        if (! modal) { return; }

        var enlaces = {
            login: modal.querySelector('[data-modal-destino="login"]'),
            register: modal.querySelector('[data-modal-destino="register"]')
        };
        var ultimoFoco = null;

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

            ultimoFoco = document.activeElement;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            var foco = modal.querySelector('a, button');
            if (foco) { foco.focus(); }
        }

        function cerrar() {
            modal.hidden = true;
            document.body.style.overflow = '';
            if (ultimoFoco && ultimoFoco.focus) { ultimoFoco.focus(); }
            ultimoFoco = null;
        }

        // El foco no debe escapar del dialogo mientras esta abierto.
        modal.addEventListener('keydown', function (evento) {
            if (evento.key !== 'Tab') { return; }

            var enfocables = modal.querySelectorAll('a[href], button:not([disabled])');
            if (! enfocables.length) { return; }

            var primero = enfocables[0];
            var ultimo = enfocables[enfocables.length - 1];

            if (evento.shiftKey && document.activeElement === primero) {
                evento.preventDefault();
                ultimo.focus();
            } else if (! evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault();
                primero.focus();
            }
        });

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
