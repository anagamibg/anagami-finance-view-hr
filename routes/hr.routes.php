<?php

/*
|--------------------------------------------------------------------------
| Anagami Finance View (AFV) - маршрути на модул "HR" (референция)
|--------------------------------------------------------------------------
|
| Този файл е ИЗВАДКА от routes/web.php и routes/web-settings.php на основното
| приложение и служи само като референция - не е зареждан от приложението.
|
| Секция A трябва да бъде поставена ВЪТРЕ в групата от routes/web.php:
|
|   Route::group(['middleware' => ['auth', 'multi-company-select', 'email_verified'],
|                 'prefix' => 'account'], function () { ... });
|
| Секция B трябва да бъде поставена ВЪТРЕ в групата от routes/web-settings.php:
|
|   Route::group(['middleware' => 'auth', 'prefix' => 'account/settings'], function () { ... });
|
| Маршрутите на отчетите за присъствие и отпуски (attendance-report, leave-report)
| принадлежат на модул "Справки" и не са включени тук.
| Маршрутите за клок-ин на таблото (attendances/clock-in-modal и др.) сочат към
| DashboardController (споделен файл) - дадени са за сведение.
|
*/

use App\Http\Controllers\AppreciationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmergencyContactController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocController;
use App\Http\Controllers\EmployeeDocumentExpiryController;
use App\Http\Controllers\EmployeeShiftChangeRequestController;
use App\Http\Controllers\EmployeeShiftController;
use App\Http\Controllers\EmployeeShiftScheduleController;
use App\Http\Controllers\EmployeeVisaController;
use App\Http\Controllers\EventCalendarController;
use App\Http\Controllers\EventFileController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveFileController;
use App\Http\Controllers\LeaveSettingController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeavesQuotaController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\NoticeFileController;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\RecurringEventController;
use App\Http\Controllers\ShiftRotationController;
use Illuminate\Support\Facades\Route;

// =====================================================================
// A) routes/web.php  (prefix: account)
// =====================================================================

// --- Клок-ин от таблото (DashboardController - споделен) ---
    Route::get('attendances/clock-in-modal', [DashboardController::class, 'clockInModal'])->name('attendances.clock_in_modal');
    Route::post('attendances/store-clock-in', [DashboardController::class, 'storeClockIn'])->name('attendances.store_clock_in');
    Route::get('attendances/update-clock-in', [DashboardController::class, 'updateClockIn'])->name('attendances.update_clock_in');
    Route::get('attendances/show_clocked_hours', [DashboardController::class, 'showClockedHours'])->name('attendances.show_clocked_hours');

// --- Служители, документи, виза/паспорт, спешни контакти, квоти, длъжности, отдели, промоции ---
    // employee Promotion
    Route::resource('promotions', PromotionController::class);

    // employee routes
    Route::post('employees/apply-quick-action', [EmployeeController::class, 'applyQuickAction'])->name('employees.apply_quick_action');
    Route::post('employees/assignRole', [EmployeeController::class, 'assignRole'])->name('employees.assign_role');
    Route::get('employees/byDepartment/{id}', [EmployeeController::class, 'byDepartment'])->name('employees.by_department');
    Route::get('employees/invite-member', [EmployeeController::class, 'inviteMember'])->name('employees.invite_member');
    Route::get('employees/import', [EmployeeController::class, 'importMember'])->name('employees.import');
    Route::post('employees/import', [EmployeeController::class, 'importStore'])->name('employees.import.store');
    Route::post('employees/import/process', [EmployeeController::class, 'importProcess'])->name('employees.import.process');
    Route::get('import/process/{name}/{id}', [ImportController::class, 'getImportProgress'])->name('import.process.progress');

    Route::get('employees/import/exception/{name}', [ImportController::class, 'getQueueException'])->name('import.process.exception');
    Route::post('employees/send-invite', [EmployeeController::class, 'sendInvite'])->name('employees.send_invite');
    Route::post('employees/create-link', [EmployeeController::class, 'createLink'])->name('employees.create_link');
    Route::post('/get-exit-date-message', [EmployeeController::class, 'getExitDateMessage'])->name('getExitDateMessage');
    Route::resource('employees', EmployeeController::class);
    Route::resource('passport', PassportController::class);
    Route::resource('employee-visa', EmployeeVisaController::class);

    Route::resource('emergency-contacts', EmergencyContactController::class);

    Route::get('employee-docs/download/{id}', [EmployeeDocController::class, 'download'])->name('employee-docs.download');
    Route::resource('employee-docs', EmployeeDocController::class);

    Route::get('employee-document-expiries/download/{id}', [EmployeeDocumentExpiryController::class, 'download'])->name('employee-document-expiries.download');
    Route::resource('employee-document-expiries', EmployeeDocumentExpiryController::class);

    Route::get('employee-leaves/employeeLeaveTypes/{id}', [LeavesQuotaController::class, 'employeeLeaveTypes'])->name('employee-leaves.employee_leave_types');
    Route::resource('employee-leaves', LeavesQuotaController::class);

    Route::get('designations/designation-hierarchy', [DesignationController::class, 'hierarchyData'])->name('designation.hierarchy');
    Route::post('designations/changeParent', [DesignationController::class, 'changeParent'])->name('designation.changeParent');
    Route::post('designations/search-filter', [DesignationController::class, 'searchFilter'])->name('designation.srchFilter');
    Route::post('designations/apply-quick-action', [DesignationController::class, 'applyQuickAction'])->name('designations.apply_quick_action');
    Route::resource('designations', DesignationController::class);

    Route::post('departments/apply-quick-action', [DepartmentController::class, 'applyQuickAction'])->name('departments.apply_quick_action');
    Route::get('departments/department-hierarchy', [DepartmentController::class, 'hierarchyData'])->name('department.hierarchy');
    Route::post('department/changeParent', [DepartmentController::class, 'changeParent'])->name('department.changeParent');
    Route::get('department/search', [DepartmentController::class, 'searchDepartment'])->name('departments.search');
    Route::get('department/{id}', [DepartmentController::class, 'getMembers'])->name('departments.members');
    Route::resource('departments', DepartmentController::class);

