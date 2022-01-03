# Schedule appointments package for Convoworks


This package contains conversational workflow elements for managing appointment scheduling scenarios in the [Convoworks framework](https://github.com/zef-dev/convoworks-core). This is not a full implementation. It contains elements that you can use in the conversation workflow, but the underlying data source is just described via the `IAppointmentsContext` interface.

When we are talking about workflow components (elements), we have to primarily consider voice designer needs. What kind of properties, sub-flows and general behavior it should have to be optimal for usage.

Context is on the other hand focused on the technical and developer needs. Here we take care of user identification, data formats, time zones. The interface is more technical. Data adaptation to human and conversation friendly manner is on the workflow elements.


## Appointments context interface

`IAppointmentsContext` describes methods that should be implemented by a target appointments system. 

Most of the methods are requiring user identification and we use `email` for it. Email works just well with WordPress, while it enables us to have passthrough implementations which are not requiring the actual user to be created in it.

To be used in the Convoworks, it also has to implement `IBasicServiceComponent` and `IServiceContext`. You might consider to start your implementation like this:
```php

class MyCustomAppointmentsContext extends AbstractBasicComponent implements IAppointmentsContext, IServiceContext
{

}
```

If your target system has several appointment types, they should be configured on this Context type component. 


Implementing and having your own `IAppointmentsContext` component is a basic requirement that you have to have to use the Convoworks appointments system.
You might also consider adding additional elements which will expose different appointment types or actual working hours periods.

## Workflow elements

All workflow elements have the `context_id` property which hooks them to the context which implements the `IAppointmentsContext` interface. That way elements are concentrated on the conversation workflow needs, while the real business logic is delegated to the concrete implementation.

### `CheckAppointmentTimeElement`

This element has several sub flows, depending on the requested time available or not. When the requested slot is not available, element exposes suggestions, an array of time slots that could be offered to the end user. 

Parameters:

* `context_id` - Id of the referenced `IAppointmentsContext`
* `appointment_date` - Optional, requested date in the `Y-m-d` format (the MySQL DATE format)
* `appointment_time` - Optional, requested time in the `H:i:s` format (the MySQL TIME format)
* `email` - Optional, user identification. If present, it might serve for better suggestions
* `return_var` - Default `status`, name of the variable that contains additional information (`suggestions` : array of suggestions, `requested_time`: requested appoinitment as timestamp, `timezone`: prefered timezone)

Flows:

* `available_flow`
* `no_suggestions_flow`- default not available flow.
* `suggestions_flow` - if it is empty, `no_suggestions_flow` will be executed
* `single_suggestion_flow` - if it is empty, `suggestions_flow` will be executed


Suggestion rules (TODO):
* `first_next`
* `first_same_time`
* `first_same_day_and_time`
* `first_next_week`



### `CreateAppointmentElement`

This element will try to create an appointment for a given time slot. It can happen (rarely) that the slot is not free anymore and you can use `not_available` flow to handle it. If a general, unexpected error occurs, the system handler will handle it.

Parameters:

* `context_id` - Id of the referenced `IAppointmentsContext`
* `email` - User identification. 
* `appointment_date` - Requested date in the `Y-m-d` format (the MySQL DATE format)
* `appointment_time` - Requested time in the `H:i:s` format (the MySQL TIME format)
* `payload` - Various other properties that might be used with implementing plugin.
* `return_var` - Default `status`, name of the variable that contains additional information (`appointment_id`)

Flows:
* `ok`
* `not_available`

### `UpdateAppointmentElement`

Element which updates existing appointment time. 

Parameters:

* `context_id` - Id of the referenced `IAppointmentsContext`
* `appointment_id` - Id of the existing appointment
* `email` - User identification. 
* `appointment_date` - Requested date in the `Y-m-d` format (the MySQL DATE format)
* `appointment_time` - Requested time in the `H:i:s` format (the MySQL TIME format)
* `payload` - Various other properties that might be used with implementing plugin.
* `return_var` - Default `status`, name of the variable that contains additional information (`appointment_id`)

Flows:
* `ok`
* `not_available`
* `not_found` - if it is empty, `not_available` will be executed

### `CancelAppointmentElement`

Parameters:

* `context_id` - Id of the referenced `IAppointmentsContext`
* `appointment_id` - Id of the existing appointment
* `email` - User identification. 

Flows:
* `ok`
* `not_found`

### `LoadAppointmentsElement`

Parameters:

* `context_id` - Id of the referenced `IAppointmentsContext`
* `mode` : `current` default, `past` or `all`
* `limit` : default `10`
* `return_var` - Default `status`, name of the variable that contains additional information (`appointments` : array of appointments, `timezone`: preffered timezone)

Flows:
* `empty`
* `multiple`
* `single` - if it is empty, `multiple` will be executed



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
