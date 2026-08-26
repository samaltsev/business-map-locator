# NBH Business Locator

## Полное обновлённое техническое задание Free/Pro и roadmap

**Владелец продукта:** Nebho.Agency  
**Тип продукта:** WordPress plugin, Free + Pro add-on  
**Версия документа:** 2.0  
**Дата:** 23 июля 2026 года  
**Статус:** мастер-спецификация для разработки и приёмки  
**Исходная сборка:** `business-map-locator (2).zip`  
**Версия исходной сборки:** `1.3.2-beta29`  
**SHA-256 исходного архива:** `4d4af15772e4aedecb13596d4554b12f2e229cf368646e910b44d82663cade33`

Этот документ заменяет и консолидирует:

- первоначальное исследование, техническое задание и roadmap;
- аудит обновлённой beta29;
- сравнительную матрицу Free/Pro;
- уточнения по карточке точки;
- требования к фильтрам и территориям;
- требования к нескольким картам и shortcode;
- требования к layouts, шаблонам и сохранённым конфигурациям;
- отдельный frontend-сценарий «каталог точек + фильтры + карта».

---

# 1. Резюме продукта

## 1.1. Назначение

NBH Business Locator позволяет компании:

1. создать и поддерживать каталог физических точек;
2. импортировать точки из CSV;
3. показывать их на интерактивной карте;
4. выводить каталог карточек с поиском и фильтрами;
5. помогать посетителю найти ближайшую подходящую точку;
6. публиковать разные конфигурации локатора на разных страницах.

Типовые точки:

- магазины;
- офисы и филиалы;
- дилеры;
- сервисные центры;
- пункты выдачи;
- клиники и аптеки;
- рестораны и отели;
- склады;
- партнёрские точки.

## 1.2. Продуктовая ниша

Плагин не должен становиться универсальным GIS-конструктором.

Основная ниша:

> Business locator для компаний и агентств, которым нужно быстро превратить таблицу филиалов в современную карту и каталог без обязательной настройки Google Cloud и billing.

Целевой масштаб первого поколения:

- основной сценарий: 2–500 точек;
- поддерживаемый сценарий: до 5 000 точек при корректной серверной загрузке;
- вне scope 1.x: десятки тысяч динамических объектов, tracking и сложные GIS-слои.

## 1.3. Ключевое обещание Free

> Импортируйте точки из CSV и запустите карту с каталогом без обязательного Google API-ключа.

Ключевая связка:

- OpenStreetMap/Leaflet;
- CSV dry run/import/update/export;
- Directory + Map;
- категории и территории;
- реальный поиск по расстоянию;
- Gutenberg и shortcode;
- простой административный UX.

## 1.4. Ключевое обещание Pro

> Создавайте несколько брендированных локаторов и показывайте ближайшую точку, которая оказывает нужную услугу и открыта сейчас.

Ключевая связка:

- Services;
- Smart Availability;
- Smart Ranking;
- Saved Locators;
- premium presets;
- собственные сохранённые presets;
- индивидуальные настройки каждого локатора.

## 1.5. Модель продукта

- Free — самостоятельный полноценный плагин для WordPress.org.
- Pro — add-on, требующий активную совместимую Free-версию.
- Free владеет локациями, категориями, территориями, индексом, REST API, импортом и базовым renderer.
- Pro расширяет Free через публичные контракты.
- Pro не создаёт второй независимый каталог точек.
- Отключение Pro не удаляет данные Pro.
- При отключении Pro локатор продолжает работать в базовом Free-режиме.

---

# 2. Аудит текущей сборки

## 2.1. Общая оценка

`1.3.2-beta29` — развитая beta-основа, но не production-ready.

Наиболее зрелые части:

- административный интерфейс;
- CPT и taxonomies;
- карта в редакторе точки;
- provider layer;
- CSV job pipeline;
- dry run и duplicate detection;
- индексная таблица;
- radius-capable SQL repository;
- REST pagination contract;
- тестовая база.

Наиболее отстающие части:

- целостный контракт данных Location;
- подробная карточка точки;
- frontend-каталог;
- серверная загрузка frontend;
- реальный Near me;
- Areas;
- локализация;
- несколько независимых локаторов;
- шаблоны;
- согласованность global settings и frontend.

## 2.2. Фактически реализовано

### Данные и администрирование

- CPT `bml_location`;
- taxonomy `bml_category`;
- плоская taxonomy `bml_city`;
- отдельные административные страницы;
- создание, редактирование, дублирование и удаление точки;
- draft/publish;
- ручной operational status;
- адрес, регион, страна, индекс;
- координаты;
- телефон;
- категории и города;
- карта и поиск адреса в редакторе;
- списки категорий и городов;
- category icon;
- dashboard и settings studio.

### Карты и frontend

- OpenStreetMap/Leaflet;
- опциональный Google Maps;
- fallback на OSM;
- Leaflet MarkerCluster;
- layouts `split`, `map`, `cards`;
- строка поиска;
- фильтры category/city;
- список карточек;
- popup;
- Gutenberg block;
- shortcode;
- REST API.

### Импорт и экспорт

- CSV upload;
- UTF-8/BOM handling;
- validation;
- dry run;
- batch processing;
- pause/resume/cancel;
- job state;
- duplicate detection;
- update по `external_id`;
- fallback fingerprint;
- CSV export;
- formula injection protection;
- sample CSV;
- import logging.

### Архитектура

- namespaced application/domain/infrastructure layer;
- legacy `BML_*` layer;
- repository + query + handler;
- отдельная индексная таблица;
- cache;
- provider registry;
- 29 test-файлов.

## 2.3. Подтверждённые блокеры

### P0-1. Несогласованный Location contract

Одни и те же данные трактуются по-разному:

- MetaRegistrar регистрирует email, website, мессенджеры, hours и services;
- CSV импорт сохраняет email, website и hours;
- индекс хранит email, website, hours, image и excerpt;
- редактор точки удаляет email, website, мессенджеры, hours, services и thumbnail;
- REST response принудительно возвращает эти поля пустыми.

Результат: данные могут существовать в базе, но быть удалены при ручном сохранении и не попасть на frontend.

### P0-2. Нет полноценной карточки точки

Текущая карточка списка показывает только часть данных.

Отсутствуют:

- полноценная компактная карточка;
- подробная выбранная карточка;
- email и website;
- фактические часы;
- изображение;
- описание;
- явные CTA;
- кнопка «Показать на карте»;
- согласованный popup/detail contract.

### P0-3. Неверный статус `Open now`

Текущий popup подписывает ручной статус `open` как `Open now`.

Это неверно: без структурированного расписания нельзя утверждать, что точка сейчас открыта.

До Pro:

- `open` отображать как `Active` или не выводить;
- `temporarily_closed` — `Temporarily closed`;
- `hidden` — не выводить на frontend.

`Open now` разрешён только после вычисления расписания.

### P0-4. Обязательные category и city скрывают точки

Repository содержит условия:

- `category_slug <> ''`;
- `city_slug <> ''`.

Точка без одного из значений исчезает из публичной выдачи.

Требование: опубликованной точке для показа нужны только:

- валидный статус;
- валидные координаты;
- отсутствие статуса `hidden`.

Категория и территория могут быть пустыми.

### P0-5. Frontend загружает всю выдачу

Frontend:

1. запрашивает первую страницу;
2. узнаёт `totalPages`;
3. параллельно загружает все остальные страницы;
4. хранит все точки в браузере;
5. выводит до 250 карточек.

Это отменяет преимущества серверной пагинации.

### P0-6. Near me не использует серверный radius

SQL repository уже умеет radius и distance.

Но frontend после геолокации:

- не передаёт `lat`, `lng`, `radius`, `orderby=distance`;
- не ограничивает выдачу;
- только сортирует уже загруженные точки в JavaScript.

### P0-7. Нет Google clustering

Leaflet clustering подключён.

Google provider не имеет завершённой совместимой кластеризации, хотя настройка показывается как общая.

### P0-8. Cities не соответствует продуктовой модели

Плоский City:

- не покрывает страну, область, землю, район;
- не поддерживает иерархию;
- ограничивает импорт;
- делает фильтр непереносимым между странами.

### P0-9. Настройки визуально есть, но не гарантированно действуют

`map_style` и `marker_color` участвуют в admin preview, но публичный frontend не использует их как единый подтверждённый contract.

### P0-10. Локализация не готова

- strings смешивают английский и русский;
- отсутствует единый English source;
- нет POT/PO/MO;
- нет подтверждённых EN/DE/UK/RU сборок;
- `block.json` остаётся на версии `1.0.0`;
- часть JS-строк имеет fallback text.

### P0-11. Нет сохранённых Locators и presets

Разные shortcode возможны только вручную.

Все экземпляры зависят от глобальных настроек.

Нет:

- именованных конфигураций;
- отдельного shortcode ID;
- per-Locator design;
- preset catalog;
- сохранения собственного preset.

### P0-12. Архитектурный переход не завершён

Legacy и новый слой одновременно владеют пересекающейся логикой.

Перед GA нужен один canonical path для:

- settings;
- search;
- REST;
- save Location;
- index;
- import;
- frontend rendering.

## 2.4. Вывод аудита

Главная задача — не добавление новых административных экранов.

Правильный порядок:

> Location contract → карточка → Areas → server-side frontend → Directory + Map → shortcode builder → localization → Free RC → Services/Schedules → Saved Locators/Presets → Pro RC.

---

# 3. Терминология и техническое именование

## 3.1. Публичные сущности

| EN | RU | UK | DE | Назначение |
|---|---|---|---|---|
| Location | Точка | Точка | Standort | Магазин, офис, филиал, сервис |
| Category | Категория | Категорія | Kategorie | Тип точки |
| Area | Территория | Територія | Gebiet | Страна, регион, город, район |
| Service | Услуга | Послуга | Leistung | Услуга в конкретной точке |
| Locator | Локатор | Локатор | Standortfinder | Опубликованная конфигурация |
| Layout | Компоновка | Компонування | Layout | Расположение карты, каталога и панели |
| Visual Preset | Шаблон оформления | Шаблон оформлення | Designvorlage | Безопасные параметры внешнего вида |

## 3.2. Рекомендуемое публичное имя

**NBH Business Locator – Store & Office Map**

Короткое имя:

**NBH Business Locator**

Vendor:

**Nebho.Agency**

## 3.3. Рекомендуемые идентификаторы до публичного релиза

| Объект | Идентификатор |
|---|---|
| Free plugin slug | `nbh-business-locator` |
| Pro plugin slug | `nbh-business-locator-pro` |
| Text domain | `nbh-business-locator` |
| PHP namespace | `Nebho\BusinessLocator` |
| Function prefix | `nbh_bl_` |
| Location CPT | `nbh_bl_location` |
| Locator CPT | `nbh_bl_locator` |
| Category taxonomy | `nbh_bl_category` |
| Area taxonomy | `nbh_bl_area` |
| Service taxonomy | `nbh_bl_service` |
| REST namespace | `nbh-business-locator/v1` |
| Main shortcode | `nbh_business_locator` |
| Gutenberg block | `nebho/business-locator` |
| Main option | `nbh_bl_settings` |
| DB version option | `nbh_bl_db_version` |

Переименование выполнить до публичного GA.

После публикации технические идентификаторы не менять без обязательной причины.

## 3.4. Обратная совместимость beta

В течение минимум двух minor-релизов поддерживать:

- `[business_map_locator]`;
- `[business_locator]`;
- параметр `city` как alias `area`;
- чтение `bml_*` meta/options;
- миграцию `bml_location`;
- миграцию `bml_category`;
- миграцию `bml_city`.

Legacy aliases не должны использоваться в новой документации.

---

# 4. Граница Free/Pro

## 4.1. Правила разделения

Free не ограничивается:

- количеством точек;
- количеством импортируемых строк искусственным paywall;
- наличием карты;
- основным каталогом и карточкой;
- базовым поиском;
- базовыми фильтрами;
- CSV import/export;
- доступностью интерфейса;
- переводами.

Pro продаёт:

- релевантность результата;
- операционную доступность;
- несколько управляемых конфигураций;
- расширенный brand control;
- сложные фильтры;
- автоматизацию.

## 4.2. Сравнительная матрица

Обозначения текущего состояния:

- `Да` — реализовано и подключено;
- `Частично` — пользовательский сценарий неполон;
- `Конфликт` — слои плагина противоречат друг другу;
- `Нет` — отсутствует.

| Возможность | beta29 | Free 1.0 | Pro 1.0 | Позже |
|---|---|---|---|---|
| Неограниченные точки | Да | Да | Да | — |
| CPT каталога | Да | Да | Расширяет | Agency roles |
| Категория | Да | Основная категория | Multi-category rules | Category groups |
| Cities | Да | Миграция | Alias only | Удаление legacy alias |
| Areas | Нет | Иерархические | Multi/preselected/locked | Геозоны при спросе |
| Services | Конфликт | Extension contract | Справочник и фильтр | Service groups |
| Адрес и координаты | Да | Да | Да | — |
| Телефон | Да | Да | Да | Call tracking |
| Email и website | Конфликт | Да | Да | Custom CTA |
| Изображение | Конфликт | Одно | Gallery | Media rules |
| Описание | Конфликт | Короткое и полное | Да | Custom fields |
| Текстовые часы | Конфликт | Да, без вычисления | Миграция в schedule | Parser helper |
| Ручной status | Да | Active/Temporarily closed/Hidden | Override schedule | Scheduled closure |
| Open now | Неверно | Нет | Да | Opens/closes soon |
| Компактная карточка | Частично | Да | Расширенная | Custom order |
| Подробная карточка | Нет | Да | Расширенная | Custom blocks |
| Popup | Частично | Да | По preset | — |
| Directory + Map | Частично | Обязательный основной layout | Расширенные фильтры | — |
| Split List + Map | Да | Да | Да | — |
| Map Only | Да | Да | Да | — |
| Directory Only | Нет | Да | Да | — |
| Поиск | Да | Название, адрес, postcode, area | Services/custom fields | Fuzzy search |
| Category filter | Да | Single select | Multi/locked/hidden | Rules |
| Area filter | City only | Иерархический | Dependent/multi | — |
| Near me | Частично | Server radius | Smart nearest | Travel time |
| Radius | Backend only | Да | Да | Custom sets |
| OSM/Leaflet | Да | Default | Да | Доп. providers |
| Google Maps | Да | BYO key | Да | Advanced styles |
| Clustering OSM | Да | Да | Да | — |
| Clustering Google | Нет | Да | Да | — |
| CSV dry run | Да | Да | Да | — |
| CSV import/update | Да | Да | Да | Remote sync |
| CSV export | Да | Да | Да | Scheduled export |
| Duplicate report | Да | Да | Да | Merge assistant |
| Shortcode | Да | Да | Да | — |
| Shortcode builder | Нет | Да | Да | Preset library |
| Gutenberg | Частично | Полные controls | Saved Locator selector | Editor preview |
| Default Locator | Global | Да | Да | — |
| Несколько ручных shortcode | Частично | Да | Да | — |
| Saved Locators | Нет | Нет | Без лимита | Agency library |
| Системные presets | Нет | 2 | 6 total | Новые по спросу |
| Custom Preset | Нет | Нет | Да | Import/export |
| Per-Locator design | Нет | Нет | Да | Brand kits |
| Localization | Нет | EN/DE/UK/RU | EN/DE/UK/RU | Community packs |
| REST read API | Да | Да | Pro fields/filters | Write API |
| Server pagination | Backend only | Да | Да | — |
| Accessibility | Частично | WCAG baseline | То же | Formal audit |
| Remote CSV sync | Нет | Нет | Не в 1.0 | Pro 1.1 |
| Analytics | Нет | Нет | Не в 1.0 | Pro 1.2 |
| Custom fields | Нет | Hooks | Не в 1.0 | Pro 1.3 |
| WooCommerce | Нет | Нет | Нет | Add-on 1.5 |