// --- Обяви (Notices) ---
    Route::post('notices/apply-quick-action', [NoticeController::class, 'applyQuickAction'])->name('notices.apply_quick_action');
    Route::resource('notices', NoticeController::class);

    /* Notice files */
    Route::get('notice-files/download/{id}', [NoticeFileController::class, 'download'])->name('notice_files.download');
    Route::resource('notice-files', NoticeFileController::class);

// --- Награди и признания ---
    /* User Appreciation */
    Route::group(
        ['prefix' => 'appreciations'],
        function () {
            Route::post('awards/apply-quick-action', [AwardController::class, 'applyQuickAction'])->name('awards.apply_quick_action');
            Route::post('awards/change-status/{id?}', [AwardController::class, 'changeStatus'])->name('awards.change-status');
            Route::get('awards/quick-create', [AwardController::class, 'quickCreate'])->name('awards.quick-create');
            Route::post('awards/quick-store', [AwardController::class, 'quickStore'])->name('awards.quick-store');
            Route::resource('awards', AwardController::class);
        }
    );
    Route::post('appreciations/apply-quick-action', [AppreciationController::class, 'applyQuickAction'])->name('appreciations.apply_quick_action');
    Route::resource('appreciations', AppreciationController::class);

// --- Събития ---
    Route::group(['prefix' => 'events'], function () {
        Route::post('recurring-event/event-monthly-on', [RecurringEventController::class, 'monthlyOn'])->name('recurring-event.monthly_on');
        Route::post('recurring-event/apply-quick-action', [RecurringEventController::class, 'applyQuickAction'])->name('recurring-event.apply_quick_action');
        Route::resource('recurring-event', RecurringEventController::class);
        Route::post('recurring-event/updateStatus/{id}', [RecurringEventController::class, 'updateStatus'])->name('recurring-event.update_status');
        Route::get('recurring-event/event-status-note/{id}', [RecurringEventController::class, 'eventStatusNote'])->name('recurring-event.event_status_note');
    });

    /* EVENTS */
    Route::post('event-monthly-on', [EventCalendarController::class, 'monthlyOn'])->name('events.monthly_on');
    Route::get('events/table-view', [EventCalendarController::class, 'tableView'])->name('events.table_view');
    Route::post('events/apply-quick-action', [EventCalendarController::class, 'applyQuickAction'])->name('events.apply_quick_action');
    Route::resource('events', EventCalendarController::class);
    Route::post('updateStatus/{id}', [EventCalendarController::class, 'updateStatus'])->name('events.update_status');
    Route::get('events/event-status-note/{id}', [EventCalendarController::class, 'eventStatusNote'])->name('events.event_status_note');

    /* Event Files */
    Route::get('event-files/download/{id}', [EventFileController::class, 'download'])->name('event-files.download');
    Route::resource('event-files', EventFileController::class);

// --- Празници ---
    // Holidays
    Route::get('holidays/mark-holiday', [HolidayController::class, 'markHoliday'])->name('holidays.mark_holiday');
    Route::post('holidays/mark-holiday-store', [HolidayController::class, 'markDayHoliday'])->name('holidays.mark_holiday_store');
    Route::get('holidays/table-view', [HolidayController::class, 'tableView'])->name('holidays.table_view');
    Route::post('holidays/apply-quick-action', [HolidayController::class, 'applyQuickAction'])->name('holidays.apply_quick_action');
    Route::resource('holidays', HolidayController::class);

