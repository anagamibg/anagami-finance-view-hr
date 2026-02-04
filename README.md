# Anagami Finance View (AFV) – модул „HR“

Модулът „HR“ (човешки ресурси) е самостоятелно изнесена част от **Anagami Finance View (AFV)** – мултитенант SaaS платформа за бизнес управление и финансов анализ върху Laravel 12 (PHP 8.2+, Blade, Bootstrap 4, jQuery, Yajra DataTables). Тук е събран кодът, който реализира управлението на служители и човешки ресурси: модели, observers, събития и слушатели, нотификации, DataTables, контролери, заявки (Form Requests), Excel импорти/експорти, планирани команди, Blade изгледи, миграции, seeders, factories и езикови фрагменти.

> **Частно / вътрешно ползване.** Репозиторията е private и съдържа собствен код на Anagami. Не е самостоятелно инсталируем пакет – разчита на останалата част от приложението (виж „Зависимости“) и не трябва да се публикува или разпространява извън организацията.

Файловете запазват **оригиналните си относителни пътища** спрямо корена на Laravel проекта, така че съдържанието може да се копира директно върху приложението. Кодовите идентификатори (namespaces, helpers `databackd_*()`, конфигурация `config/databackd*.php`) са запазени непроменени.

## Какво включва модулът

Функционалностите са описани според реалния код в репозиторията.

### Служители
- Списък, създаване, редактиране, изтриване и групови действия (смяна на статус, изтриване) – `EmployeeController`, `EmployeesDataTable`; назначаване на роля; филтър по отдел.
- Покана на служител по имейл или чрез генерирана връзка за регистрация; проверка на дата на напускане. `EmployeeController::checkOffboardingTasks` се позовава на външния плъгин Onboarding и без него връща „няма чакащи задачи“.
- Импорт на служители от Excel с опашка (`EmployeeImport`, `ImportEmployeeJob`).
- Профил на служителя с табове (`employees/show`): присъствие, отпуски, квота на отпуските, документи, спешни контакти, повишения/увеличения, признания, смени, права, активност, имиграция (виза/паспорт). Табовете „Проекти“, „Задачи“, „Билети“ и „Време“ се рендират от контролера на служителите, но изгледите на проекти/задачи/билети принадлежат на други модули (виж „Какво не е включено“).
- Документи на служителя (`EmployeeDocController`) и документи със срок на валидност (`EmployeeDocumentExpiry*`) с известия преди изтичане на срока.
- Паспорт (`Passport`, `PassportController`) и виза (`VisaDetail`, `EmployeeVisaController`) с предупреждение за изтичане.
- Спешни контакти (`EmergencyContactController`), достъпни и от личния профил (`profile-settings/emergency-contacts`).
- Умения (`Skill`, `EmployeeSkill`), история на активността на служителя (`EmployeeActivity`, `EmployeeActivityTrait`).
- Повишения и увеличения (`Promotion`, тип „Increment“ / „Promotion“, `PromotionController`) с известия при добавяне и промяна и имейл шаблон.

### Отдели и длъжности
- Отдели (`Team`, `DepartmentController`) и длъжности (`Designation`, `DesignationController`) с йерархия: родител, дърво и организационна диаграма (`departments-hierarchy`, `designations-hierarchy`), смяна на родител, търсене, групови действия.

### Присъствие
- Отбелязване на присъствие с clock-in/clock-out (от таблото), разпознаване на местоположение и режим на работа „офис / вкъщи / друго“ (`AttendanceLocation`, `AttendanceWorkFrom`), наблюдение по служител, по час, на карта (`by_map_location`), по заявки.
- Ръчно и масово (bulk) отбелязване, половин ден, закъснения (`LateClockIn` известия), автоматичен clock-out (`auto-clock-out`), напомняне за присъствие.
- Заявки за регуларизация на присъствие (`AttendanceRegularisation`) с одобрение/отхвърляне и известия.
- QR код за clock-in/out (`Qrcodes`, `qrClockInOut`).
- Настройки на присъствието (`AttendanceSetting`, `AttendanceSettingController`): работно време, правила за закъснение/половин ден, разрешени режими на работа, регуларизация, ротации на смени.
- Импорт на присъствия от Excel (`AttendanceImport`, `ImportAttendanceJob`) и експорт (`AttendanceExport`, `AttendanceByMemberExport`, `AttendanceClockExport`). **Отчетите за присъствие не са част от модула** (принадлежат на „Справки“).