---

# 5. Функциональные требования Free 1.0

## 5.1. Управление точками

Администратор может:

- просматривать список;
- искать;
- фильтровать по status/category/area;
- сортировать;
- создавать;
- сохранять draft;
- публиковать;
- редактировать;
- дублировать;
- отправлять в корзину;
- массово менять status/category/area;
- перестраивать индекс;
- экспортировать выбранную выдачу.

## 5.2. Поля Location Free

### Обязательные для публикации

- `title`;
- `latitude`;
- `longitude`;
- `post_status=publish`;
- `operational_status != hidden`.

### Основные

- internal ID;
- `external_id`;
- title;
- short description/excerpt;
- full description;
- primary image;
- street address;
- postcode;
- area;
- region text для legacy/import;
- country text для legacy/import;
- latitude;
- longitude;
- phone;
- email;
- website;
- plain hours;
- operational status;
- primary category;
- sort order;
- updated timestamp.

### Operational status

- `active`;
- `temporarily_closed`;
- `hidden`.

Миграция:

- старое `open` → `active`;
- `temporarily_closed` без изменений;
- `hidden` без изменений.

### Не входят в UI Free 1.0

- services;
- structured schedule;
- gallery;
- messengers/social;
- custom fields;
- custom CTA.

Допускаются публичные hooks, но не «мёртвые» видимые настройки.

## 5.3. Категории

Категория содержит:

- name;
- slug;
- description;
- icon;
- marker color;
- sort order;
- active state.

Free 1.0:

- одна основная категория на Location;
- пустая категория разрешена;
- category filter;
- category icon на marker/card при наличии.

## 5.4. Areas

`Areas` — иерархическая taxonomy.

Пример:

```text
Germany
└── Bavaria
    └── Upper Palatinate
        └── Friedenfels
```

Area содержит:

- name;
- slug;
- parent;
- optional type;
- sort order;
- active state.

Area types:

- `country`;
- `region`;
- `city`;
- `district`;
- `custom`.

Free:

- Location назначается на одну leaf Area;
- поиск по ancestor должен находить дочерние Location;
- пустая Area разрешена;
- фильтр может показывать иерархию;
- label фильтра настраивается;
- старый `city` поддерживается как alias.

## 5.5. Редактор Location

Разделы:

1. Basic information.
2. Address and map.
3. Classification.
4. Contact information.
5. Display information.
6. Publication.

### Basic information

- name;
- short description;
- full description;
- image;
- operational status.

### Address and map

- address search;
- address;
- postcode;
- area;
- region/country fallback fields;
- coordinates;
- draggable marker;
- manual coordinate input;
- copy coordinates.

### Contact information

- phone;
- email;
- website.

### Display information

- plain hours;
- compact-card preview;
- detail-card preview.

### Save rules

- сохранение не удаляет неизвестные или Pro meta;
- каждое поле санитизируется по собственному типу;
- email проходит `sanitize_email`;
- URL проходит `esc_url_raw`;
- description допускает безопасный WordPress HTML;
- publish невозможен без координат;
- category и area не обязательны;
- thumbnail сохраняется;
- index обновляется после успешной транзакции данных.

## 5.6. Frontend layouts

### Layout A. Directory + Map

Основной layout Free 1.0 и обязательный сценарий.

Desktop:

- заголовок/описание опционально;
- toolbar;
- sidebar фильтров или горизонтальная панель;
- карта;
- количество результатов;
- сортировка;
- grid карточек 2–3 колонки;
- pagination или Load more;
- подробная карточка выбранной точки.

Tablet:

- toolbar в 1–2 строки;
- карта над каталогом или split;
- карточки 2 колонки.

Mobile:

- переключатель `List / Map`;
- filters drawer;
- карточки в одну колонку;
- detail как bottom sheet или полноэкранная panel;
- touch targets минимум 44×44 px.

### Layout B. Split List + Map

- список слева;
- карта справа;
- configurable list width;
- выбранная карточка синхронизируется с marker;
- список имеет собственную серверную pagination/load more.

### Layout C. Map Only

- toolbar опционально;
- карта;
- marker popup;
- detail panel;
- доступная ссылка на список результатов.

### Layout D. Directory Only

- toolbar;
- количество;
- сортировка;
- grid/list карточек;
- detail;
- кнопка Directions;
- без обязательной загрузки картографического provider до действия пользователя.

## 5.7. Toolbar и фильтры Free

Обязательные controls:

- текстовый поиск;
- category;
- area;
- radius;
- Near me;
- reset;
- count;
- sort.

### Текстовый поиск

Ищет по:

- title;
- address;
- postcode;
- Area name;
- category name;
- short description.

### Category

- single select;
- `All categories`;
- counts после применения других активных фильтров;
- locked initial category из shortcode поддерживается.

### Area

- single select;
- иерархическое отображение;
- `All areas`;
- выбор parent включает descendants;
- counts после применения других фильтров.

### Radius

- значения по умолчанию: 5, 10, 25, 50, 100;
- km/mi;
- применяется только при наличии origin;
- origin Free 1.0 — browser geolocation;
- отсутствие разрешения не ломает остальные filters.

### Reset

- сбрасывает пользовательские filters;
- не сбрасывает locked shortcode filters.

## 5.8. Компактная карточка

Free может выводить:

- image;
- category;
- title;
- address;
- Area;
- phone;
- website;
- plain hours;
- distance;
- temporarily closed badge;
- `Show on map`;
- `Directions`;
- `Details`.

Состав полей зависит от global display settings или shortcode attributes.

Карточка не должна показывать:

- `Open now`;
- service badges;
- пустые строки;
- сломанное изображение;
- URL как необработанный текст.

## 5.9. Подробная карточка

Free detail содержит:

- image;
- title;
- category;
- Area;
- full address;
- short/full description;
- phone;
- email;
- website;
- plain hours;
- operational notice;
- coordinates/map context;
- `Show on map`;
- `Directions`;
- close/back.

Desktop:

- side panel, modal или inline detail согласно layout;
- фокус переводится в detail;
- закрытие возвращает фокус в исходную card/marker.

Mobile:

- bottom sheet/full-screen panel;
- явная кнопка Back/Close;
- scroll lock;
- безопасное восстановление состояния.

## 5.10. Синхронизация карты и каталога

- фильтр обновляет map и directory;
- карта показывает точки текущей фильтрованной выборки;
- click card выделяет marker и центрирует карту;
- `Show on map` прокручивает/переключает к карте;
- click marker выделяет карточку или открывает detail;
- selected state одинаков для card, marker и detail;
- изменение page обновляет visible cards;
- markers могут загружаться отдельно в текущем bounds;
- несколько Locator на странице не влияют друг на друга.

## 5.11. Сортировка

Free:

- relevance;
- title A–Z;
- distance при origin.

Default:

- при поиске — relevance;
- при Near me — distance;
- без search/origin — configured sort order, затем title.

## 5.12. Empty, loading и error states

Обязательны:

- skeleton/loading;
- no results;
- no locations configured;
- geolocation denied;
- provider failed;
- request failed;
- too many markers;
- invalid shortcode configuration.

Каждый state:

- переводим;
- имеет `role`/live region по необходимости;
- не оставляет пустой контейнер;
- предлагает безопасное действие.

## 5.13. Состояние, URL и кнопка Back

- выбранные filters, page и Location не теряются при открытии/закрытии detail;
- browser Back возвращает предыдущее состояние Locator, а не сбрасывает выдачу;
- browser Forward повторно восстанавливает его;
- несколько Locator на странице используют независимые state keys;
- перезагрузка страницы восстанавливает состояние, если включена URL synchronization;
- locked/hidden shortcode filters нельзя подменить query string;
- значения из URL проходят ту же validation, что REST и shortcode;
- shareable URL можно включить в global/Locator settings;
- при отключённой URL synchronization используется `history.state` без загрязнения адресной строки.

---

# 6. Shortcode, Gutenberg и публикация

## 6.1. Канонический shortcode

```text
[nbh_business_locator]
```

## 6.2. Free shortcode attributes

| Attribute | Тип | Пример | Назначение |
|---|---|---|---|
| `layout` | enum | `directory_map` | Layout |
| `category` | slug/CSV | `pharmacy` | Предустановленная category |
| `area` | slug/CSV | `kyiv` | Предустановленная Area |
| `category_mode` | enum | `visible` | visible/locked/hidden |
| `area_mode` | enum | `visible` | visible/locked/hidden |
| `search` | bool | `1` | Search control |
| `filters` | bool | `1` | Filter controls |
| `geolocation` | bool | `1` | Near me |
| `radius` | number | `25` | Default radius |
| `unit` | enum | `km` | km/mi |
| `height` | int | `620` | Map height |
| `list_width` | int | `38` | Split width |
| `per_page` | int | `24` | Cards per page |
| `preset` | enum | `clean` | Free system preset |
| `show_image` | bool | `1` | Card field |
| `show_address` | bool | `1` | Card field |
| `show_phone` | bool | `1` | Card field |
| `show_email` | bool | `0` | Card/detail field |
| `show_website` | bool | `1` | Card/detail field |
| `show_hours` | bool | `1` | Plain hours |
| `show_directions` | bool | `1` | Directions CTA |

CSV в `category`/`area` обрабатывается как OR.

В Free builder UI допускается один term, но renderer и contract должны быть готовы к списку.

## 6.3. Примеры

```text
[nbh_business_locator layout="directory_map"]
```

```text
[nbh_business_locator category="service-center" area="kyiv" category_mode="locked" area_mode="hidden"]
```

```text
[nbh_business_locator layout="directory" search="1" filters="1" per_page="18" preset="compact"]
```

## 6.4. Shortcode Builder Free

Отдельный admin screen или modal:

- layout preview;
- category selector;
- Area tree selector;
- filter mode;
- controls toggles;
- radius/unit;
- fields toggles;
- Free preset;
- responsive preview;
- generated shortcode;
- copy button;
- validation.

Builder не создаёт постоянную сущность.

Shortcode должен быть переносимым и полностью описывать выбранную конфигурацию.

## 6.5. Gutenberg Free

Inspector controls:

- layout;
- category;
- Area;
- filter modes;
- search;
- filters;
- Near me;
- radius;
- per page;
- preset;
- display fields.

Запрещено требовать ручной ввод slug, если term можно выбрать.

Editor показывает:

- безопасный preview;
- summary конфигурации;
- понятный fallback, если preview недоступен.

## 6.6. Несколько экземпляров

На одной странице могут находиться несколько Locator.

Требования:

- уникальный instance ID;
- scoped DOM selectors;
- scoped events;
- отдельный AbortController;
- отдельное filter state;
- отсутствие глобальных CSS ID;
- один раз загружаемые provider assets;
- корректная работа одинаковых и разных presets.

---

# 7. Visual system

## 7.1. Разделение Layout и Preset

Layout отвечает за:

- расположение карты;
- расположение каталога;
- toolbar;
- detail;
- responsive transitions.

Visual Preset отвечает за:

- colors;
- typography;
- radius;
- border;
- shadow;
- spacing;
- card density;
- image ratio;
- button style;
- popup/detail style;
- marker appearance;
- field presentation.

Layout и Preset комбинируются независимо.

## 7.2. Free presets

### Clean Locator

- нейтральный универсальный стиль;
- comfortable density;
- средние изображения;
- заметные CTA;
- подходит для большинства компаний.

### Compact Directory

- высокая плотность;
- изображения опциональны;
- небольшой vertical rhythm;
- подходит для больших сетей.

## 7.3. Pro presets

Дополнительно:

### Retail Cards

- крупное изображение;
- hours/status;
- заметные CTA;
- grid-first.

### Service Network

- services;
- availability;
- distance;
- functional information first.

### Corporate Branches

- строгий стиль;
- минимум декора;
- адреса и контакты.

### Minimal Map

- карта — главный элемент;
- компактный popup;
- detail drawer.

Всего в Pro 1.0: шесть системных presets, включая два Free.

## 7.4. Custom Preset Pro

Пользователь может:

- создать из системного preset;
- изменить;
- сохранить;
- переименовать;
- дублировать;
- назначить Locator;
- удалить, если не используется;
- восстановить system defaults.

Custom Preset хранит только allowlisted design tokens.

Запрещено хранить:

- PHP;
- JavaScript;
- произвольный template path;
- небезопасный HTML.

Custom CSS:

- не является основным UX;
- находится в Advanced;
- доступен только capability `unfiltered_html` или отдельной capability;
- проходит documented risk warning;
- не экспортируется в WordPress.org Free.

---

# 8. Free data model

## 8.1. Source of truth

Source of truth:

- WordPress posts;
- taxonomies;
- post meta.

Search index:

- производная структура;
- может быть полностью перестроена;
- не является единственным местом хранения пользовательских данных.

## 8.2. Location CPT

`nbh_bl_location`

Supports:

- title;
- editor;
- excerpt;
- thumbnail;
- revisions;
- custom-fields только для совместимости, не как основной UI.

`public=false`, но REST/frontend читают через собственный endpoint.

## 8.3. Location meta

| Key | Тип |
|---|---|
| `nbh_bl_external_id` | string |
| `nbh_bl_address` | string |
| `nbh_bl_postcode` | string |
| `nbh_bl_region` | string |
| `nbh_bl_country` | string |
| `nbh_bl_lat` | number |
| `nbh_bl_lng` | number |
| `nbh_bl_phone` | string |
| `nbh_bl_email` | string |
| `nbh_bl_website` | string |
| `nbh_bl_plain_hours` | string |
| `nbh_bl_operational_status` | enum |
| `nbh_bl_sort_order` | integer |
| `nbh_bl_import_fingerprint` | string |
| `nbh_bl_import_job_id` | integer |

## 8.4. Search index

Главная таблица:

`{$wpdb->prefix}nbh_bl_location_index`

Минимальные поля:

- location_id;
- title;
- excerpt;
- address;
- postcode;
- region;
- country;
- latitude;
- longitude;
- phone;
- email;
- website;
- plain_hours;
- image_id;
- operational_status;
- visibility;
- search_text;
- sort_order;
- updated_at.

Для связей использовать relation table:

`{$wpdb->prefix}nbh_bl_location_terms`

Поля:

- location_id;
- taxonomy;
- term_id;
- is_primary;

Индексы:

- unique `(location_id, taxonomy, term_id)`;
- `(taxonomy, term_id, location_id)`;
- `(location_id, taxonomy, is_primary)`.

