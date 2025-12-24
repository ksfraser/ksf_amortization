# UML Process Flows

```uml
@startuml
start
:User enters loan details;
:Controller validates input;
if (Valid?) then (yes)
  :AmortizationModel creates loan;
  :DataProviderInterface inserts loan in DB;
  :Controller displays confirmation;
else (no)
  :Controller shows error;
endif
stop
@enduml


@startuml
start
:User requests amortization schedule;
:Controller calls AmortizationModel;
:Model calculates schedule;
:Model calls DataProviderInterface to insert schedule rows;
:Controller displays schedule to user;
stop
@enduml
```

---