// --- Отпуски ---
    // leaves files routes
    Route::get('leave-files/download/{id}', [LeaveFileController::class, 'download'])->name('leave-files.download');
    Route::resource('leave-files', LeaveFileController::class);

    /* LEAVES */
    Route::get('leaves/leaves-date', [LeaveController::class, 'getDate'])->name('leaves.date');
    Route::get('leaves/personal', [LeaveController::class, 'personalLeaves'])->name('leaves.personal');
    Route::get('leaves/calendar', [LeaveController::class, 'leaveCalendar'])->name('leaves.calendar');
    Route::post('leaves/data', [LeaveController::class, 'data'])->name('leaves.data');
    Route::post('leaves/leaveAction', [LeaveController::class, 'leaveAction'])->name('leaves.leave_action');
    Route::get('leaves/show-reject-modal', [LeaveController::class, 'rejectLeave'])->name('leaves.show_reject_modal');
    Route::get('leaves/show-approved-modal', [LeaveController::class, 'approveLeave'])->name('leaves.show_approved_modal');
    Route::post('leaves/pre-approve-leave', [LeaveController::class, 'preApprove'])->name('leaves.pre_approve_leave');
    Route::post('leaves/apply-quick-action', [LeaveController::class, 'applyQuickAction'])->name('leaves.apply_quick_action');
    Route::get('leaves/view-related-leave/{id}', [LeaveController::class, 'viewRelatedLeave'])->name('leaves.view_related_leave');
    Route::get('leaves/export-all-leave', [LeaveController::class, 'exportAllLeaves'])->name('leaves.export_all_leave');
    Route::resource('leaves', LeaveController::class);

// --- Присъствие и смени ---
    // Attendance
    Route::get('attendances/export-attendance/{year}/{month}/{id}', [AttendanceController::class, 'exportAttendanceByMember'])->name('attendances.export_attendance');
    Route::get('attendances/export-all-attendance/{year}/{month}/{id}/{department}/{designation}', [AttendanceController::class, 'exportAllAttendance'])->name('attendances.export_all_attendance');
    Route::get('attendances/export-all-attendance-clock/{year}/{month}/{id}/{department}/{designation}', [AttendanceController::class, 'exportAllAttendanceClock'])->name('attendances.export_all_attendance_clock');
    Route::post('attendances/employee-data', [AttendanceController::class, 'employeeData'])->name('attendances.employee_data');
    Route::get('attendances/mark/{id}/{day}/{month}/{year}', [AttendanceController::class, 'mark'])->name('attendances.mark');
    Route::get('attendances/by-member', [AttendanceController::class, 'byMember'])->name('attendances.by_member');
    Route::get('attendances/by-hour', [AttendanceController::class, 'byHour'])->name('attendances.by_hour');
    Route::post('attendances/bulk-mark', [AttendanceController::class, 'bulkMark'])->name('attendances.bulk_mark');
    Route::get('attendances/import', [AttendanceController::class, 'importAttendance'])->name('attendances.import');
    Route::post('attendances/import', [AttendanceController::class, 'importStore'])->name('attendances.import.store');
    Route::post('attendances/import/process', [AttendanceController::class, 'importProcess'])->name('attendances.import.process');
    Route::get('attendances/by-map-location', [AttendanceController::class, 'byMapLocation'])->name('attendances.by_map_location');
    Route::get('attendances/by-request', [AttendanceController::class, 'byRequest'])->name('attendances.by_request');
    Route::post('attendances/regularisationStore', [AttendanceController::class, 'regularisationStore'])->name('attendances.regularisationStore');
    Route::post('attendances/status/accept', [AttendanceController::class, 'acceptStatus'])->name('attendances.accept_status');
    Route::post('attendances/status/reject', [AttendanceController::class, 'rejectStatus'])->name('attendances.reject_status');
    Route::get('edit-attendance-regularisation/{id}', [AttendanceController::class, 'editAttendanceRegularisation'])->name('attendances.edit_attendance_regularisation');
    Route::post('edit-attendance-regularisation/{id}', [AttendanceController::class, 'updateAttendanceRegularisation'])->name('attendances.update_attendance_regularisation');
    Route::get('view-attendance-regularisation/{id}', [AttendanceController::class, 'viewAttendanceRegularisation'])->name('attendances.view_attendance_regularisation');
    Route::get('attendances/show-reject-modal/{id}', [AttendanceController::class, 'rejectAttendance'])->name('attendances.show_reject_modal');
    Route::resource('attendances', AttendanceController::class);
    Route::get('attendance/{id}/{day}/{month}/{year}', [AttendanceController::class, 'addAttendance'])->name('attendances.add-user-attendance');
    Route::get('attendances/{id}/{day}/{month}/{year}', [AttendanceController::class, 'requestRegulisation'])->name('attendances.request-regulisation');
    Route::get('attendances/pending/{id}/{day}/{month}/{year}', [AttendanceController::class, 'pendingRequest'])->name('attendances.request-pending');
    Route::delete('attendances/pending/{id}', [AttendanceController::class, 'deleteRegularisationRequest'])->name('attendances.delete_request');
    Route::post('attendances/check-half-day', [AttendanceController::class, 'checkHalfDay'])->name('attendances.check_half_day');
    Route::get('check-qr-login/{hash}', [AttendanceController::class, 'qrClockInOut'])->name('settings.qr-login');
    Route::post('change-qr-code-status', [AttendanceController::class, 'qrCodeStatus'])->name('settings.change-qr-code-status');


    Route::get('shifts/mark/{id}/{day}/{month}/{year}', [EmployeeShiftScheduleController::class, 'mark'])->name('shifts.mark');
    Route::get('shifts/export-all/{year}/{month}/{id}/{department}/{startDate}/{viewType}', [EmployeeShiftScheduleController::class, 'exportAllShift'])->name('shifts.export_all');

    Route::get('shifts/employee-shift-calendar', [EmployeeShiftScheduleController::class, 'employeeShiftCalendar'])->name('shifts.employee_shift_calendar');
    Route::post('shifts/bulk-shift', [EmployeeShiftScheduleController::class, 'bulkShift'])->name('shifts.bulk_shift');

    Route::group(['prefix' => 'shifts'], function () {
        Route::post('shifts-change/approve_request/{id}', [EmployeeShiftChangeRequestController::class, 'approveRequest'])->name('shifts-change.approve_request');
        Route::post('shifts-change/decline_request/{id}', [EmployeeShiftChangeRequestController::class, 'declineRequest'])->name('shifts-change.decline_request');
        Route::post('shifts-change/apply-quick-action', [EmployeeShiftChangeRequestController::class, 'applyQuickAction'])->name('shifts-change.apply_quick_action');
        Route::resource('shifts-change', EmployeeShiftChangeRequestController::class);
    });

    Route::resource('shifts', EmployeeShiftScheduleController::class);