Это устраняет ограничение текущих `category_slug`/`city_slug` и готовит Pro multi-select.

## 8.5. Area descendants

Допустимые реализации:

- кешированный список descendant IDs;
- materialized path;
- отдельная closure table.

Для масштаба 2–5 000 точек достаточно кешированного descendant set с invalidation при изменении taxonomy.

## 8.6. Indexing

Index обновляется при:

- save Location;
- change status;
- change thumbnail;
- change category;
- change Area;
- import row commit;
- deletion;
- restore;
- migration.

Admin action:

- Diagnose;
- Rebuild;
- progress;
- cancel;
- count comparison;
- error report.

---

# 9. REST API и поиск

## 9.1. Общие правила

- namespace versioned;
- GET public;
- write endpoints не входят в 1.0;
- параметры имеют schema;
- неизвестные параметры игнорируются или возвращают 400 по документированной политике;
- одинаковая структура errors;
- `X-WP-Total` и `X-WP-TotalPages`;
- cache key учитывает все filters, locale, fields mode и Pro extensions;
- cache invalidation после изменения данных.

## 9.2. Endpoints Free

### Search cards

```text
GET /wp-json/nbh-business-locator/v1/locations
```

Параметры:

- `search`;
- `category`;
- `area`;
- `page`;
- `per_page`;
- `orderby`;
- `order`;
- `lat`;
- `lng`;
- `radius`;
- `unit`;
- `bounds`;
- `view=card|marker`;

### Location detail

```text
GET /wp-json/nbh-business-locator/v1/locations/{id}
```

### Filters

```text
GET /wp-json/nbh-business-locator/v1/filters
```

Возвращает:

- categories;
- Areas tree/flat representation;
- result counts;
- active filter availability.

### Health/diagnostics

Публичный endpoint не должен раскрывать конфигурацию или ключи.

Admin diagnostics требует capability.

## 9.3. Card response

```json
{
  "id": 123,
  "title": "Central Office",
  "excerpt": "Short description",
  "address": "Main Street 1",
  "postcode": "01001",
  "lat": 50.4501,
  "lng": 30.5234,
  "phone": "+380...",
  "website": "https://example.com",
  "plainHours": "Mon–Fri 09:00–18:00",
  "operationalStatus": "active",
  "image": {
    "url": "https://...",
    "alt": "Central Office"
  },
  "category": {
    "id": 10,
    "name": "Office",
    "slug": "office",
    "icon": "https://..."
  },
  "area": {
    "id": 20,
    "name": "Kyiv",
    "slug": "kyiv",
    "path": ["Ukraine", "Kyiv"]
  },
  "distance": 3.24
}
```

## 9.4. Detail response

Дополнительно:

- content/safe HTML;
- email;
- complete website;
- complete address;
- directions coordinates;
- all public Free fields;
- Pro extension namespace при активном Pro.

## 9.5. Radius search

Обязательная логика:

1. принять origin;
2. построить bounding box;
3. отфильтровать кандидатов по bounds;
4. вычислить Haversine distance;
5. применить `distance <= radius`;
6. сортировать по distance;
7. вернуть distance в km/mi.

Near me не может использовать client-only sorting как основной источник истины.

## 9.6. Loading strategy

Запрещено автоматически загружать все REST pages.

Directory:

- 12–36 cards на request;
- default 24;
- server pagination или Load more.

Map:

- marker payload без тяжёлых detail fields;
- bounds-based loading;
- debounce 250–400 ms;
- abort предыдущего request;
- лимит marker response;
- clustering;
- понятный message при слишком широком bounds.

Detail:

- lazy-load по ID;
- client cache на время жизни instance.

## 9.7. Search rules

- регистронезависимый;
- Unicode-safe;
- partial matching;
- title получает больший weight;
- затем address/postcode;
- затем Area/category/description;
- SQL placeholders only;
- search_text rebuild при изменении связанных terms.

## 9.8. Filters counts

Counts:

- учитывают текущий search;
- category count учитывает активную Area;
- Area count учитывает активную category;
- locked filters участвуют в count;
- hidden locations не считаются.

---

# 10. CSV import/export Free

## 10.1. Роль

CSV — главный acquisition hook Free.

Основной сценарий:

1. скачать template;
2. заполнить;
3. загрузить;
4. выполнить dry run;
5. проверить create/update/error;
6. запустить import;
7. открыть Locator.

## 10.2. CSV schema Free 1.0

### Required

- `title` или `name`;
- `lat`;
- `lng`.

### Recommended

- `external_id`;
- `address`;
- `area`;
- `category`.

### Optional

- `description`;
- `excerpt`;
- `postcode`;
- `region`;
- `country`;
- `phone`;
- `email`;
- `website`;
- `plain_hours`;
- `operational_status`;
- `status`;
- `visible`;
- `image_url`.

### Legacy aliases

- `city` → `area`;
- `hours` → `plain_hours`;
- `open` status → `active`.

## 10.3. Area import format

Поддержать:

```text
Germany > Bavaria > Upper Palatinate > Friedenfels
```

Или отдельный slug:

```text
friedenfels
```

Параметр import:

- create missing Areas;
- reject missing Areas;
- map column to existing Areas.

## 10.4. Matching

Приоритет:

1. `external_id`;
2. import fingerprint;
3. optional explicit duplicate decision.

Fingerprint:

- normalized title;
- normalized address;
- coordinates rounded to 6 decimals.

CSV с повторяющимся `external_id` не импортируется без решения пользователя.

## 10.5. Update policy

Перед import пользователь выбирает:

- update non-empty only;
- overwrite mapped fields;
- create only;
- update only.

Пустая CSV ячейка:

- не должна молча удалять существующее значение при `non-empty only`;
- удаляет значение только в explicit overwrite mode.

## 10.6. Image URL

Если включён remote image import:

- opt-in checkbox;
- only http/https;
- block private/reserved IP;
- validate content type;
- size limit;
- timeout;
- download count limit;
- sideload через WordPress APIs;
- error не откатывает всю job;
- повторный import не создаёт duplicate attachment при неизменном URL.

Если безопасная реализация не готова к RC, `image_url` исключить из 1.0 UI и template, а не оставлять полуфункциональным.

## 10.7. Import UX

- sample Basic;
- sample Full;
- inline field reference;
- delimiter detection;
- encoding validation;
- row preview;
- dry run summary;
- error CSV;
- progress;
- pause;
- resume;
- cancel;
- cleanup expired jobs;
- final report;
- link to imported Location list.

## 10.8. Export

- current filters;
- selected fields;
- UTF-8 BOM option;
- comma/semicolon/tab;
- status filter;
- category;
- Area;
- chunked streaming;
- formula injection protection;
- no memory loading of all posts.

---

# 11. Map providers и внешние сервисы

## 11.1. Provider interface

Provider обязан поддерживать:

- init/destroy;
- add/update/remove markers;
- clear markers;
- focus marker;
- fit bounds;
- user location;
- clustering;
- popup/detail trigger;
- error state;
- attribution.

## 11.2. OpenStreetMap/Leaflet

- default provider;
- API key не требуется;
- Leaflet и MarkerCluster поставляются локально;
- attribution обязательна;
- tile provider URL настраивается;
- документация объясняет, что владелец сайта отвечает за соблюдение политики выбранного tile provider;
- плагин не должен скрывать attribution;
- URL валидируется;
- внешние scripts не загружаются с CDN автоматически.

Стандартный OSM tile endpoint нельзя позиционировать как безлимитный коммерческий CDN.

## 11.3. Geocoding

Geocoding используется только в admin.

Требования:

- explicit action;
- debounce/rate limit;
- cache;
- user agent/referer согласно provider policy;
- no bulk geocoding через публичный Nominatim;
- disclosure в privacy/external services;
- возможность отключить;
- provider adapter.

