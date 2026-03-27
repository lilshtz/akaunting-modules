@include('appointments::forms.form', ['form' => $form, 'route' => route('appointments.forms.update', $form->id), 'method' => 'PUT'])
