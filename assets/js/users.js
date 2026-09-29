/* ============================================================
   Gestion de usuarios (solo admin)
   ============================================================ */

/* global api, esc, abrirModal, cerrarModal */

document.addEventListener('DOMContentLoaded', () => {
    const tbody       = document.getElementById('users-tbody');
    const msg         = document.getElementById('msg');
    const backdrop    = document.getElementById('modal-backdrop');
    const form        = document.getElementById('user-form');
    const formError   = document.getElementById('form-error');
    const btnNuevo    = document.getElementById('btn-nuevo');

    let roles = [];

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 4000);
    }

    /* Activo es el estado normal; inactivo es informativo, no un error. */
    function badgeEstado(activo) {
        return '<span class="badge badge-' + (activo == 1 ? 'ok' : 'neutral') + '">'
            + (activo == 1 ? 'activo' : 'inactivo') + '</span>';
    }

    async function cargarUsuarios() {
        const json = await api('/api/users.php?action=list');
        tbody.innerHTML = '';
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="7" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }
        roles = json.data.roles;

        if (json.data.users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="muted center">Sin usuarios registrados.</td></tr>';
        }
        json.data.users.forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + u.id + '</td>' +
                '<td>' + esc(u.nombre) + '</td>' +
                '<td>' + esc(u.email) + '</td>' +
                '<td>' + esc(u.rol) + '</td>' +
                '<td>' + badgeEstado(u.activo) + '</td>' +
                '<td>' + esc(u.fecha_creacion) + '</td>' +
                '<td class="actions-cell">' +
                    '<button class="btn btn-ghost btn-sm" data-edit="' + u.id + '">Editar</button> ' +
                    '<button class="btn btn-danger btn-sm" data-del="' + u.id + '">Eliminar</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function llenarRoles(seleccionado) {
        roles.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.nombre;
            if (r.id == seleccionado) opt.selected = true;
            document.getElementById('rol_id').appendChild(opt);
        });
    }

    // Se llama abrirForm y no abrirModal para no tapar la funcion
    // centralizada de app.js, que se usa al final para mostrar el modal.
    function abrirForm(usuario) {
        form.reset();
        formError.classList.add('hidden');
        document.getElementById('rol_id').innerHTML = '';

        if (usuario) {
            document.getElementById('modal-title').textContent = 'Editar usuario';
            document.getElementById('user-id').value   = usuario.id;
            document.getElementById('nombre').value    = usuario.nombre;
            document.getElementById('email').value     = usuario.email;
            document.getElementById('password').value  = '';
            document.getElementById('activo').checked  = usuario.activo == 1;
            llenarRoles(usuario.rol_id);
        } else {
            document.getElementById('modal-title').textContent = 'Nuevo usuario';
            llenarRoles(null);
        }
        abrirModal(backdrop);
    }

    btnNuevo.addEventListener('click', () => abrirForm(null));

    // Cancelar y el clic en el fondo los resuelve el delegado de app.js
    // (el boton lleva data-close="modal-backdrop").

    tbody.addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('[data-edit]');
        const btnDel  = e.target.closest('[data-del]');

        if (btnEdit) {
            const json = await api('/api/users.php?action=get&id=' + btnEdit.dataset.edit);
            if (json.success) abrirForm(json.data);
        }
        if (btnDel) {
            if (!confirm('¿Eliminar este usuario? Esta accion no se puede deshacer.')) return;
            const json = await api('/api/users.php?action=delete', { id: Number(btnDel.dataset.del) });
            mostrarMsg(json.message, json.success ? 'success' : 'error');
            cargarUsuarios();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id      = document.getElementById('user-id').value;
        const action  = id && Number(id) > 0 ? 'update' : 'create';

        const data = new FormData(form);
        data.set('activo', document.getElementById('activo').checked ? '1' : '0');
        if (action === 'update') data.set('id', id);

        const json = await api('/api/users.php?action=' + action, data);

        if (json.success) {
            cerrarModal(backdrop);
            mostrarMsg(json.message, 'success');
            cargarUsuarios();
        } else {
            formError.textContent = json.message;
            formError.classList.remove('hidden');
        }
    });

    cargarUsuarios();
});