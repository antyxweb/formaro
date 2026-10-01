# Скриншоты «Гайда по сайту»

Кадры для /guide/img/ (главы — local/php_interface/migrations/data/guide/).

    npm i --no-save puppeteer-core@23        # Chrome — системный, путь в shoot.js
    node shoot.js shots.json out <BITRIX_PHPSESSID>

- `shots.json` — кадр: name, url, auth (нужна сессия), height/width,
  mobile, full, rect/clip (обрезка), storage (localStorage, напр. гостевая
  корзина), actions (click, type, eval, follow, scroll, scrollText).
- Для кадров с `auth` нужна сессия администратора-партнёра (кука
  BITRIX_PHPSESSID). Личные e-mail/телефон на кадрах заменяются
  демо-значениями (см. shoot.js) — при смене данных поправить шаблоны.
- PNG → JPEG для сайта: `sips -s format jpeg -s formatOptions 82 x.png --out x.jpg`.
