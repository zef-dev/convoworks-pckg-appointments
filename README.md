# Appointments package for Convoworks


This package contains conversational workflow elements for managing appointment scheduling scenarios in the [Convoworks framework](https://github.com/zef-dev/convoworks-core). It contains elements that you can use in the conversation workflow, but the appointments data source is just described via the `IAppointmentsContext` interface.

When we are talking about workflow components (elements), we have to primarily consider voice and conversational design needs. Their properties, sub-flows and general behavior are tailored to make conversational workflow as easy as possible. They are not related to any particular booking plugin or a similar 3rd party service provider.

Appointments context is on the other hand bridge between workflow elements (Convoworks) and the real, concrete appointment system you are using in your system.

## Appointments context interface

`IAppointmentsContext` describes methods that should be implemented by a target appointments system. If you have e.g. WordPress schedule appointment plugin, you can easily enable it to be used with Convoworks by implementing this interface.

Most of the methods require user identification and we use `email` for it. Email works just well with WordPress, while it enables us to have passthrough implementations which do not require the actual user to be created in it. If your target system has several appointment types, the one you need should be configured on this `IAppointmentsContext` type component. 

To be properly used in the Convoworks GUI, it also has to implement `IBasicServiceComponent` and `IServiceContext`. You might consider to start your implementation like this:

```php

class MyCustomAppointmentsContext extends AbstractBasicComponent implements IAppointmentsContext, IServiceContext
{

}
```

Here is quick example how you can register your own package in the WordPress environment (Convoworks WP)

```php
/**
 * @param Convo\Core\Factory\PackageProviderFactory $packageProviderFactory
 * @param Psr\Container\ContainerInterface $container
 */
function my_package_registrator( $packageProviderFactory, $container) {
    $packageProviderFactory->registerPackage( new Convo\Core\Factory\FunctionPackageDescriptor('\My\Namespace\MyPackageDefinition', 
        function() use ( $container) {
            return new \My\Namespace\MyPackageDefinition( $container->get( 'logger'));
        }));
}
add_action( 'register_convoworks_package', 'my_package_registrator', 10, 2);
```

You can check for more about [developing custom packages](https://convoworks.com/docs/developers/develop-custom-packages/) on the Convoworks documentation. 


### `DummyAppointmentsContext`

Dummy implementation that can serve to test voice applications or as an example when creating your own `IAppointmentsContext` implementation.

Here are few predefined features that it has:

* business hours are 9:00 - 17:00 on workdays
* appointment duration: 30 min
* it will store appointments in the Convoworks user scope


## Workflow elements

All appointment package workflow elements have the `context_id` property which hooks them to the context which implements the `IAppointmentsContext` interface. That way elements are concentrated on the conversation workflow needs, while the real business logic is delegated to the concrete implementation.

Here are all common parameters:

* `context_id` - Id of the referenced `IAppointmentsContext`
* `email` - Optional for `CheckAppointmentTimeElement` but required for all others
* `timezone_mode` - `DEFAULT` will use default timezone from referenced appointments context, `CLIENT` will try to get client information or `SET` will allow you to explicitly set the value.
* `timezone` - only when `timezone_mode` is `SET`. PHP timezone name.

Some elements have multiple sub-flows depending on the result we got. This kind of approach enables you to use less `IF` statements in your workflow. But in order not to force you to split workflow, some of the flows are optional and when left empty, the default flow will be executed.


### `CheckAppointmentTimeElement`

This element will check if the desired time slot is available. It has several sub-flows, depending on requested slot or suggestions availability. When the requested slot is not available, element exposes suggestions, an array of time slots (timestamps) which could be offered to the end user. 

Parameters:

* `appointment_date` - Optional, requested date in the `Y-m-d` format (the MySQL DATE format)
* `appointment_time` - Optional, requested time in the `H:i:s` format (the MySQL TIME format)
* `email` - Optional, user identification. If present, it might serve for better suggestions
* `return_var` - Default `status`, name of the variable which will be present in sub-flows and that contains information about operation result (`suggestions` : array of suggestions, `requested_time`: requested appointment as timestamp, `requested_timezone`: requested time zone as string, `not_allowed`: `true` when the requested period is out of business hours)

Flows:

* `available_flow` - Executed when time slot is available
* `suggestions_flow` - Executed when the slot is not available an there are suggestions
* `single_suggestion_flow` - Optional, executed if there is just a single suggestion. If it is empty (no elements inside), `suggestions_flow` will be executed
* `no_suggestions_flow`- Optional, executed if there are no suggestions. If it is empty, `suggestions_flow` will be executed

Other:

* `suggestions_builder` - Component of type `IFreeSlotQueueFactory` which serves for building suggestions. You can use `DefaultFreeSlotQueue` or create your own.

Suggestions representation as JSON.

```json
[{
      "timestamp" : 123345678,
}, {
      "timestamp" : 123445678,
}]
```


### `CreateAppointmentElement`

This element will try to create an appointment for a given time slot. It can happen (rarely) that the slot is not free anymore and you can use `not_available` flow to handle it. If a general, unexpected error occurs, the system handler will handle it.

Parameters:

* `appointment_date` - Requested date in the `Y-m-d` format (the MySQL DATE format)
* `appointment_time` - Requested time in the `H:i:s` format (the MySQL TIME format)
* `payload` - Various other properties that might be used with an implementing booking plugin.
* `return_var` - Default `status`, name of the variable that contains additional information (`appointment_id`: string, `requested_time`: requested appointment as timestamp, `requested_timezone`: requested time zone as string, `not_allowed`: `true` when the requested period is out of business hour)

Flows:
* `ok` - This flow is executed when appointment is created
* `not_available` - Executed when the requested slot is not available


### `UpdateAppointmentElement`

Element which updates existing appointment time. 

Parameters:

* `appointment_id` - Id of the existing appointment
* `appointment_date` - Requested date in the `Y-m-d` format (MySQL DATE format)
* `appointment_time` - Requested time in the `H:i:s` format (MySQL TIME format)
* `payload` - Optional, various other properties that might be used with an implementing plugin. When empty, original payload data provided on creation, should stay the same.
* `return_var` - Default `status`, name of the variable that contains additional information (`existing` previous appointment version as you would get it with load appointment element, `requested_time`: requested appointment as timestamp, `requested_timezone`: requested time zone as string, `not_allowed`: `true` when the requested period is out of business hour)

Flows:
* `ok` - Executed when the appointment is updated
* `not_available` - Executed when the requested slot is not available


### `CancelAppointmentElement`

This element will cancel an existing appointment. 

Parameters:

* `appointment_id` - Id of the existing appointment
* `return_var` - Default `status`, name of the variable that contains additional information (`existing`: previous appointment version as you would get it with load appointment element)

Flows:
* `ok` - Executed when the appointment is canceled


### `LoadAppointmentsElement`

This elment will load existing appointments for the current user.

Parameters:

* `mode` : `current` default, `past` or `all`
* `limit` : default `10`
* `return_var` - Default `status`, name of the variable that contains additional information (`appointments` : array of appointments)

Flows:
* `multiple` - Executed when the multiple appointments are found.
* `single` - Executed when only a single appointment is found. If it is empty, `multiple` flow will be executed.
* `empty` - Executed if there are no suggestions. If it is empty, `multiple` flow will be executed


### `LoadAppointmentElement`

This element returns single appointment data.

Parameters:

* `appointment_id` - Id of the existing appointment
* `return_var` - Default `status`, name of the variable that contains additional information (`appointment`: array of appointment objects)

Flows:
* `ok` - Executed when the appointment is loaded.


Single appointment representation as JSON.

```json
{
      "appointment_id" : "123",
      "email" : "user@domain.com",
      "timestamp" : 123345678,
      "timezone" : "America/New_York",
      "payload" : {
          "some_other_fields" : "That is used by implementing appointment context & WP plugin",
          "more_fields" : "Some other data"
      }
}
```


---

For more information, please check out [convoworks.com](https://convoworks.com)
