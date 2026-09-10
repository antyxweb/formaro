"use strict";

/* пути к исходным файлам (src), к готовым файлам (build), а также к тем, за изменениями которых нужно наблюдать (watch) */
var path = {
    build: {
        root: "./",
        html: "./html/",
        js: "./local/templates/formaro_v1/js/",
        vendor: "./local/templates/formaro_v1/js/vendor/",
        css: "./local/templates/formaro_v1/css/",
        img: "./local/templates/formaro_v1/img/",
        upload: "./upload/",
        fonts: "./local/templates/formaro_v1/fonts/",
    },
    src: {
        html: "app/*.html",
        js: "app/js/scripts.js",
        vendor: "app/js/vendor/*",
        style: "app/style/styles.scss",
        img: "app/img/**/*.*",
        upload: "app/upload/**/*.*",
        fonts: "app/fonts/**/*.*",
    },
    watch: {
        html: "app/**/*.html",
        js: "app/js/**/*.js",
        css: "app/style/**/*.scss",
        img: "app/img/**/*.*",
        upload: "app/upload/**/*.*",
        fonts: "app/fonts/**/*.*",
    },
    upload: {
        local: "local/**/*",
        html: "html/*.html",
        upload: "upload/**/*.*",
    },
    clean: "./html/*",
};

/* настройки сервера */
var config = {
  server: {
    baseDir: "./",
  },
  notify: false,
};

var ftpHost = "3aaea775a2f9.hosting.myjino.ru",
  ftpUser = "j68177598",
  ftpPass = "x4EFjXwmNwAqYWPn",
  ftpRemotePath = "/domains/formaro.antyxweb.ru/";

/* подключаем gulp и плагины */
var gulp = require("gulp"), // подключаем Gulp
  webserver = require("browser-sync"), // сервер для работы и автоматического обновления страниц
  plumber = require("gulp-plumber"), // модуль для отслеживания ошибок
  rigger = require("gulp-rigger"), // модуль для импорта содержимого одного файла в другой
  sourcemaps = require("gulp-sourcemaps"), // модуль для генерации карты исходных файлов
  sass = require("gulp-sass")(require("sass")), // модуль для компиляции SASS (SCSS) в CSS
  autoprefixer = require("gulp-autoprefixer"), // модуль для автоматической установки автопрефиксов
  cleanCSS = require("gulp-clean-css"), // плагин для минимизации CSS
  uglify = require("gulp-uglify"), // модуль для минимизации JavaScript
  cache = require("gulp-cache"), // модуль для кэширования
  imagemin = require("gulp-imagemin"), // плагин для сжатия PNG, JPEG, GIF и SVG изображений
  jpegrecompress = require("imagemin-jpeg-recompress"), // плагин для сжатия jpeg
  pngquant = require("imagemin-pngquant"), // плагин для сжатия png
  rimraf = require("gulp-rimraf"), // плагин для удаления файлов и каталогов
  rename = require("gulp-rename"),
  babel = require("gulp-babel"),
  gutil = require("gulp-util"),
  replace = require("gulp-replace"),
  zip = require("gulp-zip"),
  ftp = require("gulp-ftp");

/* задачи */

// запуск сервера
gulp.task("webserver", function () {
  webserver(config);
});

// сбор html
gulp.task("html:build", function () {
  return gulp
    .src(path.src.html) // выбор всех html файлов по указанному пути
    .pipe(plumber()) // отслеживание ошибок
    .pipe(rigger()) // импорт вложений
    .pipe(replace("{{version}}", Date.now())) // подстановка версии
    .pipe(gulp.dest(path.build.html)) // выкладывание готовых файлов
    .pipe(webserver.reload({ stream: true })); // перезагрузка сервера
});

// сбор стилей
gulp.task("css:build", function () {
  return gulp
    .src(path.src.style) // получим main.scss
    .pipe(plumber()) // для отслеживания ошибок
    .pipe(sourcemaps.init()) // инициализируем sourcemap
    .pipe(sass()) // scss -> css
    .pipe(
      autoprefixer({
        overrideBrowserslist: ["last 10 version"],
        grid: true,
      })
    ) // добавим префиксы
    .pipe(gulp.dest(path.build.css))
    .pipe(rename({ suffix: ".min" }))
    .pipe(cleanCSS()) // минимизируем CSS
    .pipe(sourcemaps.write("./")) // записываем sourcemap
    .pipe(gulp.dest(path.build.css)) // выгружаем в build
    .pipe(webserver.reload({ stream: true })); // перезагрузим сервер
});

// сбор js
gulp.task("js:build", function () {
  return (
    gulp
      .src(path.src.js) // получим файл main.js
      .pipe(plumber()) // для отслеживания ошибок
      .pipe(rigger()) // импортируем все указанные файлы в main.js
      .pipe(sourcemaps.init()) //инициализируем sourcemap
      // .pipe(babel({presets: ['@babel/env']}))
      .pipe(gulp.dest(path.build.js))
      .pipe(rename({ suffix: ".min" }))
      .pipe(uglify()) // минимизируем js
      .pipe(sourcemaps.write("./")) //  записываем sourcemap
      .pipe(gulp.dest(path.build.js)) // положим готовый файл
      .pipe(webserver.reload({ stream: true }))
  ); // перезагрузим сервер
});
// перенос папки vendor
gulp.task("vendor:build", function () {
  return gulp.src(path.src.vendor).pipe(gulp.dest(path.build.vendor));
});