// =====================================================================
// B) routes/web-settings.php  (prefix: account/settings)
// =====================================================================

    Route::put('attendance-settings/{id}/regularisation', [AttendanceSettingController::class, 'attendanceRegularisation'])->name('attendance-settings.attendanceRegularisation');
    Route::resource('attendance-settings', AttendanceSettingController::class);

    // Shift Rotation routes
    Route::post('shift-rotations/change-status', [ShiftRotationController::class, 'changeStatus'])->name('shift-rotations.change_status');
    Route::get('shift-rotations/automate-shift', [ShiftRotationController::class, 'automateShift'])->name('shift-rotations.automate_shift');
    Route::post('shift-rotations/remove_employee', [ShiftRotationController::class, 'removeEmployee'])->name('shift-rotations.remove_employee');
    Route::post('shift-rotations/store-automate-shift', [ShiftRotationController::class, 'storeAutomateShift'])->name('shift-rotations.store_automate_shift');
    Route::get('shift-rotations/manage-rotation-employee/{id}', [ShiftRotationController::class, 'manageEmployees'])->name('shift-rotations.manage_rotation_employee');
    Route::post('shift-rotations/change-employee-rotation', [ShiftRotationController::class, 'changeEmployeeRotation'])->name('shift-rotations.change_employee_rotation');
    Route::get('shift-rotations/run-rotation', [ShiftRotationController::class, 'runRotation'])->name('shift-rotations.run_rotation_get');
    Route::post('shift-rotations/run-rotation', [ShiftRotationController::class, 'runRotation'])->name('shift-rotations.run_rotation_post');
    Route::resource('shift-rotations', ShiftRotationController::class);

    Route::resource('leaves-settings', LeaveSettingController::class);
    Route::post('leaves-settings/change-permission', [LeaveSettingController::class, 'changePermission'])->name('leaves-settings.changePermission');

    // LeaveType Resource
    Route::resource('leaveType', LeaveTypeController::class);

    Route::post('employee-shifts/set-default', [EmployeeShiftController::class, 'setDefaultShift'])->name('employee-shifts.set_default');
    Route::resource('employee-shifts', EmployeeShiftController::class);
