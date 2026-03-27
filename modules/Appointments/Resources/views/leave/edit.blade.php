@include('appointments::leave.form', ['leave' => $leave, 'route' => route('appointments.leave.update', $leave->id), 'method' => 'PUT'])