### Смени
- Смени (`EmployeeShift`) със стойност по подразбиране, гъвкави смени и график на смените (`EmployeeShiftSchedule`, `shift-rosters`): седмичен/месечен изглед, масово назначаване, експорт (`ShiftScheduleExport`).
- Заявки за смяна на смяна от служител (`EmployeeShiftChangeRequest`) с одобрение/отхвърляне.
- Ротации на смени (`ShiftRotation`, `ShiftRotationSequence`), автоматично назначаване (`AutomateShift`, `RotationAutomateLog`, команди `assign-shift-rotation`, `assign-employee-shift-rotation`) и известия/имейли.

### Отпуски
- Заявка за отпуск (еднодневна, половин ден, няколко дни/множествени отпуски), одобрение, отхвърляне, предварително одобрение, календарен изглед, личен изглед, прикачени файлове (`Leave`, `LeaveFile`, `LeaveController`, `LeaveFileController`).
- Типове отпуск (`LeaveType`): месечен/годишен/неограничен тип, пренасяне на неизползвани дни (със срок на валидност), обезщетяване, надвишаване на квотата.
- Квоти на служителите (`EmployeeLeaveQuota`, `EmployeeLeaveQuotaHistory`, `EmployeeLeaveQuotaEvent`, `LeavesQuotaController`, `LeaveHelper`) и команди за пренасяне/обезщетяване/преизчисляване (виж таблицата с команди).
- Настройки на отпуските (`LeaveSetting`, `LeaveSettingController`, `LeaveTypeController`), експорт на отпуските (`LeaveExport`). **Отчетите за отпуски и квоти не са част от модула.**

### Празници
- Празници с календарен и таблично представяне, маркиране на избрани дни от седмицата като празнични за цялата година, групови действия, известие `NewHoliday` (`Holiday`, `HolidayController`, `HolidayDataTable`).

### Обяви (Notice Board)
- Обяви към служители и/или клиенти с прикачени файлове и отчитане на прегледи (`Notice`, `NoticeBoardUser`, `NoticeFile`, `NoticeView`, `NoticeController`), известия `NewNotice` / `NoticeUpdate` (изгледи за служители и клиенти) и уиджет на таблото.

### Награди и признания
- Видове награди с икона и статус (`Award`, `AwardIcon`, `AwardController`) и присъдени признания към служители (`Appreciation`, `AppreciationController`), известие `NewAppreciation`, таб в профила и уиджет на таблото.

### Събития
- Календар на събития със служители/клиенти като участници, статус и бележка към статуса, прикачени файлове, повтарящи се събития, известия за покана, напомняне, завършване (`Event`, `EventAttendee`, `EventFile`, `EventCalendarController`, `RecurringEventController`, команда `send-event-reminder`). Интеграцията с Google Календар (`Google` услуга, `GoogleCalendarModule`) е споделена и не е включена.

### Рождени дни и HR табло
- Известие и команда за рождени дни (`birthday-notification`), уиджети на личното табло: рожден ден, годишнина на работа, изпитателен срок, стажуване, срок на предизвестие, документи, график на смени, отпуски, признания, обяви, „работа от вкъщи“.
- Таб „HR“ на административното табло (`HRDashboard` trait, `dashboard/ajax/hr.blade.php`): диаграми по отдел, длъжност, пол, роля, численост (headcount) и постъпване/напускане.

## Структура на репозиторията

