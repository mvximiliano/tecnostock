/**
 * Validaciones en el navegador (respuesta inmediata al usuario).
 * IMPORTANTE: todas estas reglas se repiten en PHP, porque el JavaScript
 * puede desactivarse o saltarse.
 */
document.addEventListener('DOMContentLoaded', function () {

    /** Marca un campo como válido o inválido usando las clases de Bootstrap. */
    function marcar(campo, esValido, mensaje) {
        campo.classList.toggle('is-invalid', !esValido);
        campo.classList.toggle('is-valid', esValido);
        if (mensaje) {
            const feedback = campo.parentElement.querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = mensaje;
        }
        return esValido;
    }

    function esEnteroNoNegativo(valor) {
        return /^\d+$/.test(String(valor).trim());
    }

    // ---------- Login ----------
    const formLogin = document.getElementById('form-login');
    if (formLogin) {
        formLogin.addEventListener('submit', function (ev) {
            const correo = formLogin.correo;
            const clave = formLogin.clave;
            const okCorreo = marcar(correo, /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value.trim()));
            const okClave = marcar(clave, clave.value.length > 0);
            if (!okCorreo || !okClave) ev.preventDefault();
        });
    }

    // ---------- Producto ----------
    const formProducto = document.getElementById('form-producto');
    if (formProducto) {
        const aviso = document.getElementById('aviso-stock');

        // Aviso informativo cuando el stock actual queda bajo el mínimo
        function revisarAviso() {
            const actual = formProducto.stock_actual.value;
            const minimo = formProducto.stock_minimo.value;
            const mostrar = esEnteroNoNegativo(actual) && esEnteroNoNegativo(minimo) && Number(actual) < Number(minimo);
            aviso.classList.toggle('d-none', !mostrar);
        }
        formProducto.stock_actual.addEventListener('input', revisarAviso);
        formProducto.stock_minimo.addEventListener('input', revisarAviso);
        revisarAviso();

        formProducto.addEventListener('submit', function (ev) {
            const f = formProducto;
            let ok = true;
            ok = marcar(f.codigo, /^[A-Za-z0-9\-]{2,50}$/.test(f.codigo.value.trim())) && ok;
            ok = marcar(f.nombre, f.nombre.value.trim().length > 0) && ok;
            ok = marcar(f.id_categoria, f.id_categoria.value !== '') && ok;
            ok = marcar(f.precio, f.precio.value !== '' && Number(f.precio.value) >= 0) && ok;
            ok = marcar(f.stock_actual, esEnteroNoNegativo(f.stock_actual.value)) && ok;
            ok = marcar(f.stock_minimo, esEnteroNoNegativo(f.stock_minimo.value)) && ok;
            if (!ok) {
                ev.preventDefault();
                f.querySelector('.is-invalid').focus();
            }
        });
    }

    // ---------- Movimiento ----------
    const formMov = document.getElementById('form-movimiento');
    if (formMov) {
        const stock = Number(formMov.dataset.stock);
        const ayuda = document.getElementById('stock-resultante');

        // Muestra cómo quedará el stock antes de guardar
        function calcular() {
            const cantidad = formMov.cantidad.value;
            if (!/^[1-9]\d*$/.test(cantidad)) { ayuda.textContent = ''; return; }
            const resultado = formMov.tipo.value === 'entrada' ? stock + Number(cantidad) : stock - Number(cantidad);
            ayuda.textContent = 'El stock quedará en ' + resultado + ' unidades.';
            ayuda.classList.toggle('text-danger', resultado < 0);
        }
        formMov.cantidad.addEventListener('input', calcular);
        formMov.tipo.addEventListener('change', calcular);
        calcular();

        formMov.addEventListener('submit', function (ev) {
            const cantidad = formMov.cantidad.value.trim();
            if (!/^[1-9]\d*$/.test(cantidad)) {
                marcar(formMov.cantidad, false, 'La cantidad debe ser un entero mayor que 0.');
                ev.preventDefault();
                return;
            }
            // Regla: una salida no puede dejar el stock bajo cero
            if (formMov.tipo.value === 'salida' && Number(cantidad) > stock) {
                marcar(formMov.cantidad, false, 'Stock insuficiente: solo hay ' + stock + ' unidades disponibles.');
                ev.preventDefault();
                return;
            }
            marcar(formMov.cantidad, true);
        });
    }

    // ---------- Categoría ----------
    const formCat = document.getElementById('form-categoria');
    if (formCat) {
        formCat.addEventListener('submit', function (ev) {
            if (!marcar(formCat.nombre, formCat.nombre.value.trim().length > 0)) ev.preventDefault();
        });
    }

    // ---------- Confirmaciones (desactivar) ----------
    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!confirm(form.dataset.confirmar)) ev.preventDefault();
        });
    });
});
