/* Избранное на витрине: сердечко .favorite в карточке товара и счётчик
   в шапке ([data-favorites-count]).

   Карточка участвует, если у .favorite есть data-product-id (ID товара
   cabinet_catalog). Внутри .favorite две кнопки: первая — контур
   (не в избранном), вторая — заливка (в избранном).

   Хранение: авторизованный — в аккаунте (HL-блок Favorites через
   /local/ajax/favorites.php), гость — в localStorage. При входе гостевое
   избранное переносится в аккаунт и удаляется из браузера, чтобы после
   выхода не осталось видно следующему человеку за этим компьютером.

   Подключается в footer.php после scripts.min.js: старый обработчик
   клика по .favorite из scripts.js (просто переключал кнопки, ничего не
   сохраняя) снимается здесь. Карточки, добавленные позже (AJAX-подгрузка,
   карусели), подхватываются MutationObserver. */
(function ($) {
    var endpoint = '/local/ajax/favorites.php';
    var STORAGE_KEY = 'formaro_favorites';

    var state = {authorized: false, sessid: '', ids: [], ready: false};
    var readyCallbacks = [];
    var changeCallbacks = [];

    function readLocal() {
        try {
            var list = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '[]');
            return Array.isArray(list) ? list.map(Number).filter(Boolean) : [];
        } catch (e) {
            return [];
        }
    }

    function writeLocal(ids) {
        try {
            if (ids.length) {
                window.localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
            } else {
                window.localStorage.removeItem(STORAGE_KEY);
            }
        } catch (e) {
            // приватный режим/заблокированное хранилище — избранное только до перезагрузки
        }
    }

    function request(method, fields, cb) {
        var body = Object.keys(fields).map(function (k) {
            var v = fields[k];
            return Array.isArray(v)
                ? v.map(function (item) { return encodeURIComponent(k + '[]') + '=' + encodeURIComponent(item); }).join('&')
                : encodeURIComponent(k) + '=' + encodeURIComponent(v);
        }).filter(Boolean).join('&');

        var xhr = new XMLHttpRequest();
        xhr.open(method, endpoint, true);
        if (method === 'POST') xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) { /* пусто */ }
            cb(xhr.status === 200 ? data : null);
        };
        xhr.onerror = function () { cb(null); };
        xhr.send(method === 'POST' ? body : null);
    }

    function isFavorite(id) {
        return state.ids.indexOf(id) !== -1;
    }

    function renderCard(el) {
        var id = Number(el.getAttribute('data-product-id'));
        if (!id) return;
        var buttons = el.querySelectorAll('button');
        var active = isFavorite(id);
        if (buttons[0]) buttons[0].classList.toggle('d-none', active);
        if (buttons[1]) buttons[1].classList.toggle('d-none', !active);
    }

    function renderAll(root) {
        Array.prototype.forEach.call((root || document).querySelectorAll('.favorite[data-product-id]'), renderCard);
    }

    function renderCount() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-favorites-count]'), function (el) {
            el.textContent = state.ids.length;
        });
    }

    function render() {
        renderAll();
        renderCount();
    }

    function notifyChange(id, added) {
        changeCallbacks.forEach(function (cb) { cb(state.ids.slice(), id, added); });
    }

    /** Итоговый список известен: с сервера (авторизованный) или из
     *  localStorage (гость / сервер недоступен). */
    function markReady() {
        if (state.ready) return;
        state.ready = true;
        readyCallbacks.splice(0).forEach(function (cb) { cb(state.ids.slice()); });
    }

    function applyServer(data) {
        if (!data) return;
        state.authorized = !!data.authorized;
        state.sessid = data.sessid || '';
        if (state.authorized) state.ids = (data.ids || []).map(Number);
        render();
    }

    function toggle(id) {
        var adding = !isFavorite(id);
        state.ids = adding
            ? [id].concat(state.ids)
            : state.ids.filter(function (item) { return item !== id; });
        render();

        if (state.authorized) {
            request('POST', {sessid: state.sessid, action: adding ? 'add' : 'remove', id: id}, applyServer);
        } else {
            writeLocal(state.ids);
        }
        notifyChange(id, adding);
    }

    function init() {
        state.ids = readLocal();
        render();

        request('GET', {}, function (data) {
            if (!data || !data.authorized) {
                markReady();
                return;
            }
            var guest = readLocal();
            if (!guest.length) {
                applyServer(data);
                markReady();
                return;
            }
            request('POST', {sessid: data.sessid, action: 'merge', ids: guest}, function (merged) {
                if (!merged) {
                    applyServer(data);
                } else {
                    writeLocal([]);
                    applyServer(merged);
                }
                markReady();
            });
        });

        // Клик по сердечку. Старый обработчик scripts.js снимаем — он
        // переключал кнопки у любой .favorite без сохранения. Карточки без
        // data-product-id (ещё не подключённые к каталогу) ведут себя
        // как раньше — просто переключаются.
        $('body').off('click', '.favorite').on('click', '.favorite', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var id = Number(this.getAttribute('data-product-id'));
            if (!id) {
                $(this).find('button').toggleClass('d-none');
                return;
            }
            toggle(id);
        });

        if (window.MutationObserver) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    Array.prototype.forEach.call(m.addedNodes, function (node) {
                        if (node.nodeType !== 1) return;
                        if (node.matches && node.matches('.favorite[data-product-id]')) {
                            renderCard(node);
                        } else if (node.querySelectorAll) {
                            renderAll(node);
                        }
                    });
                });
            }).observe(document.body, {childList: true, subtree: true});
        }
    }

    /* Для страницы избранного (formaro:favorites.list):
       onReady(cb(ids)) — когда список окончательно известен;
       onChange(cb(ids, id, added)) — после каждого клика по сердечку. */
    window.FormaroFavorites = {
        getIds: function () { return state.ids.slice(); },
        onReady: function (cb) {
            if (state.ready) cb(state.ids.slice()); else readyCallbacks.push(cb);
        },
        onChange: function (cb) { changeCallbacks.push(cb); }
    };

    if (!$) return;
    $(init);
})(window.jQuery);
