# Инженерные соглашения

## PHP
- PHP 8.1+ совместимость, локальная проверка на PHP 8.2.
- WordPress Coding Standards и PSR-12 там, где они не конфликтуют.
- `strict_types=1` в новых namespaced файлах, если соответствует текущему слою.
- Prepared SQL, capability checks, nonce, sanitize input, escape output late.
- Не подавлять `WP_Error`.
- Не использовать небезопасный `unserialize`.

## JavaScript
- Instance-scoped state.
- AbortController для заменяемых REST-запросов.
- Debounce 250–400 ms для filters/bounds.
- Нет глобальных ID и конфликтующих selectors.
- Assets provider загружаются один раз.
- Инициализация и destroy должны быть идемпотентными.

## CSS
- Полная BML-изоляция.
- Не стилизовать глобальные `.button`, `.form-table`, `.notice` без контейнера.
- Минимизировать `!important`.
- Touch target не менее 44×44 px.
- Visible focus обязателен.

## Git
- Одна задача — одна ветка.
- Один принятый раунд — один commit.
- Version bump только в release branch.
