yii-rest-extension
==================

This repository currently only contains some work in progress. I don't consider
it functional at all!

Relevant code of the extension is in protected/extensions/rest.

The app acts as a development container to host the extension and provides a
way to showcase its configuration.

Basic idea was to provide a configurable `RestController` for REST actions.

Common methods like CRUD are implemented directly, additional actions were
meant to be configured or automatically detected. Not sure. I've written this
code too long ago. `RestController::$restActionsPath` indicates there was
something meant to support such a machanism.

For most other functionality, the extension relies heavily on behaviors.

The `RestController` operates on interfaces, not on any concrete classes, to
allow arbitrary classes to be used as REST resources. Classes that are meant
to be exposed as REST ressources can therefore either implement the Interface
`IRestResource` directly, or they use an adapter approach. Using the adapter
approach means attaching a behavior that implements `IRestResource` to the
class to be exposed as REST ressource. The behavior's job is to delegate the
methods required by the interface to whatever methods the class provides. An
example of such an adapter behavior is given with `ActiveResourceBehavior`,
that enables the use of `CActiveRecord` classes as REST resources. Which
adapter is meant to be applied to which classes can be configured using
`RestController::$resourceAdapters` property.

The extension ships a special behavior class `RestControllerBehavior`, that
allows easy access to key extension points, like collecting access rules,
collecting filters and doing stuff in response to the implemented base actions
like `handlePagedResourceRetrieval`, `handleSingleResourceRetrieval`,
`handleResourceCreated`, and the like.

For access control, there are two beaviors that implement default rules:

1. `RestControllerDefaultAccessRulesBehavior` allows everyone to discover the
   API and checks for an auth item `rest_{$currentActionId}_{$resourceId}`
   otherwise.
2. `RestControllerDevelopmentAccessRulesBehavior` grants access to everything,
   given the request originates from localhost.

`RestController` does also support the concept of "REST behaviors", which is
basically attaching behaviors conditionally, depending on which resource it
currently operates. Those behaviors can either be configured in
`RestController::$restBehaviors`, where the key is the resource ID and the
value is some behavior, or they can be autodiscovered if named according to the
schema "Rest{ResourceId}Behavior" and stored in the folder specified by 
`RestController::$restBehaviorsPath`. Using "REST behaviors", it is possible
to change rules and behavior for single resource types.

`RestControllerDiscoverabilityBehavior` is another common purpose behavior
shipped with the extension. It reacts to the implemented base action's events
and enriches the response's HTTP headers with well-known link headers. For
example, whenever someone requests one resource instance, it will put a
"collection" link in the header, that points to the resources collection URL.
When someone browses a resource collection, it will put appropriate "first",
"prev", "next" and "last" links in the header, that will point to the
respective collection pages (all collections provided by `RestController` are
paged).

Last but not least, the REST extension implements a concept of "Message Readers"
and "Renderers". Those are basically used to read different request types like

1. www-form-urlencoded format
2. json format
3. xml messages
4. ...

and to respond in a proper format as requested by the client in the
Accept-Header (parsing not implemented, fallback to json-renderer).