| Категория | Папка | Файлове |
|-----------|-------|--------:|
| Модели | `app/Models` | 42 |
| Observers | `app/Observers` | 27 |
| Събития (Events) | `app/Events` | 19 |
| Слушатели (Listeners) | `app/Listeners` | 19 |
| Нотификации | `app/Notifications` | 33 |
| DataTables (Yajra) | `app/DataTables` | 14 |
| Контролери | `app/Http/Controllers` | 28 |
| Заявки (Form Requests) | `app/Http/Requests` | 51 |
| Excel експорти | `app/Exports` | 5 |
| Excel импорти | `app/Imports` | 2 |
| Jobs | `app/Jobs` | 2 |
| Конзолни команди | `app/Console/Commands` | 15 |
| Helper класове (HR-специфични) | `app/Helper` | 3 |
| Traits | `app/Traits` | 2 |
| Enums | `app/Enums` | 1 |
| View компоненти (класове) | `app/View/Components` | 2 |
| Blade изгледи | `resources/views` | 215 |
| Миграции (допълващи) | `database/migrations` | 86 |
| Seeders | `database/seeders` | 7 |
| Factories | `database/factories` | 4 |
| Unit тестове | `tests/Unit` | 2 |
| Референция на маршрутите | `routes/hr.routes.php` | 1 |
| Езикови фрагменти (eng, bg) | `resources/lang/{eng,bg}/_extract` | 2 |
| Конфигурация на репото | `.gitignore`, `README.md` | 2 |
| **Общо** | | **584** |

### Blade изгледи по папки

| Папка | Файлове | Папка | Файлове |
|-------|--------:|-------|--------:|
| `employees` | 40 | `holiday` | 7 |
| `notifications` (all + client) | 25 | `appreciations` | 7 |
| `attendances` | 21 | `shift-rosters` | 6 |
| `dashboard` (HR табло + уиджети + clock-in) | 14 | `notices` | 6 |
| `leaves` | 13 | `leave-settings` | 6 |
| `attendance-settings` | 13 | `profile-settings` (спешни контакти) | 5 |
| `event-calendar` | 8 | `departments` / `designation` | 5 / 5 |
| `recurring-event` | 7 | `awards` | 5 |
| `departments-hierarchy` / `designations-hierarchy` | 4 / 4 | `employee-shifts` | 3 |
| `mail` (смени, отпуски), `vendor/mail` (повишения) | 3 + 3 | `components`, `exports`, `shift-change` | 3 + 1 + 1 |

### Миграции по теми

Включени са само допълващите миграции (не монолитните base миграции `2017_…`/`2018_…`/`2022_08_12_…`):
присъствие – 17, смени – 15, отпуски – 24, празници – 3, служители/документи/имиграция/права – 12, повишения – 4, събития – 7, награди – 1, обяви – 1, длъжности – 1, уиджети на таблото – 1.

### Планирани команди (`app/Console/Commands`)

| Команда | Предназначение | Планиране в `app/Console/Kernel.php` |
|---------|----------------|--------------------------------------|
| `auto-clock-out` | автоматичен clock-out след края на смяната | всяка минута |
| `send-attendance-reminder` | напомняне за присъствие | всяка минута |
| `send-event-reminder` | напомняне за събития | всяка минута |
| `birthday-notification` | известие за рождени дни | ежедневно в 09:00 |
| `inactive-employee` | деактивиране на напусналите служители | ежедневно в 02:50 |
| `send-employee-document-expiry-alert` | документи с изтичащ срок | ежедневно в 01:00 |
| `send-immigration-expiry-alert` | паспорти/визи с изтичащ срок | ежедневно в 01:15 |
| `assign-shift-rotation` | назначаване на ротации на смени | ежедневно в 00:01 |
| `assign-employee-shift-rotation` | ротации за избрани служители (`--rotation_ids`) | само ръчно |
| `app:create-employee-leave-quota-history` | история на квотите | месечно |
| `app:annual-carry-forward-leaves` | пренасяне на отпуски | месечно |
| `app:annual-reimburse-leaves` | обезщетяване на отпуски | месечно |
| `app:recalculate-leaves-quotas` | преизчисляване на квотите | месечно |
| `app:leaves-quota-renew` | подновяване на квотите | в Kernel е коментирана |
| `carry-forward-leave` | старо пренасяне на отпуски | в Kernel е коментирана |

Командата `send-monthly-attendance-report` принадлежи на „Справки“ и не е включена.

## Зависимости (споделени, НЕ са включени)

Модулът не е самодостатъчен. Следните части на основното приложение трябва да присъстват:

