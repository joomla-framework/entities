## Known Limitations

The following limitations need to be known when using the framework. Their do not need to be urgently
solved, so they may be solved as an improvement later in the development cycle.

### Constraint loaded model cannot have additional attributes

If a model is loaded with constraints in terms of SELECT (e.g. with less columns), then the developer cannot
add a new attribute(although existing in the database) that was not initially loaded on the model instance.
An exception will be thrown.

### Insert/Update with nulls enabled

If you choose to save the model with `$nulls` set to true, you have to make sure that every attribute that
is loaded onto the model and also have a `null` value before calling `save(true)` it's corresponding column
in the table allows NULL values, otherwise it will throw an error.