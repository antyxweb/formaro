/* formaro:personal.profile — сохранение блоков «Ваш профиля»: контакты,
   реквизиты, пароль. Каждая форма .js-profile-form (data-action) уходит
   POST-ом в /local/ajax/profile.php; итог — рядом с кнопкой. Пароль после
   смены очищается. */
document.addEventListener('submit', function (e) {
    var form = e.target.closest && e.target.closest('.js-profile-form');
    if (!form) return;
    e.preventDefault();
    var root = document.getElementById('personal-profile');
    var status = form.querySelector('.js-profile-status');
    var button = form.querySelector('button[type="submit"]');

    var params = ['sessid=' + encodeURIComponent(root.getAttribute('data-sessid')), 'action=' + encodeURIComponent(form.getAttribute('data-action'))];
    Array.prototype.forEach.call(form.elements, function (input) {
        if (input.name) params.push(encodeURIComponent(input.name) + '=' + encodeURIComponent(input.value));
    });

    var show = function (text, ok) {
        status.textContent = text;
        status.classList.toggle('text-success', ok);
        status.classList.toggle('text-danger', !ok);
    };
    button.disabled = true;
    show('', true);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/local/ajax/profile.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        button.disabled = false;
        var data = null;
        try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
        if (xhr.status !== 200 || !data || data.error) {
            show((data && data.error) || 'Не удалось сохранить, попробуйте ещё раз', false);
            return;
        }
        // Сервер возвращает сохранённое (нормализованное) — показываем его.
        var saved = data.contacts || data.legal;
        if (saved) {
            Object.keys(saved).forEach(function (name) {
                if (form.elements[name]) form.elements[name].value = saved[name];
            });
        }
        if (form.getAttribute('data-action') === 'password') form.reset();
        show(form.getAttribute('data-action') === 'password' ? 'Пароль изменён' : 'Сохранено', true);
    };
    xhr.onerror = function () {
        button.disabled = false;
        show('Нет связи с сервером, попробуйте ещё раз', false);
    };
    xhr.send(params.join('&'));
});
