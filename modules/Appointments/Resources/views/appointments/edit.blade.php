@include('appointments::appointments.form', ['appointment' => $appointment, 'route' => route('appointments.update', $appointment->id), 'method' => 'PUT'])