CSV Free 1.0 требует координаты и не выполняет массовый geocoding автоматически.

## 11.4. Google Maps

- BYO API key;
- key никогда не возвращается в diagnostics;
- domain restrictions описаны;
- failed load показывает понятный fallback;
- Google marker clustering обязателен до GA;
- Google-specific features не должны ломать OSM renderer.

## 11.5. Directions

Free:

- URL строится по координатам;
- provider выбирается global setting или auto;
- target `_blank`;
- `rel="noopener noreferrer"`;
- translated accessible label.

---

# 12. Pro 1.0

## 12.1. Services

`nbh_bl_service`:

- name;
- slug;
- description;
- icon;
- sort order;
- active state.

Location:

- multiple services.

Frontend:

- single или multi select;
- service badges;
- preselected service;
- locked/hidden service;
- service counts;
- filter работает совместно с category/Area/radius.

## 12.2. Structured schedules

### Weekly schedule

Для каждого дня:

- closed;
- one or multiple intervals;
- overnight interval.

### Exceptions

- specific date;
- closed;
- custom intervals;
- label/reason.

### Timezone

- per Location;
- default site timezone;
- IANA timezone ID;
- запрещено вычислять по browser timezone без явной логики.

### Computed status

- `open_now`;
- `closed`;
- `opens_at`;
- `closes_at`;
- `temporarily_closed`;
- `unknown`.

Priority:

1. hidden;
2. temporarily closed;
3. date exception;
4. weekly schedule;
5. unknown.

## 12.3. Smart Availability

Frontend:

- filter `Open now`;
- status badge;
- next opening/closing time;
- timezone-correct calculation;
- status не кешируется дольше допустимого interval boundary.

## 12.4. Smart Ranking

Базовые factors:

1. service match — обязательный фильтр;
2. availability;
3. distance;
4. configured priority;
5. title tie-breaker.

Default modes:

- nearest;
- nearest open;
- recommended.

Ranking должен быть детерминированным и документированным.

## 12.5. Saved Locators

`nbh_bl_locator` — непубличная configuration entity.

Поля:

- name;
- status;
- layout;
- category defaults;
- Area defaults;
- services defaults;
- filter modes;
- radius;
- sort;
- visible controls;
- field visibility;
- system/custom preset;
- map provider override;
- map center/zoom behavior;
- pagination;
- empty-state text;
- custom CTA;
- updated timestamp.

Функции:

- create;
- edit;
- preview;
- duplicate;
- publish;
- archive;
- delete;
- copy shortcode;
- usage count;
- default Locator.

Shortcode:

```text
[nbh_business_locator id="123"]
```

Gutenberg:

- выбрать Saved Locator;
- открыть edit;
- preview;
- fallback summary.

Изменение Saved Locator обновляет все места его использования.

## 12.6. Per-Locator settings

Pro override:

- layout;
- preset;
- category/Area/services;
- visible/locked/hidden filters;
- display fields;
- radius;
- sort;
- map center;
- provider;
- CTA;
- empty state.

Global settings остаются fallback.

## 12.7. Custom Presets

См. раздел 7.

Preset может использоваться несколькими Locators.

Перед удалением:

- показать usage count;
- предложить replacement;
- не ломать опубликованный Locator.

## 12.8. Gallery и communication channels

Pro detail может включать:

- gallery;
- WhatsApp;
- Telegram;
- Viber;
- Facebook;
- Instagram;
- LinkedIn;
- TikTok;
- custom CTA.

Каждый channel:

- отдельный sanitized field;
- visibility toggle;
- accessible label;
- безопасный URL builder.

## 12.9. Free/Pro compatibility

Pro проверяет:

- Free installed;
- Free active;
- compatible version;
- required API contracts.

При несовместимости:

- admin notice;
- Pro feature bootstrap не запускается;
- frontend Free продолжает работать;
- fatal error отсутствует.

## 12.10. Graceful downgrade

При отключении Pro:

- schedules/services/presets/Locators не удаляются;
- `[nbh_business_locator id="123"]` использует сохранённый базовый snapshot;
- Pro filters игнорируются с безопасным fallback;
- basic Location card работает;
- admin сообщает, какие функции временно недоступны.

---

# 13. Admin information architecture

## 13.1. Free menu

```text
NBH Business Locator
├── Dashboard
├── Locations
├── Categories
├── Areas
├── Import / Export
├── Shortcode Builder
├── Display
├── Map Providers
├── Diagnostics
└── Help
```

## 13.2. Pro additions

```text
├── Services
├── Schedules
├── Locators
├── Presets
└── License
```

## 13.3. Dashboard

- published/draft/hidden counts;
- Locations without coordinates;
- Locations without category;
- Locations without Area;
- index health;
- provider health;
- last import;
- quick actions;
- shortcode/block usage guidance;
- Pro upsell только контекстно и без блокировки Free workflow.

## 13.4. Settings ownership

Global settings:

- default provider;
- fallback center;
- units;
- default radius;
- default layout;
- default Free preset;
- default display fields;
- caching;
- data retention;
- privacy/external services.

Per-Locator settings принадлежат Pro.

---

# 14. Internationalization

## 14.1. Languages

Bundled release:

- English;
- German;
- Ukrainian;
- Russian.

WordPress locale:

- Ukrainian — `uk_UA`.

## 14.2. Source language

Все исходные строки — English.

Запрещено:

- русский текст внутри `__()` как source;
- hardcoded UI text в JS;
- конкатенация переводимых предложений;
- самостоятельное plural formatting;
- смешение `City` и `Area` после migration.

## 14.3. Artifacts

- POT;
- PO;
- MO;
- JS translation JSON при необходимости;
- переведённый `readme`/Help минимум для EN и DE;
- automated scan untranslated strings.

## 14.4. User data translations

Free 1.0:

- совместимость с WordPress locale;
- отсутствие конфликтов с WPML/Polylang;
- documented integration hooks.

Native multilingual Location data не входит в 1.0.

---

# 15. Accessibility

Цель: WCAG 2.2 AA для UI, создаваемого плагином.

Обязательно:

- keyboard access;
- logical tab order;
- visible focus;
- 44×44 touch targets;
- labels;
- screen-reader text;
- `aria-live` для result count/loading;
- не полагаться только на цвет;
- contrast;
- Escape закрывает modal/drawer;
- focus trap в modal;
- focus restore;
- accessible alternative list для map;
- reduced motion;
- marker action доступен через directory;
- `role="application"` не использовать без полной keyboard model;
- screen reader не должен обходить сотни скрытых markers.

Map не считается единственным способом получить информацию.

---

# 16. Security, privacy и data lifecycle

## 16.1. Security

- capability checks;
- nonce для admin mutations;
- sanitize input;
- escape output late;
- prepared SQL;
- allowlisted enums;
- REST validation;
- no secrets in HTML/logs;
- CSV upload MIME/size validation;
- path isolation;
- scheduled cleanup;
- formula injection protection;
- SSRF protection для remote media;
- no executable uploads;
- no arbitrary template paths;
- no unsafe unserialize.

## 16.2. Capabilities

Минимум:

- `nbh_bl_view_locations`;
- `nbh_bl_edit_locations`;
- `nbh_bl_publish_locations`;
- `nbh_bl_delete_locations`;
- `nbh_bl_manage_terms`;
- `nbh_bl_manage_import`;
- `nbh_bl_manage_settings`;
- `nbh_bl_view_diagnostics`;
- Pro: `nbh_bl_manage_locators`;
- Pro: `nbh_bl_manage_presets`.

## 16.3. Privacy

Документировать:

- map tile requests;
- geocoding requests;
- Google Maps;
- browser geolocation;
- directions links;
- IP/referrer exposure внешним providers;
- отсутствие собственной telemetry по умолчанию.

Browser geolocation:

- только после действия или явного consent;
- не сохраняется сервером Free;
- denied state безопасен.

## 16.4. Uninstall

Default:

- данные сохраняются.

Опция:

- explicit `Delete all data on uninstall`;
- warning;
- capability;
- uninstall удаляет только данные плагина;
- Pro data удаляется только при отдельном подтверждённом policy.

---

# 17. Performance

## 17.1. Acceptance datasets

- 10 Locations;
- 500 Locations;
- 2 000 Locations;
- 5 000 Locations.

## 17.2. Server targets

Reference environment:

- WordPress current and previous major;
- PHP 8.1/8.2/8.3;
- MySQL 8 or MariaDB supported by WordPress;
- no persistent object cache baseline.

Targets for 500 points:

- cached filter/search API p95 ≤ 300 ms;
- uncached simple search p95 ≤ 700 ms;
- no unbounded `WP_Query` meta scan;
- no request returning all detail fields.

Targets are release gates on reference environment, не универсальная гарантия для любого hosting.

## 17.3. Frontend targets

- initial directory request ≤ configured per_page;
- no automatic all-pages loop;
- marker payload minimized;
- filter debounce;
- request abort;
- assets loaded only when Locator exists;
- provider scripts loaded once;
- no duplicate initialization;
- no layout shift from missing fixed map height;
- image lazy loading;
- width/height attributes.

## 17.4. Cache

Cache key включает:

- locale;
- filters;
- bounds;
- origin/radius;
- sort;
- page/per page;
- response mode;
- Pro extensions;
- data version.

Invalidation:

- version bump key предпочтительнее массового удаления unknown keys.

---

# 18. Extensibility

Free предоставляет documented hooks:

- register provider;
- modify Location response;
- add searchable field;
- modify card fields;
- modify detail fields;
- register directions provider;
- modify import columns;
- validate import row;
- after Location import;
- before/after search query;
- register layout;
- register preset tokens;
- Pro extension contract.

Hooks:

- versioned;
- documented;
- covered smoke tests;
- не открывают secret data;
- не требуют правки core files.

---

# 19. Testing

## 19.1. Automated

### Unit

- value objects;
- distance/radius;
- Area descendants;
- query validation;
- response DTO;
- schedule engine;
- Smart Ranking;
- CSV mapper;
- duplicate matching;
- formula escaping;
- preset validation.

### Integration

- activation/migrations;
- legacy data migration;
- Location save;
- no data deletion on save;
- index sync;
- REST filters;
- pagination;
- radius;
- empty category/Area;
- import job lifecycle;
- cache invalidation;
- Pro activation/deactivation.

### JS

- multiple instances;
- filters;
- abort;
- pagination/load more;
- List/Map mobile switch;
- card-marker-detail sync;
- focus management;
- URL/history state;
- Near me request params.

### E2E

- first Locator;
- CSV to Locator;
- builder to shortcode;
- Gutenberg;
- Directory + Map;
- Location detail;
- Areas;
- Pro Service/Open now;
- Saved Locator;
- downgrade.

## 19.2. Environment matrix

- WordPress minimum supported;
- WordPress latest;
- WordPress previous major;
- PHP 8.1;
- PHP 8.2;
- PHP 8.3;
- MySQL 8;
- supported MariaDB;
- default theme;
- block theme;
- Elementor smoke test;
- OSM;
- Google Maps;
- single site;
- multisite smoke test;
- RTL smoke test.

## 19.3. Manual acceptance

- desktop Chrome/Firefox/Edge/Safari;
- Android Chrome;
- iOS Safari;
- keyboard only;
- screen reader smoke test;
- 200% zoom;
- reduced motion;
- slow network;
- provider failure;
- geolocation denied;
- no results;
- 500/2 000/5 000 datasets.

---

# 20. Acceptance criteria

## 20.1. Location data

1. Сохранение Location не удаляет email, website, image, hours или Pro meta.
2. Точка без category отображается.
3. Точка без Area отображается.
4. Hidden точка не отображается.
5. Temporarily closed отображается с корректным badge.
6. Free не показывает `Open now`.

## 20.2. Directory + Map

1. Layout доступен в Free.
2. Search/filter одновременно обновляют directory и map.
3. Есть result count.
4. Есть pagination или Load more.
5. Card содержит настроенные поля.
6. `Show on map` выделяет marker.
7. Marker открывает соответствующую Location.
8. Detail содержит полную Free-информацию.
9. Mobile имеет List/Map и filters drawer.
10. Keyboard flow завершён.

## 20.3. Near me

1. После consent frontend отправляет coordinates.
2. REST применяет radius.
3. Результат сортируется по server distance.
4. Точки за radius отсутствуют.
5. Distance отображается в выбранных units.
6. Denied geolocation не ломает Locator.

## 20.4. Shortcode

1. Builder создаёт валидный shortcode.
2. Category и Area выбираются из UI.
3. Preset filters работают.
4. Locked filter нельзя сбросить.
5. Hidden filter влияет на выдачу, но не показывается.
6. Два shortcode на странице независимы.

## 20.5. CSV

1. Basic sample проходит dry run.
2. Full sample проходит dry run.
3. Повторный external_id обновляет, а не дублирует.
4. Ошибочная строка попадает в report.
5. Pause/resume не повторяет committed rows.
6. Cancel останавливает job.
7. Export безопасен от formula injection.
8. `city` импортируется как legacy alias Area.

## 20.6. Pro

1. Service filter возвращает только matching Locations.
2. Open now учитывает timezone и exceptions.
3. Saved Locator имеет стабильный shortcode.
4. Изменение Saved Locator обновляет все placements.
5. Custom Preset применяется только к назначенным Locators.
6. Отключение Pro не вызывает fatal error и сохраняет базовый вывод.

---

# 21. Release gates

## Gate A. Scope lock

- утверждена матрица Free/Pro;
- утверждены технические IDs;
- утверждены 4 layouts;
- утверждены 6 presets;
- новые функции не добавляются без change request.

## Gate B. Architecture

- один canonical Location save path;
- один search path;
- один REST contract;
- legacy isolated;
- migrations reversible на staging backup;
- index rebuild работает.

## Gate C. Free feature complete

- Directory + Map;
- detail;
- Areas;
- radius;
- builder;
- Gutenberg;
- CSV;
- translations.

## Gate D. Free RC quality

- automated tests green;
- Plugin Check: 0 errors, 0 warnings либо documented accepted exceptions;
- PHPCS;
- PHPStan agreed level;
- accessibility checklist;
- performance report;
- privacy/readme;
- source → ZIP parity.

## Gate E. Pro feature complete

- Services;
- schedules;
- Smart Availability;
- Saved Locators;
- presets;
- compatibility/downgrade.

## Gate F. Joint GA

- clean install;
- upgrade beta;
- Free only;
- Free + Pro;
- Pro disable/enable;
- multisite smoke;
- final evidence;
- signed hashes;
- release notes;
- rollback package.

---

# 22. Roadmap до production

Оценка для одного сильного WordPress-разработчика с частичным QA/design:

- Free 1.0: 7–9 недель;
- Pro 1.0 после стабильного Free core: ещё 3–5 недель;
- совместный production release: 10–12 недель.

Для двух разработчиков и выделенного QA:

- 7–9 календарных недель при параллельной работе после фиксации contracts.

## Iteration 0. Product lock — 2–3 дня

- утвердить документ;
- freeze scope;
- утвердить naming;
- baseline repository;
- зафиксировать current ZIP hash;
- issue map;
- release branch policy;
- test baseline.

