# Schedule appointments package for Convoworks


This package contains conversational workflow elements for managing appointment scheduling scenarios in the [Convoworks framework](https://github.com/zef-dev/convoworks-core). This is not full inmplementation. It contains elemenst that you can use in the conversation workflow, but the underlying datsource is just described via `IAppointmentsContext` interface.


## Appointments context interface

`IAppointmentsContext` describes methods that should be implemented by a target appointments system. 

Most of the methods are requiring user identification and we use `email` for it. Email works just well with WordPress, while it enables us to have passthrough implementations which are not requireing the actual user to be created in it.

 


## Workflow elements

All workflow elements have `context_id` property which hooks them to the context which implemnents `IAppointmentsContext` interface. That way elements are concentrated on the conversation workflow needs, while the real business logic is delgated to the concrete impelementation.

### `CheckAppointmentElement`

This element has two sub flows, `OK` when the requested time is available and `NOK` when it is not. When the requested slot is not available, elment exposes suggestions, array of time slots that could be offered to the end user.  



### `CreateAppointmentsElement`
Flows:
* OK

### `UpdateAppointmentsElement`

Flows:
* OK
* NOT_FOUND
* NOT_AVAILABLE


### `CancelAppointmentsElement`

Flows:
* OK
* NOT_FOUND

### `LoadAppointmentsElement`

* `mode` : `current` default, `past` or `all`
* return array of existing appointments associated to the given user


Flows:
* OK
* SINGLE



### `GetAppointmentElement`

Returns single appointment data.

Flows:
* OK
* NOK




---

For more information, please check out [convoworks.com](https://convoworks.com)