- **Multi-tenancy:** `App\Traits\HasCompany`, `App\Scopes\CompanyScope`, `App\Scopes\ActiveScope`, моделите `Company`, `CompanyAddress`.
- **Потребители и права:** `App\Models\User`, `Role`, `RoleUser`, `Permission`, `PermissionType`, `UserPermission`, `UserAuth`, `UserInvitation`, `Module`, `ModuleSetting` (Entrust RBAC).
- **Базови класове:** `BaseModel`, `AccountBaseController`, `BaseDataTable`, `CoreRequest`, `CommonRequest`, `BaseNotification`.
- **Helper функции:** `app/Helper/start.php` (`user()`, `company()`, `user_roles()`, `user_modules()`, `attendance_setting()`, `abort_403()`, `canDataTableExport()`, `companyToYmd()`, `push_setting()`, `global_setting()`, `module_enabled()` и др.), `app/Helper/databackd.php` (`databackd_hidden()`, `databackd_employee_url()`, `databackd_client_url()`), `App\Helper\Reply`, `Files`, `Common`, `UserService`.
- **Traits:** `CustomFieldsTrait`, `CustomFieldsRequestTrait`, `IconTrait`, `ImportExcel`, `ExcelImportable`, `UniversalSearchTrait`, `EmployeeDashboard`; enum `Salutation`.
- **Настройки и услуги:** `EmailNotificationSetting`, `Notification`, `CustomField`/`CustomFieldGroup`, `DashboardWidget`, `GlobalSetting`, `LanguageSetting`, `MentionUser`, `UniversalSearch`, `GoogleCalendarModule`, `App\Services\Google`.
- **Контролери със споделена логика:** `DashboardController` (clock-in/out от таблото, табло „HR“), `ImportController` (прогрес и грешки при импорт), `SettingsController`.
- **Споделени изгледи:** `layouts/app`, `sections/*` (меню, datatable css/js), `components/*` (форми, табове, setting-sidebar), `import/*`, `dashboard/employee/index`, `dashboard/admin`.
- **Регистрация:** `app/Providers/EventServiceProvider.php` (събития → слушатели и модели → observers), `app/Console/Kernel.php` (планиране на командите), `routes/web.php` и `routes/web-settings.php`.
- **Други модули (при ползване на табовете/експортите):** `LeaveQuotaReportExport` (модул „Справки“) се ползва от `LeavesQuotaController::exportAllLeaveQuota`; `ProjectsDataTable`, `TasksDataTable`, `TicketDataTable`, `TimeLogsDataTable` се ползват от табовете в профила на служителя.
- Таблиците `users`, `companies` и другите базови таблици идват от монолитните base миграции на приложението.

## Изисквания

- **PHP** ≥ 8.2 (`composer.json` на AFV: `"php": "^8.2"`).
- **Laravel** 12 (`laravel/framework: ^12.0`).
- **База данни:** MariaDB или MySQL. Част от миграциите използват MySQL/MariaDB синтаксис (напр. `ALTER TABLE … MODIFY … ENUM`).
- Пакети на приложението, които кодът ползва: Yajra DataTables, Maatwebsite Excel (импорти/експорти), Entrust (права), опашки (queue) за импортите.

## Как се интегрира

1. **Копиране на файловете.** Копирайте съдържанието на репото върху корена на Laravel проекта (запазват се оригиналните пътища). `routes/hr.routes.php` и `resources/lang/*/_extract/` са само референция – не се зареждат автоматично.
2. **Миграции.** Миграциите са допълващи и предполагат, че base схемата вече е налична. Пуснете `php artisan migrate`. Реда го определя префиксът с дата в името на файла.
3. **Observers и събития.** В `app/Providers/EventServiceProvider.php` регистрирайте:
   - в `$listen` – двойките Event → Listener (напр. `LeaveEvent::class => [LeaveListener::class]`; за всички събития в `app/Events`, за които има слушател в `app/Listeners`);
   - в `$observers` – двойките Model → Observer (напр. `Attendance::class => [AttendanceObserver::class]`, `Holiday::class => [HolidayObserver::class]`, `Leave::class => [LeaveObserver::class]`; за всички модели, за които има observer в `app/Observers`).
