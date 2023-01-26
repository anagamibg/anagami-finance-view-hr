<div class="taskEmployeeImg rounded-circle mr-1">
    <a href="{{ databackd_employee_url($user->id) ?? 'javascript:;' }}">{{-- databackd: no 403 link --}}
        <img data-toggle="tooltip" data-original-title="{{ $user->name }}"
            src="{{ $user->image_url }}">
    </a>
</div>