// перенос шрифтов
gulp.task("fonts:build", function () {
  return gulp.src(path.src.fonts).pipe(gulp.dest(path.build.fonts));
});

// обработка картинок
gulp.task("img:build", function () {
  return gulp
    .src(path.src.img) // путь с исходниками картинок
    .pipe(
      cache(
        imagemin([
          // сжатие изображений
          imagemin.gifsicle({ interlaced: true }),
          jpegrecompress({
            progressive: true,
            max: 90,
            min: 80,
          }),
          pngquant(),
          imagemin.svgo({ plugins: [{ removeViewBox: false }, { cleanupIDs: true }, { removeUnknownsAndDefaults: { keepDataAttrs: false } }] }),
        ])
      )
    )
    .pipe(gulp.dest(path.build.img)); // выгрузка готовых файлов
});
gulp.task("upload:build", function () {
  return gulp
    .src(path.src.upload) // путь с исходниками картинок
    .pipe(
      cache(
        imagemin([
          // сжатие изображений
          imagemin.gifsicle({ interlaced: true }),
          jpegrecompress({
            progressive: true,
            max: 90,
            min: 80,
          }),
          pngquant(),
          imagemin.svgo({ plugins: [{ removeViewBox: false }, { cleanupIDs: true }, { removeUnknownsAndDefaults: { keepDataAttrs: false } }] }),
        ])
      )
    )
    .pipe(gulp.dest(path.build.upload)); // выгрузка готовых файлов
});

// удаление каталога build
gulp.task("clean:build", function () {
  return gulp.src(path.clean, { read: false }).pipe(rimraf());
});

// очистка кэша
gulp.task("cache:clear", function () {
  cache.clearAll();
});

// сборка
gulp.task("build", gulp.series("clean:build", gulp.parallel("html:build", "css:build", "js:build", "vendor:build", "fonts:build", "img:build", "upload:build")));

// запуск задач при изменении файлов
gulp.task("watch", function () {
  gulp.watch(path.watch.html, gulp.series("html:build"));
  gulp.watch(path.watch.css, gulp.series("css:build"));
  gulp.watch(path.watch.js, gulp.series("js:build"));
  gulp.watch(path.watch.img, gulp.series("img:build"));
  gulp.watch(path.watch.upload, gulp.series("upload:build"));
  gulp.watch(path.watch.fonts, gulp.series("fonts:build"));
});

// задача по умолчанию
gulp.task("default", gulp.series("build", gulp.parallel("webserver", "watch")));

// zip
function getDateTime() {
  var now = new Date();
  var year = now.getFullYear();
  var month = now.getMonth() + 1;
  var day = now.getDate();
  var hour = now.getHours();
  var minute = now.getMinutes();
  var second = now.getSeconds();
  if (month.toString().length == 1) {
    var month = "0" + month;
  }
  if (day.toString().length == 1) {
    var day = "0" + day;
  }
  if (hour.toString().length == 1) {
    var hour = "0" + hour;
  }
  if (minute.toString().length == 1) {
    var minute = "0" + minute;
  }
  if (second.toString().length == 1) {
    var second = "0" + second;
  }
  var dateTime = year + "-" + month + "-" + day + "-" + hour + "-" + minute + "-" + second;
  return dateTime;
}

gulp.task("zip", async () => {
  await new Promise((resolve, reject) => {
    gulp
      .src(["**/*", "!node_modules/**", "!package-lock.json"])
      .pipe(zip("gulp-project-" + getDateTime() + ".zip"))
      .pipe(gulp.dest(path.build.root))
      .on("end", resolve);
  });
});

// ftp
gulp.task("upload:local", function () {
    return gulp
        .src(path.upload.local)
        .pipe(
            ftp({
                host: ftpHost,
                user: ftpUser,
                pass: ftpPass,
                remotePath: ftpRemotePath,
            })
        )
        .pipe(gutil.noop());
});
gulp.task("upload:html", function () {
    return gulp
        .src(path.upload.html)
        .pipe(
            ftp({
                host: ftpHost,
                user: ftpUser,
                pass: ftpPass,
                remotePath: ftpRemotePath,
            })
        )
        .pipe(gutil.noop());
});
gulp.task("upload:upload", function () {
    return gulp
        .src(path.upload.upload)
        .pipe(
            ftp({
                host: ftpHost,
                user: ftpUser,
                pass: ftpPass,
                remotePath: ftpRemotePath + "upload/",
            })
        )
        .pipe(gutil.noop());
});
gulp.task("upload:final", gulp.series("build", "upload:local", "upload:html", "upload:upload"));