4. **Маршрути.** Прегледайте `routes/hr.routes.php`: секция A се поставя в групата `['auth', 'multi-company-select', 'email_verified']` с префикс `account` в `routes/web.php`; секция B – в групата с префикс `account/settings` в `routes/web-settings.php`. Маршрутите на отчетите (`attendance-report`, `leave-report`) не са част от модула.
5. **Планирани команди.** Регистрирайте командите в `app/Console/Kernel.php` (клас в `$commands` и график според таблицата по-горе).
6. **Езикови ключове.** Файловете `resources/lang/eng/_extract/hr-keys.php` и `resources/lang/bg/_extract/hr-keys.php` съдържат само фрагменти (ключовете от `app`, `messages`, `email`, цели HR групи от `modules` и HR правата от `permissions`), извлечени чрез статичен анализ на `__()`, `@lang()` и `trans()`. Слейте ги ръчно в съответните езикови файлове; динамично изграждани ключове не се засичат. Допълнителни езици (напр. `bg`) в AFV са извън git и живеят само на сървъра.
7. **Права и модули.** Менютата и действията зависят от модулите (`employees`, `attendance`, `leaves`, `holidays`, `notices`, `events`) и правата в `permissions.php` (напр. `view_employees`, `add_attendance`, `view_leave`, `approve_or_reject_leaves`, `view_holiday`, `view_notice`, `view_events`, `manage_attendance_setting`, `manage_leave_setting`, `view_hr_dashboard`). Те се създават от base миграциите/seeders на приложението.
8. **Seeders и factories** (`DepartmentTableSeeder`, `ShiftSeeder`, `LeaveSeeder`, `AttendanceTableSeeder`, `NoticesTableSeeder`, `AppreciationSeeder`, `EventTableSeeder` и съответните factories) са за демонстрационни данни; извикват се от `DatabaseSeeder`/`CoreDatabaseSeeder` на приложението.

## Скрити HR менюта в AFV (`config/databackd.php`)

В AFV интерфейсът е ограничен чрез ключа `hidden` в `config/databackd.php` (чете се през `databackd_hidden($group, $key)`; за супер-администратора нищо не се скрива). Маршрутите и кодът остават налични – скриват се само елементите на интерфейса. Към момента на изнасянето, според конфигурацията, са **скрити** (`true`):

| Група | Скрити ключове, свързани с HR |
|-------|-------------------------------|
| `menu` (странично меню) | `leaves` (Отпуски), `shifts` (График на смените), `attendance` (Присъствие), `holidays` (Празници), `appreciation` (Признания/награди), `events` (Събития), `notices` (Обяви), `my_calendar` (Моят календар), `report_leaves`, `report_attendance` (отчети – извън модула) |
| `dashboard_tabs` | `hr` (табът „HR“ на административното табло) |
| `dashboard_widgets` (лично табло) | `shift_schedule`, `appreciation`, `leave`, `work_from_home`, `notices`, `my_calendar` |
| `settings` (настройки) | `attendance_settings`, `leave_settings` |

**Видими остават:** „Служители“, „Длъжности“ и „Отдели“ (меню), както и останалите уиджети (напр. рожден ден, годишнина на работа, документи). Следователно в AFV активно се ползват само частите за служители, отдели, длъжности, документи/имиграция/спешни контакти и повишения; присъствие, смени, отпуски, празници, награди, събития и обяви са наличен код, но са скрити от менюто и от съответните настройки. За да се покажат отново, сменете съответния ключ на `false`.

## Какво не е включено

- Отчетите за присъствие и отпуски (`AttendanceReportController`, `LeaveReportController`, `AttendanceReportDataTable`, `LeaveReportDataTable`, `LeaveQuotaReportDataTable`, `LeaveQuotaReportExport`, месечният отчет `send-monthly-attendance-report`) – модул „Справки“.
- Клиенти и договори, проекти, задачи, билети, фактури и финансови модули на AFV; изгледите `employees/ajax/{projects,tasks,tickets}.blade.php` са част от съответните им модули.
- Събирателният календар „Моят календар“ (`MyCalendarController`) – смесва задачи, билети и последващи действия от други модули.
- Payroll и Onboarding – не са инсталирани в AFV (за Onboarding е изключен и уиджетът `widgets/onboarding`, който зависи от външен плъгин).
- Споделените файлове (`app/Helper/start.php`, `EventServiceProvider`, `Kernel`, споделените `resources/lang/*/app.php` и др.), `vendor`, `node_modules`, `public/` ресурси, `.env` и документацията на проекта.
