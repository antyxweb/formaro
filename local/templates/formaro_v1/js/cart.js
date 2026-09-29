/* Корзина витрины: кнопки в карточке товара (.product-card__actions) и
   счётчик в шапке ([data-cart-count] — число позиций).

   Карточка участвует, если у .product-card__actions есть data-product-id
   (ID товара cabinet_catalog). «В корзину» кладёт товар с количеством из
   поля .cart-cnt; дальше в карточке — поле количества (меняет количество
   в корзине) и «Удалить», как в вёрстке (/html/index.html: класс in-cart).

   Хранение — как у избранного: авторизованный — в аккаунте (HL-блок
   CartItems через /local/ajax/cart.php), гость — в localStorage. При
   входе гостевая корзина переносится в аккаунт и удаляется из браузера.

   Подключается в footer.php после scripts.min.js: старые обработчики
   .cart-add/.cart-remove из scripts.js (переключали кнопки без сохранения)
   снимаются здесь. Карточки, добавленные позже (AJAX-подгрузка,
   карусели), подхватываются MutationObserver. */
(function ($) {
    var endpoint = '/local/ajax/cart.php';
    var STORAGE_KEY = 'formaro_cart';
    var QTY_DELAY = 500; // мс после последнего изменения количества

    var state = {authorized: false, sessid: '', items: [], ready: false};
    var readyCallbacks = [];
    var changeCallbacks = [];
    var qtyTimers = {};

    function readLocal() {
        try {
            var list = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '[]');
            return Array.isArray(list) ? list.map(function (item) {
                return {id: Number(item.id), qty: Number(item.qty)};
            }).filter(function (item) { return item.id > 0 && item.qty > 0; }) : [];
        } catch (e) {
            return [];
        }
    }

    function writeLocal(items) {
        try {
            if (items.length) {
                window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
            } else {
                window.localStorage.removeItem(STORAGE_KEY);
            }
        } catch (e) {
            // приватный режим/заблокированное хранилище — корзина только до перезагрузки
        }
    }

    function encode(fields) {
        var parts = [];
        Object.keys(fields).forEach(function (k) {
            var v = fields[k];
            if (Array.isArray(v)) {
                v.forEach(function (item, i) {
                    if (item !== null && typeof item === 'object') {
                        Object.keys(item).forEach(function (key) {
                            parts.push(encodeURIComponent(k + '[' + i + '][' + key + ']') + '=' + encodeURIComponent(item[key]));
                        });
                    } else {
                        parts.push(encodeURIComponent(k + '[]') + '=' + encodeURIComponent(item));
                    }
                });
            } else {
                parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
            }
        });
        return parts.join('&');
    }

    function request(method, fields, cb) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, endpoint, true);
        if (method === 'POST') xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) { /* пусто */ }
            cb(xhr.status === 200 ? data : null);
        };
        xhr.onerror = function () { cb(null); };
        xhr.send(method === 'POST' ? encode(fields) : null);
    }

    function find(id) {
        for (var i = 0; i < state.items.length; i++) {
            if (state.items[i].id === id) return state.items[i];
        }
        return null;
    }

    function actionsOf(el) {
        return el.closest && el.closest('.product-card__actions');
    }

    function renderCard(actions) {
        var id = Number(actions.getAttribute('data-product-id'));
        if (!id) return;
        var item = find(id);
        var input = actions.querySelector('.cart-cnt input');
        var add = actions.querySelector('.cart-add');
        var remove = actions.querySelector('.cart-remove');

        actions.classList.toggle('in-cart', !!item);
        if (add) add.classList.toggle('d-none', !!item);
        if (remove) remove.classList.toggle('d-none', !item);
        if (input && document.activeElement !== input && !qtyTimers[id]) {
            input.value = item ? item.qty : (input.getAttribute('data-min') || 1);
        }
    }

    function renderAll(root) {
        Array.prototype.forEach.call((root || document).querySelectorAll('.product-card__actions[data-product-id]'), renderCard);
    }

    function renderCount() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-cart-count]'), function (el) {
            el.textContent = state.items.length;
        });
    }

    function render() {
        renderAll();
        renderCount();
    }

    function snapshot() {
        return state.items.map(function (item) { return {id: item.id, qty: item.qty}; });
    }

    function notifyChange(ids) {
        changeCallbacks.forEach(function (cb) { cb(snapshot(), ids); });
    }

    function markReady() {
        if (state.ready) return;
        state.ready = true;
        readyCallbacks.splice(0).forEach(function (cb) { cb(snapshot()); });
    }

    /** Корзина с сервера целиком — при загрузке страницы и после переноса
     *  гостевой корзины. */
    function applyServer(data) {
        if (!data) return;
        state.authorized = !!data.authorized;
        state.sessid = data.sessid || '';
        if (state.authorized) {
            state.items = (data.items || []).map(function (item) { return {id: Number(item.id), qty: Number(item.qty)}; });
            render();
        }
    }

    /** Ответ на изменение одного товара: запросы уходят параллельно, поэтому
     *  остальную корзину из ответа не берём (он мог обогнать соседний
     *  запрос), а по этому товару — принимаем поправку сервера (количество
     *  урезано до остатка, товар больше нельзя купить). */
    function applyItemResult(id, data) {
        if (!data || !data.authorized) return;
        var local = find(id);
        if (!local) return;
        var server = null;
        (data.items || []).forEach(function (item) {
            if (Number(item.id) === id) server = item;
        });
        if (server && Number(server.qty) === local.qty) return;
        if (server) {
            local.qty = Number(server.qty);
        } else {
            state.items = state.items.filter(function (x) { return x.id !== id; });
        }
        render();
        notifyChange([id]);
    }

    function persist(fields, id) {
        if (state.authorized) {
            fields.sessid = state.sessid;
            request('POST', fields, function (data) {
                if (id) applyItemResult(id, data);
            });
        } else {
            writeLocal(state.items);
        }
    }

    /** Положить товар / поменять количество; qty <= 0 — убрать. */
    function set(id, qty) {
        id = Number(id);
        qty = Math.max(0, parseInt(qty, 10) || 0);
        var item = find(id);
        if (!qty) {
            if (!item) return;
            state.items = state.items.filter(function (x) { return x.id !== id; });
        } else if (item) {
            if (item.qty === qty) return;
            item.qty = qty;
        } else {
            state.items.push({id: id, qty: qty});
        }
        render();
        persist({action: 'set', id: id, qty: qty}, qty ? id : 0);
        notifyChange([id]);
    }

    function remove(ids) {
        ids = (ids || []).map(Number);
        var before = state.items.length;
        state.items = state.items.filter(function (x) { return ids.indexOf(x.id) === -1; });
        if (state.items.length === before) return;
        render();
        persist({action: 'remove', ids: ids});
        notifyChange(ids);
    }

    function init() {
        state.items = readLocal();
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
            request('POST', {sessid: data.sessid, action: 'merge', items: guest}, function (merged) {
                if (!merged) {
                    applyServer(data);
                } else {
                    writeLocal([]);
                    applyServer(merged);
                }
                markReady();
            });
        });

        // Старые обработчики scripts.js переключали кнопки без сохранения.
        // Карточки без data-product-id (вёрстка без каталога) ведут себя
        // как раньше.
        $('body').off('click', '.cart-add').on('click', '.cart-add', function (e) {
            var actions = actionsOf(this);
            var id = actions ? Number(actions.getAttribute('data-product-id')) : 0;
            e.preventDefault();
            if (this.disabled) return;
            if (!id) {
                $(this).addClass('d-none');
                $(actions).addClass('in-cart').find('.cart-remove').removeClass('d-none');
                return;
            }
            var input = actions.querySelector('.cart-cnt input');
            set(id, input ? input.value : 1);
        });

        $('body').off('click', '.cart-remove').on('click', '.cart-remove', function (e) {
            var actions = actionsOf(this);
            var id = actions ? Number(actions.getAttribute('data-product-id')) : 0;
            e.preventDefault();
            if (!id) {
                $(this).addClass('d-none');
                $(actions).removeClass('in-cart').find('.cart-add').removeClass('d-none');
                var $input = $(actions).find('input');
                $input.val($input.data('min'));
                return;
            }
            set(id, 0);
        });

        // Количество в карточке товара, который уже в корзине. Границы поля
        // (data-min/data-max) выставляет обработчик scripts.js — он висит
        // раньше и срабатывает первым.
        $('body').on('keyup change', '.product-card__actions.in-cart[data-product-id] .cart-cnt input', function () {
            var actions = actionsOf(this);
            var id = Number(actions.getAttribute('data-product-id'));
            var input = this;
            clearTimeout(qtyTimers[id]);
            qtyTimers[id] = setTimeout(function () {
                delete qtyTimers[id];
                var qty = parseInt(input.value, 10);
                if (qty > 0) set(id, qty);
            }, QTY_DELAY);
        });

        if (window.MutationObserver) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    Array.prototype.forEach.call(m.addedNodes, function (node) {
                        if (node.nodeType !== 1) return;
                        if (node.matches && node.matches('.product-card__actions[data-product-id]')) {
                            renderCard(node);
                        } else if (node.querySelectorAll) {
                            renderAll(node);
                        }
                    });
                });
            }).observe(document.body, {childList: true, subtree: true});
        }
    }

    /* Для страницы корзины (formaro:cart):
       getItems() — [{id, qty}] в порядке добавления;
       onReady(cb(items)) — когда корзина окончательно известна;
       onChange(cb(items, ids)) — после каждого изменения;
       set(id, qty), remove(ids). */
    window.FormaroCart = {
        getItems: snapshot,
        onReady: function (cb) {
            if (state.ready) cb(snapshot()); else readyCallbacks.push(cb);
        },
        onChange: function (cb) { changeCallbacks.push(cb); },
        set: set,
        remove: remove
    };

    if (!$) return;
    $(init);
})(window.jQuery);