Exit:

- нет спорных сущностей City/Area, Layout/Preset, Map/Locator.

## Iteration 1. Architecture and migration — 5–7 дней

- canonical namespace;
- legacy isolation;
- technical rename;
- migration bml → nbh_bl;
- version synchronization;
- canonical settings;
- canonical save service;
- migration tests.

Exit:

- clean install и upgrade сохраняют данные.

## Iteration 2. Location contract and detail — 5–7 дней

- Free fields;
- editor sections;
- image;
- descriptions;
- email/website/hours;
- operational status rename;
- REST card/detail DTO;
- compact card;
- detail;
- popup alignment;
- no destructive save.

Exit:

- все Free fields проходят editor → index → REST → frontend.

## Iteration 3. Areas and index — 5–7 дней

- hierarchical Areas;
- City migration;
- ancestor filtering;
- relation index;
- points without terms;
- filter counts;
- CSV Area path;
- tests.

Exit:

- City legacy и новые Areas работают без потери данных.

## Iteration 4. Server-side frontend — 5–7 дней

- remove all-pages loading;
- paginated cards;
- marker view;
- bounds loading;
- radius request;
- distance sorting;
- request abort;
- OSM/Google clustering;
- multiple instances.

Exit:

- 2 000 points не загружаются полностью в browser.

## Iteration 5. Directory + Map UX — 5–7 дней

- primary layout;
- directory grid;
- filters;
- counts;
- sort;
- Load more/pagination;
- card-marker-detail sync;
- mobile List/Map;
- drawer/bottom sheet;
- empty/error/loading states;
- accessibility.

Exit:

- референсный сценарий «фильтры + карта + карточки точек» принимается целиком.

## Iteration 6. Publication tools — 4–6 дней

- shortcode contract;
- builder;
- category/Area term selector;
- locked/hidden modes;
- Gutenberg controls;
- four layouts;
- two Free presets;
- responsive preview.

Exit:

- пользователь публикует несколько разных ручных Locators без кода.

## Iteration 7. CSV and release quality — 5–7 дней

- schema update;
- Area aliases;
- update policies;
- error report;
- sample files;
- security;
- Help;
- external service docs;
- EN source;
- DE/UK/RU;
- Plugin Check/PHPCS/PHPStan/tests.

Exit:

- Free 1.0 RC.

## Iteration 8. Free RC/GA — 4–6 дней

- environment matrix;
- performance;
- accessibility;
- visual QA;
- upgrade rehearsal;
- WordPress.org assets;
- release ZIP;
- parity;
- evidence;
- owner acceptance.

Exit:

- Free 1.0 GA.

## Iteration 9. Pro Services and Availability — 7–10 дней

- Services;
- UI;
- schedule model;
- exceptions;
- timezone;
- status engine;
- Open now;
- Smart Ranking;
- filters;
- tests.

Exit:

- nearest suitable open Location работает end-to-end.

## Iteration 10. Pro Locators and Presets — 7–10 дней

- Saved Locators;
- per-Locator settings;
- four premium presets;
- Custom Preset;
- Gutenberg selector;
- shortcode ID;
- preview;
- compatibility guard;
- license integration;
- downgrade snapshot.

Exit:

- несколько брендированных Locators управляются централизованно.

## Iteration 11. Joint Pro GA — 5–7 дней

- Free/Pro combinations;
- license states;
- performance;
- accessibility;
- upgrade/downgrade;
- docs;
- sales demo;
- evidence;
- owner acceptance.

Exit:

- Free 1.0 + Pro 1.0 production.

---

# 23. Post-launch roadmap

## 1.0.x — первые 4–8 недель

- compatibility fixes;
- import diagnostics;
- provider edge cases;
- translation corrections;
- accessibility fixes;
- template refinements;
- no large new features.

## Pro 1.1 — Remote CSV Sync

- authenticated URL;
- scheduled sync;
- manual run;
- last successful state;
- change preview;
- failure notification;
- safe missing-row policy;
- source ownership.

## Pro 1.2 — Privacy-aware Analytics

- searches;
- no-result queries;
- filter usage;
- selected Locations;
- directions clicks;
- opt-in;
- retention;
- export/delete.

## Pro 1.3 — Custom Fields Lite

- allowlisted types;
- text;
- URL;
- number;
- select;
- badge;
- field visibility;
- card/detail placement.

## Pro 1.4 — Agency Toolkit

- import/export Locator configs;
- import/export presets;
- brand kits;
- staging-to-production transfer;
- capability presets.

## Add-on 1.5 — WooCommerce bridge

- pickup Location selector;
- order meta;
- checkout compatibility;
- no inventory logic in first add-on release.

## 2.0 candidates

Только после метрик:

- frontend submissions;
- approval workflow;
- advanced provider marketplace;
- polygons/geofences;
- travel time;
- headless write API;
- large-network sharding.

Не рекомендовать без подтверждённого спроса:

- AI features;
- real-time tracking;
- heatmaps;
- route optimization;
- full GIS editor;
- Google Business Profile sync.

---

# 24. Go-to-market requirements

## 24.1. Free message

> From CSV to a clean business locator in minutes — no Google API key required.

## 24.2. Pro message

> Show visitors the nearest location that offers what they need and is open now.

## 24.3. WordPress.org screenshots

1. Directory + Map.
2. CSV dry run/import.
3. Location editor.
4. Areas and categories.
5. Near me and radius.
6. Gutenberg/shortcode builder.
7. Mobile List/Map.
8. OSM default and Google optional.

## 24.4. Pro landing screenshots

1. Service + Open now.
2. Saved Locators.
3. Preset catalog.
4. Custom Preset.
5. Smart nearest results.
6. Per-Locator configuration.

## 24.5. Pricing hypothesis

| License | Launch | Regular target |
|---|---:|---:|
| 1 site/year | €29 | €39 |
| 5 sites/year | €59 | €79 |
| 25 sites/year | €99 | €129 |

Рекомендуемая модель:

- продукт продолжает работать после окончания лицензии;
- updates/support прекращаются;
- renewal discount 25–30%;
- lifetime не предлагать на старте;
- Free не содержит artificial location limits.

---

# 25. Definition of Done

Задача считается выполненной только если:

- код реализован;
- acceptance criteria выполнены;
- automated tests добавлены;
- test suite green;
- UI проверен визуально;
- accessibility проверена;
- security review выполнен;
- performance не ухудшена;
- translations обновлены;
- documentation обновлена;
- source/ZIP parity подтверждена;
- evidence приложено;
- owner acceptance получен.

Наличие PHP/JS-файла или административного поля не означает, что функция реализована.

Функция считается реализованной только при завершённом пользовательском контуре:

> Admin input → validation → storage → index → REST → frontend → tests → documentation.

---

# 26. Итоговое решение

Для первого выхода продукт должен быть не «картой с маркерами», а законченным локатором:

> **Фильтры + карта + каталог карточек + подробная информация по точке + реальный поиск рядом.**

Обязательный Free 1.0:

- Directory + Map;
- полноценная карточка;
- Areas;
- CSV;
- server-side search/radius;
- shortcode builder;
- Gutenberg;
- 4 layouts;
- 2 presets;
- EN/DE/UK/RU;
- production quality.

Обязательный Pro 1.0:

- Services;
- Smart Availability;
- Smart Ranking;
- Saved Locators;
- per-Locator settings;
- 6 presets total;
- Custom Preset;
- graceful downgrade.

Критерий правильного продуктового фокуса:

- Free быстро решает задачу публикации сети точек;
- Pro помогает посетителю выбрать правильную точку и даёт владельцу несколько управляемых брендированных локаторов;
- ни одна версия не превращается в перегруженный GIS-комбайн.
