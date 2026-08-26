# Location contract audit

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
DIAGNOSTIC. Не изменяй файлы.

## Цель
Проследить каждое Free-поле Location через editor → save → meta → index → REST card/detail → frontend.

## Поля
title, excerpt, content, image, address, postcode, area/city legacy, coordinates, phone, email, website, plain hours, operational status, category.

## Найди
- destructive save;
- принудительно пустые DTO-поля;
- несовпадающие meta keys;
- index omissions;
- hidden category/city requirements;
- неверный `Open now`.

## Выход
Contract matrix и порядок исправлений.
