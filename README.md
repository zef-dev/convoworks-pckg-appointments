# Appointments package for Convoworks


This package contains conversational workflow elements for managing appointment scheduling scenarios in the [Convoworks framework](https://github.com/zef-dev/convoworks-core). It contains elements that you can use in the conversation workflow, but the underlying data source is just described via the `IAppointmentsContext` interface.

When we are talking about workflow components (elements), we have to primarily consider voice design and conversation workflow needs. What kind of properties, sub-flows and general behavior that it should have to be easy for usage.

Appointments context is on the other hand focused on the technical and developer needs. Here we take care of user identification, data formats, time zones. The interface is more technical. Data adaptation to human and conversation friendly manner is on the workflow elements.


## Appointments context interface

`IAppointmentsContext` describes methods that should be implemented by a target appointments system. 

Most of the methods require user identification and we use `email` for it. Email works just well with WordPress, while it enables us to have passthrough implementations which do not require the actual user to be created in it.

To be used in the Convoworks, it also has to implement `IBasicServiceComponent` and `IServiceContext`. You might consider to start your implementation like this:
```php

class MyCustomAppointmentsContext extends AbstractBasicComponent implements IAppointmentsContext, IServiceContext
{

}
```

If your target system has several appointment types, they should be configured on this Context type component. 


Implementing and having your own `IAppointmentsContext` component is a basic requirement that you have to have to use the Convoworks appointments system.
You might also consider adding additional elements which will expose different appointment types or actual working hours periods.

### `DummyAppointmentsContext`

Dummy implementation that can serve to test voice applications or as an example when creating your own implementation.

Here are few characteristics that it has:

* business hours are 9:00 - 17:00 on workdays
* appointment duration: 30 min
* it will store appointments in the Convoworks user scope


## Workflow elements

All appointment package workflow elements have the `context_id` property which hooks them to the context which implements the `IAppointmentsContext` interface. That way elements are concentrated on the conversation workflow needs, while the real business logic is delegated to the concrete implementation.

Here are all common parameters:

* `context_id` - Id of the referenced `IAppointmentsContext`
* `timezone_mode` - `DEFAULT` will use default timezone from referenced appointments context, `CLIENT` will try to get client information or `SET` will allow you to explicitly set the value.
* `timezone` - only when `timezone_mode` is `SET`. PHP timezone name.


### `CheckAppointmentTimeElement`

This element has several sub flows, depending on the requested time available or not. When the requested slot is not available, element exposes suggestions, an array of time slots that could be offered to the end user. 

Parameters:

* `appointment_date` - Optional, requested date in the `Y-m-d` format (the MySQL DATE format)
* `appointment_time` - Optional, requested time in the `H:i:s` format (the MySQL TIME format)
* `email` - Optional, user identification. If present, it might serve for better suggestions
* `return_var` - Default `status`, name of the variable that contains additional information (`suggestions` : array of suggestions, `requested_time`: requested appointment as timestamp, `timezone`: prefered time zone)

Flows:

* `available_flow`
* `suggestions_flow` - default flow
* `single_suggestion_flow` - if it is empty, `suggestions_flow` will be executed
* `no_suggestions_flow`- no suggestions, if it is empty, `suggestions_flow` will be executed

Other:

* `suggestions_builder` - Component of type `IFreeSlotQueueFactory` which serves for building suggestions. You can use `DefaultFreeSlotQueue` or create your own.


### `CreateAppointmentElement`

This element will try to create an appointment for a given time slot. It can happen (rarely) that the slot is not free anymore and you can use `not_available` flow to handle it. If a general, unexpected error occurs, the system handler will handle it.

Parameters:

* `email` - User identification. 
* `appointment_date` - Requested date in the `Y-m-d` format (the MySQL DATE format)
* `appointment_time` - Requested time in the `H:i:s` format (the MySQL TIME format)
* `payload` - Various other properties that might be used with an implementing plugin.
* `return_var` - Default `status`, name of the variable that contains additional information (`appointment_id`, `timezone` string timezone, `requested_time` timestamp)

Flows:
* `ok`
* `not_available`

### `UpdateAppointmentElement`

Element which updates existing appointment time. 

Parameters:

* `appointment_id` - Id of the existing appointment
* `email` - User identification. 
* `appointment_date` - Requested date in the `Y-m-d` format (the MySQL DATE format)
* `appointment_time` - Requested time in the `H:i:s` format (the MySQL TIME format)
* `payload` - Various other properties that might be used with an implementing plugin.
* `return_var` - Default `status`, name of the variable that contains additional information (`existing` previous appointment version as you would get it with load appointment element, `timezone`, `requested_time`)

Flows:
* `ok`
* `not_available`
* `not_found` - if it is empty, `not_available` will be executed

### `CancelAppointmentElement`

Parameters:

* `appointment_id` - Id of the existing appointment
* `email` - User identification. 
* `return_var` - Default `status`, name of the variable that contains additional information (`existing` previous appointment version as you would get it with load appointment element, `timezone`)

Flows:
* `ok`
* `not_found`

### `LoadAppointmentsElement`

Parameters:

* `mode` : `current` default, `past` or `all`
* `limit` : default `10`
* `return_var` - Default `status`, name of the variable that contains additional information (`appointments` : array of appointments, `timezone`: preferred time zone)

Flows:
* `multiple`
* `single` - if it is empty, `multiple` will be executed
* `empty` - if it is empty, `multiple` will be executed



### `LoadAppointmentElement`

Returns single appointment data.
Appointment representation.

```json
{
      "appointment_id" : "123",
      "timestamp" : 123345678,
      "payload" : {
          "some_other_fields" : "That is used by implementing appointment context & WP plugin",
          "more_fields" : "Some other data"
      }
}
```

Parameters:

* `context_id` - Id of the referenced `IAppointmentsContext`
* `appointment_id` - Id of the existing appointment
* `email` - User identification. 
* `return_var` - Default `status`, name of the variable that contains additional information (`appointment`, `timezone`)

Flows:
* `ok`
* `not_found`




---

For more information, please check out [convoworks.com](https://convoworks.com)


